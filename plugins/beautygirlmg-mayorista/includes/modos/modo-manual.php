<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: SURTIDO MANUAL (cliente arma)
 *
 * UI en página de producto variable:
 * - Grilla con todas las variaciones disponibles + input qty
 * - Contador en vivo de cantidad total
 * - Validación visual de max_por_variacion
 * - Aviso del tier que aplica según total
 * - Botón "Agregar surtido"
 * =========================================================
 */

// ─── Encolar JS del modo manual ──────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'bgm_modo_manual_enqueue' );
function bgm_modo_manual_enqueue() {
    if ( ! is_product() ) return;

    $modo = bgm_get_modo_surtido();
    if ( $modo !== 'manual' && $modo !== 'ambos' ) return;

    wp_enqueue_script(
        'bgm-frontend-manual',
        BGM_URL . 'assets/frontend-manual.js',
        [ 'jquery', 'bgm-frontend-common' ],
        BGM_VERSION,
        true
    );

    wp_localize_script( 'bgm-frontend-manual', 'BGM_MANUAL', [
        'ajax_url'        => admin_url( 'admin-ajax.php' ),
        'nonce'           => wp_create_nonce( 'bgm_manual' ),
        'cart_url'        => wc_get_cart_url(),
        'txt_adding'      => __( 'Agregando…', 'beautygirlmg-mayorista' ),
        'txt_added'       => __( 'Ver carrito', 'beautygirlmg-mayorista' ),
        'txt_error'       => __( 'Error. Intenta de nuevo.', 'beautygirlmg-mayorista' ),
        'txt_detalle'     => __( 'Llevas %d ud. · Precio detalle', 'beautygirlmg-mayorista' ),
        'txt_tier1'       => __( 'Llevas %d ud. · Precio mayorista 1 ✓', 'beautygirlmg-mayorista' ),
        'txt_tier2'       => __( 'Llevas %d ud. · Precio mayorista 2 ✓✓', 'beautygirlmg-mayorista' ),
        'txt_excede_max'  => __( 'Excediste el máximo en una variación · Aplica precio detalle', 'beautygirlmg-mayorista' ),
        'txt_falta_tier1' => __( 'Faltan %d para mayorista 1', 'beautygirlmg-mayorista' ),
        'txt_subtotal'    => __( 'Subtotal:', 'beautygirlmg-mayorista' ),
        'txt_seleccionar' => __( 'Selecciona variaciones para empezar', 'beautygirlmg-mayorista' ),
        'txt_minimo_una'  => __( 'Selecciona al menos una variación', 'beautygirlmg-mayorista' ),
        'txt_total_cart'  => __( '(Total carrito: %d productos)', 'beautygirlmg-mayorista' ),
        'currency_symbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
        'thousand_sep'    => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : '.',
        'decimal_sep'     => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : ',',
        'decimals'        => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0,
    ] );
}

