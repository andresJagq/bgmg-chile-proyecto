<?php
/**
 * Tracking de envío para BeautyGirlMG.
 *
 * Decisión 2026-05-17: la dueña a veces despacha en moto propia (sin código),
 * a veces por Starken / Chilexpress (con código). Por eso registramos:
 *   - Código de seguimiento (texto libre, puede quedar vacío)
 *   - Método/courier (texto libre: "Moto propia", "Starken", "Chilexpress", etc.)
 *
 * Al guardar, si la dueña marcó el checkbox "Avisar al cliente por email",
 * se dispara un email custom (WC_Email) con los datos del despacho.
 *
 * El cliente ve el tracking en "Mi cuenta → Detalle del pedido" cuando hay
 * información disponible.
 *
 * Compatible con HPOS y con el editor legacy de órdenes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  0. HELPERS de "Estado del despacho" (sub-estado operativo, independiente
 *     del estado WC de pago/orden).
 *
 *  Solo 3 estados (más "sin estado" implícito = meta vacío):
 *     - preparando    → la dueña está armando el pedido
 *     - despachado    → ya salió por courier / moto
 *     - listo_retiro  → llegó a la tienda y el cliente puede pasar a buscarlo
 *
 *  "Listo para retiro" SOLO se ofrece a órdenes cuyo método de envío fue
 *  bgmg_chile_retiro (retiro en tienda). En despacho normal esa opción no
 *  aparece en el select del metabox.
 * ------------------------------------------------------------------------- */

/**
 * Diccionario canónico: slug interno → etiqueta humana.
 *
 * @return array<string,string>
 */
function bgmg_chile_get_estados_despacho() {
	return array(
		'preparando'   => __( 'Preparando', 'bgmg-chile' ),
		'despachado'   => __( 'Despachado', 'bgmg-chile' ),
		'listo_retiro' => __( 'Listo para retiro', 'bgmg-chile' ),
	);
}

/**
 * Etiqueta humana de un slug. Devuelve '' si no es un estado conocido.
 *
 * @param string $slug
 * @return string
 */
function bgmg_chile_get_estado_despacho_label( $slug ) {
	$estados = bgmg_chile_get_estados_despacho();
	return isset( $estados[ $slug ] ) ? $estados[ $slug ] : '';
}

/**
 * ¿Esta orden se hizo por retiro en tienda? Recorremos los métodos de envío
 * de la orden y buscamos bgmg_chile_retiro.
 *
 * @param WC_Order $order
 * @return bool
 */
function bgmg_chile_orden_es_retiro( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	foreach ( $order->get_shipping_methods() as $item ) {
		if ( 'bgmg_chile_retiro' === $item->get_method_id() ) {
			return true;
		}
	}
	return false;
}

/**
 * Render del badge del estado del despacho. Se usa en:
 *   - cabecera del editor de orden (al lado del estado de WC)
 *   - "Mi cuenta → Detalle del pedido"
 *   - email custom
 *
 * Devuelve HTML listo para imprimir. Vacío si no hay estado.
 *
 * @param string $slug
 * @param string $context 'admin' | 'frontend' | 'email'
 * @return string
 */
function bgmg_chile_render_estado_badge( $slug, $context = 'frontend' ) {
	$label = bgmg_chile_get_estado_despacho_label( $slug );
	if ( '' === $label ) {
		return '';
	}
	// Cada estado tiene su par de colores (fondo claro / texto oscuro).
	$paletas = array(
		'preparando'   => array( 'bg' => '#FFF3E0', 'fg' => '#A0561B', 'icon' => '⚙️' ),
		'despachado'   => array( 'bg' => '#E3F2FD', 'fg' => '#1565C0', 'icon' => '🚚' ),
		'listo_retiro' => array( 'bg' => '#E8F5E9', 'fg' => '#2E7D32', 'icon' => '📍' ),
	);
	$p = isset( $paletas[ $slug ] ) ? $paletas[ $slug ] : array( 'bg' => '#EEE', 'fg' => '#333', 'icon' => '' );

	$style = 'display:inline-block;padding:4px 12px;border-radius:14px;font-size:0.85em;font-weight:600;'
		. 'background:' . $p['bg'] . ';color:' . $p['fg'] . ';font-family:\'DM Sans\',system-ui,sans-serif;';
	$icon  = $p['icon'] ? $p['icon'] . ' ' : '';

	return '<span class="bgmg-chile-estado-badge bgmg-chile-estado-' . esc_attr( $slug ) . '" style="' . esc_attr( $style ) . '">'
		. esc_html( $icon . $label )
		. '</span>';
}

