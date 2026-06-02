<?php
/**
 * Asistente de configuración de envíos.
 *
 * Página admin que guía paso a paso la configuración del sistema de despachos:
 *   1. Zona de envío Chile.
 *   2. Método "Envío BeautyGirlMG (Chile)".
 *   3. Método "Retiro en tienda" + datos del local (editor inline).
 *   4. Tarifas RM por comuna (resumen + link al admin existente).
 *   5. Comunas con retiro disponible (resumen + link al admin existente).
 *
 * Idempotente: cada paso detecta su estado en vivo consultando WC, sin flags
 * persistidos. Se puede entrar/salir/reconfigurar sin perder progreso.
 *
 * Al activar el plugin la primera vez (o al reactivar tras desactivar), se
 * setea un transient que dispara una redirección automática al wizard en el
 * próximo admin_init. Si la dueña navega a otra pantalla, no se vuelve a
 * forzar la redirección.
 *
 * Se registra como submenú dentro de "Despachos BGMG" (creado en
 * admin-despachos-menu.php) para mantener todo el flujo bajo un mismo techo.
 *
 * @package BGMG_Chile
 * @since 1.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slug de la página admin del wizard. Usamos define + guarda para alinear con
 * la convención del plugin (BGMG_CHILE_VERSION también es define) y soportar
 * casos de doble carga sin error fatal.
 */
if ( ! defined( 'BGMG_CHILE_WIZARD_ENVIOS_SLUG' ) ) {
	define( 'BGMG_CHILE_WIZARD_ENVIOS_SLUG', 'bgmg-despachos-asistente' );
}

/* ------------------------------------------------------------------------- *
 *  1. REGISTRO DEL SUBMENÚ
 * ------------------------------------------------------------------------- */

// Prio 80 para entrar DESPUÉS de que admin-despachos-menu.php (prio 70) haya
// registrado el menú padre 'bgmg-despachos'.
add_action( 'admin_menu', 'bgmg_chile_wizard_envios_register_submenu', 80 );

function bgmg_chile_wizard_envios_register_submenu() {
	// Usa helper con fallback a top-level si 'bgmg-despachos' no existe.
	// Ver bgmg_chile_wizard_register_submenu en inc/helpers.php.
	bgmg_chile_wizard_register_submenu(
		__( 'Asistente de Envíos', 'bgmg-chile' ),
		__( '🪄 Asistente', 'bgmg-chile' ),
		'manage_woocommerce',
		BGMG_CHILE_WIZARD_ENVIOS_SLUG,
		'bgmg_chile_wizard_envios_render'
	);
}

/* ------------------------------------------------------------------------- *
 *  2. REDIRECT AL ACTIVAR
 *
 *  bgmg_chile_on_activate() (en bgmg-chile.php) setea el transient
 *  'bgmg_chile_wizard_envios_redirect' al activar el plugin. Acá lo
 *  consumimos UNA vez y redirigimos al wizard. Cualquier carga posterior
 *  no encuentra el transient y queda en el flujo normal.
 * ------------------------------------------------------------------------- */

add_action( 'admin_init', 'bgmg_chile_wizard_envios_maybe_redirect' );