// ─── Render del UI dentro del contenedor de modos ────────────────────────────
add_action( 'bgm_render_modo_manual', 'bgm_render_modo_manual', 10, 1 );
function bgm_render_modo_manual( $product ) {
    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'variable' ) ) return;

    $product_id  = $product->get_id();
    $variaciones = bgm_obtener_variaciones_disponibles( $product );

    if ( empty( $variaciones ) ) {
        echo '<p class="bgm-aviso-error">' . esc_html__( 'Sin variaciones disponibles.', 'beautygirlmg-mayorista' ) . '</p>';
        return;
    }

    $info       = bgm_resumen_mayorista_variable( $product );
    $min_1      = $info['min_1'];
    $min_2      = $info['min_2'];
    $desc_1_max = $info['desc_1_max'];
    $desc_2_max = $info['desc_2_max'];

    $tolerancia_pct = bgm_get_tolerancia_porcentaje( $product_id );
    $n_disponibles  = bgm_contar_variaciones_disponibles( $product );

    // Precios aproximados (para mostrar tiers)
    $precio_min     = bgm_get_precio_base( $product );
    $precio_tier_1  = $desc_1_max > 0 ? max( 0, $precio_min - $desc_1_max ) : 0;
    $precio_tier_2  = $desc_2_max > 0 ? max( 0, $precio_min - $desc_2_max ) : 0;

    ?>
    <div class="bgm-bloque-manual"
         data-product-id="<?php echo esc_attr( $product_id ); ?>"
         data-min-1="<?php echo esc_attr( $min_1 ); ?>"
         data-min-2="<?php echo esc_attr( $min_2 ); ?>"
         data-tier-1-disponible="<?php echo $desc_1_max > 0 ? '1' : '0'; ?>"
         data-tier-2-disponible="<?php echo $desc_2_max > 0 ? '1' : '0'; ?>"
         data-tolerancia-pct="<?php echo esc_attr( $tolerancia_pct ); ?>"
         data-n-disponibles="<?php echo esc_attr( $n_disponibles ); ?>"
         data-precio-detalle="<?php echo esc_attr( $precio_min ); ?>"
         data-precio-tier-1="<?php echo esc_attr( $precio_tier_1 ); ?>"
         data-precio-tier-2="<?php echo esc_attr( $precio_tier_2 ); ?>">

        <p class="bgm-bloque-desc">
            <?php esc_html_e( 'Elige cuántas unidades por variación:', 'beautygirlmg-mayorista' ); ?>
            <strong>
                <?php printf(
                    esc_html__( 'Para mayorista distribuye entre las variaciones de forma balanceada (tolerancia %d%%).', 'beautygirlmg-mayorista' ),
                    absint( $tolerancia_pct )
                ); ?>
            </strong>
        </p>

        <div class="bgm-grilla-variaciones">
            <?php foreach ( $variaciones as $v ) : ?>
                <div class="bgm-variacion-row" data-vid="<?php echo esc_attr( $v['id'] ); ?>" data-stock="<?php echo esc_attr( $v['stock'] === PHP_INT_MAX ? -1 : $v['stock'] ); ?>">
                    <div class="bgm-variacion-info">
                        <span class="bgm-variacion-nombre"><?php echo esc_html( $v['nombre'] ); ?></span>
                        <?php if ( $v['stock'] !== PHP_INT_MAX && $v['stock'] <= 5 ) : ?>
                            <span class="bgm-variacion-stock"><?php printf( esc_html__( 'Quedan %d', 'beautygirlmg-mayorista' ), absint( $v['stock'] ) ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bgm-qty-controls">
                        <button type="button" class="bgm-qty-btn bgm-qty-menos" aria-label="−">−</button>
                        <input type="number" class="bgm-qty-input"
                               value="0" min="0" step="1"
                               <?php if ( $v['stock'] !== PHP_INT_MAX ) : ?>max="<?php echo esc_attr( $v['stock'] ); ?>"<?php endif; ?> />
                        <button type="button" class="bgm-qty-btn bgm-qty-mas" aria-label="+">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bgm-contador-vivo">
            <div class="bgm-contador-mensaje" data-estado="detalle">
                <?php printf( esc_html__( 'Llevas %d ud. · Precio detalle', 'beautygirlmg-mayorista' ), 0 ); ?>
            </div>
            <div class="bgm-contador-subtotal">
                <span class="bgm-subtotal-label"><?php esc_html_e( 'Subtotal:', 'beautygirlmg-mayorista' ); ?></span>
                <span class="bgm-subtotal-valor"><?php echo wc_price( 0 ); ?></span>
            </div>
        </div>

        <div class="bgm-acciones-manual">
            <button type="button" class="bgm-btn-secundario bgm-btn-sugerir">
                <?php esc_html_e( '✨ Sugerir surtido', 'beautygirlmg-mayorista' ); ?>
            </button>
            <button type="button" class="bgm-btn-primario bgm-btn-agregar-manual" disabled>
                <?php esc_html_e( 'Agregar al carrito', 'beautygirlmg-mayorista' ); ?>
            </button>
        </div>

        <div class="bgm-feedback" role="status" aria-live="polite"></div>
    </div>
    <?php
}

/**
 * Helper: lista de variaciones disponibles con su nombre legible.
 *
 * @return array [{id, nombre, stock, precio}, ...]
 */
function bgm_obtener_variaciones_disponibles( $product ) {
    $resultado = [];
    if ( ! $product || ! $product->is_type( 'variable' ) ) return $resultado;

    // Cache de get_term_by por (taxonomia, slug) — evita N×M lookups cuando el
    // mismo atributo se repite entre variaciones del mismo padre.
    static $term_cache = [];

    foreach ( $product->get_children() as $vid ) {
        $variacion = wc_get_product( $vid );
        if ( ! $variacion || ! $variacion->is_purchasable() ) continue;

        $stock = PHP_INT_MAX;
        if ( $variacion->managing_stock() ) {
            if ( $variacion->get_stock_status() === 'outofstock' ) continue;
            $s = (int) $variacion->get_stock_quantity();
            if ( $s <= 0 ) continue;
            $stock = $s;
        } else {
            if ( $variacion->get_stock_status() === 'outofstock' ) continue;
        }

        // Construir nombre legible a partir de los atributos
        $atributos = $variacion->get_variation_attributes();
        $partes    = [];
        foreach ( $atributos as $atributo => $valor ) {
            if ( $valor === '' ) continue;
            $taxonomia = str_replace( 'attribute_', '', $atributo );
            $cache_key = $taxonomia . '|' . $valor;
            if ( ! array_key_exists( $cache_key, $term_cache ) ) {
                $t = get_term_by( 'slug', $valor, $taxonomia );
                $term_cache[ $cache_key ] = ( $t && ! is_wp_error( $t ) ) ? $t->name : null;
            }
            $partes[] = $term_cache[ $cache_key ] !== null ? $term_cache[ $cache_key ] : $valor;
        }
        $nombre = ! empty( $partes ) ? implode( ' / ', $partes ) : '#' . $vid;

        $resultado[] = [
            'id'     => $vid,
            'nombre' => $nombre,
            'stock'  => $stock,
            'precio' => bgm_get_precio_base( $variacion ),
        ];
    }

    return $resultado;
}
