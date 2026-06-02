<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO FRONTEND: SWATCHES DE VARIACIONES (PILLS)
 *
 * Reemplaza el <select> nativo de WooCommerce por botones tipo
 * píldora (pills) para cada término del atributo, en productos
 * variables que tengan el meta _bgm_usar_swatches activo.
 *
 * El <select> original queda oculto y sincronizado para que el
 * form de WC siga funcionando sin tocar su lógica.
 *
 * Estado disabled: cuando WC marca un option como no disponible
 * (por stock o combinación), el pill correspondiente se ve tachado.
 * =========================================================
 */

add_action( 'wp_enqueue_scripts', 'bgm_swatches_enqueue' );
function bgm_swatches_enqueue() {
    if ( ! is_product() ) return;

    global $product;
    if ( ! $product ) $product = wc_get_product( get_queried_object_id() );
    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'variable' ) ) return;

    if ( ! bgm_usar_swatches( $product->get_id() ) ) return;

    wp_enqueue_script(
        'bgm-frontend-swatches',
        BGM_URL . 'assets/frontend-swatches.js',
        [ 'jquery' ],
        BGM_VERSION,
        true
    );
}