function bgmg_chile_wizard_envios_maybe_redirect() {

	if ( ! get_transient( 'bgmg_chile_wizard_envios_redirect' ) ) {
		return;
	}
	// Lo borramos antes de redirigir para que sea estrictamente one-shot.
	delete_transient( 'bgmg_chile_wizard_envios_redirect' );

	// Salvaguardas: no interrumpir AJAX, CLI ni activaciones masivas.
	if ( wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) || is_network_admin() ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	if ( isset( $_GET['activate-multi'] ) ) {
		return;
	}
	// Si ya estamos en el wizard, no redirigir (evita bucle si el transient
	// se setea estando ya dentro).
	if ( isset( $_GET['page'] ) && BGMG_CHILE_WIZARD_ENVIOS_SLUG === $_GET['page'] ) {
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=' . BGMG_CHILE_WIZARD_ENVIOS_SLUG ) );
	exit;
}

/* ------------------------------------------------------------------------- *
 *  3. RENDER PRINCIPAL
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_envios_render() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pantalla.', 'bgmg-chile' ) );
	}

	// Pre-flight: WC y clases necesarias. Si faltan, helper renderiza error.
	if ( ! bgmg_chile_wizard_preflight_check() ) {
		return;
	}

	// Procesar acciones POST ANTES de leer el estado para que los cambios
	// se reflejen en el mismo render.
	$mensaje_post = bgmg_chile_wizard_envios_handle_post();

	// Detectar estado en vivo.
	$zone_data      = bgmg_chile_wizard_get_zona_chile();
	$metodo_envio   = bgmg_chile_wizard_zona_buscar_metodo( $zone_data, 'bgmg_chile_envio' );
	$metodo_retiro  = bgmg_chile_wizard_zona_buscar_metodo( $zone_data, 'bgmg_chile_retiro' );
	$stats_rm       = bgmg_chile_wizard_stats_tarifas_rm();
	$stats_retiro_c = bgmg_chile_wizard_stats_retiro_comunas();

	$completados = 0;
	if ( $zone_data )      { $completados++; }
	if ( $metodo_envio )   { $completados++; }
	if ( $metodo_retiro )  { $completados++; }
	if ( $stats_rm['configuradas'] > 0 )       { $completados++; }
	if ( $stats_retiro_c['marcadas'] > 0 )     { $completados++; }
	$total = 5;
	?>
	<div class="wrap bgmg-wizard">
		<h1 class="bgmg-wizard-title">
			🪄 <?php esc_html_e( 'Asistente de configuración — Envíos', 'bgmg-chile' ); ?>
		</h1>
		<p class="bgmg-wizard-intro">
			<?php esc_html_e( 'Configuremos paso a paso el sistema de envíos. Cada paso detecta su estado automáticamente: lo que ya tengas hecho aparece con ✓ y lo que falte aparece con un botón para resolverlo. Puedes entrar y salir cuando quieras.', 'bgmg-chile' ); ?>
		</p>

		<?php if ( $mensaje_post ) : ?>
			<div class="notice notice-<?php echo esc_attr( $mensaje_post['tipo'] ); ?> is-dismissible">
				<p><?php echo wp_kses_post( $mensaje_post['texto'] ); ?></p>
			</div>
		<?php endif; ?>

		<div class="bgmg-wizard-progreso-wrap">
			<div class="bgmg-wizard-progreso">
				<div class="bgmg-wizard-progreso-bar" style="width:<?php echo esc_attr( round( ( $completados / $total ) * 100 ) ); ?>%;"></div>
			</div>
			<p class="bgmg-wizard-progreso-label">
				<?php
				printf(
					/* translators: 1: pasos completados, 2: total */
					esc_html__( '%1$d de %2$d pasos completados', 'bgmg-chile' ),
					(int) $completados,
					(int) $total
				);
				?>
			</p>
		</div>

		<?php
		bgmg_chile_wizard_render_paso_1_zona( $zone_data );
		bgmg_chile_wizard_render_paso_2_envio( $zone_data, $metodo_envio );
		bgmg_chile_wizard_render_paso_3_retiro( $zone_data, $metodo_retiro );
		bgmg_chile_wizard_render_paso_4_tarifas_rm( $stats_rm );
		bgmg_chile_wizard_render_paso_5_retiro_comunas( $stats_retiro_c );
		?>

		<?php if ( $completados === $total ) : ?>
			<div class="bgmg-wizard-fin">
				<h2>🎉 <?php esc_html_e( '¡Todo configurado!', 'bgmg-chile' ); ?></h2>
				<p>
					<?php esc_html_e( 'Los envíos están listos para recibir pedidos. Puedes volver a este asistente cuando quieras para ajustar algo.', 'bgmg-chile' ); ?>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-despachos' ) ); ?>">
						<?php esc_html_e( 'Ir al resumen de despachos', 'bgmg-chile' ); ?>
					</a>
				</p>
			</div>
		<?php endif; ?>
	</div>

	<?php bgmg_chile_wizard_envios_render_styles(); ?>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  4. HANDLER DE POST
 *
 *  Router único: lee la acción y delega. Cada acción valida nonce y permisos.
 *  Devuelve array ['tipo' => success|error|warning, 'texto' => ...] o null
 *  si no hubo POST.
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_envios_handle_post() {

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return null;
	}
	if ( empty( $_POST['bgmg_wizard_action'] ) ) {
		return null;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return array( 'tipo' => 'error', 'texto' => __( 'Sin permisos.', 'bgmg-chile' ) );
	}
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'bgmg_chile_wizard_envios' ) ) {
		return array( 'tipo' => 'error', 'texto' => __( 'Nonce inválido. Vuelve a cargar la página.', 'bgmg-chile' ) );
	}

	$action = sanitize_key( wp_unslash( $_POST['bgmg_wizard_action'] ) );

	switch ( $action ) {
		case 'crear_zona':
			return bgmg_chile_wizard_accion_crear_zona();
		case 'agregar_envio':
			return bgmg_chile_wizard_accion_agregar_metodo( 'bgmg_chile_envio' );
		case 'agregar_retiro':
			return bgmg_chile_wizard_accion_agregar_metodo( 'bgmg_chile_retiro' );
		case 'guardar_retiro':
			return bgmg_chile_wizard_accion_guardar_retiro();
	}

	return null;
}

