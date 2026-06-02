<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: HELPERS
 *
 * Funciones compartidas para leer configuración del plugin
 * y calcular precios mayoristas.
 *
 * Convención: meta keys del plugin con prefijo `_bgm_`.
 * Ajustes globales en wp_options con prefijo `bgm_`.
 * =========================================================
 */

// ─── Lectura de ajustes globales ─────────────────────────────────────────────
function bgm_get_setting( $key, $default = '' ) {
    return get_option( $key, $default );
}

function bgm_get_min_global_1() {
    return absint( bgm_get_setting( 'bgm_min_global_1', BGM_DEFAULT_MIN_1 ) );
}

function bgm_get_min_global_2() {
    return absint( bgm_get_setting( 'bgm_min_global_2', BGM_DEFAULT_MIN_2 ) );
}

function bgm_get_modo_surtido() {
    $val = bgm_get_setting( 'bgm_modo_surtido', BGM_DEFAULT_MODO_SURTIDO );
    return in_array( $val, [ 'auto', 'manual', 'ambos' ], true ) ? $val : 'ambos';
}

function bgm_debug_activo() {
    return bgm_get_setting( 'bgm_debug_activo', '0' ) === '1';
}

// ─── Validación de producto ──────────────────────────────────────────────────
function bgm_es_producto_valido( $product ) {
    return $product && is_object( $product ) && method_exists( $product, 'get_id' );
}

// ─── Lectura de configuración por producto/variación ─────────────────────────

/**
 * Modo de descuento para variables: 'unico' o 'individual'.
 * Para simples siempre devuelve 'unico'.
 */
function bgm_get_modo_descuento( $product_id ) {
    $val = get_post_meta( $product_id, '_bgm_modo_descuento', true );
    return ( $val === 'individual' ) ? 'individual' : 'unico';
}

/**
 * Devuelve el ID donde leer la configuración de descuentos.
 * - Producto simple → su propio ID
 * - Variable modo único → ID del padre
 * - Variable modo individual → ID de la variación
 */
function bgm_get_id_para_config( $product_id, $variation_id = 0 ) {
    if ( ! $variation_id ) return $product_id;

    $modo = bgm_get_modo_descuento( $product_id );
    return ( $modo === 'individual' ) ? $variation_id : $product_id;
}

// ─── Lectura de meta con fallback a global ───────────────────────────────────
function bgm_get_min_1( $product_id, $variation_id = 0 ) {
    $config_id = bgm_get_id_para_config( $product_id, $variation_id );
    $val = get_post_meta( $config_id, '_bgm_min_1', true );
    return ( $val === '' ) ? bgm_get_min_global_1() : absint( $val );
}

function bgm_get_min_2( $product_id, $variation_id = 0 ) {
    $config_id = bgm_get_id_para_config( $product_id, $variation_id );
    $val = get_post_meta( $config_id, '_bgm_min_2', true );
    return ( $val === '' ) ? bgm_get_min_global_2() : absint( $val );
}

function bgm_get_descuento_1( $product_id, $variation_id = 0 ) {
    $config_id = bgm_get_id_para_config( $product_id, $variation_id );
    $val = get_post_meta( $config_id, '_bgm_descuento_1', true );
    return ( $val === '' ) ? 0 : floatval( $val );
}

function bgm_get_descuento_2( $product_id, $variation_id = 0 ) {
    $config_id = bgm_get_id_para_config( $product_id, $variation_id );
    $val = get_post_meta( $config_id, '_bgm_descuento_2', true );
    return ( $val === '' ) ? 0 : floatval( $val );
}

/**
 * Tolerancia porcentual de diferencia entre variaciones para mayorista.
 *
 * Jerarquía:
 *   1. Producto tiene valor en _bgm_tolerancia_porcentaje → usar ese
 *   2. Si no, usar el global (bgm_tolerancia_porcentaje)
 *   3. Si no hay global, default 15
 *
 * @return int Porcentaje entre 1-100
 */
