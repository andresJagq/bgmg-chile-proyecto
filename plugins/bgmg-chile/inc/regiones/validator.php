<?php
/**
 * Validación PHP de la combinación región/comuna en el checkout y en
 * "Mi cuenta → Editar dirección".
 *
 * El JS de cascada protege la UX, pero un POST manipulado puede traer una
 * comuna que no pertenece a la región seleccionada. Aquí cerramos esa puerta.
 *
 * Aplica solo cuando el país (billing o shipping) es Chile.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook principal de validación del checkout.
 */
add_action( 'woocommerce_after_checkout_validation', 'bgmg_chile_validate_region_comuna', 20, 2 );

function bgmg_chile_validate_region_comuna( $data, $errors ) {

	// Validamos billing.
	if ( 'CL' === ( $data['billing_country'] ?? '' ) ) {
		$err = bgmg_chile_validar_par_region_comuna(
			$data['billing_state'] ?? '',
			$data['billing_city'] ?? '',
			__( 'facturación', 'bgmg-chile' )
		);
		if ( $err ) {
			$errors->add( 'bgmg_chile_billing_region_comuna', $err );
		}
	}

	// Validamos shipping solo si el usuario marcó "enviar a otra dirección".
	$ship_to_diff = isset( $data['ship_to_different_address'] ) && $data['ship_to_different_address'];
	if ( $ship_to_diff && 'CL' === ( $data['shipping_country'] ?? '' ) ) {
		$err = bgmg_chile_validar_par_region_comuna(
			$data['shipping_state'] ?? '',
			$data['shipping_city'] ?? '',
			__( 'envío', 'bgmg-chile' )
		);
		if ( $err ) {
			$errors->add( 'bgmg_chile_shipping_region_comuna', $err );
		}
	}
}

/**
 * Lógica reutilizable: valida que la región exista y que la comuna pertenezca
 * a esa región. Devuelve string con el mensaje de error o '' si todo ok.
 *
 * @param string $region_code
 * @param string $comuna_slug
 * @param string $contexto Texto humano: "facturación" / "envío".
 * @return string
 */
function bgmg_chile_validar_par_region_comuna( $region_code, $comuna_slug, $contexto ) {

	$regiones = bgmg_chile_get_regiones();

	if ( '' === $region_code || ! isset( $regiones[ $region_code ] ) ) {
		return sprintf(
			/* translators: %s: facturación o envío */
			__( 'Por favor selecciona una región de Chile válida para la dirección de %s.', 'bgmg-chile' ),
			$contexto
		);
	}

	if ( '' === $comuna_slug ) {
		return sprintf(
			/* translators: %s: facturación o envío */
			__( 'Por favor selecciona una comuna para la dirección de %s.', 'bgmg-chile' ),
			$contexto
		);
	}

	if ( ! bgmg_chile_comuna_pertenece_a_region( $comuna_slug, $region_code ) ) {
		return sprintf(
			/* translators: %s: facturación o envío */
			__( 'La comuna seleccionada no pertenece a la región elegida (dirección de %s).', 'bgmg-chile' ),
			$contexto
		);
	}

	return '';
}

/**
 * Validación en "Mi cuenta → Editar dirección".
 * WC dispara woocommerce_after_save_address_validation con $errors.
 */
add_action( 'woocommerce_after_save_address_validation', 'bgmg_chile_validate_account_address', 10, 3 );

function bgmg_chile_validate_account_address( $user_id, $load_address, $address ) {

	if ( ! is_array( $address ) ) {
		return;
	}
	$country_key = $load_address . '_country';
	if ( ! isset( $address[ $country_key ] ) || 'CL' !== $address[ $country_key ] ) {
		return;
	}

	$err = bgmg_chile_validar_par_region_comuna(
		$address[ $load_address . '_state' ] ?? '',
		$address[ $load_address . '_city' ] ?? '',
		$load_address // billing | shipping
	);
	if ( $err ) {
		wc_add_notice( $err, 'error' );
	}
}
