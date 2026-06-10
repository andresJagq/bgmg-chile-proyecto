<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * AJAX: Modo automático ("Sorpréndeme")
 *
 * Recibe product_id + qty.
 * Calcula distribución equitativa entre variaciones.
 * Crea N items en el carrito, uno por cada variación elegida, marcados
 * con cart_item_data ['bgm_origen' => 'surtido'] para distinguirlos del
 * detalle en avisos visuales (no afecta el precio). Mismo valor que el
 * modo manual → WC fusiona la misma variación entre ambos caminos.
 * =========================================================
 */

add_action( 'wp_ajax_bgm_agregar_auto',        'bgm_ajax_agregar_auto' );
add_action( 'wp_ajax_nopriv_bgm_agregar_auto', 'bgm_ajax_agregar_auto' );

function bgm_ajax_agregar_auto() {
    if ( ! check_ajax_referer( 'bgm_auto', 'nonce', false ) ) {
        bgm_log_error( 'auto', 'Nonce inválido' );
        bgm_ajax_responder_error( 'auto', __( 'Solicitud no válida.', 'beautygirlmg-mayorista' ), 403 );
    }

    if ( bgm_rate_limit_exceeded( 'auto', 30 ) ) {
        bgm_ajax_responder_error( 'auto', __( 'Demasiadas solicitudes. Espera unos segundos.', 'beautygirlmg-mayorista' ), 429 );
    }

    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $qty        = isset( $_POST['qty'] )        ? absint( $_POST['qty'] )        : 0;

    if ( ! $product_id || $qty <= 0 ) {
        bgm_ajax_responder_error( 'auto', __( 'Producto o cantidad inválidos.', 'beautygirlmg-mayorista' ) );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        bgm_ajax_responder_error( 'auto', __( 'Producto no válido para surtido.', 'beautygirlmg-mayorista' ) );
    }

    if ( ! bgm_variable_tiene_mayorista( $product ) ) {
        bgm_ajax_responder_error( 'auto', __( 'Este producto no tiene precio mayorista configurado.', 'beautygirlmg-mayorista' ) );
    }

    // Validar cantidad mínima del tier 1
    $info  = bgm_resumen_mayorista_variable( $product );
    $min_1 = $info['min_1'];
    if ( $qty < $min_1 ) {
        bgm_ajax_responder_error( 'auto', sprintf(
            __( 'Mínimo %d unidades para surtido mayorista.', 'beautygirlmg-mayorista' ),
            $min_1
        ) );
    }

    // Calcular distribución
    $caps = bgm_capacidades_variaciones( $product );
    if ( empty( $caps ) ) {
        bgm_ajax_responder_error( 'auto', __( 'Sin variaciones con stock disponible.', 'beautygirlmg-mayorista' ) );
    }

    $distribucion = bgm_distribucion_auto( $caps, $qty );

    if ( is_wp_error( $distribucion ) ) {
        bgm_log_warning( 'auto', 'Distribución falló', [
            'product_id' => $product_id,
            'qty'        => $qty,
            'error'      => $distribucion->get_error_code(),
        ] );
        bgm_ajax_responder_error( 'auto', $distribucion->get_error_message() );
    }

    // Flag de surtido UNIFICADO ('surtido' en auto y manual, v2.7.6): con el
    // mismo cart_item_data, WC fusiona la misma variación venga del camino que
    // venga (antes 'auto' vs 'manual' generaban 2 líneas duplicadas por
    // variación en la misma orden). Nadie lee el valor — solo presencia.
    $agregados = 0;
    $errores   = [];

    foreach ( $distribucion as $vid => $qty_var ) {
        $variacion = wc_get_product( $vid );
        if ( ! $variacion ) {
            $errores[] = "vid:{$vid} no encontrado";
            continue;
        }

        $cart_key = WC()->cart->add_to_cart(
            $product_id,
            $qty_var,
            $vid,
            $variacion->get_variation_attributes(),
            [ 'bgm_origen' => 'surtido' ]
        );

        if ( $cart_key ) {
            $agregados++;
        } else {
            $errores[] = "vid:{$vid} qty:{$qty_var} no se agregó";
        }
    }

    if ( $agregados === 0 ) {
        bgm_log_error( 'auto', 'Ningún item agregado', [ 'errores' => $errores ] );
        bgm_ajax_responder_error( 'auto', __( 'No se pudo agregar al carrito.', 'beautygirlmg-mayorista' ) );
    }

    WC()->cart->calculate_totals();

    bgm_log( 'auto', 'Surtido agregado', [
        'product_id'   => $product_id,
        'qty'          => $qty,
        'distribucion' => $distribucion,
    ] );

    bgm_ajax_responder_exito( 'auto', [
        'message'      => __( 'Surtido agregado al carrito.', 'beautygirlmg-mayorista' ),
        'cart_count'   => WC()->cart->get_cart_contents_count(),
        'cart_url'     => wc_get_cart_url(),
        'distribucion' => $distribucion,
    ] );
}