/* ------------------------------------------------------------------------- *
 *  5. ACCIONES
 * ------------------------------------------------------------------------- */

/**
 * Crea una nueva zona de envío con país=CL.
 * Si ya existe una zona con CL, no crea otra (idempotente).
 */
function bgmg_chile_wizard_accion_crear_zona() {

	if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
		return array( 'tipo' => 'error', 'texto' => __( 'WooCommerce no está disponible.', 'bgmg-chile' ) );
	}
	if ( bgmg_chile_wizard_get_zona_chile() ) {
		return array( 'tipo' => 'warning', 'texto' => __( 'Ya existía una zona de envío con Chile. No se creó otra.', 'bgmg-chile' ) );
	}

	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( __( 'Chile', 'bgmg-chile' ) );
	$zone->set_locations( array(
		array( 'code' => 'CL', 'type' => 'country' ),
	) );
	$zone_id = $zone->save();

	if ( ! $zone_id ) {
		return array( 'tipo' => 'error', 'texto' => __( 'No se pudo crear la zona. Revisa los logs de WooCommerce.', 'bgmg-chile' ) );
	}

	return array( 'tipo' => 'success', 'texto' => __( 'Zona "Chile" creada. ✓', 'bgmg-chile' ) );
}

/**
 * Agrega un método de envío a la zona Chile.
 *
 * @param string $method_id 'bgmg_chile_envio' o 'bgmg_chile_retiro'.
 */
function bgmg_chile_wizard_accion_agregar_metodo( $method_id ) {

	$zone_data = bgmg_chile_wizard_get_zona_chile();
	if ( ! $zone_data ) {
		return array( 'tipo' => 'error', 'texto' => __( 'Primero crea la zona Chile (paso 1).', 'bgmg-chile' ) );
	}
	if ( bgmg_chile_wizard_zona_buscar_metodo( $zone_data, $method_id ) ) {
		return array( 'tipo' => 'warning', 'texto' => __( 'Ese método ya está agregado a la zona Chile.', 'bgmg-chile' ) );
	}

	$zone = WC_Shipping_Zones::get_zone( $zone_data['zone_id'] );
	if ( ! $zone ) {
		return array( 'tipo' => 'error', 'texto' => __( 'No se pudo abrir la zona Chile.', 'bgmg-chile' ) );
	}

	$instance_id = $zone->add_shipping_method( $method_id );
	if ( ! $instance_id ) {
		return array( 'tipo' => 'error', 'texto' => __( 'No se pudo agregar el método. Verifica que el plugin esté activo.', 'bgmg-chile' ) );
	}

	$label = ( 'bgmg_chile_envio' === $method_id )
		? __( 'Método "Envío BeautyGirlMG (Chile)" agregado a la zona Chile. ✓', 'bgmg-chile' )
		: __( 'Método "Retiro en tienda" agregado a la zona Chile. ✓', 'bgmg-chile' );

	return array( 'tipo' => 'success', 'texto' => $label );
}

/**
 * Guarda los 4 datos del método retiro (dirección, horario, whatsapp,
 * instrucciones) en las opciones de instancia del método.
 *
 * Las opciones de un shipping method instance se persisten en
 * `woocommerce_{method_id}_{instance_id}_settings` como array. Usamos
 * el helper get_option_key() que WC expone para no hardcodear el nombre.
 */
