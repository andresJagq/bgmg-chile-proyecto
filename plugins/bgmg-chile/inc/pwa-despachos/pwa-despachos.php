<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * =========================================================
 * MÓDULO: PWA DE DESPACHOS — mini-app móvil de despachos
 *
 * Mini-app móvil para gestionar despachos desde el teléfono, instalable
 * vía "Agregar a pantalla de inicio" (manifest + standalone). Vive en la
 * URL /despachos/ del propio sitio: NO es una app externa — el servidor
 * consulta los pedidos directo de WooCommerce y la seguridad es el login
 * de WordPress (auth_redirect) + capability por acción.
 *
 * Parte 1: ruta, login/permisos, manifest y pantalla de LISTA con pestañas
 *   Por despachar / Enviados / Retiro.
 * Parte 2 (v1.20.0): DETALLE del pedido (`?pedido=ID`) + guardar courier /
 *   código / estado + "avisar al cliente" por AJAX. La persistencia y el
 *   email se delegan en bgmg_chile_persistir_tracking() (núcleo COMPARTIDO
 *   con el metabox de wp-admin → cero drift). La página /despachos/ NO se
 *   cachea, así que el nonce del formulario siempre va fresco.
 * Fase 2+: foto del voucher, rol "Despachos", etiqueta, búsqueda.
 *
 * Diseño y decisiones acordadas: HANDOFF.md §"DISEÑO ACORDADO (2026-06-12)".
 * =========================================================
 */

/* ------------------------------------------------------------------------- *
 *  1. RUTA /despachos/
 * ------------------------------------------------------------------------- */

add_action( 'init', 'bgmg_chile_pwa_register_route' );

function bgmg_chile_pwa_register_route() {
	add_rewrite_rule( '^despachos/?$', 'index.php?bgmg_despachos=1', 'top' );

	// Flush perezoso: una sola vez por versión de la regla. Evita pedirle a la
	// usuaria "guardar enlaces permanentes" tras subir el zip (el flush de la
	// activación no corre cuando se reemplaza el plugin sin reactivar).
	if ( get_option( 'bgmg_chile_pwa_rw' ) !== '1' ) {
		flush_rewrite_rules();
		update_option( 'bgmg_chile_pwa_rw', '1' );
	}
}

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'bgmg_despachos';
		return $vars;
	}
);

/**
 * ¿El usuario actual puede usar la app de despachos?
 * Admins y gerentes de tienda (edit_shop_orders) entran directo; la
 * capability bgmg_despachos queda lista para el rol "Despachos" (Fase 2).
 */
function bgmg_chile_pwa_user_can() {
	return current_user_can( 'edit_shop_orders' ) || current_user_can( 'bgmg_despachos' );
}

/* ------------------------------------------------------------------------- *
 *  2. CONTROLADOR: auth + render (template_redirect, antes del tema)
 * ------------------------------------------------------------------------- */

add_action( 'template_redirect', 'bgmg_chile_pwa_render', 0 );

