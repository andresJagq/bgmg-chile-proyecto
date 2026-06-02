<?php
/**
 * Localización del formato de dirección de WooCommerce para Chile.
 *
 * En Chile el orden estándar para mostrar una dirección postal es:
 *
 *   Nombre Apellido
 *   Empresa (si aplica)
 *   Calle 123, Depto 45
 *   Comuna
 *   Región
 *   Chile
 *
 * Esto es lo que reflejamos en el formato. También nos aseguramos de que,
 * al guardar una dirección, la comuna se persista como slug en `city` pero
 * en la visualización aparezca el nombre legible (con tildes).
 *
 * IMPORTANTE: order-display.php ya añade la línea `{bgmg_rut_line}` al final
 * del formato CL. Aquí solo armamos el resto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reemplaza el slug guardado en `city` por el nombre legible al renderizar.
 * Tanto en la orden como en "Mis direcciones".
 */
add_filter( 'woocommerce_formatted_address_replacements', 'bgmg_chile_format_replace_city', 20, 2 );

function bgmg_chile_format_replace_city( $replacements, $args ) {

	if ( ! isset( $args['country'] ) || 'CL' !== $args['country'] ) {
		return $replacements;
	}

	if ( isset( $args['city'] ) && '' !== $args['city'] ) {
		$nombre = bgmg_chile_get_comuna_nombre( $args['city'] );
		if ( '' !== $nombre ) {
			$replacements['{city}']           = $nombre;
			$replacements['{city_upper}']     = mb_strtoupper( $nombre );
		}
	}

	// Idem para el state si por alguna razón llegó como código sin traducir.
	if ( isset( $args['state'] ) && '' !== $args['state'] ) {
		$regiones = bgmg_chile_get_regiones();
		if ( isset( $regiones[ $args['state'] ] ) ) {
			$replacements['{state}']       = $regiones[ $args['state'] ];
			$replacements['{state_upper}'] = mb_strtoupper( $regiones[ $args['state'] ] );
		}
	}

	return $replacements;
}

/**
 * Etiqueta humana de la comuna también en el editor de orden de admin.
 * (Esa pantalla a veces muestra el slug crudo si no interceptamos.)
 */
add_filter( 'woocommerce_admin_billing_fields', 'bgmg_chile_admin_billing_fields_label' );
add_filter( 'woocommerce_admin_shipping_fields', 'bgmg_chile_admin_shipping_fields_label' );

function bgmg_chile_admin_billing_fields_label( $fields ) {
	if ( isset( $fields['city'] ) ) {
		$fields['city']['label'] = __( 'Comuna', 'bgmg-chile' );
	}
	if ( isset( $fields['state'] ) ) {
		$fields['state']['label'] = __( 'Región', 'bgmg-chile' );
	}
	return $fields;
}

function bgmg_chile_admin_shipping_fields_label( $fields ) {
	if ( isset( $fields['city'] ) ) {
		$fields['city']['label'] = __( 'Comuna', 'bgmg-chile' );
	}
	if ( isset( $fields['state'] ) ) {
		$fields['state']['label'] = __( 'Región', 'bgmg-chile' );
	}
	return $fields;
}
