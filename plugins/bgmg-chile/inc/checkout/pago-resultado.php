<?php
/**
 * Resultado del pago Webpay (Transbank): VISIBILIDAD + RECUPERACIÓN.
 *
 * CONTEXTO DEL PROBLEMA
 * ---------------------
 * El plugin oficial de Transbank (Webpay Plus REST) NO deja rastro claro cuando
 * un pago no se completa. Comportamiento real (v1.14.0):
 *
 *   - Tarjeta rechazada / cliente anula / timeout / error de formulario:
 *       deja la orden en estado `failed` y redirige al CHECKOUT con
 *       `?transbank_status=N` (8=rechazo, 9=anulado, 10=timeout, 11=form),
 *       pero NO agrega ningún `wc_add_notice`. El cliente cae al checkout sin
 *       saber qué pasó ("solo te echa al carrito").
 *
 *   - El cliente cierra el navegador en la pasarela y NUNCA vuelve:
 *       la orden queda `pending` y WooCommerce la cancela sola por falta de
 *       pago (timeout de "mantener stock"). En el listado solo se ve
 *       "Cancelado", sin explicación. Es el caso MÁS común en tráfico de
 *       redes (IG/FB).
 *
 * En ningún caso hay un cobro real: el sistema de pago funciona. El problema
 * es de TRAZABILIDAD (la dueña no sabe por qué se cancelan) y de UX (el cliente
 * no entiende el error ni sabe cómo reintentar).
 *
 * QUÉ HACE ESTE MÓDULO
 * --------------------
 *   1. NOTA CLARA EN LA ORDEN (para la dueña): al pasar la orden a `failed` o
 *      al cancelarse sin pago, escribe una nota legible explicando qué ocurrió,
 *      para no tener que abrir el log de Transbank.
 *
 *   2. AVISO + REINTENTO (para el cliente): cuando vuelve del checkout con
 *      `?transbank_status=N`, le mostramos un mensaje claro según el código y
 *      un botón "Reintentar pago" que lo lleva al enlace de pago de SU MISMA
 *      orden (sin re-llenar nada). Además aclaramos que los productos se pagan
 *      ahora con tarjeta (solo el flete se paga al recibir), porque parte del
 *      abandono viene de clientes que creen que TODO es "pago al recibir".
 *
 * Solo actuamos sobre órdenes de Transbank Webpay Plus; otros métodos (ej.
 * transferencia/bacs) no se tocan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  HELPER: ¿la orden es de Transbank Webpay Plus?
 *
 *  El id del gateway en v1.14.0 es `wc_gateway_transbank_webpay_plus_rest`.
 *  Aceptamos también cualquier prefijo `transbank`/`wc_gateway_transbank` por
 *  si en una futura versión cambia el slug exacto.
 * ------------------------------------------------------------------------- */

function bgmg_chile_es_orden_transbank( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	$metodo = (string) $order->get_payment_method();
	if ( '' === $metodo ) {
		return false;
	}
	return ( 'wc_gateway_transbank_webpay_plus_rest' === $metodo )
		|| ( 0 === strpos( $metodo, 'transbank' ) )
		|| ( 0 === strpos( $metodo, 'wc_gateway_transbank' ) );
}

