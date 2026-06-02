<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO FRONTEND: PRODUCTO SIMPLE
 *
 * Renderiza:
 *   - Badges inline de tiers mayoristas
 *   - Bloque "preview en vivo" que reacciona al input cantidad
 *     del form nativo de WC (resalta tier activo, muestra precio
 *     unitario actual y aviso "agrega N más para tier siguiente").
 *
 * El descuento se aplica automáticamente cuando el cliente sube
 * la cantidad desde el botón nativo de WC (lógica en carrito.php).
 * =========================================================
 */

// Hooks: priority 15 dentro del summary + before_add_to_cart_form como fallback.
// Guard estático evita duplicar si ambos disparan.
add_action( 'woocommerce_single_product_summary',    'bgm_avisos_simple', 15 );
add_action( 'woocommerce_before_add_to_cart_form',   'bgm_avisos_simple', 5 );

function bgm_avisos_simple() {
    static $renderizado_para = [];

    global $product;
    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'simple' ) ) return;

    $product_id = $product->get_id();
    if ( isset( $renderizado_para[ $product_id ] ) ) return;
    $renderizado_para[ $product_id ] = true;

    if ( ! bgm_tiene_precio_mayorista( $product_id ) ) return;

    $precio_base = bgm_get_precio_base( $product );
    if ( $precio_base <= 0 ) return;

    bgm_render_tier_badges( $product_id, $precio_base, /* es_variable */ false );
    bgm_render_simple_preview( $product_id, $precio_base );
}

// ─── Encolar JS del preview de producto simple ──────────────────────────────
add_action( 'wp_enqueue_scripts', 'bgm_enqueue_simple' );
function bgm_enqueue_simple() {
    if ( ! is_product() ) return;

    global $product;
    if ( ! $product ) $product = wc_get_product( get_queried_object_id() );
    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'simple' ) ) return;
    if ( ! bgm_tiene_precio_mayorista( $product->get_id() ) ) return;

    wp_enqueue_script(
        'bgm-frontend-simple',
        BGM_URL . 'assets/frontend-simple.js',
        [ 'jquery' ],
        BGM_VERSION,
        true
    );

    $product_id  = $product->get_id();
    $precio_base = bgm_get_precio_base( $product );
    $min_1       = bgm_get_min_1( $product_id );
    $min_2       = bgm_get_min_2( $product_id );
    $desc_1      = bgm_get_descuento_1( $product_id );
    $desc_2      = bgm_get_descuento_2( $product_id );

    wp_localize_script( 'bgm-frontend-simple', 'BGM_SIMPLE', [
        'precio_base'     => $precio_base,
        'min_1'           => $min_1,
        'min_2'           => $min_2,
        'desc_1'          => $desc_1,
        'desc_2'          => $desc_2,
        'currency_symbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
        'thousand_sep'    => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : '.',
        'decimal_sep'     => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : ',',
        'decimals'        => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0,
        'txt_pagas'       => __( 'Pagas', 'beautygirlmg-mayorista' ),
        'txt_cu'          => __( 'c/u', 'beautygirlmg-mayorista' ),
        'txt_ahorras'     => __( 'ahorras', 'beautygirlmg-mayorista' ),
        'txt_agrega'      => __( 'Agrega %d más para', 'beautygirlmg-mayorista' ),
        'txt_tier1'       => __( 'Mayorista', 'beautygirlmg-mayorista' ),
        'txt_tier2'       => __( 'Mayoreo grande', 'beautygirlmg-mayorista' ),
    ] );
}

/**
 * Renderiza el bloque "preview en vivo" que el JS actualiza según qty.
 * Estado inicial = precio detalle (qty=1).
 */
