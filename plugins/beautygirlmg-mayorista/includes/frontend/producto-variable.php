<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO FRONTEND: PRODUCTO VARIABLE
 *
 * Renderiza el bloque "Comprar por mayor" completo:
 *   - Badges inline de tiers
 *   - Sub-tabs Sorpréndeme / Armar mi surtido (según modo)
 *   - Paneles del modo activo (poblados por modo-auto y modo-manual)
 *
 * Punto de entrada principal:
 *   bgm_render_mayorista_bloque_publico( $product )
 *
 * Llamado directamente desde bgmg-product.php dentro del tab
 * "Por mayor". Hook fallback para temas que no llamen la función.
 * =========================================================
 */

// ─── Fallback: si el tema no llama la función pública, renderizar
//      después del summary para garantizar visibilidad. El guard
//      estático evita doble render si el tema sí la llama. ────────
add_action( 'woocommerce_after_single_product_summary', 'bgm_render_mayorista_fallback', 5 );
add_action( 'woocommerce_after_single_product',         'bgm_render_mayorista_fallback', 5 );

function bgm_render_mayorista_fallback() {
    global $product;
    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'variable' ) ) return;
    bgm_render_mayorista_bloque_publico( $product );
}

/**
 * Punto de entrada PÚBLICO. El tema lo llama dentro del tab "Por mayor".
 * Guard estático asegura un solo render por request por producto.
 */
function bgm_render_mayorista_bloque_publico( $product ) {
    static $renderizado_para = [];

    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'variable' ) ) return;
    if ( ! bgm_variable_tiene_mayorista( $product ) ) return;

    $product_id = $product->get_id();
    if ( isset( $renderizado_para[ $product_id ] ) ) return;
    $renderizado_para[ $product_id ] = true;

    bgm_render_html_bloque_mayorista( $product );
}

/**
 * Render puro (sin guards) — usado por el shortcode y la función pública.
 */
function bgm_render_html_bloque_mayorista( $product ) {
    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'variable' ) ) return;
    if ( ! bgm_variable_tiene_mayorista( $product ) ) return;

    $product_id  = $product->get_id();
    $modo_global = bgm_get_modo_surtido();
    $precio_base = bgm_get_precio_base( $product );
    ?>
    <div class="bgm-bloque-mayor" data-product-id="<?php echo esc_attr( $product_id ); ?>">

        <?php
        // ── Badges inline de tiers (reutiliza helper de producto-simple.php) ──
        if ( $precio_base > 0 ) {
            bgm_render_tier_badges_variable( $product );
        }
        ?>

        <?php if ( $modo_global === 'ambos' ) : ?>
        <div class="bgm-subtabs" role="tablist">
            <button type="button" class="bgm-subtab is-active" data-subtab="auto" role="tab"><?php esc_html_e( 'Sorpréndeme', 'beautygirlmg-mayorista' ); ?></button>
            <button type="button" class="bgm-subtab"           data-subtab="manual" role="tab"><?php esc_html_e( 'Armar mi surtido', 'beautygirlmg-mayorista' ); ?></button>
        </div>
        <?php endif; ?>

        <?php if ( $modo_global === 'auto' || $modo_global === 'ambos' ) : ?>
        <div class="bgm-subpanel is-active" data-subpanel="auto">
            <?php do_action( 'bgm_render_modo_auto', $product ); ?>
        </div>
        <?php endif; ?>

        <?php if ( $modo_global === 'manual' || $modo_global === 'ambos' ) : ?>
        <div class="bgm-subpanel <?php echo $modo_global === 'manual' ? 'is-active' : ''; ?>" data-subpanel="manual"<?php if ( $modo_global === 'ambos' ) echo ' hidden'; ?>>
            <?php do_action( 'bgm_render_modo_manual', $product ); ?>
        </div>
        <?php endif; ?>

    </div>
    <?php
}

/**
 * Renderiza badges de tiers para producto variable.
 * Para modo único: usa min/desc del padre.
 * Para modo individual: muestra el descuento máximo entre variaciones.
 */
