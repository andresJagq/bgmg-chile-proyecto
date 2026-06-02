<?php
/**
 * Integración del teléfono móvil chileno en el checkout de WC.
 *
 * Funciones:
 *   1. Forzar el campo billing_phone como obligatorio.
 *   2. Quitar el texto " (opcional)" del label (algunos temas y la traducción
 *      es_CL muestran "Teléfono (opcional)" porque consideran no required).
 *   3. Cambiar el placeholder a un ejemplo chileno.
 *   4. Validar en PHP que sea móvil +56 9... (módulo extractor estricto).
 *   5. Normalizar al formato "+56 9 XXXX XXXX" antes de guardar en la orden.
 *
 * Solo aplica cuando el país de billing es Chile (o aún no está definido,
 * caso primer render). Para otros países dejamos el comportamiento nativo
 * de WC intacto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  1 + 2 + 3. CAMPO: required + label limpio + placeholder
 * ------------------------------------------------------------------------- */

add_filter( 'woocommerce_billing_fields', 'bgmg_chile_force_phone_required', 20 );

function bgmg_chile_force_phone_required( $fields ) {

	if ( ! isset( $fields['billing_phone'] ) ) {
		return $fields;
	}

	// Solo cuando aplicamos a CL (o aún no hay país en sesión).
	if ( function_exists( 'bgmg_chile_should_apply_to_address' )
		&& ! bgmg_chile_should_apply_to_address() ) {
		return $fields;
	}

	$fields['billing_phone']['required']    = true;
	$fields['billing_phone']['label']       = __( 'Teléfono móvil', 'bgmg-chile' );
	$fields['billing_phone']['placeholder'] = __( '+56 9 1234 5678', 'bgmg-chile' );
	$fields['billing_phone']['class']       = array_merge(
		isset( $fields['billing_phone']['class'] ) ? (array) $fields['billing_phone']['class'] : array(),
		array( 'bgmg-chile-telefono-field' )
	);
	// 'autocomplete' tel para que el navegador ofrezca el guardado.
	$fields['billing_phone']['autocomplete'] = 'tel';
	// Pista para teclado móvil numérico en celulares.
	$fields['billing_phone']['custom_attributes'] = array(
		'inputmode' => 'tel',
		'pattern'   => '.*',
	);

	return $fields;
}

/**
 * Algunos temas anteponen " (opcional)" en función de si el campo es
 * required en el momento del render. Para evitar carreras de filtros y
 * por defensa, también quitamos cualquier "(opcional)" residual del label.
 */
add_filter( 'woocommerce_form_field_args', 'bgmg_chile_strip_opcional_phone_label', 20, 3 );

function bgmg_chile_strip_opcional_phone_label( $args, $key, $value ) {
	if ( 'billing_phone' !== $key ) {
		return $args;
	}
	if ( isset( $args['label'] ) ) {
		// Quitamos "(opcional)" en español y "(optional)" en inglés por si acaso.
		$args['label'] = preg_replace( '/\s*\((opcional|optional)\)\s*/iu', '', $args['label'] );
	}
	// Suprimir el texto auxiliar que WC añade automáticamente.
	$args['label_class']       = isset( $args['label_class'] ) ? $args['label_class'] : array();
	return $args;
}

/* ------------------------------------------------------------------------- *
 *  4. VALIDACIÓN PHP DEL MÓVIL
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_after_checkout_validation', 'bgmg_chile_validate_phone_checkout', 30, 2 );

function bgmg_chile_validate_phone_checkout( $data, $errors ) {

	// Solo aplicamos validación estricta para Chile.
	if ( 'CL' !== ( $data['billing_country'] ?? '' ) ) {
		return;
	}

	$telefono = isset( $data['billing_phone'] ) ? (string) $data['billing_phone'] : '';

	if ( '' === trim( $telefono ) ) {
		$errors->add(
			'bgmg_chile_phone_required',
			__( 'Por favor ingresa tu teléfono móvil para coordinar la entrega.', 'bgmg-chile' )
		);
		return;
	}

	if ( ! BGMG_Chile_Telefono_Validator::is_valid_movil( $telefono ) ) {
		$errors->add(
			'bgmg_chile_phone_invalido',
			__( 'El teléfono no es un móvil chileno válido. Debe ser un número que empieza con 9 y tiene 9 dígitos (ej: +56 9 1234 5678).', 'bgmg-chile' )
		);
	}
}

/* ------------------------------------------------------------------------- *
 *  5. NORMALIZACIÓN AL GUARDAR
 * ------------------------------------------------------------------------- */

/**
 * Filtro que WC aplica a cada campo antes de guardarlo en la orden.
 * Normalizamos al formato "+56 9 XXXX XXXX" solo si pasa validación.
 */
add_filter( 'woocommerce_process_checkout_field_billing_phone', 'bgmg_chile_normalize_phone_field' );

function bgmg_chile_normalize_phone_field( $valor ) {
	$valor = sanitize_text_field( (string) $valor );
	$formato = BGMG_Chile_Telefono_Validator::format_internacional( $valor );
	return '' !== $formato ? $formato : $valor; // si no es móvil CL devolvemos crudo (no es CL).
}

/* ------------------------------------------------------------------------- *
 *  ENCOLADO DEL JS DEL VALIDADOR EN CHECKOUT
 *
 *  El script principal del checkout (assets/js/checkout.js) ya tiene la
 *  inicialización del RUT; añadimos aquí un pequeño initializer para teléfono
 *  que vive como un script aparte para mantener la separación por feature.
 * ------------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'bgmg_chile_enqueue_telefono_assets', 20 );

function bgmg_chile_enqueue_telefono_assets() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	wp_enqueue_script(
		'bgmg-chile-telefono-validator',
		BGMG_CHILE_URL . 'assets/js/telefono-validator.js',
		array(),
		BGMG_CHILE_VERSION,
		true
	);

	wp_add_inline_script(
		'bgmg-chile-telefono-validator',
		"(function(){function init(){var i=document.getElementById('billing_phone');" .
		"if(!i||!window.BgmgChileTelefono)return;" .
		"window.BgmgChileTelefono.bindInput(i,{});}" .
		"if(window.jQuery){jQuery(document.body).on('updated_checkout',init);" .
		"jQuery(document).ready(init);}else{document.addEventListener('DOMContentLoaded',init);}})();"
	);
}