function bgmg_chile_pwa_render() {

	if ( '1' !== get_query_var( 'bgmg_despachos' ) ) {
		return;
	}

	// El manifest se sirve ANTES del login: el navegador lo pide SIN cookies
	// (spec de Web App Manifest) y con auth recibiría el HTML del formulario
	// de login en vez del JSON → la instalación como app fallaría. Solo
	// contiene metadatos públicos (nombre, colores, ícono del sitio).
	if ( isset( $_GET['bgmg_manifest'] ) ) {
		bgmg_chile_pwa_manifest();
	}

	// Contenido privado por usuario: jamás cachear (ni navegador ni LiteSpeed).
	nocache_headers();
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	header( 'X-LiteSpeed-Cache-Control: no-cache' );

	// Sin sesión → login de WP y de vuelta acá (auth_redirect arma el redirect_to).
	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}

	if ( ! bgmg_chile_pwa_user_can() ) {
		wp_die(
			esc_html__( 'Tu usuario no tiene permiso para usar la app de despachos. Pídele acceso a la administradora.', 'bgmg-chile' ),
			esc_html__( 'Acceso restringido', 'bgmg-chile' ),
			array( 'response' => 403 )
		);
	}

	// ── DETALLE de un pedido (?pedido=ID) ───────────────────────────────────
	$pedido_id = isset( $_GET['pedido'] ) ? absint( $_GET['pedido'] ) : 0;
	if ( $pedido_id > 0 ) {
		$order = wc_get_order( $pedido_id );
		// Solo pedidos reales (los reembolsos no tienen dirección de envío).
		if ( $order instanceof WC_Order ) {
			$detalle = bgmg_chile_pwa_detalle_data( $order );
			require BGMG_CHILE_DIR . 'inc/pwa-despachos/vista-detalle.php';
			exit;
		}
		// Pedido inexistente o no válido → caemos a la lista con un aviso suave.
		$pedido_no_encontrado = true;
	}

	// ── LISTA (pantalla principal) ──────────────────────────────────────────
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'pendientes';
	if ( ! in_array( $tab, array( 'pendientes', 'enviados', 'retiro' ), true ) ) {
		$tab = 'pendientes';
	}

	$listas = bgmg_chile_pwa_get_listas();

	require BGMG_CHILE_DIR . 'inc/pwa-despachos/vista-lista.php';
	exit;
}

/* ------------------------------------------------------------------------- *
 *  3. DATOS: las tres listas
 * ------------------------------------------------------------------------- */

/**
 * Clasifica los pedidos en las tres pestañas:
 *   - pendientes: pagados (processing), envío a domicilio, aún sin despachar.
 *   - retiro:     pagados (processing) con método "retiro en tienda".
 *   - enviados:   despachados (meta) aún en processing + completados recientes.
 *
 * Los pendientes van del más antiguo al más nuevo (lo urgente primero).
 *
 * @return array{pendientes: WC_Order[], retiro: WC_Order[], enviados: WC_Order[]}
 */
function bgmg_chile_pwa_get_listas() {

	$pendientes = array();
	$retiro     = array();
	$enviados   = array();

	$processing = wc_get_orders(
		array(
			'status'  => array( 'processing' ),
			'limit'   => 100,
			'orderby' => 'date',
			'order'   => 'ASC',
			'type'    => 'shop_order', // sin refunds (WC_Order_Refund no tiene shipping)
		)
	);

	foreach ( $processing as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$estado = (string) $order->get_meta( '_bgmg_estado_despacho' );

		if ( bgmg_chile_orden_es_retiro( $order ) ) {
			// Sigue en Retiro aunque esté "listo_retiro": sale al completarse.
			$retiro[] = $order;
		} elseif ( 'despachado' === $estado ) {
			$enviados[] = $order;
		} else {
			$pendientes[] = $order;
		}
	}

	// Historial corto: completados recientes también se listan como enviados.
	$completados = wc_get_orders(
		array(
			'status'  => array( 'completed' ),
			'limit'   => 20,
			'orderby' => 'date',
			'order'   => 'DESC',
			'type'    => 'shop_order',
		)
	);
	foreach ( $completados as $order ) {
		if ( $order instanceof WC_Order ) {
			$enviados[] = $order;
		}
	}

	// Enviados: más recientes primero (mezcla processing-despachado + completed).
	usort(
		$enviados,
		function ( $a, $b ) {
			$ta = $a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0;
			$tb = $b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0;
			return $tb <=> $ta;
		}
	);

	return array(
		'pendientes' => $pendientes,
		'retiro'     => $retiro,
		'enviados'   => $enviados,
	);
}

/**
 * Datos listos-para-pintar de una tarjeta de pedido.
 * Reusa bgmg_chile_get_datos_despacho() (etiqueta) para nombre/comuna/método.
 *
 * @param WC_Order $order
 * @return array<string,mixed>
 */
