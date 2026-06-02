<?php
/**
 * Menú unificado "Despachos BGMG" en wp-admin.
 *
 * Reorganiza toda la configuración de envíos / tracking / retiro / reportes
 * bajo un solo menú top-level (en lugar de tenerlo disperso bajo WooCommerce).
 *
 * Submenús:
 *   - Resumen        (landing con estado del sistema y accesos rápidos)
 *   - Tarifas RM     (admin existente — slug bgmg-chile-tarifas-rm, intacto)
 *   - Retiro en tienda (placeholder, se implementará en próximas versiones)
 *   - Reportes       (placeholder)
 *
 * El slug del admin de tarifas se mantiene para no romper bookmarks que la
 * dueña pudiera tener guardados.
 *
 * @package BGMG_Chile
 * @since 1.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  1. REGISTRO DEL MENÚ TOP-LEVEL + SUBMENÚS
 * ------------------------------------------------------------------------- */

add_action( 'admin_menu', 'bgmg_chile_register_despachos_menu', 70 );

function bgmg_chile_register_despachos_menu() {

	// Top-level menu.
	add_menu_page(
		__( 'Despachos BGMG', 'bgmg-chile' ),
		__( 'Despachos BGMG', 'bgmg-chile' ),
		'manage_woocommerce',
		'bgmg-despachos',
		'bgmg_chile_render_despachos_resumen',
		'dashicons-cart',
		56 // después de WooCommerce (~55).
	);

	// Sub: Resumen (mismo slug que el top-level para que sea el default al hacer click).
	add_submenu_page(
		'bgmg-despachos',
		__( 'Resumen', 'bgmg-chile' ),
		__( 'Resumen', 'bgmg-chile' ),
		'manage_woocommerce',
		'bgmg-despachos',
		'bgmg_chile_render_despachos_resumen'
	);

	// Sub: Tarifas RM (reusa el slug y callback existentes — no rompe nada).
	add_submenu_page(
		'bgmg-despachos',
		__( 'Tarifas RM', 'bgmg-chile' ),
		__( 'Tarifas RM', 'bgmg-chile' ),
		'manage_woocommerce',
		'bgmg-chile-tarifas-rm',
		'bgmg_chile_render_admin_tarifas_rm'
	);

	// Sub: Retiro en tienda (placeholder por ahora).
	add_submenu_page(
		'bgmg-despachos',
		__( 'Retiro en tienda', 'bgmg-chile' ),
		__( 'Retiro en tienda', 'bgmg-chile' ),
		'manage_woocommerce',
		'bgmg-despachos-retiro',
		'bgmg_chile_render_despachos_retiro_placeholder'
	);

	// Sub: Reportes.
	add_submenu_page(
		'bgmg-despachos',
		__( 'Reportes', 'bgmg-chile' ),
		__( 'Reportes', 'bgmg-chile' ),
		'manage_woocommerce',
		'bgmg-despachos-reportes',
		'bgmg_chile_render_despachos_reportes'
	);
}

/**
 * Quitar el submenú viejo bajo WooCommerce → "Envíos Chile (RM)" para evitar
 * tenerlo duplicado (la nueva ubicación está bajo "Despachos BGMG").
 * Corre con prioridad 999 para que ocurra DESPUÉS del registro original (60).
 */
add_action( 'admin_menu', 'bgmg_chile_remove_old_tarifas_submenu', 999 );

function bgmg_chile_remove_old_tarifas_submenu() {
	remove_submenu_page( 'woocommerce', 'bgmg-chile-tarifas-rm' );
}

/* ------------------------------------------------------------------------- *
 *  2. RENDER — PÁGINA "RESUMEN"
 *
 *  Landing del sistema de despachos. Datos reales con queries baratas.
 * ------------------------------------------------------------------------- */

