<?php
/**
 * Personaliza los emails post-checkout para que digan lo mismo que la
 * thank-you page.
 *
 * Por defecto, WooCommerce envía:
 *   - "Customer on hold order"   → cuando la orden queda en espera (transferencia
 *                                   bancaria pendiente de confirmación).
 *   - "Customer processing order" → cuando el pago se confirma (Transbank ok).
 *
 * Estos emails llegan con textos genéricos de WC que no reflejan el flujo
 * real: el cliente que paga por transferencia recibía un mensaje neutro y se
 * quedaba sin saber qué hacer; el de Transbank ok no veía la promesa
 * específica sobre courier que sí ve en la web.
 *
 * Lo que hace este módulo:
 *   1. Reescribe asunto y heading del email "en espera" cuando el método es
 *      transferencia bancaria (bacs) para dejar claro que falta confirmar
 *      el pago.
 *   2. Inserta un bloque destacado al inicio del cuerpo (antes de la tabla de
 *      productos) con el MISMO mensaje que la thank-you, reutilizando
 *      bgmg_chile_get_thankyou_message() — fuente única de verdad.
 *
 * Solo modificamos cuando la orden es de Chile. Para otros países dejamos los
 * emails nativos intactos.
 *
 * Los bloques "RUT/factura" y "Datos para tu retiro" ya se inyectan en TODOS
 * los emails desde sus propios módulos (rut/order-display.php y
 * envio/class-shipping-retiro.php) vía woocommerce_email_after_order_table,
 * así que no hace falta tocarlos acá.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  1. ASUNTO Y HEADING DEL EMAIL "EN ESPERA" (solo si es transferencia)
 * ------------------------------------------------------------------------- */

add_filter( 'woocommerce_email_subject_customer_on_hold_order', 'bgmg_chile_email_on_hold_subject', 10, 2 );

function bgmg_chile_email_on_hold_subject( $subject, $order ) {
	if ( ! bgmg_chile_email_aplica_on_hold( $order ) ) {
		return $subject;
	}
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	return sprintf(
		/* translators: 1: nombre de la tienda, 2: número de pedido */
		__( '[%1$s] Pedido #%2$s — pendiente de transferencia', 'bgmg-chile' ),
		$blogname,
		$order->get_order_number()
	);
}

add_filter( 'woocommerce_email_heading_customer_on_hold_order', 'bgmg_chile_email_on_hold_heading', 10, 2 );

function bgmg_chile_email_on_hold_heading( $heading, $order ) {
	if ( ! bgmg_chile_email_aplica_on_hold( $order ) ) {
		return $heading;
	}
	return __( 'Tu pedido está pendiente de transferencia', 'bgmg-chile' );
}

/* ------------------------------------------------------------------------- *
 *  2. AVISO DESTACADO AL INICIO DEL CUERPO DEL EMAIL
 *
 *  Reutilizamos bgmg_chile_get_thankyou_message para que web y email digan
 *  exactamente lo mismo. Si en el futuro se cambia un texto, basta tocarlo
 *  en landing-helpers.php.
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_email_before_order_table', 'bgmg_chile_email_aviso_estado', 5, 4 );

function bgmg_chile_email_aviso_estado( $order, $sent_to_admin, $plain_text, $email ) {

	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( $sent_to_admin ) {
		return; // este aviso es para el cliente, no para el admin.
	}
	if ( 'CL' !== $order->get_billing_country() ) {
		return; // órdenes no chilenas conservan el texto WC nativo.
	}

	// Solo aplicamos a estos dos emails. "Completed" ya recibe el email custom
	// de tracking; "failed" no se envía al cliente por defecto.
	$id = isset( $email->id ) ? $email->id : '';
	if ( 'customer_on_hold_order' !== $id && 'customer_processing_order' !== $id ) {
		return;
	}

	// Para "on-hold" no aplicamos nuestro aviso si el pago no es transferencia
	// (ej. la dueña pasó manualmente la orden a on-hold por otro motivo): el
	// mensaje "realiza la transferencia abajo" sería incorrecto.
	if ( 'customer_on_hold_order' === $id && ! bgmg_chile_email_aplica_on_hold( $order ) ) {
		return;
	}

	$msg = bgmg_chile_get_thankyou_message( $order );
	if ( '' === $msg ) {
		return;
	}

	if ( $plain_text ) {
		echo "\n" . wp_strip_all_tags( $msg ) . "\n\n";
		return;
	}

	// Diferenciamos visualmente: rosa para on-hold (acción pendiente del cliente),
	// verde suave para processing (todo ok, solo informativo).
	$es_pendiente = ( 'customer_on_hold_order' === $id );
	$bg     = $es_pendiente ? '#FBF0F2' : '#F1F8E9';
	$border = $es_pendiente ? '#C4728A' : '#7CB342';
	$color  = $es_pendiente ? '#1A1015' : '#33691E';

	printf(
		'<div style="margin:0 0 24px;padding:16px 20px;background:%1$s;border-left:3px solid %2$s;font-family:\'DM Sans\',Arial,sans-serif;color:%3$s;font-size:15px;line-height:1.5;">%4$s</div>',
		esc_attr( $bg ),
		esc_attr( $border ),
		esc_attr( $color ),
		esc_html( $msg )
	);
}

/* ------------------------------------------------------------------------- *
 *  HELPERS INTERNOS
 * ------------------------------------------------------------------------- */

/**
 * ¿La orden cumple las condiciones para aplicar nuestra personalización del
 * email "en espera"? Sí cuando: es de Chile y el método de pago es
 * transferencia bancaria (bacs). Cualquier otro caso → no tocamos nada y WC
 * usa el texto nativo.
 *
 * @param mixed $order
 * @return bool
 */
function bgmg_chile_email_aplica_on_hold( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	if ( 'CL' !== $order->get_billing_country() ) {
		return false;
	}
	return 'bacs' === $order->get_payment_method();
}