function bgm_render_tier_badges_variable( $product ) {
    $product_id = $product->get_id();
    $info       = bgm_resumen_mayorista_variable( $product );
    $precio_min = bgm_get_precio_base( $product );

    if ( $precio_min <= 0 ) return;
    if ( $info['desc_1_max'] <= 0 && $info['desc_2_max'] <= 0 ) return;

    echo '<div class="bgm-tiers-row">';

    if ( $info['desc_1_max'] > 0 ) {
        $precio_1 = max( 0, $precio_min - $info['desc_1_max'] );
        bgm_render_tier_badge_row(
            /* tier */     1,
            /* label */    __( 'Mayorista', 'beautygirlmg-mayorista' ),
            /* min */      $info['min_1'],
            /* precio */   $precio_1,
            /* variable */ true
        );
    }

    if ( $info['desc_2_max'] > 0 ) {
        $precio_2 = max( 0, $precio_min - $info['desc_2_max'] );
        bgm_render_tier_badge_row(
            /* tier */     2,
            /* label */    __( 'Mayoreo grande', 'beautygirlmg-mayorista' ),
            /* min */      $info['min_2'],
            /* precio */   $precio_2,
            /* variable */ true
        );
    }

    echo '</div>';
}

// ─── Shortcode [bgm_surtido] — fallback manual ──────────────────────────────
add_shortcode( 'bgm_surtido', 'bgm_shortcode_surtido' );
function bgm_shortcode_surtido( $atts ) {
    $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'bgm_surtido' );

    if ( ! $atts['id'] ) {
        global $product;
        $atts['id'] = bgm_es_producto_valido( $product ) ? $product->get_id() : get_the_ID();
    }

    $producto = wc_get_product( absint( $atts['id'] ) );
    if ( ! $producto ) return '';

    ob_start();
    bgm_render_html_bloque_mayorista( $producto );
    return ob_get_clean();
}

/**
 * Verifica si un producto variable tiene precio mayorista configurado.
 */
function bgm_variable_tiene_mayorista( $product ) {
    if ( ! bgm_es_producto_valido( $product ) ) return false;
    if ( ! $product->is_type( 'variable' ) ) return false;

    $product_id = $product->get_id();
    $modo       = bgm_get_modo_descuento( $product_id );

    if ( $modo === 'unico' ) {
        return bgm_tiene_precio_mayorista( $product_id );
    }

    foreach ( $product->get_children() as $vid ) {
        if ( bgm_tiene_precio_mayorista( $product_id, $vid ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Resumen agregado de la configuración mayorista de un producto variable.
 *
 * Cacheado por request: se consume en badges + modo-auto + modo-manual +
 * ajax-evaluar dentro de una sola vista de producto.
 */
function bgm_resumen_mayorista_variable( $product ) {
    static $cache = [];
    $product_id = $product->get_id();
    if ( isset( $cache[ $product_id ] ) ) return $cache[ $product_id ];

    $modo = bgm_get_modo_descuento( $product_id );

    $info = [
        'min_1'      => bgm_get_min_global_1(),
        'min_2'      => bgm_get_min_global_2(),
        'desc_1_max' => 0,
        'desc_2_max' => 0,
    ];

    if ( $modo === 'unico' ) {
        $info['min_1']      = bgm_get_min_1( $product_id );
        $info['min_2']      = bgm_get_min_2( $product_id );
        $info['desc_1_max'] = bgm_get_descuento_1( $product_id );
        $info['desc_2_max'] = bgm_get_descuento_2( $product_id );
        return $cache[ $product_id ] = $info;
    }

    foreach ( $product->get_children() as $vid ) {
        $d1 = bgm_get_descuento_1( $product_id, $vid );
        $d2 = bgm_get_descuento_2( $product_id, $vid );
        if ( $d1 > $info['desc_1_max'] ) $info['desc_1_max'] = $d1;
        if ( $d2 > $info['desc_2_max'] ) $info['desc_2_max'] = $d2;
    }
    return $cache[ $product_id ] = $info;
}
