<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * AJAX: Evaluación de surtido en vivo
 *
 * Endpoint que centraliza la lógica de evaluación del surtido
 * manual para que el JS (modo Manual) no duplique las reglas.
 *
 * Input:
 *   - product_id (int)        producto padre variable
 *   - cantidades (assoc)      [vid => qty]
 *
 * Output:
 *   {
 *     califica:    bool       cumple regla de surtido
 *     razon:       string     'ok' | 'pocas_variaciones' | 'faltan_variaciones' | 'diferencia_excede' | 'sin_seleccion'
 *     estado:      string     'detalle' | 'tier1' | 'tier2' | 'no-cumple'
 *     tier:        int        0, 1 o 2
 *     qty_total:   int
 *     precio_unit: float      precio aplicado según tier
 *     subtotal:    float      qty_total × precio_unit
 *     mensaje:     string     línea humana para el contador del bloque
 *     faltan:      int[]      vids para resaltar en verde ("agregar más aquí")
 *     sobran:      int[]      vids para resaltar en rojo ("reducir aquí")
 *   }
 *
 * Usa exactamente bgm_evaluar_distribucion() del plugin —
 * cualquier cambio a la regla queda en un solo sitio (PHP).
 * =========================================================
 */

add_action( 'wp_ajax_bgm_evaluar_surtido',        'bgm_ajax_evaluar_surtido' );
add_action( 'wp_ajax_nopriv_bgm_evaluar_surtido', 'bgm_ajax_evaluar_surtido' );