function bgmg_chile_pwa_card_data( $order ) {

	$d      = bgmg_chile_get_datos_despacho( $order );
	$estado = (string) $order->get_meta( '_bgmg_estado_despacho' );
	$fecha  = $order->get_date_created();

	return array(
		'id'           => $order->get_id(),
		'numero'       => $d['id'],
		'nombre'       => $d['nombre'],
		'comuna'       => $d['comuna'],
		'metodo'       => $d['metodo'],
		'telefono'     => $d['telefono'],
		'total'        => wp_strip_all_tags( wc_price( $order->get_total() ) ),
		'items'        => (int) $order->get_item_count(),
		'fecha'        => $fecha ? date_i18n( 'j M', $fecha->getOffsetTimestamp() ) : '',
		'estado'       => $estado,
		'estado_label' => $estado ? bgmg_chile_get_estado_despacho_label( $estado ) : '',
		'wc_status'    => $order->get_status(),
		'tracking'     => trim( (string) $order->get_meta( '_bgmg_tracking_codigo' ) ),
	);
}

/**
 * Couriers sugeridos como pills en el detalle. La dueña puede tocar uno para
 * rellenar el campo, o escribir cualquier otro a mano (el texto manda).
 *
 * @return string[]
 */
function bgmg_chile_pwa_couriers() {
	return apply_filters(
		'bgmg_chile_pwa_couriers',
		array( 'Starken', 'Chilexpress', 'Bluexpress', 'Correos Chile', 'Pullman Cargo', 'Despacho propio' )
	);
}

/**
 * Datos completos para la pantalla de DETALLE de un pedido.
 * Reusa bgmg_chile_get_datos_despacho() (nombre/dirección/courier) y agrega
 * ítems, totales, nota del cliente y el estado/tracking actuales.
 *
 * @param WC_Order $order
 * @return array<string,mixed>
 */
function bgmg_chile_pwa_detalle_data( $order ) {

	$d = bgmg_chile_get_datos_despacho( $order );

	$items = array();
	foreach ( $order->get_items() as $item ) {
		$producto = $item->get_product();
		$items[]  = array(
			'nombre' => $item->get_name(),
			'qty'    => (int) $item->get_quantity(),
			'sku'    => $producto ? $producto->get_sku() : '',
		);
	}

	$es_retiro = bgmg_chile_orden_es_retiro( $order );

	// Estados disponibles (igual que el metabox: sin "listo_retiro" si no es retiro).
	$estados = bgmg_chile_get_estados_despacho();
	if ( ! $es_retiro ) {
		unset( $estados['listo_retiro'] );
	}

	$enviado_ts = (int) $order->get_meta( '_bgmg_tracking_email_enviado' );

	return array(
		'id'            => $order->get_id(),
		'numero'        => $d['id'],
		'nombre'        => $d['nombre'],
		'rut'           => $d['rut'],
		'telefono'      => $d['telefono'],
		'correo'        => $d['correo'],
		'calle'         => $d['calle'],
		'comuna'        => $d['comuna'],
		'region'        => $d['region'],
		'es_retiro'     => $es_retiro,
		'metodo'        => trim( (string) $order->get_meta( '_bgmg_tracking_metodo' ) ),
		'metodo_envio'  => $d['metodo'], // courier guardado o método del checkout
		'codigo'        => trim( (string) $order->get_meta( '_bgmg_tracking_codigo' ) ),
		'estado'        => (string) $order->get_meta( '_bgmg_estado_despacho' ),
		'estados'       => $estados,
		'items'         => $items,
		'item_count'    => (int) $order->get_item_count(),
		'total'         => wp_strip_all_tags( wc_price( $order->get_total() ) ),
		'envio'         => wp_strip_all_tags( wc_price( $order->get_shipping_total() ) ),
		'pago'          => $order->get_payment_method_title(),
		'nota'          => $order->get_customer_note(),
		'fecha'         => $order->get_date_created() ? date_i18n( 'j M Y, H:i', $order->get_date_created()->getOffsetTimestamp() ) : '',
		'wc_status'     => $order->get_status(),
		'wc_status_lbl' => wc_get_order_status_name( $order->get_status() ),
		'email_fecha'   => $enviado_ts ? date_i18n( 'd/m/Y H:i', $enviado_ts ) : '',
		'edit_url'      => $order->get_edit_order_url(),
	);
}

