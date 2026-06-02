<?php
/**
 * Reemplaza el listado de "states" de Chile en WooCommerce con las 16 regiones
 * oficiales y obliga a Chile como país por defecto.
 *
 * WooCommerce ya trae un set para Chile pero no siempre está actualizado y
 * usa nombres distintos a los oficiales. Sobrescribir es seguro: cualquier
 * plugin/tema que lea WC()->countries->get_states('CL') verá esta versión.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Override total del array de regiones para Chile.
 * Clave = código ISO 3166-2 (sin el prefijo "CL-").
 */
add_filter( 'woocommerce_states', 'bgmg_chile_override_states', 20 );

function bgmg_chile_override_states( $states ) {
	$states['CL'] = bgmg_chile_get_regiones();
	return $states;
}

/**
 * Fija Chile como país por defecto del cliente nuevo (si la tienda solo vende
 * en Chile). No fuerza si el usuario logueado ya tiene otro país guardado.
 */
add_filter( 'default_checkout_billing_country', 'bgmg_chile_default_country' );
add_filter( 'default_checkout_shipping_country', 'bgmg_chile_default_country' );

function bgmg_chile_default_country( $country ) {
	if ( empty( $country ) ) {
		return 'CL';
	}
	return $country;
}

/**
 * Asegura que Chile esté en la lista de países a los que se vende, sin tocar
 * el resto. Si la dueña limita "vender solo a estos países" en wp-admin, este
 * filtro no la pasa por encima: solo añade CL si está habilitado globalmente.
 */
add_filter( 'woocommerce_countries_allowed_countries', 'bgmg_chile_ensure_country_in_allowed' );

function bgmg_chile_ensure_country_in_allowed( $countries ) {
	if ( empty( $countries ) ) {
		return $countries; // si está vacío significa "ningún país", respetamos.
	}
	if ( ! isset( $countries['CL'] ) ) {
		// Solo añadimos si el config global permite vender a Chile.
		$todos = WC()->countries->get_countries();
		if ( isset( $todos['CL'] ) ) {
			$countries['CL'] = $todos['CL'];
		}
	}
	return $countries;
}

/**
 * Oculta visualmente el campo "País / Región" (billing_country / shipping_country)
 * tanto en checkout como en "Mi cuenta → Editar dirección".
 *
 * Estrategia: agregamos la clase `bgmg-chile-country-hidden` al field y el
 * CSS de frontend.css esconde el `<p class="form-row ...">` completo. El valor
 * sigue viajando al backend (es "CL" por defecto, gracias a los filtros
 * default_checkout_*_country de arriba), así que WC sigue calculando envío,
 * impuestos y validación normalmente.
 *
 * Si algún día la dueña vende a otros países, basta con quitar este filtro
 * o el CSS para que el selector vuelva a verse.
 */
add_filter( 'woocommerce_billing_fields', 'bgmg_chile_hide_country_field_billing', 30 );
add_filter( 'woocommerce_shipping_fields', 'bgmg_chile_hide_country_field_shipping', 30 );

function bgmg_chile_hide_country_field_billing( $fields ) {
	if ( isset( $fields['billing_country'] ) ) {
		$fields['billing_country']['class'] = array_merge(
			isset( $fields['billing_country']['class'] ) ? (array) $fields['billing_country']['class'] : array(),
			array( 'bgmg-chile-country-hidden' )
		);
		// Aseguramos que el default sea CL incluso si llegamos por edición de
		// dirección y el customer aún no tiene país guardado.
		if ( empty( $fields['billing_country']['default'] ) ) {
			$fields['billing_country']['default'] = 'CL';
		}
	}
	return $fields;
}

function bgmg_chile_hide_country_field_shipping( $fields ) {
	if ( isset( $fields['shipping_country'] ) ) {
		$fields['shipping_country']['class'] = array_merge(
			isset( $fields['shipping_country']['class'] ) ? (array) $fields['shipping_country']['class'] : array(),
			array( 'bgmg-chile-country-hidden' )
		);
		if ( empty( $fields['shipping_country']['default'] ) ) {
			$fields['shipping_country']['default'] = 'CL';
		}
	}
	return $fields;
}
