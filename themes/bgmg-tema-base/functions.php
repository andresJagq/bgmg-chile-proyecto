<?php
/**
 * BGMG Base — Theme functions.
 *
 * Solo registramos lo mínimo que WP necesita para considerar el sitio
 * "moderno" (title-tag managed por WP, thumbnails para productos WC, etc.).
 *
 * NO registramos templates ni filtros template_include / template_redirect:
 * el plugin bgmg-landing ya se encarga de servir todo. Cualquier filter
 * acá podría competir con el plugin y romper la integración.
 *
 * @package BGMG_Base
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup mínimo del tema.
 */
function bgmg_base_setup() {

	// WP gestiona el <title> (en lugar de hardcodearlo en index.php).
	add_theme_support( 'title-tag' );

	// Imágenes destacadas (productos WooCommerce las necesitan).
	add_theme_support( 'post-thumbnails' );

	// Logo custom desde Customizer → Identidad del sitio.
	// El header de bgmg-landing ya lee get_theme_mod('custom_logo'); este
	// registro habilita el control de subida en wp-admin.
	add_theme_support( 'custom-logo', array(
		'height'      => 120,
		'width'       => 400,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	// Compatibilidad con WooCommerce sin warnings + galería de producto
	// con zoom/lightbox/slider (lo usa bgmg-landing en single-product).
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// HTML5 semántico para los outputs nativos de WP.
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );

	// Permite que los plugins/WP inyecten <link rel="pingback">,
	// feed links, etc. en el <head>.
	add_theme_support( 'automatic-feed-links' );

	// Embebidos responsivos.
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'bgmg_base_setup' );

/**
 * No registramos ningún enqueue de CSS/JS — todo vive en los plugins.
 *
 * Si en algún momento necesitás un script global del tema (raro), iría acá
 * con wp_enqueue_scripts. Pero el patrón correcto es ponerlo en el plugin
 * que lo necesita.
 */