function bgm_ajax_evaluar_surtido() {
    if ( ! check_ajax_referer( 'bgm_manual', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Solicitud no válida.', 'beautygirlmg-mayorista' ) ], 403 );
    }

    if ( bgm_rate_limit_exceeded( 'evaluar', 120 ) ) {
        wp_send_json_error( [ 'message' => __( 'Demasiadas solicitudes. Espera unos segundos.', 'beautygirlmg-mayorista' ) ], 429 );
    }

    $product_id     = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $cantidades_raw = isset( $_POST['cantidades'] ) ? $_POST['cantidades'] : [];

    if ( ! $product_id ) {
        wp_send_json_error( [ 'message' => __( 'Producto inválido.', 'beautygirlmg-mayorista' ) ] );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        wp_send_json_error( [ 'message' => __( 'Producto no válido.', 'beautygirlmg-mayorista' ) ] );
    }

    if ( ! is_array( $cantidades_raw ) ) {
        $cantidades_raw = [];
    }

    // Sanitizar [vid => qty]
    $cantidades = [];
    foreach ( $cantidades_raw as $vid => $qty ) {
        $vid = absint( $vid );
        $qty = is_scalar( $qty ) ? max( 0, absint( $qty ) ) : 0;
        if ( $vid > 0 && $qty > 0 ) {
            $cantidades[ $vid ] = $qty;
        }
    }

    $stocks        = bgm_capacidades_variaciones( $product );
    $precio_base   = bgm_get_precio_base( $product );
    $min_1         = bgm_get_min_1( $product_id );
    $min_2         = bgm_get_min_2( $product_id );
    $desc_1        = bgm_get_descuento_1( $product_id );
    $desc_2        = bgm_get_descuento_2( $product_id );
    $tier1_disp    = $desc_1 > 0;
    $tier2_disp    = $desc_2 > 0;
    $n_disponibles = bgm_contar_variaciones_disponibles( $product );

    $qty_total = array_sum( $cantidades );

    // ─── Caso vacío ─────────────────────────────────────────────
    if ( $qty_total === 0 ) {
        wp_send_json_success( [
            'califica'    => false,
            'razon'       => 'sin_seleccion',
            'estado'      => 'detalle',
            'tier'        => 0,
            'qty_total'   => 0,
            'precio_unit' => $precio_base,
            'subtotal'    => 0,
            'mensaje'     => __( 'Selecciona variaciones para empezar', 'beautygirlmg-mayorista' ),
            'faltan'      => [],
            'sobran'      => [],
        ] );
    }

    // ─── Evaluar regla (centralizada) ───────────────────────────
    $evaluacion = bgm_evaluar_distribucion( $product_id, $cantidades, $n_disponibles, $stocks );
    $califica   = ! is_wp_error( $evaluacion );
    $razon      = $califica ? 'ok' : $evaluacion->get_error_code();

    // ─── Tier aplicado ──────────────────────────────────────────
    $tier        = 0;
    $precio_unit = $precio_base;
    if ( $califica ) {
        if ( $tier2_disp && $qty_total >= $min_2 ) {
            $tier        = 2;
            $precio_unit = max( 0, $precio_base - $desc_2 );
        } elseif ( $tier1_disp && $qty_total >= $min_1 ) {
            $tier        = 1;
            $precio_unit = max( 0, $precio_base - $desc_1 );
        }
    }

    // ─── Filas a resaltar (sobran/faltan) según el caso ─────────
    $faltan = [];
    $sobran = [];

    if ( ! $califica ) {
        if ( $razon === 'diferencia_excede' ) {
            // Identificar variaciones no atrancadas para sugerir reducir/aumentar
            $no_atrancadas = [];
            foreach ( $cantidades as $vid => $qty ) {
                $stock_max = isset( $stocks[ $vid ] ) ? (int) $stocks[ $vid ] : PHP_INT_MAX;
                if ( $stock_max <= 0 || $qty < $stock_max ) {
                    $no_atrancadas[ $vid ] = $qty;
                }
            }
            if ( count( $no_atrancadas ) >= 2 ) {
                $max_q = max( $no_atrancadas );
                $min_q = min( $no_atrancadas );
                foreach ( $no_atrancadas as $vid => $qty ) {
                    if ( $qty === $max_q ) $sobran[] = $vid;
                    elseif ( $qty === $min_q ) $faltan[] = $vid;
                }
            }
        } elseif ( $razon === 'pocas_variaciones' || $razon === 'faltan_variaciones' ) {
            // Sugerir variaciones disponibles que no están elegidas
            foreach ( $product->get_children() as $vid ) {
                if ( ! isset( $cantidades[ $vid ] ) ) {
                    $variation = wc_get_product( $vid );
                    if ( $variation && $variation->is_purchasable()
                        && ! ( $variation->managing_stock() && $variation->get_stock_status() === 'outofstock' ) ) {
                        $faltan[] = (int) $vid;
                    }
                }
            }
        }
    }

    // ─── Mensaje humano para el contador ────────────────────────
    $mensaje = bgm_evaluar_construir_mensaje(
        $qty_total, $tier, $califica, $razon, $evaluacion,
        $min_1, $min_2, $tier1_disp, $tier2_disp
    );

    $estado = $tier === 2 ? 'tier2' : ( $tier === 1 ? 'tier1' : ( $califica ? 'detalle' : 'no-cumple' ) );

    wp_send_json_success( [
        'califica'    => $califica,
        'razon'       => $razon,
        'estado'      => $estado,
        'tier'        => $tier,
        'qty_total'   => $qty_total,
        'precio_unit' => $precio_unit,
        'subtotal'    => $qty_total * $precio_unit,
        'mensaje'     => $mensaje,
        'faltan'      => $faltan,
        'sobran'      => $sobran,
    ] );
}

/**
 * Genera el mensaje humano para mostrar en el contador en vivo.
 */
function bgm_evaluar_construir_mensaje( $qty_total, $tier, $califica, $razon, $evaluacion, $min_1, $min_2, $tier1_disp, $tier2_disp ) {
    if ( $qty_total === 0 ) {
        return __( 'Selecciona variaciones para empezar', 'beautygirlmg-mayorista' );
    }

    if ( $tier === 2 ) {
        return sprintf(
            __( 'Llevas %d ud. · Mayorista 2 ✓✓ — surtido equilibrado', 'beautygirlmg-mayorista' ),
            $qty_total
        );
    }

    if ( $tier === 1 ) {
        $msg = sprintf(
            __( 'Llevas %d ud. · Mayorista 1 ✓ — surtido equilibrado', 'beautygirlmg-mayorista' ),
            $qty_total
        );
        if ( $tier2_disp && $qty_total < $min_2 ) {
            $msg .= sprintf(
                __( ' · Agrega %d más para Mayorista 2', 'beautygirlmg-mayorista' ),
                $min_2 - $qty_total
            );
        }
        return $msg;
    }

    if ( $califica ) {
        // Pasó la regla pero qty < min_1 (no llega a tier)
        $msg = sprintf( __( 'Llevas %d ud. · Precio detalle', 'beautygirlmg-mayorista' ), $qty_total );
        if ( $tier1_disp && $qty_total < $min_1 ) {
            $msg .= sprintf(
                __( ' · Faltan %d para Mayorista 1', 'beautygirlmg-mayorista' ),
                $min_1 - $qty_total
            );
        }
        return $msg;
    }

    // No califica → mensaje del WP_Error
    return sprintf(
        __( 'Llevas %d ud. · %s', 'beautygirlmg-mayorista' ),
        $qty_total,
        $evaluacion->get_error_message()
    );
}
