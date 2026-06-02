<?php
/**
 * Convierte el campo "city" (comuna) en un <select> dependiente de la región.
 *
 * Estrategia:
 *   - El campo state ya es un select (lo entrega WC con el filtro woocommerce_states).
 *   - El campo city por defecto es un input libre. Lo cambiamos a tipo "select"
 *     y le inyectamos opciones via filter en el checkout.
 *   - En frontend, regiones-comunas.js se encarga de filtrar las opciones de
 *     comuna según la región elegida (cascada en cliente, sin AJAX).
 *
 * Aplica tanto a billing como a shipping.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cambia el tipo del campo city a select y le pone TODAS las comunas como
 * opciones. El filtrado por región lo hace JS en cliente. PHP valida después.
 *
 * Aplica solo si el país del checkout es Chile.
 */
add_filter( 'woocommerce_default_address_fields', 'bgmg_chile_modify_city_field', 20 );

function bgmg_chile_modify_city_field( $fields ) {

	// Solo modificamos si la sesión apunta a CL o si no hay sesión aún.
	if ( ! bgmg_chile_should_apply_to_address() ) {
		return $fields;
	}

	if ( ! isset( $fields['city'] ) ) {
		return $fields;
	}

	$fields['city']['type']        = 'select';
	$fields['city']['label']       = __( 'Comuna', 'bgmg-chile' );
	$fields['city']['placeholder'] = __( 'Selecciona tu comuna', 'bgmg-chile' );
	$fields['city']['required']    = true;
	$fields['city']['class']       = array_merge(
		isset( $fields['city']['class'] ) ? (array) $fields['city']['class'] : array(),
		array( 'bgmg-chile-comuna-field' )
	);
	$fields['city']['options']     = bgmg_chile_build_comunas_options_full();
	// Forzamos que la comuna aparezca DESPUÉS de la región (cascada natural):
	//   address_2 = 60, region = 65, comuna = 75, postcode = 90.
	// Default WC era city=70, state=80; lo invertimos para mejor UX mobile.
	$fields['city']['priority']    = 75;

	// state ya es select por get_states; reforzamos label, placeholder y subimos
	// su priority para que aparezca ANTES que comuna.
	if ( isset( $fields['state'] ) ) {
		$fields['state']['label']       = __( 'Región', 'bgmg-chile' );
		$fields['state']['placeholder'] = __( 'Selecciona tu región', 'bgmg-chile' );
		$fields['state']['priority']    = 65;
		$fields['state']['class']       = array_merge(
			isset( $fields['state']['class'] ) ? (array) $fields['state']['class'] : array(),
			array( 'bgmg-chile-region-field' )
		);
	}

	return $fields;
}

/**
 * ¿Debemos aplicar los cambios de campos de dirección al cliente actual?
 * Sí cuando: no hay país aún (primer render), o el país es CL.
 */
function bgmg_chile_should_apply_to_address() {
	if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
		return true; // antes de que WC bootee, asumimos CL (el plugin es solo CL).
	}
	$billing  = WC()->customer->get_billing_country();
	$shipping = WC()->customer->get_shipping_country();
	if ( '' === $billing && '' === $shipping ) {
		return true;
	}
	return ( 'CL' === $billing ) || ( 'CL' === $shipping );
}

/**
 * Construye el array de opciones para el <select> de comunas, plano.
 * Formato esperado por WC: array( slug => 'Nombre' ).
 *
 * Incluimos TODAS las comunas para que JS pueda filtrar sin AJAX.
 */
function bgmg_chile_build_comunas_options_full() {
	$opciones    = array( '' => __( 'Selecciona tu comuna', 'bgmg-chile' ) );
	$comunas_map = bgmg_chile_get_comunas_flat();
	// Ordenamos alfabéticamente por nombre legible (con tildes).
	asort( $comunas_map, SORT_NATURAL | SORT_FLAG_CASE );
	foreach ( $comunas_map as $slug => $nombre ) {
		$opciones[ $slug ] = $nombre;
	}
	return $opciones;
}