function bgm_get_tolerancia_porcentaje( $product_id ) {
    $val = get_post_meta( $product_id, '_bgm_tolerancia_porcentaje', true );
    if ( $val !== '' && is_numeric( $val ) ) return absint( $val );

    $global = absint( bgm_get_setting( 'bgm_tolerancia_porcentaje', defined( 'BGM_DEFAULT_TOLERANCIA' ) ? BGM_DEFAULT_TOLERANCIA : 15 ) );
    return $global > 0 ? $global : 15;
}

/**
 * Indica si el producto debe usar el selector visual (pills) para variaciones.
 *
 * Default: TRUE. El admin puede desactivarlo desde la pestaña Mayorista del
 * editor de producto (checkbox).
 *
 * Valores del meta `_bgm_usar_swatches`:
 *   - '1'  → activo (explícito)
 *   - '0'  → desactivado (explícito)
 *   - ''   → no hay decisión guardada → default TRUE
 */
function bgm_usar_swatches( $product_id ) {
    $val = get_post_meta( $product_id, '_bgm_usar_swatches', true );
    return $val !== '0';
}

/**
 * Convierte el porcentaje de tolerancia en unidades absolutas según el total pedido.
 *
 * Fórmula: max(1, ceil(total * porcentaje / 100))
 *
 * @return int Diferencia máxima permitida en unidades
 */
function bgm_calcular_tolerancia_unidades( $product_id, $total ) {
    $porcentaje = bgm_get_tolerancia_porcentaje( $product_id );
    return max( 1, (int) ceil( $total * $porcentaje / 100 ) );
}

/**
 * Evalúa si una distribución de cantidades por variación cumple la regla de
 * surtido equilibrado para aplicar precio mayorista.
 *
 * Reglas:
 *   1. Si total < n_disponibles: debe elegir `total` variaciones distintas (1 c/u)
 *   2. Si total >= n_disponibles: debe usar TODAS las disponibles
 *   3. La diferencia entre max y min de las cantidades elegidas ≤ tolerancia
 *      Si se pasa $stocks: variaciones "atrancadas en su techo" (qty === stock)
 *      se excluyen del cálculo, porque el cliente no podía agregar más de esa
 *      variación. Si todas están atrancadas, balance OK por defecto.
 *
 * @param int   $product_id    ID del producto padre (variable)
 * @param array $cantidades    [vid => qty] (incluye solo qty > 0)
 * @param int|null $n_disponibles  Si null, se calcula
 * @param array|null $stocks   [vid => stock_max] opcional. Si se provee, las
 *                             variaciones atrancadas (qty === stock_max) se
 *                             excluyen del cálculo de tolerancia.
 * @return true|WP_Error
 */
