<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: SURTIDO AUTOMÁTICO ("Sorpréndeme")
 *
 * UI en página de producto variable:
 * - Selector de cantidad
 * - Subtotal en vivo
 * - Botón "Agregar surtido"
 *
 * El reparto entre variaciones se hace en backend (ajax-auto)
 * usando bgm_distribucion_auto().
 * =========================================================
 */

// ─── Encolar JS del modo auto ────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'bgm_modo_auto_enqueue' );
function bgm_modo_auto_enqueue() {
    if ( ! is_product() ) return;

    $modo = bgm_get_modo_surtido();
    if ( $modo !== 'auto' && $modo !== 'ambos' ) return;

    wp_enqueue_script(
        'bgm-frontend-auto',
        BGM_URL . 'assets/frontend-auto.js',
        [ 'jquery', 'bgm-frontend-common' ],
        BGM_VERSION,
        true
    );

    wp_localize_script( 'bgm-frontend-auto', 'BGM_AUTO', [
        'ajax_url'        => admin_url( 'admin-ajax.php' ),
        'nonce'           => wp_create_nonce( 'bgm_auto' ),
        'cart_url'        => wc_get_cart_url(),
        'txt_adding'      => __( 'Agregando…', 'beautygirlmg-mayorista' ),
        'txt_added'       => __( 'Ver carrito', 'beautygirlmg-mayorista' ),
        'txt_error'       => __( 'Error. Intenta de nuevo.', 'beautygirlmg-mayorista' ),
        'txt_min_qty'     => __( 'Mínimo %d unidades', 'beautygirlmg-mayorista' ),
        'txt_agregar'     => __( 'Agregar surtido al carrito', 'beautygirlmg-mayorista' ),
        'currency_symbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
        'thousand_sep'    => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : '.',
        'decimal_sep'     => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : ',',
        'decimals'        => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0,
    ] );
}

// ─── Render del UI dentro del contenedor de modos ────────────────────────────
add_action( 'bgm_render_modo_auto', 'bgm_render_modo_auto', 10, 1 );
function bgm_render_modo_auto( $product ) {
    if ( ! bgm_es_producto_valido( $product ) ) return;
    if ( ! $product->is_type( 'variable' ) ) return;

    $product_id = $product->get_id();
    $info       = bgm_resumen_mayorista_variable( $product );
    $min_1      = $info['min_1'];
    $min_2      = $info['min_2'];
    $desc_1_max = $info['desc_1_max'];
    $desc_2_max = $info['desc_2_max'];

    // Precio aprox por nivel: precio mínimo del rango menos el descuento del nivel.
    // El subtotal en vivo usa el nivel que corresponde a la cantidad (igual que el
    // carrito): desde min_2 aplica el precio de nivel 2, si está configurado.
    $precio_min   = bgm_get_precio_base( $product );
    $precio_1     = max( 0, $precio_min - $desc_1_max );                              // nivel 1
    $tiene_nivel2 = ( $desc_2_max > 0 && $min_2 > 0 );
    $precio_2     = $tiene_nivel2 ? max( 0, $precio_min - $desc_2_max ) : $precio_1;  // nivel 2
    $min_2_data   = $tiene_nivel2 ? $min_2 : 0;                                       // 0 = sin nivel 2

    $precio_aprox     = $precio_1;            // compat con data-precio-aprox
    $subtotal_inicial = $min_1 * $precio_1;   // cantidad inicial = min_1 → nivel 1

    ?>
    <div class="bgm-bloque-auto"
         data-product-id="<?php echo esc_attr( $product_id ); ?>"
         data-min="<?php echo esc_attr( $min_1 ); ?>"
         data-min-2="<?php echo esc_attr( $min_2_data ); ?>"
         data-precio-aprox="<?php echo esc_attr( $precio_aprox ); ?>"
         data-precio-2="<?php echo esc_attr( $precio_2 ); ?>">

        <p class="bgm-bloque-desc">
            <?php esc_html_e( 'Te enviamos variedad equilibrada según el stock disponible.', 'beautygirlmg-mayorista' ); ?>
        </p>

        <div class="bgm-fila-cantidad">
            <label class="bgm-label-cantidad"><?php esc_html_e( 'Cantidad', 'beautygirlmg-mayorista' ); ?></label>
            <div class="bgm-qty-controls">
                <button type="button" class="bgm-qty-btn bgm-qty-menos" aria-label="<?php esc_attr_e( 'Disminuir', 'beautygirlmg-mayorista' ); ?>">−</button>
                <input type="number" class="bgm-qty-input"
                       value="<?php echo esc_attr( $min_1 ); ?>"
                       min="<?php echo esc_attr( $min_1 ); ?>"
                       step="1" />
                <button type="button" class="bgm-qty-btn bgm-qty-mas" aria-label="<?php esc_attr_e( 'Aumentar', 'beautygirlmg-mayorista' ); ?>">+</button>
            </div>
        </div>

        <div class="bgm-fila-subtotal">
            <span class="bgm-subtotal-label"><?php esc_html_e( 'Subtotal aprox.', 'beautygirlmg-mayorista' ); ?></span>
            <span class="bgm-subtotal-valor"><?php echo wc_price( $subtotal_inicial ); ?></span>
        </div>

        <button type="button" class="bgm-btn-primario bgm-btn-agregar-auto">
            <?php esc_html_e( 'Agregar surtido al carrito', 'beautygirlmg-mayorista' ); ?>
        </button>

        <div class="bgm-feedback" role="status" aria-live="polite"></div>
    </div>
    <?php
}

