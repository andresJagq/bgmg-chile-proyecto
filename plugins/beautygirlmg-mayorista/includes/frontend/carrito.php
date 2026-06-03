<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO FRONTEND: CARRITO
 *
 * Aplica precios mayoristas en el carrito según 2 escenarios:
 *
 * 1. PRODUCTO SIMPLE: tiered según qty del item
 *    - qty >= min_2 → precio nivel 2
 *    - qty >= min_1 → precio nivel 1
 *    - else → detalle
 *
 * 2. CONJUNTO DE VARIACIONES DEL MISMO PADRE:
 *    Se evalúan TODOS los items del mismo producto padre como un único
 *    conjunto, sin importar si vinieron de modo Auto, Manual o del botón
 *    nativo de WC. Si el conjunto cumple la regla de surtido equilibrado
 *    (con la relajación de "atrancadas no cuentan"), se aplica precio
 *    mayorista. Si NO aplica mayorista (surtido no cumple o qty bajo el
 *    umbral), se intenta la PROMO minorista (no exige surtido). Si tampoco,
 *    queda a precio detalle.
 *
 * El flag bgm_origen ('auto' | 'manual') se guarda en cart_item_data al
 * agregar, pero NO afecta la lógica de precio: se usa solo para distinguir
 * en avisos visuales (minicart, página /cart/).
 * =========================================================
 */

// ─── Encolar CSS y JS del frontend ───────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'bgm_enqueue_frontend' );
function bgm_enqueue_frontend() {
    if ( ! is_product() && ! is_cart() && ! is_checkout() ) return;

    wp_enqueue_style(
        'bgm-frontend',
        BGM_URL . 'assets/frontend.css',
        [],
        BGM_VERSION
    );

    wp_enqueue_script(
        'bgm-frontend-common',
        BGM_URL . 'assets/frontend-common.js',
        [ 'jquery' ],
        BGM_VERSION,
        true
    );

    wp_localize_script( 'bgm-frontend-common', 'BGM_COMMON', [
        'cart_url_fallback' => wc_get_cart_url(),
        'txt_agregado'      => __( '¡Agregado al carrito!', 'beautygirlmg-mayorista' ),
        'txt_en_carrito'    => __( 'en tu carrito', 'beautygirlmg-mayorista' ),
        'txt_producto'      => __( 'producto', 'beautygirlmg-mayorista' ),
        'txt_productos'     => __( 'productos', 'beautygirlmg-mayorista' ),
        'txt_ver_carrito'   => __( 'Ver carrito', 'beautygirlmg-mayorista' ),
        'txt_cerrar'        => __( 'Cerrar', 'beautygirlmg-mayorista' ),
    ] );
}

// ─── Aplicar precios al recalcular el carrito ────────────────────────────────
//
// Priority 99: alta para correr DESPUÉS de la mayoría de plugins que toquen
// precio en `woocommerce_before_calculate_totals`. Si otro plugin se engancha
// con prioridad >99 (poco común) puede pisar el descuento mayorista; en ese
// caso conviene subir aún más este número o re-aplicar en una prioridad
// posterior. Tradicional: WC core no usa este hook con priority alta.
add_action( 'woocommerce_before_calculate_totals', 'bgm_aplicar_precios_carrito', 99 );
function bgm_aplicar_precios_carrito( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( ! $cart || ! method_exists( $cart, 'get_cart' ) ) return;

    $items = $cart->get_cart();
    if ( empty( $items ) ) return;

    // Agrupar items: variaciones por padre + simples sueltos
    $por_padre = []; // [padre_id => [keys...]]
    $simples   = []; // [keys...]

    foreach ( $items as $key => $item ) {
        $producto = $item['data'];
        if ( ! $producto ) continue;

        if ( $producto->is_type( 'variation' ) ) {
            $padre_id = $producto->get_parent_id();
            // Promo (Fase 2): incluir también padres en promo aunque NO tengan mayorista.
            $promo_ok = function_exists( 'bgm_calcular_precio_promo' )
                && bgm_promo_activa_ahora()
                && bgm_producto_en_promo( $padre_id );
            if ( bgm_tiene_precio_mayorista( $padre_id ) || bgm_variacion_padre_tiene_mayorista( $padre_id ) || $promo_ok ) {
                $por_padre[ $padre_id ][] = $key;
            }
        } elseif ( $producto->is_type( 'simple' ) ) {
            $simples[] = $key;
        }
    }

    foreach ( $por_padre as $padre_id => $keys ) {
        bgm_aplicar_precio_conjunto_variaciones( $cart, $padre_id, $keys );
    }

    foreach ( $simples as $key ) {
        bgm_aplicar_precio_simple( $cart, $key );
    }
}

