<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO ADMIN: CAMPOS EN CADA VARIACIÓN
 *
 * Solo se muestran si el producto padre está en modo
 * "individual". En modo "único" estos campos no aparecen
 * porque el descuento vive en el padre.
 * =========================================================
 */

// ─── Mostrar campos en el editor de cada variación ───────────────────────────
add_action( 'woocommerce_variation_options_pricing', 'bgm_campos_variacion', 10, 3 );
function bgm_campos_variacion( $loop, $variation_data, $variation ) {
    // Verificar que el padre esté en modo individual
    $padre_id = $variation->post_parent;
    if ( bgm_get_modo_descuento( $padre_id ) !== 'individual' ) {
        return;
    }

    $variation_id = $variation->ID;

    $min_1  = get_post_meta( $variation_id, '_bgm_min_1',       true );
    $desc_1 = get_post_meta( $variation_id, '_bgm_descuento_1', true );
    $min_2  = get_post_meta( $variation_id, '_bgm_min_2',       true );
    $desc_2 = get_post_meta( $variation_id, '_bgm_descuento_2', true );

    $min_global_1 = bgm_get_min_global_1();
    $min_global_2 = bgm_get_min_global_2();

    // Calcular precio base de esta variación específica
    $variation_obj = wc_get_product( $variation_id );
    $precio_base   = $variation_obj ? bgm_get_precio_base( $variation_obj ) : 0;
    ?>

    <div class="bgm-variacion-mayorista" data-loop="<?php echo esc_attr( $loop ); ?>">

        <p class="form-row form-row-full bgm-variacion-titulo">
            <strong><?php esc_html_e( 'Mayorista (esta variación)', 'beautygirlmg-mayorista' ); ?></strong>
        </p>

        <p class="form-row form-row-first">
            <label><?php esc_html_e( 'Cantidad mínima nivel 1', 'beautygirlmg-mayorista' ); ?></label>
            <input type="number"
                   name="bgm_var_min_1[<?php echo esc_attr( $loop ); ?>]"
                   value="<?php echo esc_attr( $min_1 ); ?>"
                   placeholder="<?php echo esc_attr( $min_global_1 ); ?>"
                   min="1" step="1" />
        </p>

        <p class="form-row form-row-last">
            <label><?php esc_html_e( 'Descuento $ nivel 1', 'beautygirlmg-mayorista' ); ?></label>
            <input type="number"
                   name="bgm_var_descuento_1[<?php echo esc_attr( $loop ); ?>]"
                   class="bgm-var-descuento"
                   data-loop="<?php echo esc_attr( $loop ); ?>"
                   data-tier="1"
                   data-precio-base="<?php echo esc_attr( $precio_base ); ?>"
                   value="<?php echo esc_attr( $desc_1 ); ?>"
                   min="0" step="1" />
            <span class="bgm-var-preview" data-loop="<?php echo esc_attr( $loop ); ?>" data-tier="1">
                <?php if ( $desc_1 !== '' && $precio_base > 0 ) : ?>
                    → <?php echo wc_price( max( 0, $precio_base - floatval( $desc_1 ) ) ); ?>
                <?php endif; ?>
            </span>
        </p>

        <p class="form-row form-row-first">
            <label><?php esc_html_e( 'Cantidad mínima nivel 2', 'beautygirlmg-mayorista' ); ?></label>
            <input type="number"
                   name="bgm_var_min_2[<?php echo esc_attr( $loop ); ?>]"
                   value="<?php echo esc_attr( $min_2 ); ?>"
                   placeholder="<?php echo esc_attr( $min_global_2 ); ?>"
                   min="1" step="1" />
        </p>

        <p class="form-row form-row-last">
            <label><?php esc_html_e( 'Descuento $ nivel 2', 'beautygirlmg-mayorista' ); ?></label>
            <input type="number"
                   name="bgm_var_descuento_2[<?php echo esc_attr( $loop ); ?>]"
                   class="bgm-var-descuento"
                   data-loop="<?php echo esc_attr( $loop ); ?>"
                   data-tier="2"
                   data-precio-base="<?php echo esc_attr( $precio_base ); ?>"
                   value="<?php echo esc_attr( $desc_2 ); ?>"
                   min="0" step="1" />
            <span class="bgm-var-preview" data-loop="<?php echo esc_attr( $loop ); ?>" data-tier="2">
                <?php if ( $desc_2 !== '' && $precio_base > 0 ) : ?>
                    → <?php echo wc_price( max( 0, $precio_base - floatval( $desc_2 ) ) ); ?>
                <?php endif; ?>
            </span>
        </p>

    </div>
    <?php
}

// ─── Guardar datos al guardar variación ──────────────────────────────────────
add_action( 'woocommerce_save_product_variation', 'bgm_guardar_variacion', 10, 2 );
function bgm_guardar_variacion( $variation_id, $loop ) {
    // WC ya verifica nonce antes de disparar este hook, pero validamos
    // capability como defensa adicional contra cambios futuros del core.
    if ( ! current_user_can( 'edit_product', $variation_id ) ) return;

    $campos = [
        '_bgm_min_1'       => 'bgm_var_min_1',
        '_bgm_descuento_1' => 'bgm_var_descuento_1',
        '_bgm_min_2'       => 'bgm_var_min_2',
        '_bgm_descuento_2' => 'bgm_var_descuento_2',
    ];

    foreach ( $campos as $meta_key => $field_name ) {
        if ( ! isset( $_POST[ $field_name ][ $loop ] ) ) continue;

        $valor = wc_clean( wp_unslash( $_POST[ $field_name ][ $loop ] ) );

        if ( $valor === '' ) {
            delete_post_meta( $variation_id, $meta_key );
        } elseif ( is_numeric( $valor ) && floatval( $valor ) >= 0 ) {
            update_post_meta( $variation_id, $meta_key, floatval( $valor ) );
        }
    }

    bgm_log( 'admin', 'Variación guardada', [ 'variation_id' => $variation_id, 'loop' => $loop ] );
}