/**
 * =========================================================
 * ALGORITMO: distribución equitativa entre variaciones
 *
 * @param array $caps  [vid => capacidad_máxima] (PHP_INT_MAX si no gestionado)
 * @param int   $qty   Cantidad total a distribuir
 * @return array|WP_Error  [vid => qty_asignada] (excluye 0) o WP_Error
 * =========================================================
 */
function bgm_distribucion_auto( $caps, $qty ) {
    $caps = array_filter( $caps, function( $c ) { return $c > 0; } );

    if ( empty( $caps ) ) {
        return new WP_Error( 'sin_stock', __( 'No hay variaciones con stock disponible.', 'beautygirlmg-mayorista' ) );
    }

    if ( $qty <= 0 ) {
        return new WP_Error( 'qty_invalida', __( 'Cantidad inválida.', 'beautygirlmg-mayorista' ) );
    }

    $stock_total = array_sum( $caps );
    if ( $stock_total < $qty ) {
        return new WP_Error(
            'stock_insuficiente',
            sprintf( __( 'Stock total disponible: %d unidades.', 'beautygirlmg-mayorista' ), $stock_total )
        );
    }

    $vids = array_keys( $caps );
    shuffle( $vids );

    $n         = count( $vids );
    $base      = intdiv( $qty, $n );
    $sobrantes = $qty % $n;

    $asignaciones = [];

    // Asignar base + 1 a las primeras `sobrantes` (ya están aleatorizadas por shuffle)
    foreach ( $vids as $i => $vid ) {
        $extra              = ( $i < $sobrantes ) ? 1 : 0;
        $asignaciones[ $vid ] = $base + $extra;
    }

    // Cap por capacidad máxima y acumular excedente
    $excedente = 0;
    foreach ( $asignaciones as $vid => $asig ) {
        if ( $asig > $caps[ $vid ] ) {
            $excedente             += $asig - $caps[ $vid ];
            $asignaciones[ $vid ]   = $caps[ $vid ];
        }
    }

    // Redistribuir excedente entre variaciones con capacidad sobrante
    if ( $excedente > 0 ) {
        $loop_seguridad = 1000;
        while ( $excedente > 0 && $loop_seguridad-- > 0 ) {
            $alguno = false;
            foreach ( $vids as $vid ) {
                if ( $excedente === 0 ) break;
                if ( $asignaciones[ $vid ] < $caps[ $vid ] ) {
                    $asignaciones[ $vid ]++;
                    $excedente--;
                    $alguno = true;
                }
            }
            if ( ! $alguno ) break;
        }
    }

    // Filtrar las que quedaron en 0
    return array_filter( $asignaciones, function( $q ) { return $q > 0; } );
}

// bgm_capacidades_variaciones() vive ahora en includes/core/helpers.php
// (debe estar disponible incluso en modo "manual" puro, donde modo-auto.php no carga).