function bgmg_chile_wizard_accion_guardar_retiro() {

	$zone_data = bgmg_chile_wizard_get_zona_chile();
	$metodo    = bgmg_chile_wizard_zona_buscar_metodo( $zone_data, 'bgmg_chile_retiro' );
	if ( ! $metodo ) {
		return array( 'tipo' => 'error', 'texto' => __( 'Primero agrega el método "Retiro en tienda" (paso 3).', 'bgmg-chile' ) );
	}

	$direccion    = isset( $_POST['retiro_direccion'] )    ? sanitize_text_field( wp_unslash( $_POST['retiro_direccion'] ) ) : '';
	$horario      = isset( $_POST['retiro_horario'] )      ? sanitize_text_field( wp_unslash( $_POST['retiro_horario'] ) ) : '';
	$whatsapp     = isset( $_POST['retiro_whatsapp'] )     ? sanitize_text_field( wp_unslash( $_POST['retiro_whatsapp'] ) ) : '';
	$instruccion  = isset( $_POST['retiro_instrucciones'] ) ? sanitize_textarea_field( wp_unslash( $_POST['retiro_instrucciones'] ) ) : '';

	// Si la dueña puso un WhatsApp, validar que sea móvil chileno válido. Vacío
	// está OK: el campo es opcional. Si pasa, normalizamos al formato canónico
	// "+56 9 XXXX XXXX" para que los links de WhatsApp del checkout siempre
	// abran wa.me con un número parseable.
	if ( '' !== $whatsapp ) {
		if ( ! class_exists( 'BGMG_Chile_Telefono_Validator' ) || ! BGMG_Chile_Telefono_Validator::is_valid_movil( $whatsapp ) ) {
			return array(
				'tipo'  => 'error',
				'texto' => __( 'El WhatsApp no es un móvil chileno válido. Formato esperado: +56 9 1234 5678.', 'bgmg-chile' ),
			);
		}
		$formato = BGMG_Chile_Telefono_Validator::format_internacional( $whatsapp );
		if ( '' !== $formato ) {
			$whatsapp = $formato;
		}
	}

	$option_key = $metodo->get_option_key();
	$settings   = get_option( $option_key, array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	$settings['direccion']     = $direccion;
	$settings['horario']       = $horario;
	$settings['whatsapp']      = $whatsapp;
	$settings['instrucciones'] = $instruccion;

	update_option( $option_key, $settings );

	return array( 'tipo' => 'success', 'texto' => __( 'Datos de retiro guardados. ✓', 'bgmg-chile' ) );
}

/* ------------------------------------------------------------------------- *
 *  6. HELPERS DE DETECCIÓN
 * ------------------------------------------------------------------------- */

/**
 * Busca la primera zona de envío que tenga a Chile como location.
 *
 * @return array|null El array de la zona (zone_id, zone_name, ...) o null.
 */
function bgmg_chile_wizard_get_zona_chile() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return null;
	}
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		if ( empty( $zone['zone_locations'] ) ) {
			continue;
		}
		foreach ( $zone['zone_locations'] as $loc ) {
			// Match directo país=CL o estado CL:* (cualquier sub-región).
			if ( 'country' === $loc->type && 'CL' === $loc->code ) {
				return $zone;
			}
			if ( 'state' === $loc->type && 0 === strpos( (string) $loc->code, 'CL:' ) ) {
				return $zone;
			}
		}
	}
	return null;
}

/**
 * Devuelve la primera instancia de $method_id en la zona dada, o null.
 *
 * @param array|null $zone_data
 * @param string     $method_id
 * @return WC_Shipping_Method|null
 */
function bgmg_chile_wizard_zona_buscar_metodo( $zone_data, $method_id ) {
	if ( empty( $zone_data['shipping_methods'] ) ) {
		return null;
	}
	foreach ( $zone_data['shipping_methods'] as $method ) {
		if ( $method->id === $method_id ) {
			return $method;
		}
	}
	return null;
}

/**
 * Estadísticas de tarifas RM: total de comunas RM y cuántas tienen tarifa
 * configurada (precio > 0 y activo).
 *
 * @return array{total:int, configuradas:int, por_pagar:int}
 */
function bgmg_chile_wizard_stats_tarifas_rm() {
	$total = 0;
	if ( function_exists( 'bgmg_chile_get_comunas_por_region' ) ) {
		$por_region = bgmg_chile_get_comunas_por_region();
		if ( isset( $por_region['RM'] ) ) {
			$total = count( $por_region['RM'] );
		}
	}

	$configuradas = 0;
	$por_pagar    = 0;
	if ( function_exists( 'bgmg_chile_load_all_tarifas_rm' ) ) {
		foreach ( bgmg_chile_load_all_tarifas_rm() as $row ) {
			if ( empty( $row['activo'] ) ) {
				$por_pagar++;
			} elseif ( (float) ( $row['precio'] ?? 0 ) > 0 ) {
				$configuradas++;
			}
		}
	}

	return array(
		'total'        => $total,
		'configuradas' => $configuradas,
		'por_pagar'    => $por_pagar,
	);
}