function bgm_evaluar_distribucion( $product_id, $cantidades, $n_disponibles = null, $stocks = null ) {
    $cantidades = array_filter( $cantidades, function( $q ) { return $q > 0; } );
    $n_elegidas = count( $cantidades );
    $total      = array_sum( $cantidades );

    if ( $total <= 0 || $n_elegidas === 0 ) {
        return new WP_Error( 'sin_cantidades', __( 'Selecciona al menos una variación.', 'beautygirlmg-mayorista' ) );
    }

    if ( $n_disponibles === null ) {
        $product = wc_get_product( $product_id );
        $n_disponibles = bgm_contar_variaciones_disponibles( $product );
    }

    // Caso degenerado: solo 1 variación disponible → sin regla de surtido
    if ( $n_disponibles <= 1 ) {
        return true;
    }

    // Regla 1: número de variaciones a usar
    if ( $total < $n_disponibles ) {
        // Cliente pide menos que variaciones: debe usar `total` distintas (1 c/u)
        if ( $n_elegidas < $total ) {
            return new WP_Error(
                'pocas_variaciones',
                sprintf(
                    __( 'Para mayorista debes elegir al menos %d variaciones distintas (1 unidad de cada).', 'beautygirlmg-mayorista' ),
                    $total
                )
            );
        }
    } else {
        // Cliente pide igual o más que variaciones: debe usar TODAS las disponibles
        if ( $n_elegidas < $n_disponibles ) {
            return new WP_Error(
                'faltan_variaciones',
                sprintf(
                    __( 'Para mayorista debes incluir todas las %d variaciones disponibles.', 'beautygirlmg-mayorista' ),
                    $n_disponibles
                )
            );
        }
    }

    // Regla 2: tolerancia de diferencia entre las cantidades elegidas
    if ( $n_elegidas > 1 ) {
        // Si hay info de stocks, excluir variaciones "atrancadas en techo"
        // (qty === stock_max) porque el cliente no podía agregar más de esas.
        $para_evaluar = $cantidades;
        if ( is_array( $stocks ) && ! empty( $stocks ) ) {
            $no_atrancadas = [];
            foreach ( $cantidades as $vid => $qty ) {
                $stock_max = isset( $stocks[ $vid ] ) ? (int) $stocks[ $vid ] : PHP_INT_MAX;
                if ( $stock_max <= 0 || $qty < $stock_max ) {
                    $no_atrancadas[ $vid ] = $qty;
                }
            }
            // Si quedan <2 variaciones no atrancadas, no podemos evaluar tolerancia
            // de manera significativa → balance OK por defecto.
            if ( count( $no_atrancadas ) < 2 ) {
                return true;
            }
            $para_evaluar = $no_atrancadas;
        }

        $max_q = max( $para_evaluar );
        $min_q = min( $para_evaluar );
        $diff  = $max_q - $min_q;

        $tolerancia = bgm_calcular_tolerancia_unidades( $product_id, $total );

        if ( $diff > $tolerancia ) {
            return new WP_Error(
                'diferencia_excede',
                sprintf(
                    __( 'La diferencia entre variaciones (%d unidades) excede la tolerancia permitida (%d).', 'beautygirlmg-mayorista' ),
                    $diff,
                    $tolerancia
                )
            );
        }
    }

    return true;
}

/**
 * Cuenta cuántas variaciones de un producto variable están disponibles
 * (existen, son comprables, no out_of_stock, tienen stock > 0 si gestionan stock).
 *
 * Cacheado por request: en carrito.php + avisos-carrito.php + ajax-evaluar se
 * llama varias veces por el mismo padre dentro de una sola request.
 */
function bgm_contar_variaciones_disponibles( $product ) {
    if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_children' ) ) return 0;
    if ( ! $product->is_type( 'variable' ) ) return 0;

    static $cache = [];
    $pid = (int) $product->get_id();
    if ( isset( $cache[ $pid ] ) ) return $cache[ $pid ];

    $count = 0;
    foreach ( $product->get_children() as $vid ) {
        $variacion = wc_get_product( $vid );
        if ( ! $variacion || ! $variacion->is_purchasable() ) continue;

        if ( $variacion->managing_stock() ) {
            if ( $variacion->get_stock_status() === 'outofstock' ) continue;
            $stock = (int) $variacion->get_stock_quantity();
            if ( $stock <= 0 ) continue;
        } else {
            if ( $variacion->get_stock_status() === 'outofstock' ) continue;
        }

        $count++;
    }
    return $cache[ $pid ] = $count;
}

/**
 * Helper: capacidades por variación de un producto variable.
 *
 * Cacheado por request: lo consumen carrito.php, ajax-evaluar.php y
 * avisos-carrito.php para el mismo padre dentro de una sola request.
 *
 * Vive en core (no en modo-auto) porque se usa también en modo "manual" puro
 * — donde modo-auto.php NO se carga.
 *
 * @return array [vid => capacidad] donde capacidad = stock o PHP_INT_MAX si ilimitado
 */