/* ------------------------------------------------------------------------- *
 *  AJAX: guardar tracking/estado + (opcional) avisar al cliente
 * ------------------------------------------------------------------------- */

add_action( 'wp_ajax_bgmg_pwa_guardar', 'bgmg_chile_pwa_ajax_guardar' );
// Sin nopriv a propósito: la app es solo para usuarias autenticadas.

function bgmg_chile_pwa_ajax_guardar() {

	check_ajax_referer( 'bgmg_pwa_guardar', 'nonce' );

	if ( ! bgmg_chile_pwa_user_can() ) {
		wp_send_json_error( array( 'message' => __( 'Sin permiso.', 'bgmg-chile' ) ), 403 );
	}

	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$order    = $order_id ? wc_get_order( $order_id ) : null;
	if ( ! $order instanceof WC_Order ) {
		wp_send_json_error( array( 'message' => __( 'Pedido no encontrado.', 'bgmg-chile' ) ), 404 );
	}

	$codigo = isset( $_POST['codigo'] )
		? bgmg_chile_sanitize_text( wp_unslash( $_POST['codigo'] ), 100 )
		: '';
	$metodo = isset( $_POST['metodo'] )
		? bgmg_chile_sanitize_text( wp_unslash( $_POST['metodo'] ), 80 )
		: '';
	$estado = isset( $_POST['estado'] )
		? sanitize_key( wp_unslash( $_POST['estado'] ) )
		: '';
	$avisar = ! empty( $_POST['avisar'] );

	// Mismo núcleo que el metabox de wp-admin → metas, notas y email idénticos.
	$res = bgmg_chile_persistir_tracking( $order, $codigo, $metodo, $estado, $avisar );

	$estado_label = $res['estado'] ? bgmg_chile_get_estado_despacho_label( $res['estado'] ) : '';

	if ( $res['emailed'] ) {
		$mensaje = sprintf(
			/* translators: %s: correo del cliente */
			__( 'Guardado · aviso enviado a %s', 'bgmg-chile' ),
			$order->get_billing_email()
		);
	} elseif ( $res['sin_datos'] ) {
		$mensaje = __( 'Guardado. No se envió aviso: falta el courier o el código.', 'bgmg-chile' );
	} else {
		$mensaje = __( 'Cambios guardados', 'bgmg-chile' );
	}

	wp_send_json_success(
		array(
			'message'      => $mensaje,
			'emailed'      => (bool) $res['emailed'],
			'sin_datos'    => (bool) $res['sin_datos'],
			'estado'       => $res['estado'],
			'estado_label' => $estado_label,
			'codigo'       => $codigo,
			'metodo'       => $metodo,
		)
	);
}

/* ------------------------------------------------------------------------- *
 *  4. MANIFEST (instalable como app)
 * ------------------------------------------------------------------------- */

function bgmg_chile_pwa_manifest() {

	$icons = array();
	foreach ( array( 192, 512 ) as $size ) {
		$url = get_site_icon_url( $size );
		if ( $url ) {
			$icons[] = array(
				'src'   => $url,
				'sizes' => $size . 'x' . $size,
			);
		}
	}

	$manifest = array(
		'name'             => 'Despachos BGMG',
		'short_name'       => 'Despachos',
		'description'      => 'Gestión de despachos de BeautyGirlMG',
		'start_url'        => home_url( '/despachos/' ),
		'scope'            => home_url( '/despachos/' ),
		'display'          => 'standalone',
		'background_color' => '#FDF7F4',
		'theme_color'      => '#C4728A',
		'icons'            => $icons,
	);

	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	echo wp_json_encode( $manifest );
	exit;
}