/**
 * Cuántas comunas están marcadas con retiro disponible.
 *
 * @return array{marcadas:int}
 */
function bgmg_chile_wizard_stats_retiro_comunas() {
	$marcadas = 0;
	if ( function_exists( 'bgmg_chile_load_all_tarifas_rm' ) ) {
		foreach ( bgmg_chile_load_all_tarifas_rm() as $row ) {
			if ( ! empty( $row['retiro_disponible'] ) ) {
				$marcadas++;
			}
		}
	}
	return array( 'marcadas' => $marcadas );
}

/* ------------------------------------------------------------------------- *
 *  7. RENDER DE PASOS
 *
 *  Cada paso renderiza una "card" con:
 *    - Encabezado (icono, número, título, badge de estado).
 *    - Descripción de qué hace.
 *    - Acción: botón POST si está pendiente, o resumen + link si está ok.
 * ------------------------------------------------------------------------- */

/** PASO 1 — Zona de envío Chile. */
function bgmg_chile_wizard_render_paso_1_zona( $zone_data ) {
	$ok = (bool) $zone_data;
	?>
	<section class="bgmg-wizard-paso <?php echo $ok ? 'ok' : 'pendiente'; ?>">
		<h2>
			<span class="bgmg-wizard-num"><?php echo $ok ? '✓' : '1'; ?></span>
			<?php esc_html_e( 'Zona de envío "Chile"', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-<?php echo $ok ? 'ok' : 'pendiente'; ?>">
				<?php echo $ok ? esc_html__( 'Listo', 'bgmg-chile' ) : esc_html__( 'Pendiente', 'bgmg-chile' ); ?>
			</span>
		</h2>
		<p>
			<?php esc_html_e( 'WooCommerce necesita una "zona de envío" que cubra Chile para poder cobrar despachos. Sin esto, ningún método aparece en el checkout.', 'bgmg-chile' ); ?>
		</p>
		<?php if ( $ok ) : ?>
			<p class="bgmg-wizard-info">
				<?php
				printf(
					/* translators: %s: nombre de la zona */
					esc_html__( 'Zona detectada: %s', 'bgmg-chile' ),
					'<strong>' . esc_html( $zone_data['zone_name'] ) . '</strong>'
				);
				?>
				<a class="bgmg-wizard-link-edit" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=' . (int) $zone_data['zone_id'] ) ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Editar zona en WooCommerce ↗', 'bgmg-chile' ); ?>
				</a>
			</p>
		<?php else : ?>
			<form method="post" class="bgmg-wizard-form">
				<?php wp_nonce_field( 'bgmg_chile_wizard_envios' ); ?>
				<input type="hidden" name="bgmg_wizard_action" value="crear_zona">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Crear zona "Chile"', 'bgmg-chile' ); ?>
				</button>
			</form>
		<?php endif; ?>
	</section>
	<?php
}

/** PASO 2 — Método "Envío BeautyGirlMG (Chile)". */
function bgmg_chile_wizard_render_paso_2_envio( $zone_data, $metodo ) {
	$ok           = (bool) $metodo;
	$puede_actuar = (bool) $zone_data;
	?>
	<section class="bgmg-wizard-paso <?php echo $ok ? 'ok' : ( $puede_actuar ? 'pendiente' : 'bloqueado' ); ?>">
		<h2>
			<span class="bgmg-wizard-num"><?php echo $ok ? '✓' : '2'; ?></span>
			<?php esc_html_e( 'Método "Envío BeautyGirlMG (Chile)"', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-<?php echo $ok ? 'ok' : 'pendiente'; ?>">
				<?php echo $ok ? esc_html__( 'Listo', 'bgmg-chile' ) : esc_html__( 'Pendiente', 'bgmg-chile' ); ?>
			</span>
		</h2>
		<p>
			<?php esc_html_e( 'Este método maneja tarifas fijas para Región Metropolitana y "Por pagar" (Starken / Bluexpress) para el resto. Costo $0 en checkout para los couriers; el cliente paga el flete al recibir.', 'bgmg-chile' ); ?>
		</p>
		<?php if ( $ok ) : ?>
			<p class="bgmg-wizard-info">
				<?php esc_html_e( 'Método activo. Editables desde WooCommerce: título, tarifa default RM, envío gratis sobre monto, etiquetas Starken/Bluexpress.', 'bgmg-chile' ); ?>
				<a class="bgmg-wizard-link-edit" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=' . (int) $zone_data['zone_id'] ) ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Editar configuración del método ↗', 'bgmg-chile' ); ?>
				</a>
			</p>
		<?php elseif ( $puede_actuar ) : ?>
			<form method="post" class="bgmg-wizard-form">
				<?php wp_nonce_field( 'bgmg_chile_wizard_envios' ); ?>
				<input type="hidden" name="bgmg_wizard_action" value="agregar_envio">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Agregar a la zona Chile', 'bgmg-chile' ); ?>
				</button>
			</form>
		<?php else : ?>
			<p class="bgmg-wizard-bloqueado">
				<?php esc_html_e( 'Primero completa el paso 1 (crear zona Chile).', 'bgmg-chile' ); ?>
			</p>
		<?php endif; ?>
	</section>
	<?php
}

