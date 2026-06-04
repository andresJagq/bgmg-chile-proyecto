<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: ETIQUETA DE OFERTA (badge de precio rebajado)
 *
 * "Oferta" se refiere a la oferta NATIVA de WooCommerce: el producto tiene
 * precio regular + precio rebajado (is_on_sale()). NO es la promo minorista
 * (eso vive en promo.php y se muestra con bgm_promo_badge_html()).
 *
 * Este módulo solo expone DATOS (texto configurable + % de descuento). El
 * MARKUP/CSS del badge lo arma el tema (bgmg-landing), que llama a estos
 * helpers con function_exists() y cae a "Oferta" si el plugin se desactiva.
 *
 * Config: WC → Ajustes → Mayorista → "Etiqueta de oferta" (bgm_oferta_etiqueta).
 * Ver CONTRATO-PLUGIN-TEMA.md §2.
 * =========================================================
 */

// ─── Texto del badge de oferta (configurable; default "Oferta") ──────────────
function bgm_get_oferta_etiqueta() {
    $val = trim( (string) bgm_get_setting( 'bgm_oferta_etiqueta', 'Oferta' ) );
    return $val !== '' ? $val : 'Oferta';
}

// ─── % de descuento de la oferta nativa de WC para un producto ───────────────
//
// Devuelve un entero (ej. 17 para -17%) o 0 si el producto no está en oferta o
// no se puede calcular. Soporta simple, variación y variable (en variable toma
// el MAYOR % entre sus variaciones en oferta). Cache estático por request.
function bgm_get_oferta_descuento_pct( $product ) {
    static $cache = [];

    if ( ! bgm_es_producto_valido( $product ) ) return 0;
    if ( ! method_exists( $product, 'is_on_sale' ) || ! $product->is_on_sale() ) return 0;

    $pid = $product->get_id();
    if ( isset( $cache[ $pid ] ) ) return $cache[ $pid ];

    // Simple / variación: cálculo directo (precio regular vs precio activo).
    $regular = (float) $product->get_regular_price();
    $activo  = (float) $product->get_price();
    if ( $regular > 0 && $activo > 0 && $activo < $regular ) {
        return $cache[ $pid ] = (int) round( ( $regular - $activo ) / $regular * 100 );
    }

    // Variable: el precio es un rango → mayor % entre variaciones en oferta.
    if ( $product->is_type( 'variable' ) ) {
        $max = 0;
        foreach ( $product->get_children() as $vid ) {
            $v = wc_get_product( $vid );
            if ( ! $v || ! $v->is_on_sale() ) continue;
            $vr = (float) $v->get_regular_price();
            $va = (float) $v->get_price();
            if ( $vr > 0 && $va > 0 && $va < $vr ) {
                $p = (int) round( ( $vr - $va ) / $vr * 100 );
                if ( $p > $max ) $max = $p;
            }
        }
        return $cache[ $pid ] = $max;
    }

    return $cache[ $pid ] = 0;
}
