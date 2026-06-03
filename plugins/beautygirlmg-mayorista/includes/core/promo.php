<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: DESCUENTO PROMOCIONAL MINORISTA
 *
 * Descuento por ocasión especial (ej. Cyber) para compras AL DETALLE.
 * Es para minoristas: solo aplica cuando el precio mayorista NO entró.
 * La precedencia (mayorista primero, promo solo si mayorista nivel 0) se
 * decide en `includes/frontend/carrito.php` — NO aquí. Son mutuamente
 * excluyentes por construcción: nunca se pisan ni se suman.
 *
 * Este módulo es AISLADO: `bgm_calcular_precio()` (lógica mayorista pura)
 * queda intacta. Config global en wp_options con prefijo `bgm_promo_`.
 *
 * Fase 1: productos SIMPLES. (Fase 2: variables.)
 * =========================================================
 */

// ─── ¿La promo está activa AHORA? (interruptor + ventana de fechas) ──────────
//
// Zona horaria del sitio. Inclusiva en ambos extremos: desde las 00:00 del día
// de inicio hasta las 23:59:59 del día de fin. Se compara a nivel de día con
// strings 'Y-m-d' (orden cronológico = orden lexicográfico), evitando líos de
// timezone con timestamps.
function bgm_promo_activa_ahora() {
    static $cache = null;
    if ( $cache !== null ) return $cache;

    if ( bgm_get_setting( 'bgm_promo_activa', 'no' ) !== 'yes' ) {
        return $cache = false;
    }

    $hoy    = current_time( 'Y-m-d' ); // fecha local del sitio
    $inicio = trim( (string) bgm_get_setting( 'bgm_promo_fecha_inicio', '' ) );
    $fin    = trim( (string) bgm_get_setting( 'bgm_promo_fecha_fin', '' ) );

    if ( $inicio !== '' && $hoy < $inicio ) return $cache = false;
    if ( $fin    !== '' && $hoy > $fin    ) return $cache = false;

    return $cache = true;
}

// ─── IDs de productos en promo (lista manual de IDs) ─────────────────────────
function bgm_promo_ids_productos() {
    static $cache = null;
    if ( $cache !== null ) return $cache;

    $raw = (string) bgm_get_setting( 'bgm_promo_productos', '' );
    $ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $raw ) ) );
    return $cache = array_values( array_unique( $ids ) );
}

// ─── term IDs de categorías en promo ─────────────────────────────────────────
function bgm_promo_ids_categorias() {
    static $cache = null;
    if ( $cache !== null ) return $cache;

    $val = bgm_get_setting( 'bgm_promo_categorias', [] );
    if ( ! is_array( $val ) ) $val = [];
    return $cache = array_values( array_filter( array_map( 'absint', $val ) ) );
}

// ─── ¿Este producto (padre) participa en la promo? ───────────────────────────
//
// Cache estático por request: el carrito recalcula varias veces por request.
function bgm_producto_en_promo( $product_id ) {
    static $cache = [];

    $product_id = (int) $product_id;
    if ( $product_id <= 0 ) return false;
    if ( isset( $cache[ $product_id ] ) ) return $cache[ $product_id ];

    // Por ID directo
    if ( in_array( $product_id, bgm_promo_ids_productos(), true ) ) {
        return $cache[ $product_id ] = true;
    }

    // Por categoría
    $cats_promo = bgm_promo_ids_categorias();
    if ( ! empty( $cats_promo ) && function_exists( 'wc_get_product_term_ids' ) ) {
        $term_ids = wc_get_product_term_ids( $product_id, 'product_cat' );
        if ( ! empty( array_intersect( $cats_promo, $term_ids ) ) ) {
            return $cache[ $product_id ] = true;
        }
    }

    return $cache[ $product_id ] = false;
}

/**
 * Calcula el precio promocional minorista para un producto a una cantidad dada.
 *
 * @param WC_Product $product Producto (simple o variación).
 * @param int        $qty     Cantidad del ítem.
 * @return int|null Precio unitario (entero CLP) o null si la promo NO aplica.
 */
function bgm_calcular_precio_promo( $product, $qty ) {
    if ( ! bgm_es_producto_valido( $product ) ) return null;
    if ( ! bgm_promo_activa_ahora() )           return null;

    $qty = (int) $qty;
    if ( $qty <= 0 ) return null;

    // Para variaciones, la pertenencia a la promo se resuelve por el PADRE.
    $pid = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
    if ( ! bgm_producto_en_promo( $pid ) ) return null;

    // Límites de cantidad opcionales (0 = sin límite).
    $qmin = absint( bgm_get_setting( 'bgm_promo_qty_min', 1 ) );
    $qmax = absint( bgm_get_setting( 'bgm_promo_qty_max', 0 ) );
    if ( $qmin > 0 && $qty < $qmin ) return null;
    if ( $qmax > 0 && $qty > $qmax ) return null;

    // Siempre sobre el precio REGULAR (ignora ofertas), igual que el mayorista.
    $base = bgm_get_precio_base( $product );
    if ( $base <= 0 ) return null;

    $tipo  = bgm_get_setting( 'bgm_promo_tipo', 'porcentaje' );
    $valor = (float) bgm_get_setting( 'bgm_promo_valor', 0 );
    if ( $valor <= 0 ) return null;

    if ( $tipo === 'monto' ) {
        $precio = $base - $valor;
    } else { // porcentaje
        if ( $valor > 100 ) $valor = 100;
        $precio = $base - ( $base * $valor / 100 );
    }

    $precio = (int) max( 0, round( $precio ) );

    // Solo aplica si realmente baja el precio.
    if ( $precio >= (int) round( $base ) ) return null;

    return $precio;
}