/** PASO 3 — Método "Retiro en tienda" + editor inline. */
function bgmg_chile_wizard_render_paso_3_retiro( $zone_data, $metodo ) {
	$ok           = (bool) $metodo;
	$puede_actuar = (bool) $zone_data;
	$settings     = array();
	if ( $metodo ) {
		$settings = get_option( $metodo->get_option_key(), array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
	}
	$direccion    = $settings['direccion']     ?? ( $metodo->direccion     ?? '' );
	$horario      = $settings['horario']       ?? ( $metodo->horario       ?? '' );
	$whatsapp     = $settings['whatsapp']      ?? ( $metodo->whatsapp      ?? '' );
	$instruccion  = $settings['instrucciones'] ?? ( $metodo->instrucciones ?? '' );
	?>
	<section class="bgmg-wizard-paso <?php echo $ok ? 'ok' : ( $puede_actuar ? 'pendiente' : 'bloqueado' ); ?>">
		<h2>
			<span class="bgmg-wizard-num"><?php echo $ok ? '✓' : '3'; ?></span>
			<?php esc_html_e( 'Método "Retiro en tienda" + datos del local', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-<?php echo $ok ? 'ok' : 'pendiente'; ?>">
				<?php echo $ok ? esc_html__( 'Listo', 'bgmg-chile' ) : esc_html__( 'Pendiente', 'bgmg-chile' ); ?>
			</span>
		</h2>
		<p>
			<?php esc_html_e( 'Si quieres permitir retiro presencial (gratis), agrega el método y completa los datos del local. Solo se ofrece a las comunas que marques en el paso 5.', 'bgmg-chile' ); ?>
		</p>
		<?php if ( $ok ) : ?>
			<form method="post" class="bgmg-wizard-form bgmg-wizard-form-edit">
				<?php wp_nonce_field( 'bgmg_chile_wizard_envios' ); ?>
				<input type="hidden" name="bgmg_wizard_action" value="guardar_retiro">

				<label for="bgmg-wizard-retiro-direccion"><?php esc_html_e( 'Dirección', 'bgmg-chile' ); ?></label>
				<input type="text" id="bgmg-wizard-retiro-direccion" name="retiro_direccion" value="<?php echo esc_attr( $direccion ); ?>" placeholder="Antonio López de Bello 461, Recoleta">

				<label for="bgmg-wizard-retiro-horario"><?php esc_html_e( 'Horario', 'bgmg-chile' ); ?></label>
				<input type="text" id="bgmg-wizard-retiro-horario" name="retiro_horario" value="<?php echo esc_attr( $horario ); ?>" placeholder="Lun-Vie 10:00-19:00 / Sáb 11:00-14:00">

				<label for="bgmg-wizard-retiro-whatsapp"><?php esc_html_e( 'WhatsApp de coordinación', 'bgmg-chile' ); ?></label>
				<input type="text" id="bgmg-wizard-retiro-whatsapp" name="retiro_whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="+56 9 1234 5678">

				<label for="bgmg-wizard-retiro-instrucciones"><?php esc_html_e( 'Instrucciones extra (opcional)', 'bgmg-chile' ); ?></label>
				<textarea id="bgmg-wizard-retiro-instrucciones" name="retiro_instrucciones" rows="3" placeholder="<?php esc_attr_e( 'Ej: Escríbenos al WhatsApp para coordinar antes de venir.', 'bgmg-chile' ); ?>"><?php echo esc_textarea( $instruccion ); ?></textarea>

				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Guardar datos del retiro', 'bgmg-chile' ); ?>
				</button>
			</form>
		<?php elseif ( $puede_actuar ) : ?>
			<form method="post" class="bgmg-wizard-form">
				<?php wp_nonce_field( 'bgmg_chile_wizard_envios' ); ?>
				<input type="hidden" name="bgmg_wizard_action" value="agregar_retiro">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Agregar a la zona Chile', 'bgmg-chile' ); ?>
				</button>
			</form>
		<?php else : ?>
			<p class="bgmg-wizard-bloqueado">
				<?php esc_html_e( 'Primero completa el paso 1 (crear zona Chile).', 'bgmg-chile' ); ?>
			</p>
		<?php endif; ?>
	</section>
	<?php
}

/** PASO 4 — Tarifas RM por comuna. */
function bgmg_chile_wizard_render_paso_4_tarifas_rm( $stats ) {
	$ok = ( $stats['configuradas'] > 0 );
	?>
	<section class="bgmg-wizard-paso <?php echo $ok ? 'ok' : 'pendiente'; ?>">
		<h2>
			<span class="bgmg-wizard-num"><?php echo $ok ? '✓' : '4'; ?></span>
			<?php esc_html_e( 'Tarifas RM por comuna', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-<?php echo $ok ? 'ok' : 'pendiente'; ?>">
				<?php echo $ok ? esc_html__( 'Listo', 'bgmg-chile' ) : esc_html__( 'Pendiente', 'bgmg-chile' ); ?>
			</span>
		</h2>
		<p>
			<?php esc_html_e( 'Define cuánto cobras de despacho en cada comuna de la Región Metropolitana. Las que no configures aquí cobran la tarifa default RM (o "Por pagar" si pones default en 0).', 'bgmg-chile' ); ?>
		</p>
		<p class="bgmg-wizard-info">
			<?php
			printf(
				/* translators: 1: configuradas, 2: total RM, 3: por pagar */
				esc_html__( '%1$d de %2$d comunas RM tienen tarifa configurada. %3$d marcadas como "Por pagar" explícito.', 'bgmg-chile' ),
				(int) $stats['configuradas'],
				(int) $stats['total'],
				(int) $stats['por_pagar']
			);
			?>
		</p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-chile-tarifas-rm' ) ); ?>">
			<?php echo $ok
				? esc_html__( 'Editar tarifas RM →', 'bgmg-chile' )
				: esc_html__( 'Configurar tarifas RM →', 'bgmg-chile' );
			?>
		</a>
	</section>
	<?php
}

/** PASO 5 — Comunas con retiro disponible. */
function bgmg_chile_wizard_render_paso_5_retiro_comunas( $stats ) {
	$ok = ( $stats['marcadas'] > 0 );
	?>
	<section class="bgmg-wizard-paso <?php echo $ok ? 'ok' : 'pendiente'; ?>">
		<h2>
			<span class="bgmg-wizard-num"><?php echo $ok ? '✓' : '5'; ?></span>
			<?php esc_html_e( 'Comunas con retiro disponible', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-<?php echo $ok ? 'ok' : 'pendiente'; ?>">
				<?php echo $ok ? esc_html__( 'Listo', 'bgmg-chile' ) : esc_html__( 'Pendiente', 'bgmg-chile' ); ?>
			</span>
		</h2>
		<p>
			<?php esc_html_e( 'Marca en qué comunas ofreces retiro en tienda. La opción "Retiro en tienda" solo aparecerá en el checkout para clientes de esas comunas.', 'bgmg-chile' ); ?>
		</p>
		<p class="bgmg-wizard-info">
			<?php
			printf(
				/* translators: %d: cantidad de comunas */
				esc_html( _n( '%d comuna marcada con retiro.', '%d comunas marcadas con retiro.', (int) $stats['marcadas'], 'bgmg-chile' ) ),
				(int) $stats['marcadas']
			);
			?>
		</p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-chile-tarifas-rm' ) ); ?>">
			<?php echo $ok
				? esc_html__( 'Editar comunas con retiro →', 'bgmg-chile' )
				: esc_html__( 'Marcar comunas con retiro →', 'bgmg-chile' );
			?>
		</a>
		<p class="bgmg-wizard-tip">
			<?php esc_html_e( 'La columna "Retiro" está en la misma tabla que tarifas RM (paso 4).', 'bgmg-chile' ); ?>
		</p>
	</section>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  8. ESTILOS INLINE
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_envios_render_styles() {
	?>
	<style>
		.bgmg-wizard { max-width: 920px; }
		.bgmg-wizard-title { font-size: 26px; margin: 16px 0 6px; color: #1A1015; }
		.bgmg-wizard-intro { font-size: 14px; color: #555; max-width: 760px; line-height: 1.5; margin-bottom: 20px; }
		.bgmg-wizard-progreso-wrap { margin-bottom: 28px; }
		.bgmg-wizard-progreso { background: #f0e0e5; height: 8px; border-radius: 4px; overflow: hidden; }
		.bgmg-wizard-progreso-bar { background: #C4728A; height: 100%; transition: width .3s; }
		.bgmg-wizard-progreso-label { font-size: 13px; color: #7A5060; margin: 8px 0 0; }

		.bgmg-wizard-paso {
			background: #fff;
			border: 1px solid #e8d8dd;
			border-left: 4px solid #ccc;
			border-radius: 8px;
			padding: 20px 24px;
			margin-bottom: 16px;
			box-shadow: 0 1px 2px rgba(26,16,21,.03);
		}
		.bgmg-wizard-paso.ok { border-left-color: #4caf50; }
		.bgmg-wizard-paso.pendiente { border-left-color: #ff9800; }
		.bgmg-wizard-paso.bloqueado { border-left-color: #b0b0b0; opacity: 0.72; }

		.bgmg-wizard-paso h2 {
			display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
			margin: 0 0 10px;
			font-size: 17px;
			color: #1A1015;
		}
		.bgmg-wizard-num {
			width: 28px; height: 28px; border-radius: 50%;
			background: #FBF0F2; color: #C4728A;
			display: inline-flex; align-items: center; justify-content: center;
			font-size: 14px; font-weight: 700;
			flex-shrink: 0;
		}
		.bgmg-wizard-paso.ok .bgmg-wizard-num { background: #E8F5E9; color: #2E7D32; }

		.bgmg-wizard-badge {
			margin-left: auto;
			font-size: 11px; padding: 3px 10px; border-radius: 12px;
			font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
		}
		.bgmg-wizard-badge-ok { background: #E8F5E9; color: #2E7D32; }
		.bgmg-wizard-badge-pendiente { background: #FFF3E0; color: #A0561B; }

		.bgmg-wizard-paso p { margin: 8px 0; color: #555; line-height: 1.5; font-size: 14px; }
		.bgmg-wizard-info { background: #FBF0F2; padding: 10px 14px; border-radius: 6px; font-size: 13px; color: #1A1015 !important; }
		.bgmg-wizard-link-edit { margin-left: 8px; color: #C4728A; text-decoration: none; font-weight: 500; font-size: 13px; }
		.bgmg-wizard-link-edit:hover { text-decoration: underline; }
		.bgmg-wizard-bloqueado { color: #999 !important; font-style: italic; }
		.bgmg-wizard-tip { font-size: 12px !important; color: #999 !important; margin-top: 10px !important; }

		.bgmg-wizard-form { margin-top: 12px; }
		.bgmg-wizard-form-edit {
			background: #FBF0F2;
			padding: 16px 18px;
			border-radius: 6px;
			margin-top: 14px;
		}
		.bgmg-wizard-form-edit label {
			display: block;
			margin: 10px 0 4px;
			font-size: 12px; font-weight: 600;
			text-transform: uppercase; letter-spacing: 0.5px;
			color: #7A5060;
		}
		.bgmg-wizard-form-edit input[type="text"],
		.bgmg-wizard-form-edit textarea {
			width: 100%; max-width: 520px;
			padding: 8px 12px;
			border: 1px solid #e0d0d5; border-radius: 6px;
			font-size: 14px;
			background: #fff;
		}
		.bgmg-wizard-form-edit textarea { resize: vertical; min-height: 60px; }
		.bgmg-wizard-form-edit button { margin-top: 14px; }
		.bgmg-wizard-paso .button-primary {
			background: #C4728A; border-color: #A85B73; color: #fff;
		}
		.bgmg-wizard-paso .button-primary:hover {
			background: #A85B73; border-color: #8B4A5F;
		}

		.bgmg-wizard-fin {
			background: linear-gradient(135deg, #E8F5E9 0%, #F1F8E9 100%);
			border: 1px solid #c5e1a5;
			border-radius: 10px;
			padding: 28px 32px;
			margin-top: 24px;
			text-align: center;
		}
		.bgmg-wizard-fin h2 { margin: 0 0 8px; font-size: 22px; color: #2E7D32; }
		.bgmg-wizard-fin p { color: #1A1015; }
	</style>
	<?php
}