/* ------------------------------------------------------------------------- *
 *  1. METABOX EN ADMIN DE ORDEN (HPOS + legacy)
 * ------------------------------------------------------------------------- */

add_action( 'add_meta_boxes', 'bgmg_chile_register_tracking_metabox' );

function bgmg_chile_register_tracking_metabox() {

	// wc_get_page_screen_id devuelve el screen id correcto según HPOS esté
	// activo o no. En legacy es 'shop_order', en HPOS es
	// 'woocommerce_page_wc-orders'.
	$screen = function_exists( 'wc_get_page_screen_id' )
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';

	add_meta_box(
		'bgmg-chile-tracking',
		__( '📦 Tracking de envío', 'bgmg-chile' ),
		'bgmg_chile_render_tracking_metabox',
		$screen,
		'side',
		'high'
	);
}

/**
 * Render del metabox. Recibe el WC_Order (HPOS) o el WP_Post (legacy);
 * en ambos casos resolvemos a WC_Order.
 *
 * @param mixed $post_or_order
 */
function bgmg_chile_render_tracking_metabox( $post_or_order ) {

	$order = is_a( $post_or_order, 'WC_Order' )
		? $post_or_order
		: wc_get_order( is_object( $post_or_order ) ? $post_or_order->ID : $post_or_order );

	if ( ! $order ) {
		echo '<p>' . esc_html__( 'No se puede leer la orden.', 'bgmg-chile' ) . '</p>';
		return;
	}

	$codigo  = $order->get_meta( '_bgmg_tracking_codigo' );
	$metodo  = $order->get_meta( '_bgmg_tracking_metodo' );
	$enviado = $order->get_meta( '_bgmg_tracking_email_enviado' );
	$estado  = $order->get_meta( '_bgmg_estado_despacho' );

	$es_retiro = bgmg_chile_orden_es_retiro( $order );
	$estados   = bgmg_chile_get_estados_despacho();
	// Si la orden NO es retiro, escondemos "Listo para retiro" del select.
	if ( ! $es_retiro ) {
		unset( $estados['listo_retiro'] );
	}

	wp_nonce_field( 'bgmg_chile_save_tracking', 'bgmg_chile_tracking_nonce' );

	?>
	<div class="bgmg-chile-tracking-box">

		<p>
			<label for="bgmg_estado_despacho">
				<strong><?php esc_html_e( 'Estado del despacho:', 'bgmg-chile' ); ?></strong>
			</label>
			<select id="bgmg_estado_despacho" name="bgmg_estado_despacho" style="width:100%;">
				<option value=""><?php esc_html_e( '— Sin estado —', 'bgmg-chile' ); ?></option>
				<?php foreach ( $estados as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $estado, $slug ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( $estado ) : ?>
				<span style="display:block;margin-top:6px;">
					<?php echo bgmg_chile_render_estado_badge( $estado, 'admin' ); /* phpcs:ignore WordPress.Security.EscapeOutput */ ?>
				</span>
			<?php endif; ?>
			<small style="display:block;margin-top:4px;color:#7A5060;">
				<?php esc_html_e( 'Es un sub-estado operativo del despacho. No reemplaza el estado de pago de WooCommerce.', 'bgmg-chile' ); ?>
			</small>
		</p>

		<p>
			<label for="bgmg_tracking_metodo">
				<strong><?php esc_html_e( 'Método / Courier:', 'bgmg-chile' ); ?></strong>
			</label>
			<input
				type="text"
				id="bgmg_tracking_metodo"
				name="bgmg_tracking_metodo"
				value="<?php echo esc_attr( $metodo ); ?>"
				placeholder="<?php esc_attr_e( 'Starken, Bluexpress, Moto propia…', 'bgmg-chile' ); ?>"
				style="width:100%;"
			/>
		</p>

		<p>
			<label for="bgmg_tracking_codigo">
				<strong><?php esc_html_e( 'Código de seguimiento:', 'bgmg-chile' ); ?></strong>
			</label>
			<input
				type="text"
				id="bgmg_tracking_codigo"
				name="bgmg_tracking_codigo"
				value="<?php echo esc_attr( $codigo ); ?>"
				placeholder="<?php esc_attr_e( 'Déjalo vacío si no aplica', 'bgmg-chile' ); ?>"
				style="width:100%;"
			/>
			<small style="color:#7A5060;">
				<?php esc_html_e( 'Puedes dejarlo vacío si el método (ej: moto propia) no genera código.', 'bgmg-chile' ); ?>
			</small>
		</p>

		<p style="margin-top:14px;">
			<label>
				<input
					type="checkbox"
					name="bgmg_tracking_avisar"
					value="1"
				/>
				<strong><?php esc_html_e( 'Avisar al cliente por email', 'bgmg-chile' ); ?></strong>
			</label>
		</p>

		<?php if ( $enviado ) : ?>
			<p style="margin:8px 0 0;padding:8px;background:#FBF0F2;border-radius:4px;font-size:12px;color:#7A5060;">
				<?php
				/* translators: %s: fecha en formato local */
				printf(
					esc_html__( 'Último aviso enviado al cliente: %s', 'bgmg-chile' ),
					esc_html( date_i18n( 'd/m/Y H:i', (int) $enviado ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  2. GUARDAR + (opcionalmente) DISPARAR EMAIL
 * ------------------------------------------------------------------------- */

/**
 * woocommerce_process_shop_order_meta funciona en ambos contextos (HPOS y
 * legacy). Recibe el order_id; nosotros resolvemos a WC_Order.
 */
add_action( 'woocommerce_process_shop_order_meta', 'bgmg_chile_save_tracking_meta', 20, 2 );

function bgmg_chile_save_tracking_meta( $order_id, $order = null ) {

	if ( ! isset( $_POST['bgmg_chile_tracking_nonce'] ) ||
		! wp_verify_nonce( wp_unslash( $_POST['bgmg_chile_tracking_nonce'] ), 'bgmg_chile_save_tracking' )
	) {
		return;
	}
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		return;
	}

	$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$codigo = isset( $_POST['bgmg_tracking_codigo'] )
		? bgmg_chile_sanitize_text( wp_unslash( $_POST['bgmg_tracking_codigo'] ), 100 )
		: '';
	$metodo = isset( $_POST['bgmg_tracking_metodo'] )
		? bgmg_chile_sanitize_text( wp_unslash( $_POST['bgmg_tracking_metodo'] ), 80 )
		: '';
	$avisar = ! empty( $_POST['bgmg_tracking_avisar'] );

	// Estado del despacho: el helper compartido lo valida (slug conocido +
	// regla de retiro) y persiste todo. Mismo núcleo que usa la PWA por AJAX.
	$estado_nuevo = isset( $_POST['bgmg_estado_despacho'] )
		? sanitize_key( wp_unslash( $_POST['bgmg_estado_despacho'] ) )
		: '';

	$resultado = bgmg_chile_persistir_tracking( $order, $codigo, $metodo, $estado_nuevo, $avisar );

	// Marcó "avisar" pero dejó método y código vacíos: avisamos en pantalla
	// para que sepa que no se envió nada (antes era silencioso → bug UX).
	if ( ! empty( $resultado['sin_datos'] ) ) {
		set_transient(
			'bgmg_chile_tracking_notice_' . get_current_user_id(),
			__( 'No se envió aviso al cliente: necesitas completar al menos el método o el código de tracking.', 'bgmg-chile' ),
			30
		);
	}
}

/**
 * Núcleo COMPARTIDO de persistencia de tracking + estado de despacho.
 *
 * Lo usan tanto el metabox de wp-admin (bgmg_chile_save_tracking_meta) como la
 * PWA de despachos (AJAX). Centralizarlo asegura que ambos guarden EXACTAMENTE
 * las mismas metas, dejen las mismas notas y disparen el mismo email — sin que
 * uno se desincronice del otro.
 *
 * Los valores llegan YA sanitizados por el llamador (cada contexto valida su
 * propio nonce y capability). El estado se revalida aquí como última defensa.
 *
 * @param WC_Order $order
 * @param string   $codigo  Código de seguimiento (puede ir vacío).
 * @param string   $metodo  Método / courier (puede ir vacío).
 * @param string   $estado  Slug de estado de despacho ('' = sin estado).
 * @param bool     $avisar  Si true y hay método o código, dispara el email.
 * @return array{ok:bool, estado:string, emailed:bool, sin_datos:bool}
 */
function bgmg_chile_persistir_tracking( $order, $codigo, $metodo, $estado, $avisar ) {

	if ( ! $order instanceof WC_Order ) {
		return array(
			'ok'        => false,
			'estado'    => '',
			'emailed'   => false,
			'sin_datos' => false,
		);
	}

	// Estado: solo slugs conocidos. Si la orden no es retiro, rechazamos
	// "listo_retiro" (defensa contra POST manual).
	$estados_validos = array_keys( bgmg_chile_get_estados_despacho() );
	if ( ! bgmg_chile_orden_es_retiro( $order ) ) {
		$estados_validos = array_diff( $estados_validos, array( 'listo_retiro' ) );
	}
	if ( '' !== $estado && ! in_array( $estado, $estados_validos, true ) ) {
		$estado = '';
	}
	$estado_previo = (string) $order->get_meta( '_bgmg_estado_despacho' );

	$order->update_meta_data( '_bgmg_tracking_codigo', $codigo );
	$order->update_meta_data( '_bgmg_tracking_metodo', $metodo );
	$order->update_meta_data( '_bgmg_estado_despacho', $estado );

	// Dejamos rastro en el log de la orden para auditoría.
	$notas = array();
	if ( $codigo && $metodo ) {
		$notas[] = sprintf( __( 'Tracking actualizado: %1$s — %2$s', 'bgmg-chile' ), $metodo, $codigo );
	} elseif ( $metodo ) {
		$notas[] = sprintf( __( 'Método de despacho actualizado: %s', 'bgmg-chile' ), $metodo );
	} elseif ( $codigo ) {
		$notas[] = sprintf( __( 'Código de tracking actualizado: %s', 'bgmg-chile' ), $codigo );
	}
	if ( $estado !== $estado_previo ) {
		$label_nuevo  = $estado ? bgmg_chile_get_estado_despacho_label( $estado ) : __( 'sin estado', 'bgmg-chile' );
		$label_previo = $estado_previo ? bgmg_chile_get_estado_despacho_label( $estado_previo ) : __( 'sin estado', 'bgmg-chile' );
		$notas[] = sprintf(
			/* translators: 1: estado anterior, 2: estado nuevo */
			__( 'Estado del despacho: %1$s → %2$s', 'bgmg-chile' ),
			$label_previo,
			$label_nuevo
		);
	}
	foreach ( $notas as $nota ) {
		$order->add_order_note( $nota, false, true ); // private note, current user
	}

	$order->save();

	// Si pidió avisar al cliente: necesitamos al menos método o código.
	$emailed   = false;
	$sin_datos = false;
	if ( $avisar ) {
		if ( $codigo || $metodo ) {
			bgmg_chile_send_tracking_email( $order );
			$order->update_meta_data( '_bgmg_tracking_email_enviado', time() );
			$order->save();
			$emailed = true;
		} else {
			$sin_datos = true;
		}
	}

	return array(
		'ok'        => true,
		'estado'    => $estado,
		'emailed'   => $emailed,
		'sin_datos' => $sin_datos,
	);
}

/**
 * Render del aviso "no se envió email por falta de datos" en la siguiente
 * pantalla admin que cargue ese usuario.
 */
add_action( 'admin_notices', 'bgmg_chile_tracking_show_notice' );

function bgmg_chile_tracking_show_notice() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}
	$msg = get_transient( 'bgmg_chile_tracking_notice_' . $user_id );
	if ( ! $msg ) {
		return;
	}
	delete_transient( 'bgmg_chile_tracking_notice_' . $user_id );
	echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
}

/**
 * Dispara el email custom "Tu pedido fue despachado".
 */
function bgmg_chile_send_tracking_email( $order ) {

	$mailer = WC()->mailer();
	$emails = $mailer ? $mailer->get_emails() : array();

	if ( isset( $emails['BGMG_Chile_Email_Tracking'] ) ) {
		/** @var BGMG_Chile_Email_Tracking $email */
		$email = $emails['BGMG_Chile_Email_Tracking'];
		$email->trigger( $order->get_id(), $order );
	}
}

/* ------------------------------------------------------------------------- *
 *  2.5. BADGE DEL ESTADO EN LA CABECERA DEL EDITOR DE ORDEN (admin)
 *
 *  WC tiene el filtro `woocommerce_admin_order_preview_get_order_details`
 *  para el panel rápido, pero para el editor de orden no expone un hook
 *  directo en el título. La forma más limpia es enganchar al panel lateral
 *  derecho (woocommerce_admin_order_data_after_order_details), arriba de
 *  la dirección de facturación.
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_admin_order_data_after_order_details', 'bgmg_chile_render_estado_en_cabecera' );

function bgmg_chile_render_estado_en_cabecera( $order ) {
	$estado = $order->get_meta( '_bgmg_estado_despacho' );
	if ( ! $estado ) {
		return;
	}
	echo '<p style="margin-top:8px;"><strong>'
		. esc_html__( 'Estado del despacho:', 'bgmg-chile' )
		. '</strong> '
		. bgmg_chile_render_estado_badge( $estado, 'admin' ) /* phpcs:ignore WordPress.Security.EscapeOutput */
		. '</p>';
}

/* ------------------------------------------------------------------------- *
 *  3. DISPLAY EN "MI CUENTA → DETALLE DEL PEDIDO"
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_order_details_after_order_table', 'bgmg_chile_render_tracking_in_my_account', 20 );

function bgmg_chile_render_tracking_in_my_account( $order ) {

	$codigo = $order->get_meta( '_bgmg_tracking_codigo' );
	$metodo = $order->get_meta( '_bgmg_tracking_metodo' );
	$estado = $order->get_meta( '_bgmg_estado_despacho' );

	// Si no hay nada que mostrar, salimos.
	if ( ! $codigo && ! $metodo && ! $estado ) {
		return;
	}
	?>
	<section class="bgmg-chile-order-tracking" style="margin-top:32px;">
		<h2 style="font-family:'Cormorant Garamond',Georgia,serif;color:#1A1015;display:flex;align-items:center;gap:8px;">
			📦 <?php esc_html_e( 'Seguimiento del despacho', 'bgmg-chile' ); ?>
		</h2>
		<table class="woocommerce-table shop_table">
			<tbody>
				<?php if ( $estado ) : ?>
					<tr>
						<th><?php esc_html_e( 'Estado', 'bgmg-chile' ); ?></th>
						<td><?php echo bgmg_chile_render_estado_badge( $estado, 'frontend' ); /* phpcs:ignore WordPress.Security.EscapeOutput */ ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $metodo ) : ?>
					<tr>
						<th><?php esc_html_e( 'Método de envío', 'bgmg-chile' ); ?></th>
						<td><?php echo esc_html( $metodo ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $codigo ) : ?>
					<tr>
						<th><?php esc_html_e( 'Código de seguimiento', 'bgmg-chile' ); ?></th>
						<td>
							<strong style="font-family:monospace;font-size:1.05em;letter-spacing:0.5px;">
								<?php echo esc_html( $codigo ); ?>
							</strong>
							<button
								type="button"
								class="bgmg-chile-copiar-tracking"
								data-codigo="<?php echo esc_attr( $codigo ); ?>"
								style="margin-left:8px;padding:4px 10px;border:1px solid #C4728A;background:#FBF0F2;color:#1A1015;border-radius:4px;font-size:0.85em;cursor:pointer;"
							>📋 <?php esc_html_e( 'Copiar', 'bgmg-chile' ); ?></button>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</section>
	<?php
}

/**
 * Encola el JS de "copiar código" solo en páginas donde hay tracking visible:
 * mi cuenta (view-order) y la thank you page después del pago.
 */
add_action( 'wp_enqueue_scripts', 'bgmg_chile_enqueue_copiar_tracking_js', 30 );

function bgmg_chile_enqueue_copiar_tracking_js() {
	$en_mi_cuenta_orden = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'view-order' );
	$en_thankyou        = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );

	if ( ! $en_mi_cuenta_orden && ! $en_thankyou ) {
		return;
	}

	wp_enqueue_script(
		'bgmg-chile-copiar-tracking',
		BGMG_CHILE_URL . 'assets/js/copiar-tracking.js',
		array(),
		BGMG_CHILE_VERSION,
		true
	);
}