function bgm_render_simple_preview( $product_id, $precio_base ) {
    $min_1  = bgm_get_min_1( $product_id );
    $desc_1 = bgm_get_descuento_1( $product_id );
    $min_2  = bgm_get_min_2( $product_id );
    $desc_2 = bgm_get_descuento_2( $product_id );

    // Aviso inicial: cuánto falta para el primer tier configurado
    if ( $desc_1 > 0 ) {
        $faltan       = max( 0, $min_1 - 1 );
        $label_tier   = __( 'Mayorista', 'beautygirlmg-mayorista' );
        $precio_tier  = max( 0, $precio_base - $desc_1 );
    } elseif ( $desc_2 > 0 ) {
        $faltan       = max( 0, $min_2 - 1 );
        $label_tier   = __( 'Mayoreo grande', 'beautygirlmg-mayorista' );
        $precio_tier  = max( 0, $precio_base - $desc_2 );
    } else {
        return;
    }
    ?>
    <div class="bgm-simple-preview" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-tier-activo="0">
        <div class="bgm-simple-preview-precio">
            <span class="bgm-simple-precio-label"><?php esc_html_e( 'Pagas', 'beautygirlmg-mayorista' ); ?></span>
            <strong class="bgm-simple-precio-valor"><?php echo wc_price( $precio_base ); ?></strong>
            <span class="bgm-simple-precio-suffix"><?php esc_html_e( 'c/u', 'beautygirlmg-mayorista' ); ?></span>
        </div>
        <div class="bgm-simple-preview-aviso" data-estado="detalle">
            <?php if ( $faltan > 0 ) : ?>
                <?php printf(
                    /* translators: 1: cantidad faltante 2: nombre tier 3: precio unitario */
                    esc_html__( 'Agrega %1$d más para %2$s a %3$s c/u', 'beautygirlmg-mayorista' ),
                    $faltan,
                    esc_html( $label_tier ),
                    wp_strip_all_tags( wc_price( $precio_tier ) )
                ); ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Renderiza la fila de badges de tiers (inline, una línea por tier).
 * Reutilizada por producto-simple y por el bloque mayorista de variables.
 *
 * @param int   $product_id   ID del producto (para leer min/desc)
 * @param float $precio_base  Precio regular (para calcular el precio mayor)
 * @param bool  $es_variable  Si true, ajusta el texto de la condición ("armando surtido")
 */
function bgm_render_tier_badges( $product_id, $precio_base, $es_variable = false ) {
    $min_1  = bgm_get_min_1( $product_id );
    $desc_1 = bgm_get_descuento_1( $product_id );
    $min_2  = bgm_get_min_2( $product_id );
    $desc_2 = bgm_get_descuento_2( $product_id );

    if ( $desc_1 <= 0 && $desc_2 <= 0 ) return;

    echo '<div class="bgm-tiers-row">';

    if ( $desc_1 > 0 ) {
        $precio_1 = max( 0, $precio_base - $desc_1 );
        bgm_render_tier_badge_row(
            /* tier */     1,
            /* label */    __( 'Mayorista', 'beautygirlmg-mayorista' ),
            /* min */      $min_1,
            /* precio */   $precio_1,
            /* variable */ $es_variable
        );
    }

    if ( $desc_2 > 0 ) {
        $precio_2 = max( 0, $precio_base - $desc_2 );
        bgm_render_tier_badge_row(
            /* tier */     2,
            /* label */    __( 'Mayoreo grande', 'beautygirlmg-mayorista' ),
            /* min */      $min_2,
            /* precio */   $precio_2,
            /* variable */ $es_variable
        );
    }

    echo '</div>';
}

/**
 * Renderiza una fila individual de badge.
 */
function bgm_render_tier_badge_row( $tier, $label, $min, $precio, $es_variable = false ) {
    $clase_tier = $tier === 2 ? 'bgm-tier-row bgm-tier-row-2' : 'bgm-tier-row';
    $icono      = $tier === 2 ? '★★' : '★';
    $cond       = $es_variable
        ? sprintf( __( 'armando surtido desde %d ud', 'beautygirlmg-mayorista' ), $min )
        : sprintf( __( 'desde %d ud', 'beautygirlmg-mayorista' ), $min );
    ?>
    <div class="<?php echo esc_attr( $clase_tier ); ?>" data-tier="<?php echo (int) $tier; ?>" data-min="<?php echo (int) $min; ?>" data-price="<?php echo esc_attr( $precio ); ?>">
        <div class="bgm-tier-row-left">
            <span class="bgm-tier-icon"><?php echo esc_html( $icono ); ?></span>
            <span class="bgm-tier-label"><?php echo esc_html( $label ); ?></span>
            <span class="bgm-tier-cond"><?php echo esc_html( $cond ); ?></span>
            <span class="bgm-tier-check"><?php esc_html_e( 'aplicado', 'beautygirlmg-mayorista' ); ?></span>
        </div>
        <div class="bgm-tier-price">
            <?php echo wc_price( $precio ); ?><span class="bgm-tier-price-cu"> <?php esc_html_e( 'c/u', 'beautygirlmg-mayorista' ); ?></span>
        </div>
    </div>
    <?php
}
