<?php
/**
 * Campo RUT en el checkout de WooCommerce + toggle "Necesito factura"
 * + campos extra de empresa (razón social, giro, dirección comercial).
 *
 * Comportamiento esperado:
 *   1. Por defecto el checkout muestra solo el campo RUT (obligatorio).
 *   2. Al marcar "Necesito factura" se despliegan razón social, giro y
 *      dirección comercial (todos obligatorios bajo ese toggle).
 *   3. Si el RUT es de empresa (>= 50.000.000) y NO marcaron factura,
 *      mostramos un aviso suave sugiriendo activar el toggle, pero no
 *      bloqueamos: la dueña decidió no obligar.
 *
 * La validación módulo 11 viaja dos veces:
 *   - JS en checkout.js (UX inmediato).
 *   - PHP acá (seguridad, no se puede evadir).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inyecta el campo RUT y los de facturación dentro del bloque de billing
 * del checkout clásico (shortcode [woocommerce_checkout]).
 *
 * Para el checkout de bloques (Cart/Checkout Block) se requiere otro flujo
 * basado en register_checkout_field; lo dejamos como TODO si la dueña
 * migra a bloques.
 */
add_filter( 'woocommerce_checkout_fields', 'bgmg_chile_checkout_fields' );

function bgmg_chile_checkout_fields( $fields ) {

	// Razón social, giro y dirección comercial: marcados como NO required
	// a nivel WC, pero los validamos manualmente cuando el toggle factura está activo.
	foreach ( bgmg_chile_get_billing_extra_fields_definition() as $key => $config ) {
		$fields['billing'][ $key ] = $config;
	}

	return $fields;
}

/**
 * Validación PHP del RUT en el checkout.
 *
 * Hook `woocommerce_after_checkout_validation` recibe (data, errors). Si
 * agregamos errores con $errors->add() WC los muestra al usuario y bloquea
 * el pago.
 */
add_action( 'woocommerce_after_checkout_validation', 'bgmg_chile_validate_checkout_rut', 10, 2 );

function bgmg_chile_validate_checkout_rut( $data, $errors ) {

	$rut_crudo = isset( $data['billing_bgmg_rut'] ) ? (string) $data['billing_bgmg_rut'] : '';

	if ( '' === trim( $rut_crudo ) ) {
		$errors->add(
			'bgmg_chile_rut_required',
			__( 'Por favor ingresa tu RUT para continuar.', 'bgmg-chile' )
		);
		return;
	}

	if ( ! BGMG_Chile_RUT_Validator::is_valid( $rut_crudo ) ) {
		$errors->add(
			'bgmg_chile_rut_invalido',
			__( 'El RUT ingresado no es válido. Verifica el número y el dígito verificador.', 'bgmg-chile' )
		);
		return;
	}

	// Si pidieron factura, exigimos razón social y giro.
	$necesita_factura = ! empty( $data['billing_bgmg_necesita_factura'] );
	if ( $necesita_factura ) {
		if ( empty( trim( (string) ( $data['billing_bgmg_razon_social'] ?? '' ) ) ) ) {
			$errors->add(
				'bgmg_chile_razon_social_required',
				__( 'Para emitir factura necesitamos la razón social.', 'bgmg-chile' )
			);
		}
		if ( empty( trim( (string) ( $data['billing_bgmg_giro'] ?? '' ) ) ) ) {
			$errors->add(
				'bgmg_chile_giro_required',
				__( 'Para emitir factura necesitamos el giro comercial.', 'bgmg-chile' )
			);
		}
	}
}

/**
 * Normaliza el RUT antes de guardar en la orden.
 * Hook tardío para asegurar que llegamos después de la validación.
 */
add_filter( 'woocommerce_process_checkout_field_billing_bgmg_rut', 'bgmg_chile_normalize_rut_field' );

function bgmg_chile_normalize_rut_field( $valor ) {
	if ( ! BGMG_Chile_RUT_Validator::is_valid( $valor ) ) {
		return sanitize_text_field( (string) $valor );
	}
	// Guardamos el formato bonito en la orden (con puntos y guion).
	return BGMG_Chile_RUT_Validator::format( $valor );
}
