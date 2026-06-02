<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * AJAX: Modo manual (cliente arma)
 *
 * Recibe product_id + cantidades por variación: { vid => qty }.
 * Valida la regla de surtido equilibrado (delegada a bgm_evaluar_distribucion).
 * Crea items en el carrito marcados con cart_item_data ['bgm_origen' =>
 * 'manual'] para distinguirlos en avisos visuales (no afecta el precio).
 * =========================================================
 */

add_action( 'wp_ajax_bgm_agregar_manual',        'bgm_ajax_agregar_manual' );
add_action( 'wp_ajax_nopriv_bgm_agregar_manual', 'bgm_ajax_agregar_manual' );

function bgm_ajax_agregar_manual() {
    if ( ! check_ajax_referer( 'bgm_manual', 'nonce', false ) ) {
        bgm_log_error( 'manual', 'Nonce inválido' );
        bgm_ajax_responder_error( 'manual', __( 'Solicitud no válida.', 'beautygirlmg-mayorista' ), 403 );
    }

    if ( bgm_rate_limit_exceeded( 'manual', 30 ) ) {
        bgm_ajax_responder_error( 'manual', __( 'Demasiadas solicitudes. Espera unos segundos.', 'beautygirlmg-mayorista' ), 429 );
    }

    $product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $cantidades  = isset( $_POST['cantidades'] ) ? (array) $_POST['cantidades']   : [];

    if ( ! $product_id || empty( $cantidades ) ) {
        bgm_ajax_responder_error( 'manual', __( 'Datos incompletos.', 'beautygirlmg-mayorista' ) );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        bgm_ajax_responder_error( 'manual', __( 'Producto no válido.', 'beautygirlmg-mayorista' ) );
    }

    if ( ! bgm_variable_tiene_mayorista( $product ) ) {
        bgm_ajax_responder_error( 'manual', __( 'Este producto no tiene precio mayorista configurado.', 'beautygirlmg-mayorista' ) );
    }

    // Sanitizar cantidades: [vid => qty positiva]
    $cantidades_limpias = [];
    foreach ( $cantidades as $vid => $qty ) {
        $vid = absint( $vid );
        $qty = is_scalar( $qty ) ? absint( $qty ) : 0;
        if ( $vid > 0 && $qty > 0 ) {
            $cantidades_limpias[ $vid ] = $qty;
        }
    }

    if ( empty( $cantidades_limpias ) ) {
        bgm_ajax_responder_error( 'manual', __( 'Selecciona al menos una variación.', 'beautygirlmg-mayorista' ) );
    }

    // Validar cantidad mínima total
    $info       = bgm_resumen_mayorista_variable( $product );
    $min_1      = $info['min_1'];
    $qty_total  = array_sum( $cantidades_limpias );

    if ( $qty_total < $min_1 ) {
        bgm_ajax_responder_error( 'manual', sprintf(
            __( 'Mínimo %d unidades en total para surtido mayorista.', 'beautygirlmg-mayorista' ),
            $min_1
        ) );
    }

    // Validar regla de surtido equilibrado (tolerancia + variaciones requeridas)
    $evaluacion = bgm_evaluar_distribucion( $product_id, $cantidades_limpias );
    if ( is_wp_error( $evaluacion ) ) {
        bgm_log_warning( 'manual', 'No cumple regla de surtido', [
            'product_id' => $product_id,
            'razon'      => $evaluacion->get_error_code(),
            'cantidades' => $cantidades_limpias,
        ] );
        bgm_ajax_responder_error( 'manual', $evaluacion->get_error_message() );
    }

    // Validar stock por variación
    foreach ( $cantidades_limpias as $vid => $qty ) {
        $variacion = wc_get_product( $vid );
        if ( ! $variacion ) {
            bgm_ajax_responder_error( 'manual', sprintf( __( 'Variación %d no encontrada.', 'beautygirlmg-mayorista' ), $vid ) );
        }

        if ( $variacion->managing_stock() ) {
            $stock = (int) $variacion->get_stock_quantity();
            if ( $qty > $stock ) {
                bgm_ajax_responder_error( 'manual', sprintf(
                    __( 'Stock insuficiente para %s (disponible: %d).', 'beautygirlmg-mayorista' ),
                    $variacion->get_name(),
                    $stock
                ) );
            }
        }
    }

    // Agregar al carrito SIN metadata custom → WC fusiona items duplicados
    $agregados = 0;
    $errores   = [];

    foreach ( $cantidades_limpias as $vid => $qty ) {
        $variacion = wc_get_product( $vid );
        if ( ! $variacion ) continue;

        $cart_key = WC()->cart->add_to_cart(
            $product_id,
            $qty,
            $vid,
            $variacion->get_variation_attributes(),
            [ 'bgm_origen' => 'manual' ]
        );

        if ( $cart_key ) {
            $agregados++;
        } else {
            $errores[] = "vid:{$vid} qty:{$qty}";
        }
    }

    if ( $agregados === 0 ) {
        bgm_log_error( 'manual', 'Ningún item agregado', [ 'errores' => $errores ] );
        bgm_ajax_responder_error( 'manual', __( 'No se pudo agregar al carrito.', 'beautygirlmg-mayorista' ) );
    }

    WC()->cart->calculate_totals();

    bgm_log( 'manual', 'Surtido manual agregado', [
        'product_id' => $product_id,
        'qty_total'  => $qty_total,
        'cantidades' => $cantidades_limpias,
    ] );

    bgm_ajax_responder_exito( 'manual', [
        'message'    => __( 'Surtido agregado al carrito.', 'beautygirlmg-mayorista' ),
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_url'   => wc_get_cart_url(),
        'qty_total'  => $qty_total,
    ] );
}