function bgm_capacidades_variaciones( $product ) {
    $caps = [];
    if ( ! $product || ! $product->is_type( 'variable' ) ) return $caps;

    static $cache = [];
    $pid = (int) $product->get_id();
    if ( isset( $cache[ $pid ] ) ) return $cache[ $pid ];

    foreach ( $product->get_children() as $vid ) {
        $variacion = wc_get_product( $vid );
        if ( ! $variacion || ! $variacion->is_purchasable() ) continue;

        if ( ! $variacion->managing_stock() ) {
            if ( $variacion->get_stock_status() === 'outofstock' ) continue;
            $caps[ $vid ] = PHP_INT_MAX;
            continue;
        }

        $stock = (int) $variacion->get_stock_quantity();
        if ( $stock <= 0 ) continue;

        $caps[ $vid ] = $stock;
    }
    return $cache[ $pid ] = $caps;
}

// ─── Verificar si un producto tiene precio mayorista configurado ─────────────
function bgm_tiene_precio_mayorista( $product_id, $variation_id = 0 ) {
    return bgm_get_descuento_1( $product_id, $variation_id ) > 0
        || bgm_get_descuento_2( $product_id, $variation_id ) > 0;
}

// ─── Obtener precio regular base (siempre ignora ofertas) ────────────────────
function bgm_get_precio_base( $product ) {
    if ( ! bgm_es_producto_valido( $product ) ) return 0;

    $regular = $product->get_regular_price();
    return $regular !== '' ? floatval( $regular ) : floatval( $product->get_price() );
}

/**
 * =========================================================
 * FUNCIÓN CENTRAL: calcular precio mayorista según cantidad
 * =========================================================
 *
 * Aplica tiered pricing según cantidad solicitada.
 *
 * Reglas de tolerancia:
 *   - Si solo configuró nivel 1 → solo aplica nivel 1
 *   - Si solo configuró nivel 2 → aplica nivel 2 desde min_2
 *   - Si no hay descuentos configurados → devuelve precio detalle
 *
 * @param WC_Product $product    Producto (simple o variación)
 * @param int        $qty        Cantidad solicitada
 * @param int        $product_id ID del padre (para variables) — opcional, se infiere
 * @return array {
 *     @type float  precio        Precio unitario calculado
 *     @type int    nivel         0 (detalle), 1 o 2
 *     @type float  descuento     Monto descontado por unidad
 *     @type float  precio_base   Precio regular original
 * }
 */
function bgm_calcular_precio( $product, $qty, $product_id = 0 ) {
    $precio_base = bgm_get_precio_base( $product );

    $resultado = [
        'precio'      => $precio_base,
        'nivel'       => 0,
        'descuento'   => 0,
        'precio_base' => $precio_base,
    ];

    if ( ! bgm_es_producto_valido( $product ) || $qty <= 0 ) {
        return $resultado;
    }

    // Determinar IDs para leer configuración
    $variation_id = $product->is_type( 'variation' ) ? $product->get_id() : 0;
    if ( ! $product_id ) {
        $product_id = $variation_id ? $product->get_parent_id() : $product->get_id();
    }

    $min_1       = bgm_get_min_1( $product_id, $variation_id );
    $min_2       = bgm_get_min_2( $product_id, $variation_id );
    $descuento_1 = bgm_get_descuento_1( $product_id, $variation_id );
    $descuento_2 = bgm_get_descuento_2( $product_id, $variation_id );

    // Sin descuentos configurados → precio detalle
    if ( $descuento_1 <= 0 && $descuento_2 <= 0 ) {
        return $resultado;
    }

    // Evaluar tier 2 primero (es el más alto)
    if ( $descuento_2 > 0 && $qty >= $min_2 ) {
        $resultado['precio']    = max( 0, $precio_base - $descuento_2 );
        $resultado['nivel']     = 2;
        $resultado['descuento'] = $descuento_2;
        return $resultado;
    }

    // Evaluar tier 1
    if ( $descuento_1 > 0 && $qty >= $min_1 ) {
        $resultado['precio']    = max( 0, $precio_base - $descuento_1 );
        $resultado['nivel']     = 1;
        $resultado['descuento'] = $descuento_1;
        return $resultado;
    }

    return $resultado;
}