function bgmg_chile_render_despachos_resumen() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pantalla.', 'bgmg-chile' ) );
	}

	$stats = bgmg_chile_despachos_get_stats();

	?>
	<div class="wrap bgmg-despachos-wrap">

		<h1 class="bgmg-despachos-title">
			<span class="dashicons dashicons-cart" style="font-size:30px;width:30px;height:30px;vertical-align:middle;color:#C4728A;"></span>
			<?php esc_html_e( 'Despachos BGMG', 'bgmg-chile' ); ?>
		</h1>
		<p class="bgmg-despachos-sub">
			<?php esc_html_e( 'Resumen del estado de tu sistema de despachos.', 'bgmg-chile' ); ?>
		</p>

		<!-- ESTADO DEL SISTEMA -->
		<div class="bgmg-card bgmg-card-status">
			<h2><?php esc_html_e( 'Estado del sistema', 'bgmg-chile' ); ?></h2>
			<ul class="bgmg-status-list">
				<?php foreach ( $stats['status'] as $item ) : ?>
					<li class="bgmg-status-<?php echo esc_attr( $item['level'] ); ?>">
						<span class="bgmg-status-icon"><?php echo esc_html( $item['icon'] ); ?></span>
						<span class="bgmg-status-text"><?php echo wp_kses_post( $item['text'] ); ?></span>
						<?php if ( ! empty( $item['action_url'] ) ) : ?>
							<a class="bgmg-status-action" href="<?php echo esc_url( $item['action_url'] ); ?>">
								<?php echo esc_html( $item['action_label'] ); ?> →
							</a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<!-- TARJETAS DE DATOS -->
		<div class="bgmg-cards-grid">

			<div class="bgmg-card">
				<h3><?php esc_html_e( 'Tarifas configuradas', 'bgmg-chile' ); ?></h3>
				<div class="bgmg-card-stat">
					<strong class="bgmg-big-number"><?php echo (int) $stats['tarifas']['custom']; ?></strong>
					<span><?php esc_html_e( 'comunas con tarifa custom', 'bgmg-chile' ); ?></span>
				</div>
				<ul class="bgmg-mini-list">
					<li>
						<?php
						/* translators: 1: cantidad, 2: monto formateado en CLP */
						printf(
							esc_html__( '%1$d comunas RM al default %2$s', 'bgmg-chile' ),
							(int) $stats['tarifas']['al_default'],
							esc_html( bgmg_chile_clp( $stats['tarifas']['default_rm'] ) )
						);
						?>
					</li>
					<li>
						<?php
						printf(
							/* translators: %d: cantidad */
							esc_html__( '%d marcadas "Por pagar"', 'bgmg-chile' ),
							(int) $stats['tarifas']['por_pagar']
						);
						?>
					</li>
				</ul>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-chile-tarifas-rm' ) ); ?>">
					<?php esc_html_e( 'Configurar tarifas RM', 'bgmg-chile' ); ?>
				</a>
			</div>

			<div class="bgmg-card">
				<h3><?php esc_html_e( 'Pedidos últimos 30 días', 'bgmg-chile' ); ?></h3>
				<div class="bgmg-card-stat">
					<strong class="bgmg-big-number"><?php echo (int) $stats['pedidos']['total']; ?></strong>
					<span><?php esc_html_e( 'pedidos con envío BGMG', 'bgmg-chile' ); ?></span>
				</div>
				<ul class="bgmg-mini-list">
					<li>
						<?php
						printf(
							/* translators: %d: cantidad */
							esc_html__( '%d con tarifa fija', 'bgmg-chile' ),
							(int) $stats['pedidos']['fija']
						);
						?>
					</li>
					<li>
						<?php
						printf(
							/* translators: %d: cantidad */
							esc_html__( '%d "Por pagar"', 'bgmg-chile' ),
							(int) $stats['pedidos']['por_pagar']
						);
						?>
					</li>
					<li>
						<?php
						printf(
							/* translators: %d: cantidad */
							esc_html__( '%d retiro en tienda', 'bgmg-chile' ),
							(int) $stats['pedidos']['retiro']
						);
						?>
					</li>
				</ul>
				<a class="button" href="<?php echo esc_url( bgmg_chile_admin_orders_url() ); ?>">
					<?php esc_html_e( 'Ver pedidos', 'bgmg-chile' ); ?>
				</a>
			</div>

			<div class="bgmg-card">
				<h3><?php esc_html_e( 'Top 3 comunas del mes', 'bgmg-chile' ); ?></h3>
				<?php if ( empty( $stats['top_comunas'] ) ) : ?>
					<p class="bgmg-empty"><?php esc_html_e( 'Todavía no hay pedidos en los últimos 30 días.', 'bgmg-chile' ); ?></p>
				<?php else : ?>
					<ol class="bgmg-top-list">
						<?php foreach ( $stats['top_comunas'] as $row ) : ?>
							<li>
								<strong><?php echo esc_html( $row['nombre'] ); ?></strong>
								<span class="bgmg-top-count"><?php echo (int) $row['count']; ?> <?php echo $row['count'] === 1 ? esc_html__( 'pedido', 'bgmg-chile' ) : esc_html__( 'pedidos', 'bgmg-chile' ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</div>

		</div>

		<!-- ACCIONES RÁPIDAS -->
		<div class="bgmg-card">
			<h2><?php esc_html_e( 'Acciones rápidas', 'bgmg-chile' ); ?></h2>
			<div class="bgmg-actions-grid">
				<a class="bgmg-action-tile" href="<?php echo esc_url( bgmg_chile_admin_orders_url() ); ?>">
					<strong><?php esc_html_e( 'Imprimir etiquetas', 'bgmg-chile' ); ?></strong>
					<span><?php esc_html_e( 'Seleccionar pedidos y acción masiva "Imprimir etiquetas BGMG"', 'bgmg-chile' ); ?></span>
				</a>
				<a class="bgmg-action-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-chile-tarifas-rm' ) ); ?>">
					<strong><?php esc_html_e( 'Gestionar tarifas RM', 'bgmg-chile' ); ?></strong>
					<span><?php esc_html_e( 'Excepciones manuales por comuna (precio o "por pagar")', 'bgmg-chile' ); ?></span>
				</a>
				<a class="bgmg-action-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>">
					<strong><?php esc_html_e( 'Zonas y métodos de envío', 'bgmg-chile' ); ?></strong>
					<span><?php esc_html_e( 'Configurar default RM, envío gratis, etiquetas', 'bgmg-chile' ); ?></span>
				</a>
				<a class="bgmg-action-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-despachos-retiro' ) ); ?>">
					<strong><?php esc_html_e( 'Retiro en tienda', 'bgmg-chile' ); ?></strong>
					<span><?php esc_html_e( 'Dirección, horarios, WhatsApp', 'bgmg-chile' ); ?></span>
				</a>
			</div>
		</div>

	</div>

	<style>
		.bgmg-despachos-wrap { max-width: 1100px; }
		.bgmg-despachos-title { font-size: 24px; margin: 16px 0 4px; }
		.bgmg-despachos-sub { color: #7A5060; margin: 0 0 24px; }

		.bgmg-card {
			background: #fff;
			border: 1px solid #f0e0e5;
			border-radius: 10px;
			padding: 20px 24px;
			margin-bottom: 16px;
			box-shadow: 0 1px 3px rgba(26,16,21,.04);
		}
		.bgmg-card h2 { font-size: 16px; margin: 0 0 12px; color: #1A1015; }
		.bgmg-card h3 { font-size: 14px; margin: 0 0 12px; color: #7A5060; text-transform: uppercase; letter-spacing: 0.5px; }

		.bgmg-card-status .bgmg-status-list { list-style: none; margin: 0; padding: 0; }
		.bgmg-card-status .bgmg-status-list li {
			display: flex; align-items: center; gap: 10px;
			padding: 8px 0;
			border-bottom: 1px dashed #f5e6ea;
			font-size: 14px;
		}
		.bgmg-card-status .bgmg-status-list li:last-child { border-bottom: 0; }
		.bgmg-status-icon { font-size: 16px; width: 22px; text-align: center; }
		.bgmg-status-ok .bgmg-status-icon { color: #2e7d32; }
		.bgmg-status-warn .bgmg-status-icon { color: #ed6c02; }
		.bgmg-status-err .bgmg-status-icon { color: #c62828; }
		.bgmg-status-text { flex: 1; color: #1A1015; }
		.bgmg-status-action { color: #C4728A; font-weight: 600; text-decoration: none; font-size: 13px; }
		.bgmg-status-action:hover { text-decoration: underline; }

		.bgmg-cards-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 16px;
			margin-bottom: 16px;
		}
		.bgmg-card-stat {
			display: flex; flex-direction: column; gap: 4px;
			padding: 12px 0;
			border-bottom: 1px dashed #f5e6ea;
			margin-bottom: 12px;
		}
		.bgmg-big-number { font-size: 36px; color: #C4728A; line-height: 1; font-weight: 700; }
		.bgmg-card-stat span { color: #7A5060; font-size: 13px; }
		.bgmg-mini-list { list-style: none; margin: 0 0 16px; padding: 0; font-size: 13px; color: #7A5060; }
		.bgmg-mini-list li { padding: 3px 0; }
		.bgmg-empty { color: #999; font-style: italic; margin: 12px 0; }
		.bgmg-top-list { margin: 0 0 0 18px; padding: 0; font-size: 14px; }
		.bgmg-top-list li { padding: 4px 0; color: #1A1015; }
		.bgmg-top-count { color: #7A5060; font-size: 12px; margin-left: 6px; }

		.bgmg-actions-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
			gap: 12px;
		}
		.bgmg-action-tile {
			display: flex; flex-direction: column; gap: 4px;
			padding: 14px 16px;
			background: #FBF0F2;
			border: 1px solid #f0d0d8;
			border-radius: 8px;
			text-decoration: none;
			transition: all 0.15s;
		}
		.bgmg-action-tile:hover {
			background: #F2C4CE;
			border-color: #C4728A;
			transform: translateY(-1px);
		}
		.bgmg-action-tile strong { color: #1A1015; font-size: 14px; }
		.bgmg-action-tile span { color: #7A5060; font-size: 12px; }
	</style>
	<?php
}

/**
 * Helper: formato CLP simple ($3.500).
 */
function bgmg_chile_clp( $monto ) {
	return '$' . number_format( (float) $monto, 0, ',', '.' );
}

/**
 * Calcula todas las estadísticas que muestra la página Resumen.
 * Centralizado acá para tener una sola query por dato.
 *
 * @return array
 */
function bgmg_chile_despachos_get_stats() {

	$out = array(
		'status'      => array(),
		'tarifas'     => array(),
		'pedidos'     => array(),
		'top_comunas' => array(),
	);

	/* ── 1. ESTADO DEL SISTEMA ────────────────────────────────────────── */

	// Shipping method activo en alguna zona? (y de paso detectamos el retiro)
	$tiene_metodo  = false;
	$retiro_activo = false;
	$default_rm    = 0.0;
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'bgmg_chile_envio' === $method->id ) {
					$tiene_metodo = true;
					if ( isset( $method->default_rm ) ) {
						$default_rm = max( $default_rm, (float) $method->default_rm );
					}
				} elseif ( 'bgmg_chile_retiro' === $method->id ) {
					$retiro_activo = true;
				}
			}
		}
	}

	$out['status'][] = $tiene_metodo
		? array( 'level' => 'ok', 'icon' => '✓', 'text' => __( 'Método de envío <strong>BGMG Chile</strong> activo en zona de envío.', 'bgmg-chile' ) )
		: array(
			'level' => 'err',
			'icon'  => '✕',
			'text'  => __( 'Método de envío <strong>BGMG Chile</strong> NO está activo. Sin esto no se cobra envío.', 'bgmg-chile' ),
			'action_url'   => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
			'action_label' => __( 'Configurar zona', 'bgmg-chile' ),
		);

	$out['status'][] = $default_rm > 0
		? array(
			'level' => 'ok',
			'icon'  => '✓',
			'text'  => sprintf(
				/* translators: %s: monto */
				__( 'Tarifa default RM configurada: <strong>%s</strong>.', 'bgmg-chile' ),
				bgmg_chile_clp( $default_rm )
			),
		)
		: array(
			'level' => 'warn',
			'icon'  => '!',
			'text'  => __( 'No hay default RM configurado. Comunas RM no listadas cobrarán "Por pagar".', 'bgmg-chile' ),
			'action_url'   => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
			'action_label' => __( 'Configurar', 'bgmg-chile' ),
		);

	// Retiro en tienda configurado? Está activo si el método bgmg_chile_retiro está
	// agregado a alguna zona (detectado en el loop de arriba). Antes se leía
	// get_option('bgmg_chile_retiro_direccion'), una opción que NUNCA se escribe —
	// los datos del retiro viven en las settings del método de envío —, por lo que
	// el estado salía "no configurado" de forma permanente (falso negativo).
	$out['status'][] = $retiro_activo
		? array( 'level' => 'ok', 'icon' => '✓', 'text' => __( 'Retiro en tienda configurado.', 'bgmg-chile' ) )
		: array(
			'level' => 'warn',
			'icon'  => '!',
			'text'  => __( 'Retiro en tienda no configurado.', 'bgmg-chile' ),
			'action_url'   => admin_url( 'admin.php?page=bgmg-despachos-asistente' ),
			'action_label' => __( 'Configurar', 'bgmg-chile' ),
		);

	/* ── 2. ESTADÍSTICAS DE TARIFAS ────────────────────────────────────── */

	$todas_tarifas = function_exists( 'bgmg_chile_load_all_tarifas_rm' )
		? bgmg_chile_load_all_tarifas_rm()
		: array();

	$count_custom    = 0;
	$count_por_pagar = 0;
	foreach ( $todas_tarifas as $row ) {
		if ( empty( $row['activo'] ) ) {
			$count_por_pagar++;
		} elseif ( (float) ( $row['precio'] ?? 0 ) > 0 ) {
			$count_custom++;
		}
	}

	// Total comunas RM = 52 (oficial INE 2026).
	$total_rm = 52;
	$al_default = max( 0, $total_rm - $count_custom - $count_por_pagar );

	$out['tarifas'] = array(
		'custom'     => $count_custom,
		'por_pagar'  => $count_por_pagar,
		'al_default' => $al_default,
		'default_rm' => $default_rm,
	);

	/* ── 3. ESTADÍSTICAS DE PEDIDOS (últimos 30 días) ──────────────────── */

	$desde = date( 'Y-m-d', strtotime( '-30 days' ) );

	$pedidos_30d = wc_get_orders( array(
		'limit'        => -1,
		'date_created' => '>=' . $desde,
		'status'       => array( 'processing', 'completed', 'on-hold' ),
		'return'       => 'objects',
	) );

	$count_fija      = 0;
	$count_por_pagar_ped = 0;
	$count_retiro    = 0;
	$comuna_tally    = array();

	foreach ( $pedidos_30d as $order ) {
		$is_bgmg_method = false;
		$is_retiro      = false;
		$tipo_tarifa    = '';
		foreach ( $order->get_shipping_methods() as $item ) {
			$method_id = $item->get_method_id();
			if ( 'bgmg_chile_envio' === $method_id ) {
				$is_bgmg_method = true;
				$tipo_tarifa    = (string) $item->get_meta( 'bgmg_tarifa_tipo' );
			} elseif ( 'bgmg_chile_retiro' === $method_id ) {
				$is_retiro = true;
			}
		}

		if ( $is_retiro ) {
			$count_retiro++;
			continue;
		}
		if ( ! $is_bgmg_method ) {
			continue;
		}

		if ( 'por_pagar' === $tipo_tarifa ) {
			$count_por_pagar_ped++;
		} else {
			$count_fija++;
		}

		// Top comunas.
		$comuna_slug = $order->get_shipping_city() ?: $order->get_billing_city();
		if ( $comuna_slug ) {
			if ( ! isset( $comuna_tally[ $comuna_slug ] ) ) {
				$comuna_tally[ $comuna_slug ] = 0;
			}
			$comuna_tally[ $comuna_slug ]++;
		}
	}

	$out['pedidos'] = array(
		'total'     => $count_fija + $count_por_pagar_ped + $count_retiro,
		'fija'      => $count_fija,
		'por_pagar' => $count_por_pagar_ped,
		'retiro'    => $count_retiro,
	);

	// Top 3 comunas.
	arsort( $comuna_tally );
	$top = array_slice( $comuna_tally, 0, 3, true );
	foreach ( $top as $slug => $cnt ) {
		$out['top_comunas'][] = array(
			'nombre' => function_exists( 'bgmg_chile_get_comuna_nombre' )
				? ( bgmg_chile_get_comuna_nombre( $slug ) ?: $slug )
				: $slug,
			'count'  => $cnt,
		);
	}

	return $out;
}

/* ------------------------------------------------------------------------- *
 *  3. PLACEHOLDERS — Retiro en tienda y Reportes
 *
 *  Renderizan una pantalla "Próximamente" amigable. En próximas versiones
 *  reemplazamos estos callbacks por el render completo.
 * ------------------------------------------------------------------------- */

function bgmg_chile_render_despachos_retiro_placeholder() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bgmg-chile' ) );
	}
	bgmg_chile_render_placeholder_pantalla(
		__( 'Retiro en tienda', 'bgmg-chile' ),
		__( 'Próximamente: configuración de dirección, horarios y WhatsApp del punto de retiro desde acá. Por ahora, los datos se configuran en el método de envío "Retiro en tienda" (WooCommerce → Ajustes → Envío → tu zona).', 'bgmg-chile' )
	);
}

function bgmg_chile_render_despachos_reportes() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bgmg-chile' ) );
	}

	// Ventana de tiempo: 7, 30 o 90 días. Default 30.
	$dias_validos = array( 7, 30, 90 );
	$dias         = isset( $_GET['dias'] ) ? absint( $_GET['dias'] ) : 30;
	if ( ! in_array( $dias, $dias_validos, true ) ) {
		$dias = 30;
	}

	$stats = bgmg_chile_reportes_obtener_stats( $dias );

	?>
	<div class="wrap bgmg-despachos-wrap">

		<h1 class="bgmg-despachos-title">
			<span class="dashicons dashicons-chart-bar" style="font-size:30px;width:30px;height:30px;vertical-align:middle;color:#C4728A;"></span>
			<?php esc_html_e( 'Reportes de despachos', 'bgmg-chile' ); ?>
		</h1>
		<p class="bgmg-despachos-sub">
			<?php esc_html_e( 'Datos de pedidos pagados (estados Procesando y Completado) en la ventana seleccionada.', 'bgmg-chile' ); ?>
		</p>

		<!-- Selector de ventana -->
		<form method="get" style="margin:18px 0;">
			<input type="hidden" name="page" value="bgmg-despachos-reportes">
			<label style="font-size:13px;color:#7A5060;margin-right:8px;">
				<?php esc_html_e( 'Período:', 'bgmg-chile' ); ?>
			</label>
			<?php foreach ( $dias_validos as $opt ) :
				$url_opt = add_query_arg( array( 'page' => 'bgmg-despachos-reportes', 'dias' => $opt ), admin_url( 'admin.php' ) );
				$active  = ( $opt === $dias );
				?>
				<a href="<?php echo esc_url( $url_opt ); ?>"
				   class="button <?php echo $active ? 'button-primary' : ''; ?>"
				   style="margin-right:6px;">
					<?php
					/* translators: %d: número de días */
					echo esc_html( sprintf( __( 'Últimos %d días', 'bgmg-chile' ), $opt ) );
					?>
				</a>
			<?php endforeach; ?>
		</form>

		<?php if ( 0 === $stats['total_pedidos'] ) : ?>
			<div class="bgmg-card">
				<p style="margin:0;color:#7A5060;">
					<?php esc_html_e( 'Aún no hay pedidos pagados en este período.', 'bgmg-chile' ); ?>
				</p>
			</div>
		<?php else : ?>

		<!-- 1. RESUMEN DEL PERÍODO -->
		<div class="bgmg-cards-grid">
			<div class="bgmg-card">
				<h3><?php esc_html_e( 'Pedidos despachados', 'bgmg-chile' ); ?></h3>
				<div class="bgmg-card-stat"><?php echo (int) $stats['total_pedidos']; ?></div>
			</div>
			<div class="bgmg-card">
				<h3><?php esc_html_e( 'Ingresos por envío', 'bgmg-chile' ); ?></h3>
				<div class="bgmg-card-stat"><?php echo wp_kses_post( wc_price( $stats['total_ingresos_envio'] ) ); ?></div>
			</div>
			<div class="bgmg-card">
				<h3><?php esc_html_e( 'Promedio por pedido', 'bgmg-chile' ); ?></h3>
				<div class="bgmg-card-stat">
					<?php
					$prom = $stats['total_pedidos'] > 0
						? $stats['total_ingresos_envio'] / $stats['total_pedidos']
						: 0;
					echo wp_kses_post( wc_price( $prom ) );
					?>
				</div>
			</div>
		</div>

		<!-- 2. RANKING DE COMUNAS -->
		<div class="bgmg-card" style="margin-top:20px;">
			<h2>📍 <?php esc_html_e( 'Ranking de comunas (top 10)', 'bgmg-chile' ); ?></h2>
			<?php if ( empty( $stats['ranking_comunas'] ) ) : ?>
				<p style="color:#7A5060;"><?php esc_html_e( 'Sin datos de comunas en este período.', 'bgmg-chile' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" style="margin-top:8px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Comuna', 'bgmg-chile' ); ?></th>
							<th style="width:90px;text-align:right;"><?php esc_html_e( 'Pedidos', 'bgmg-chile' ); ?></th>
							<th style="width:80px;text-align:right;"><?php esc_html_e( '%', 'bgmg-chile' ); ?></th>
							<th style="width:200px;"><?php esc_html_e( 'Tarifa configurada', 'bgmg-chile' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['ranking_comunas'] as $row ) :
							$pct      = $stats['total_pedidos'] > 0 ? round( ( $row['count'] / $stats['total_pedidos'] ) * 100 ) : 0;
							$tarifa   = bgmg_chile_get_tarifa_fija( $row['slug'] );
							$tiene    = ( null !== $tarifa );
							?>
							<tr>
								<td><strong><?php echo esc_html( $row['nombre'] ); ?></strong></td>
								<td style="text-align:right;"><?php echo (int) $row['count']; ?></td>
								<td style="text-align:right;"><?php echo esc_html( $pct ); ?>%</td>
								<td>
									<?php if ( $tiene ) : ?>
										<span style="color:#1a7f37;">✓ <?php echo wp_kses_post( wc_price( $tarifa ) ); ?></span>
									<?php else : ?>
										<span style="color:#bf4040;">⚠ <?php esc_html_e( 'Sin tarifa fija', 'bgmg-chile' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<!-- 3. MÉTODOS DE ENVÍO -->
		<div class="bgmg-card" style="margin-top:20px;">
			<h2>🚚 <?php esc_html_e( 'Métodos de envío', 'bgmg-chile' ); ?></h2>
			<?php if ( empty( $stats['metodos'] ) ) : ?>
				<p style="color:#7A5060;"><?php esc_html_e( 'Sin datos de métodos en este período.', 'bgmg-chile' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" style="margin-top:8px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Método', 'bgmg-chile' ); ?></th>
							<th style="width:90px;text-align:right;"><?php esc_html_e( 'Pedidos', 'bgmg-chile' ); ?></th>
							<th style="width:80px;text-align:right;"><?php esc_html_e( '%', 'bgmg-chile' ); ?></th>
							<th style="width:140px;text-align:right;"><?php esc_html_e( 'Ingresos', 'bgmg-chile' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['metodos'] as $row ) :
							$pct = $stats['total_pedidos'] > 0 ? round( ( $row['count'] / $stats['total_pedidos'] ) * 100 ) : 0;
							?>
							<tr>
								<td><strong><?php echo esc_html( $row['nombre'] ); ?></strong></td>
								<td style="text-align:right;"><?php echo (int) $row['count']; ?></td>
								<td style="text-align:right;"><?php echo esc_html( $pct ); ?>%</td>
								<td style="text-align:right;"><?php echo wp_kses_post( wc_price( $row['ingresos'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<?php endif; // total_pedidos > 0 ?>

	</div>
	<?php
}

/**
 * Calcula stats agregadas para la pantalla de Reportes.
 *
 * Hace una sola query wc_get_orders con filtro por status y fecha, y luego
 * itera acumulando ranking de comunas, métodos de envío e ingresos.
 *
 * A 50K visits/mes y ~500 pedidos/mes, una ventana de 90 días procesa ~1500
 * pedidos. Cuando ese volumen empiece a notarse en TTFB, agregar caché
 * transient de 5 min acá (ver memoria bgmg-tech-debt-pendiente).
 *
 * @param int $dias Ventana en días (7, 30 o 90).
 * @return array {
 *     @type int   total_pedidos
 *     @type float total_ingresos_envio
 *     @type array ranking_comunas        Top 10 [{slug, nombre, count}]
 *     @type array metodos                [{slug, nombre, count, ingresos}]
 * }
 */
function bgmg_chile_reportes_obtener_stats( $dias = 30 ) {

	$out = array(
		'total_pedidos'        => 0,
		'total_ingresos_envio' => 0.0,
		'ranking_comunas'      => array(),
		'metodos'              => array(),
	);

	if ( ! function_exists( 'wc_get_orders' ) ) {
		return $out;
	}

	$desde = gmdate( 'Y-m-d', strtotime( '-' . (int) $dias . ' days' ) );

	$pedidos = wc_get_orders( array(
		'limit'        => -1,
		'date_created' => '>=' . $desde,
		'status'       => array( 'processing', 'completed' ),
		'return'       => 'objects',
	) );

	$comunas_acum = array(); // [slug => count]
	$metodos_acum = array(); // [slug => ['nombre', 'count', 'ingresos']]

	foreach ( $pedidos as $order ) {

		$out['total_pedidos']++;
		$out['total_ingresos_envio'] += (float) $order->get_shipping_total();

		// Comuna: usar shipping_city si existe, sino billing_city.
		$comuna_slug = $order->get_shipping_city();
		if ( '' === $comuna_slug ) {
			$comuna_slug = $order->get_billing_city();
		}
		if ( '' !== $comuna_slug ) {
			$comunas_acum[ $comuna_slug ] = ( $comunas_acum[ $comuna_slug ] ?? 0 ) + 1;
		}

		// Método de envío: clasificar a un nombre canónico.
		$metodo_info  = bgmg_chile_reportes_clasificar_metodo( $order );
		$metodo_slug  = $metodo_info['slug'];
		$metodo_nom   = $metodo_info['nombre'];

		if ( ! isset( $metodos_acum[ $metodo_slug ] ) ) {
			$metodos_acum[ $metodo_slug ] = array(
				'slug'     => $metodo_slug,
				'nombre'   => $metodo_nom,
				'count'    => 0,
				'ingresos' => 0.0,
			);
		}
		$metodos_acum[ $metodo_slug ]['count']++;
		$metodos_acum[ $metodo_slug ]['ingresos'] += (float) $order->get_shipping_total();
	}

	// Ranking comunas: ordenar desc, tomar top 10, agregar nombre legible.
	arsort( $comunas_acum );
	$top = array_slice( $comunas_acum, 0, 10, true );
	foreach ( $top as $slug => $count ) {
		$nombre = function_exists( 'bgmg_chile_get_comuna_nombre' ) ? bgmg_chile_get_comuna_nombre( $slug ) : '';
		if ( '' === $nombre ) {
			$nombre = $slug; // fallback: mostrar el slug crudo
		}
		$out['ranking_comunas'][] = array(
			'slug'   => $slug,
			'nombre' => $nombre,
			'count'  => (int) $count,
		);
	}

	// Métodos: ordenar desc por count.
	usort( $metodos_acum, function( $a, $b ) {
		return $b['count'] <=> $a['count'];
	} );
	$out['metodos'] = $metodos_acum;

	return $out;
}

/**
 * Clasifica el método de envío de un pedido a un nombre canónico para Reportes.
 *
 *   bgmg_chile_envio + tarifa fija   → "Tarifa fija RM"
 *   bgmg_chile_envio + por pagar     → "Por pagar — Starken" / "Por pagar — Bluexpress" / "Por pagar"
 *   bgmg_chile_retiro                → "Retiro en tienda"
 *   cualquier otro                   → label del shipping item
 *
 * @param WC_Order $order
 * @return array ['slug' => ..., 'nombre' => ...]
 */
function bgmg_chile_reportes_clasificar_metodo( $order ) {

	$shipping_items = $order->get_items( 'shipping' );

	foreach ( $shipping_items as $item ) {
		$method_id = $item->get_method_id();

		if ( 'bgmg_chile_retiro' === $method_id ) {
			return array( 'slug' => 'retiro', 'nombre' => __( 'Retiro en tienda', 'bgmg-chile' ) );
		}

		if ( 'bgmg_chile_envio' === $method_id ) {
			// Distinguir tarifa fija vs por pagar mirando el courier elegido.
			$courier = $item->get_meta( 'bgmg_courier' );
			if ( '' !== $courier ) {
				$nombre_courier = function_exists( 'bgmg_chile_orden_courier_nombre' )
					? bgmg_chile_orden_courier_nombre( $courier )
					: ucfirst( $courier );
				return array(
					'slug'   => 'por_pagar_' . $courier,
					'nombre' => sprintf( __( 'Por pagar — %s', 'bgmg-chile' ), $nombre_courier ),
				);
			}
			// Sin courier meta → es tarifa fija (o pedido anterior a v1.12.0).
			return array( 'slug' => 'tarifa_fija', 'nombre' => __( 'Tarifa fija RM', 'bgmg-chile' ) );
		}

		// Otro shipping method (no es de bgmg-chile): mostrar su label.
		$label = $item->get_method_title();
		if ( '' === $label ) {
			$label = $method_id;
		}
		return array( 'slug' => 'otro_' . sanitize_key( $method_id ), 'nombre' => $label );
	}

	// Sin shipping items → ej. retiros sin método registrado, o pedidos virtuales.
	return array( 'slug' => 'sin_envio', 'nombre' => __( 'Sin envío', 'bgmg-chile' ) );
}

function bgmg_chile_render_placeholder_pantalla( $titulo, $mensaje ) {
	?>
	<div class="wrap" style="max-width:800px;">
		<h1 style="font-size:24px;margin-top:16px;"><?php echo esc_html( $titulo ); ?></h1>
		<div style="background:#fff;border:1px solid #f0e0e5;border-radius:10px;padding:32px;margin-top:20px;text-align:center;">
			<div style="font-size:48px;margin-bottom:12px;">🚧</div>
			<h2 style="margin:0 0 12px;color:#1A1015;font-size:20px;">
				<?php esc_html_e( 'En construcción', 'bgmg-chile' ); ?>
			</h2>
			<p style="color:#7A5060;font-size:14px;max-width:480px;margin:0 auto;line-height:1.5;">
				<?php echo esc_html( $mensaje ); ?>
			</p>
			<p style="margin-top:24px;">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-despachos' ) ); ?>">
					← <?php esc_html_e( 'Volver al resumen', 'bgmg-chile' ); ?>
				</a>
			</p>
		</div>
	</div>
	<?php
}