// ─── Escenario 1: producto simple ────────────────────────────────────────────
function bgm_aplicar_precio_simple( $cart, $key ) {
    $items = $cart->get_cart();
    if ( ! isset( $items[ $key ] ) ) return;

    $item     = $items[ $key ];
    $producto = $item['data'];
    $qty      = (int) $item['quantity'];

    // 1) Mayorista PRIMERO (lógica intacta). Si aplica, gana y no se evalúa promo.
    if ( bgm_tiene_precio_mayorista( $producto->get_id() ) ) {
        $resultado = bgm_calcular_precio( $producto, $qty );

        if ( $resultado['nivel'] > 0 ) {
            $producto->set_price( $resultado['precio'] );
            bgm_log( 'cart', 'Simple tiered aplicado', [
                'product_id' => $producto->get_id(),
                'qty'        => $qty,
                'nivel'      => $resultado['nivel'],
                'precio'     => $resultado['precio'],
            ] );
            return; // mayorista aplicó → mutuamente excluyente con la promo
        }
    }

    // 2) Promo minorista (solo si el mayorista NO aplicó). Aislada; puede aplicar
    //    incluso a productos sin configuración mayorista. Devuelve null si no toca.
    if ( function_exists( 'bgm_calcular_precio_promo' ) ) {
        $precio_promo = bgm_calcular_precio_promo( $producto, $qty );
        if ( $precio_promo !== null ) {
            $producto->set_price( $precio_promo );
            bgm_log( 'cart', 'Promo minorista aplicada', [
                'product_id' => $producto->get_id(),
                'qty'        => $qty,
                'precio'     => $precio_promo,
            ] );
        }
    }
}

// ─── Escenario 2: conjunto de variaciones del mismo producto padre ──────────
//
// Se aplica una única regla unificada (bgm_evaluar_distribucion) que considera
// los stocks por variación para relajar la tolerancia ante variaciones
// atrancadas. Si el cliente modifica el carrito (elimina, agrega, ajusta qty),
// el conjunto se reevalúa en cada recálculo y el descuento se pierde si deja
// de cumplir la regla.
function bgm_aplicar_precio_conjunto_variaciones( $cart, $padre_id, $keys ) {
    $items = $cart->get_cart();

    // Sumar qty combinada por variación
    $qty_total     = 0;
    $por_variacion = [];

    foreach ( $keys as $key ) {
        if ( ! isset( $items[ $key ] ) ) continue;
        $qty = (int) $items[ $key ]['quantity'];
        $vid = $items[ $key ]['variation_id'];

        $qty_total            += $qty;
        $por_variacion[ $vid ] = ( $por_variacion[ $vid ] ?? 0 ) + $qty;
    }

    // Capacidades de stock por variación para la regla "atrancadas no cuentan"
    $padre = wc_get_product( $padre_id );
    $stocks = function_exists( 'bgm_capacidades_variaciones' ) && $padre
        ? bgm_capacidades_variaciones( $padre )
        : null;

    $evaluacion = bgm_evaluar_distribucion( $padre_id, $por_variacion, null, $stocks );

    // 1) MAYORISTA primero: requiere surtido equilibrado (evaluacion OK) Y que el
    //    qty_total alcance algún nivel. Si algún ítem entra a nivel mayorista, el
    //    conjunto se considera mayorista → mutuamente excluyente con la promo.
    if ( ! is_wp_error( $evaluacion ) ) {
        $mayorista_aplico = false;

        foreach ( $keys as $key ) {
            if ( ! isset( $items[ $key ] ) ) continue;

            $producto  = $items[ $key ]['data'];
            $resultado = bgm_calcular_precio( $producto, $qty_total, $padre_id );

            if ( $resultado['nivel'] > 0 ) {
                $producto->set_price( $resultado['precio'] );
                $mayorista_aplico = true;
            }
        }

        if ( $mayorista_aplico ) {
            bgm_log( 'cart', 'Conjunto variaciones: precio mayorista aplicado', [
                'padre_id'  => $padre_id,
                'qty_total' => $qty_total,
                'items'     => count( $keys ),
            ] );
            return; // mayorista ganó → no se evalúa promo
        }
    }

    // 2) PROMO minorista (Fase 2): solo si el mayorista NO aplicó (surtido no
    //    cumple, o qty_total por debajo del umbral). NO exige surtido equilibrado
    //    (es un descuento al detalle). Se cuenta por el TOTAL del producto, igual
    //    que el mayorista mide los variables; el precio base es el de cada variación.
    if ( ! function_exists( 'bgm_calcular_precio_promo' ) ) return;

    $promo_aplico = false;
    foreach ( $keys as $key ) {
        if ( ! isset( $items[ $key ] ) ) continue;

        $producto     = $items[ $key ]['data'];
        $precio_promo = bgm_calcular_precio_promo( $producto, $qty_total );

        if ( $precio_promo !== null ) {
            $producto->set_price( $precio_promo );
            $promo_aplico = true;
        }
    }

    if ( $promo_aplico ) {
        bgm_log( 'cart', 'Conjunto variaciones: promo minorista aplicada', [
            'padre_id'  => $padre_id,
            'qty_total' => $qty_total,
            'items'     => count( $keys ),
        ] );
    } elseif ( is_wp_error( $evaluacion ) ) {
        bgm_log( 'cart', 'Conjunto variaciones: sin mayorista ni promo → precio detalle', [
            'padre_id'   => $padre_id,
            'qty_total'  => $qty_total,
            'razon'      => $evaluacion->get_error_code(),
            'cantidades' => $por_variacion,
        ] );
    }
}

// ─── Helper: el padre tiene mayorista en alguna variación (modo individual) ──
function bgm_variacion_padre_tiene_mayorista( $padre_id ) {
    $modo = bgm_get_modo_descuento( $padre_id );
    if ( $modo !== 'individual' ) return false;

    $padre = wc_get_product( $padre_id );
    if ( ! $padre ) return false;

    foreach ( $padre->get_children() as $vid ) {
        if ( bgm_tiene_precio_mayorista( $padre_id, $vid ) ) return true;
    }
    return false;
}