/* ------------------------------------------------------------------------- *
 *  1. NOTA CLARA EN LA ORDEN (visibilidad para la dueña)
 *
 *  Enganchamos al cambio de estado (más robusto que las acciones internas del
 *  plugin de Transbank, que pueden variar entre versiones).
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_order_status_changed', 'bgmg_chile_nota_resultado_pago', 20, 4 );

function bgmg_chile_nota_resultado_pago( $order_id, $old_status, $new_status, $order ) {

	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( $order_id );
	}
	if ( ! bgmg_chile_es_orden_transbank( $order ) ) {
		return;
	}

	// Caso A — quedó en "failed": el cliente VOLVIÓ de Webpay sin pagar
	// (rechazo de tarjeta, anulación o timeout en la pasarela). No hubo cobro.
	if ( 'failed' === $new_status ) {
		$order->add_order_note(
			__( '🔎 BGMG — Diagnóstico de pago: el cliente volvió de Webpay SIN completar el pago (rechazo de tarjeta, anulación o tiempo agotado en la pasarela). No se realizó ningún cobro y esto NO es un error del sistema. El cliente puede reintentar desde el enlace de pago de esta misma orden.', 'bgmg-chile' ),
			false
		);
		return;
	}

	// Caso B — pending → cancelled en orden NUNCA pagada = ABANDONO.
	// El cliente fue a Webpay y no completó el pago; WooCommerce la canceló por
	// falta de pago (o se canceló manualmente). En ambos casos no hubo cobro.
	// `get_date_paid()` vacío confirma que jamás se pagó.
	if ( 'cancelled' === $new_status && 'pending' === $old_status && ! $order->get_date_paid() ) {
		$order->add_order_note(
			__( '🔎 BGMG — Diagnóstico de pago: orden cancelada SIN pago. El cliente llegó a Webpay pero no completó el pago (abandono / tiempo de espera agotado). El sistema de pago funciona correctamente. Para recuperar la venta, puedes escribirle por WhatsApp o email con el enlace para pagar.', 'bgmg-chile' ),
			false
		);
		return;
	}
}

/* ------------------------------------------------------------------------- *
 *  2. AVISO + REINTENTO (claridad para el cliente)
 *
 *  Al volver de Webpay sin pagar, el plugin redirige al checkout con
 *  `?transbank_status=N` pero sin mensaje. Nosotros detectamos ese parámetro y
 *  mostramos un aviso claro + botón "Reintentar pago" (enlace de pago de la
 *  misma orden). Lo encolamos como `wc_add_notice`: WooCommerce lo pinta en el
 *  checkout (y en el carrito) donde aterrice el cliente.
 * ------------------------------------------------------------------------- */

add_action( 'template_redirect', 'bgmg_chile_aviso_pago_fallido_cliente' );

function bgmg_chile_aviso_pago_fallido_cliente() {

	if ( is_admin() || ! function_exists( 'wc_add_notice' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo lectura de un parámetro informativo de retorno de la pasarela.
	if ( ! isset( $_GET['transbank_status'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$code = sanitize_text_field( wp_unslash( $_GET['transbank_status'] ) );

	// Mensaje según el código del plugin de Transbank (v1.14.0).
	switch ( $code ) {
		case '9':
			$titulo = __( 'Cancelaste el pago en Webpay.', 'bgmg-chile' );
			break;
		case '10':
			$titulo = __( 'Se agotó el tiempo para completar el pago.', 'bgmg-chile' );
			break;
		case '11':
			$titulo = __( 'Hubo un problema con el formulario de pago.', 'bgmg-chile' );
			break;
		case '8':
		default:
			$titulo = __( 'Tu pago no se pudo completar y no se realizó ningún cobro.', 'bgmg-chile' );
			break;
	}

	// Aclaración clave: parte del abandono viene de clientes de redes que creen
	// que TODO es "pago al recibir". Dejamos claro que los productos se pagan ya.
	$aclaracion = __( 'Recuerda: los productos se pagan ahora con tarjeta por Webpay; solo el flete del despacho se paga al recibir.', 'bgmg-chile' );

	// Botón de reintento: enlace de pago de la MISMA orden (no pierde el carrito).
	$reintento_html = '';
	$order_id       = ( WC()->session ) ? WC()->session->get( 'order_awaiting_payment' ) : 0;
	if ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order && ! $order->get_date_paid() ) {
			$reintento_html = ' <a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="button bgmg-reintentar-pago">'
				. esc_html__( 'Reintentar pago', 'bgmg-chile' )
				. '</a>';
		}
	}

	$mensaje = '<strong>' . esc_html( $titulo ) . '</strong> ' . esc_html( $aclaracion ) . $reintento_html;

	// Evitamos duplicar el aviso si ya está encolado (recargas).
	if ( ! wc_has_notice( $mensaje, 'error' ) ) {
		wc_add_notice( $mensaje, 'error' );
	}
}
