<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * =========================================================
 * MÓDULO: PWA DE DESPACHOS (v1.19.0) — Parte 1: ruta + lista
 *
 * Mini-app móvil para gestionar despachos desde el teléfono, instalable
 * vía "Agregar a pantalla de inicio" (manifest + standalone). Vive en la
 * URL /despachos/ del propio sitio: NO es una app externa — el servidor
 * consulta los pedidos directo de WooCommerce y la seguridad es el login
 * de WordPress (auth_redirect) + capability por acción.
 *
 * Parte 1 (esta): ruta, login/permisos, manifest y pantalla de LISTA con
 * pestañas Por despachar / Enviados / Retiro.
 * Parte 2 (pendiente): detalle del pedido + guardar tracking/estado +
 * "avisar al cliente" (reusa bgmg_chile_send_tracking_email).
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
