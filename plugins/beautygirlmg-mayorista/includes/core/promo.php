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
 * Aplica a productos SIMPLES y VARIABLES (la precedencia vive en carrito.php).
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

// ─── Modo de la promo por producto: '' (heredar) | 'custom' | 'excluir' ──────
function bgm_get_promo_modo( $product_id ) {
    $val = get_post_meta( (int) $product_id, '_bgm_promo_modo', true );
    return ( $val === 'custom' || $val === 'excluir' ) ? $val : '';
}

// ─── Valor del descuento promo para un producto (per-product → fallback global)
//
// Solo el modo 'custom' usa valor propio; en cualquier otro caso, el global.
function bgm_get_promo_valor( $product_id ) {
    if ( bgm_get_promo_modo( $product_id ) === 'custom' ) {
        $val = get_post_meta( (int) $product_id, '_bgm_promo_valor', true );
        if ( $val !== '' && is_numeric( $val ) && (float) $val > 0 ) {
            return (float) $val;
        }
    }
    return (float) bgm_get_setting( 'bgm_promo_valor', 0 );
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
// Precedencia: el modo por producto MANDA sobre lo global.
//   excluir → nunca · custom → siempre · '' (heredar) → según categorías.
// Cache estático por request: el carrito recalcula varias veces por request.
function bgm_producto_en_promo( $product_id ) {
    static $cache = [];

    $product_id = (int) $product_id;
    if ( $product_id <= 0 ) return false;
    if ( isset( $cache[ $product_id ] ) ) return $cache[ $product_id ];

    $modo = bgm_get_promo_modo( $product_id );

    if ( $modo === 'excluir' ) return $cache[ $product_id ] = false;
    if ( $modo === 'custom' )  return $cache[ $product_id ] = true;

    // Heredar: participa si pertenece a alguna categoría en promo.
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
    $qmax = absint( bgm_get_setting( 'bgm_promo_qty_max', 2 ) );
    if ( $qmin > 0 && $qty < $qmin ) return null;
    if ( $qmax > 0 && $qty > $qmax ) return null;

    // Siempre sobre el precio REGULAR (ignora ofertas), igual que el mayorista.
    $base = bgm_get_precio_base( $product );
    if ( $base <= 0 ) return null;

    $tipo  = bgm_get_setting( 'bgm_promo_tipo', 'porcentaje' );
    $valor = bgm_get_promo_valor( $pid ); // per-product (custom) → fallback global
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

// ─── Conteo de productos afectados por la promo (panel de Ajustes) ───────────
//
// total = (productos en categorías ∪ personalizados) − excluidos.
// Cacheado en transient 5 min; se invalida al guardar un producto o los ajustes.
function bgm_promo_contar_afectados() {
    $cache = get_transient( 'bgm_promo_afectados' );
    if ( is_array( $cache ) ) return $cache;

    $cats = bgm_promo_ids_categorias();

    $por_categoria = [];
    if ( ! empty( $cats ) ) {
        $por_categoria = get_posts( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'tax_query'      => [ [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cats,
            ] ],
        ] );
    }

    $base_meta = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    ];
    $custom  = get_posts( $base_meta + [ 'meta_key' => '_bgm_promo_modo', 'meta_value' => 'custom' ] );
    $excluir = get_posts( $base_meta + [ 'meta_key' => '_bgm_promo_modo', 'meta_value' => 'excluir' ] );

    $afectados = array_diff( array_unique( array_merge( $por_categoria, $custom ) ), $excluir );

    $res = [
        'total'           => count( $afectados ),
        'por_categoria'   => count( $por_categoria ),
        'personalizados'  => count( $custom ),
        'excluidos'       => count( $excluir ),
        'cat_con_custom'  => count( array_intersect( $por_categoria, $custom ) ),
        'cat_con_excluir' => count( array_intersect( $por_categoria, $excluir ) ),
    ];

    set_transient( 'bgm_promo_afectados', $res, 5 * MINUTE_IN_SECONDS );
    return $res;
}

// Invalidar el conteo cacheado cuando cambie algo relevante.
add_action( 'save_post_product', 'bgm_promo_invalidar_afectados' );
add_action( 'woocommerce_update_options_bgm_mayorista', 'bgm_promo_invalidar_afectados' );
function bgm_promo_invalidar_afectados() {
    delete_transient( 'bgm_promo_afectados' );
}

// ─── Migración: el tope de la promo pasó a 2 por defecto (antes 0 = sin límite) ─
// Sube de 0 → 2 una sola vez, para instalaciones que ya guardaron el ajuste viejo.
add_action( 'admin_init', 'bgm_promo_migrar_qty_max' );
function bgm_promo_migrar_qty_max() {
    if ( get_option( 'bgm_promo_qty_max_migrado' ) ) return;
    if ( (int) get_option( 'bgm_promo_qty_max', 0 ) === 0 ) {
        update_option( 'bgm_promo_qty_max', 2 );
    }
    update_option( 'bgm_promo_qty_max_migrado', '1' );
}

// ─── Info de promo para mostrar al CLIENTE (precio tachado) ──────────────────
//
// Devuelve los datos de display (precio unitario en promo a la cantidad mínima)
// o null si el producto no está en promo / no aplica. Fuente única de verdad:
// reusa bgm_calcular_precio_promo(). Lo consumen el filtro de price_html (abajo)
// y el tema (Parte B: badge), siempre con function_exists().
function bgm_get_promo_info( $product ) {
    if ( ! bgm_es_producto_valido( $product ) ) return null;
    if ( ! bgm_promo_activa_ahora() )           return null;

    $qmin         = max( 1, absint( bgm_get_setting( 'bgm_promo_qty_min', 1 ) ) );
    $precio_promo = bgm_calcular_precio_promo( $product, $qmin );
    if ( $precio_promo === null ) return null;

    $base = bgm_get_precio_base( $product );
    if ( $base <= 0 ) return null;

    $ahorro = $base - $precio_promo;
    if ( $ahorro <= 0 ) return null;

    return [
        'precio_base'  => (float) $base,
        'precio_promo' => (int) $precio_promo,
        'ahorro'       => (float) $ahorro,
        'pct'          => (int) round( $ahorro / $base * 100 ),
        'qty_min'      => $qmin,
    ];
}

// ─── Mostrar el precio promo (tachado) en tarjetas / producto / relacionados ──
//
// Engancha get_price_html para que el cliente vea «~precio normal~ → precio promo»
// SIN tocar el tema (las plantillas de bgmg-landing ya usan get_price_html).
// Solo SIMPLES: en variables el precio es un rango → eso lo cubre el badge (Parte B).
add_filter( 'woocommerce_get_price_html', 'bgm_promo_price_html', 20, 2 );
function bgm_promo_price_html( $price_html, $product ) {
    if ( is_admin() && ! wp_doing_ajax() )  return $price_html;
    if ( ! $product instanceof WC_Product ) return $price_html;
    if ( ! $product->is_type( 'simple' ) )  return $price_html;

    $info = bgm_get_promo_info( $product );
    if ( ! $info ) return $price_html;

    return wc_format_sale_price( $info['precio_base'], $info['precio_promo'] ) . $product->get_price_suffix();
}

// ─── Badge "Promo −X%" para el CLIENTE (Parte B; lo consume el tema) ──────────
//
// Funciona para SIMPLES y VARIABLES (en variables el % es sobre el precio mínimo).
// El tema lo llama con function_exists() en sus tarjetas y la página de producto.
// El estilo .bgm-promo-badge vive en bgmg-landing (bgmg-global.css, cargado en
// todas las páginas). Ver CONTRATO-PLUGIN-TEMA.md §2 y §7.
function bgm_promo_badge_html( $product ) {
    if ( is_string( $product ) || is_numeric( $product ) ) {
        $product = wc_get_product( $product );
    }

    $info = bgm_get_promo_info( $product );
    if ( ! $info ) return '';

    $tipo  = bgm_get_setting( 'bgm_promo_tipo', 'porcentaje' );
    $label = ( $tipo === 'porcentaje' && $info['pct'] > 0 )
        ? sprintf( '-%d%%', $info['pct'] )
        : __( 'Promo', 'beautygirlmg-mayorista' );

    return '<span class="bgm-promo-badge">' . esc_html( $label ) . '</span>';
}
