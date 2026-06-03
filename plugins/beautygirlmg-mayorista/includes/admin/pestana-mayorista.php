<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO ADMIN: PESTAÑA MAYORISTA EN EDITAR PRODUCTO
 *
 * Para productos SIMPLES y VARIABLES.
 * Campos:
 *   - Switch modo descuento (solo variables): único / individual
 *   - Nivel 1: cantidad mínima + descuento $
 *   - Nivel 2: cantidad mínima + descuento $
 *   - Máx. por variación (solo variables, opcional)
 *   - Tabla resumen comparativa
 *
 * Si el producto variable está en modo "individual", los campos
 * de descuento se muestran deshabilitados aquí (se editan en
 * cada variación). El switch sigue editable.
 * =========================================================
 */

// ─── Encolar estilos y JS del admin ──────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'bgm_admin_enqueue' );
function bgm_admin_enqueue( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

    global $post;
    if ( ! $post || $post->post_type !== 'product' ) return;

    wp_enqueue_style(
        'bgm-admin',
        BGM_URL . 'assets/admin.css',
        [],
        BGM_VERSION
    );

    wp_enqueue_script(
        'bgm-admin',
        BGM_URL . 'assets/admin.js',
        [ 'jquery' ],
        BGM_VERSION,
        true
    );

    // Pasar el precio regular y los defaults globales al JS para preview en vivo
    $product       = wc_get_product( $post->ID );
    $precio_base   = $product ? bgm_get_precio_base( $product ) : 0;

    wp_localize_script( 'bgm-admin', 'BGM_ADMIN', [
        'precio_base'        => $precio_base,
        'min_global_1'       => bgm_get_min_global_1(),
        'min_global_2'       => bgm_get_min_global_2(),
        'promo_tipo'         => bgm_get_setting( 'bgm_promo_tipo', 'porcentaje' ),
        'promo_valor_global' => (float) bgm_get_setting( 'bgm_promo_valor', 0 ),
        'currency_symbol'  => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
        'thousand_sep'     => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : '.',
        'decimal_sep'      => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : ',',
        'decimals'         => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0,
        'txt_precio_final' => __( 'Precio final', 'beautygirlmg-mayorista' ),
        'txt_sin_desc'     => __( '(sin descuento)', 'beautygirlmg-mayorista' ),
    ] );
}

// ─── Registrar pestaña "Mayorista" en el editor de producto ──────────────────
add_filter( 'woocommerce_product_data_tabs', 'bgm_registrar_pestana' );
function bgm_registrar_pestana( $tabs ) {
    $tabs['bgm_mayorista'] = [
        'label'    => __( 'Mayorista', 'beautygirlmg-mayorista' ),
        'target'   => 'bgm_mayorista_panel',
        'class'    => [ 'show_if_simple', 'show_if_variable' ],
        'priority' => 25,
    ];
    return $tabs;
}

// ─── Contenido del panel de la pestaña ───────────────────────────────────────
add_action( 'woocommerce_product_data_panels', 'bgm_render_panel_mayorista' );
function bgm_render_panel_mayorista() {
    global $post;
    if ( ! $post ) return;

    $product = wc_get_product( $post->ID );
    if ( ! $product ) return;

    $es_variable    = $product->is_type( 'variable' );
    $modo_desc      = $es_variable ? bgm_get_modo_descuento( $post->ID ) : 'unico';
    $usar_swatches  = $es_variable ? bgm_usar_swatches( $post->ID ) : false;

    // Para variables en modo "individual", los descuentos viven en cada variación
    $disabled_descuentos = ( $es_variable && $modo_desc === 'individual' );

    // Lectura de valores actuales
    $min_1   = get_post_meta( $post->ID, '_bgm_min_1',       true );
    $desc_1  = get_post_meta( $post->ID, '_bgm_descuento_1', true );
    $min_2   = get_post_meta( $post->ID, '_bgm_min_2',       true );
    $desc_2  = get_post_meta( $post->ID, '_bgm_descuento_2', true );

    $min_global_1 = bgm_get_min_global_1();
    $min_global_2 = bgm_get_min_global_2();
    $precio_base  = bgm_get_precio_base( $product );
    ?>

    <div id="bgm_mayorista_panel" class="panel woocommerce_options_panel bgm-panel">

        <?php wp_nonce_field( 'bgm_guardar_panel', 'bgm_panel_nonce' ); ?>

        <?php if ( $es_variable ) : ?>
        <!-- ─── Switch modo descuento (solo variables) ───────────────────── -->
        <div class="options_group bgm-grupo bgm-grupo-modo">
            <p class="form-field">
                <label><?php esc_html_e( 'Modo de descuento', 'beautygirlmg-mayorista' ); ?></label>
                <span class="bgm-radio-grupo">
                    <label class="bgm-radio">
                        <input type="radio" name="_bgm_modo_descuento" value="unico" <?php checked( $modo_desc, 'unico' ); ?> />
                        <span><?php esc_html_e( 'Único para todas las variaciones', 'beautygirlmg-mayorista' ); ?></span>
                    </label>
                    <label class="bgm-radio">
                        <input type="radio" name="_bgm_modo_descuento" value="individual" <?php checked( $modo_desc, 'individual' ); ?> />
                        <span><?php esc_html_e( 'Individual por variación', 'beautygirlmg-mayorista' ); ?></span>
                    </label>
                </span>
                <span class="description"><?php esc_html_e( 'Único = mismo descuento aplicado a todas. Individual = cada variación tiene su propio descuento (configurable en el editor de cada variación).', 'beautygirlmg-mayorista' ); ?></span>
            </p>
        </div>

        <?php if ( $disabled_descuentos ) : ?>
        <div class="bgm-aviso-modo-individual">
            <strong><?php esc_html_e( 'Modo individual activo.', 'beautygirlmg-mayorista' ); ?></strong>
            <?php esc_html_e( 'Los descuentos se configuran en el editor de cada variación (pestaña Variaciones).', 'beautygirlmg-mayorista' ); ?>
        </div>
        <?php endif; ?>

        <!-- ─── Selector visual de variaciones (swatches) ────────────────── -->
        <div class="options_group bgm-grupo bgm-grupo-swatches">
            <p class="form-field">
                <label for="_bgm_usar_swatches"><?php esc_html_e( 'Selector de variaciones', 'beautygirlmg-mayorista' ); ?></label>
                <span class="bgm-checkbox-wrap">
                    <!-- Hidden 0 garantiza que siempre llegue valor al guardar (incluso desmarcado) -->
                    <input type="hidden" name="_bgm_usar_swatches" value="0" />
                    <input type="checkbox" id="_bgm_usar_swatches" name="_bgm_usar_swatches" value="1" <?php checked( $usar_swatches ); ?> />
                    <label for="_bgm_usar_swatches" class="bgm-checkbox-label"><?php esc_html_e( 'Mostrar como botones tipo píldora (activo por defecto)', 'beautygirlmg-mayorista' ); ?></label>
                </span>
                <span class="description"><?php esc_html_e( 'Por defecto los atributos del producto se muestran como pills clickeables en la página. Desmarca para usar el select nativo del navegador. Si una combinación no tiene stock, el pill aparece tachado.', 'beautygirlmg-mayorista' ); ?></span>
            </p>
        </div>
        <?php endif; ?>

        <!-- ─── Nivel 1 ──────────────────────────────────────────────────── -->
        <div class="options_group bgm-grupo bgm-grupo-tier" data-tier="1">
            <h4 class="bgm-tier-titulo"><?php esc_html_e( 'Nivel 1 — mayoreo', 'beautygirlmg-mayorista' ); ?></h4>

            <p class="form-field bgm-field-row">
                <label for="_bgm_min_1"><?php esc_html_e( 'Cantidad mínima', 'beautygirlmg-mayorista' ); ?></label>
                <input type="number"
                       id="_bgm_min_1"
                       name="_bgm_min_1"
                       value="<?php echo esc_attr( $min_1 ); ?>"
                       placeholder="<?php echo esc_attr( $min_global_1 ); ?>"
                       min="1" step="1"
                       <?php disabled( $disabled_descuentos ); ?> />
                <span class="description"><?php echo esc_html( sprintf( __( 'Vacío = usa default global (%d)', 'beautygirlmg-mayorista' ), $min_global_1 ) ); ?></span>
            </p>

            <p class="form-field bgm-field-row">
                <label for="_bgm_descuento_1"><?php esc_html_e( 'Descuento $', 'beautygirlmg-mayorista' ); ?></label>
                <input type="number"
                       id="_bgm_descuento_1"
                       name="_bgm_descuento_1"
                       class="bgm-input-descuento"
                       data-tier="1"
                       value="<?php echo esc_attr( $desc_1 ); ?>"
                       min="0" step="1"
                       <?php disabled( $disabled_descuentos ); ?> />
                <span class="bgm-preview-precio" data-tier="1">
                    <?php if ( $desc_1 !== '' && $precio_base > 0 ) : ?>
                        → <?php esc_html_e( 'Precio final', 'beautygirlmg-mayorista' ); ?>: <strong><?php echo wc_price( max( 0, $precio_base - floatval( $desc_1 ) ) ); ?></strong>
                    <?php endif; ?>
                </span>
            </p>
        </div>

        <!-- ─── Nivel 2 ──────────────────────────────────────────────────── -->
        <div class="options_group bgm-grupo bgm-grupo-tier" data-tier="2">
            <h4 class="bgm-tier-titulo"><?php esc_html_e( 'Nivel 2 — mayoreo grande (opcional)', 'beautygirlmg-mayorista' ); ?></h4>

            <p class="form-field bgm-field-row">
                <label for="_bgm_min_2"><?php esc_html_e( 'Cantidad mínima', 'beautygirlmg-mayorista' ); ?></label>
                <input type="number"
                       id="_bgm_min_2"
                       name="_bgm_min_2"
                       value="<?php echo esc_attr( $min_2 ); ?>"
                       placeholder="<?php echo esc_attr( $min_global_2 ); ?>"
                       min="1" step="1"
                       <?php disabled( $disabled_descuentos ); ?> />
                <span class="description"><?php echo esc_html( sprintf( __( 'Vacío = usa default global (%d)', 'beautygirlmg-mayorista' ), $min_global_2 ) ); ?></span>
            </p>

            <p class="form-field bgm-field-row">
                <label for="_bgm_descuento_2"><?php esc_html_e( 'Descuento $', 'beautygirlmg-mayorista' ); ?></label>
                <input type="number"
                       id="_bgm_descuento_2"
                       name="_bgm_descuento_2"
                       class="bgm-input-descuento"
                       data-tier="2"
                       value="<?php echo esc_attr( $desc_2 ); ?>"
                       min="0" step="1"
                       <?php disabled( $disabled_descuentos ); ?> />
                <span class="bgm-preview-precio" data-tier="2">
                    <?php if ( $desc_2 !== '' && $precio_base > 0 ) : ?>
                        → <?php esc_html_e( 'Precio final', 'beautygirlmg-mayorista' ); ?>: <strong><?php echo wc_price( max( 0, $precio_base - floatval( $desc_2 ) ) ); ?></strong>
                    <?php endif; ?>
                </span>
            </p>
        </div>

        <!-- ─── Promo minorista ──────────────────────────────────────────── -->
        <?php
        $promo_modo         = get_post_meta( $post->ID, '_bgm_promo_modo', true ); // '' | custom | excluir
        $promo_valor        = get_post_meta( $post->ID, '_bgm_promo_valor', true );
        $promo_tipo_global  = bgm_get_setting( 'bgm_promo_tipo', 'porcentaje' );
        $promo_valor_global = (float) bgm_get_setting( 'bgm_promo_valor', 0 );
        $es_monto           = ( $promo_tipo_global === 'monto' );
        $sym                = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
        $global_legible     = $es_monto ? ( $sym . number_format_i18n( $promo_valor_global ) ) : ( (float) $promo_valor_global . '%' );

        // Badge de estado efectivo: la validación "qué le pasa a ESTE producto".
        if ( $promo_modo === 'excluir' ) {
            $badge_clase = 'bgm-badge-estatico';
            $badge_texto = __( 'Excluido', 'beautygirlmg-mayorista' );
            $badge_desc  = __( 'No recibe la promo, aunque su categoría esté en promo.', 'beautygirlmg-mayorista' );
        } elseif ( $promo_modo === 'custom' ) {
            $val_efectivo   = ( $promo_valor !== '' && is_numeric( $promo_valor ) ) ? (float) $promo_valor : $promo_valor_global;
            $custom_legible = $es_monto ? ( $sym . number_format_i18n( $val_efectivo ) ) : ( $val_efectivo . '%' );
            $badge_clase = 'bgm-badge-estatico';
            $badge_texto = __( 'Personalizado', 'beautygirlmg-mayorista' );
            $badge_desc  = sprintf( __( 'Usa su propio valor (%s), no el global.', 'beautygirlmg-mayorista' ), $custom_legible );
        } elseif ( bgm_producto_en_promo( $post->ID ) ) {
            $badge_clase = 'bgm-badge-global';
            $badge_texto = __( 'Global', 'beautygirlmg-mayorista' );
            $badge_desc  = sprintf( __( 'Está en una categoría en promo: usa el valor global (%s).', 'beautygirlmg-mayorista' ), $global_legible );
        } else {
            $badge_clase = 'bgm-badge-global';
            $badge_texto = __( 'Sin promo', 'beautygirlmg-mayorista' );
            $badge_desc  = __( 'No está en ninguna categoría en promo; no recibe descuento promocional.', 'beautygirlmg-mayorista' );
        }
        ?>
        <div class="options_group bgm-grupo bgm-grupo-promo">
            <h4 class="bgm-tier-titulo"><?php esc_html_e( 'Promo minorista', 'beautygirlmg-mayorista' ); ?></h4>

            <p class="form-field bgm-field-row">
                <label for="_bgm_promo_modo"><?php esc_html_e( 'Promo para este producto', 'beautygirlmg-mayorista' ); ?></label>
                <select id="_bgm_promo_modo" name="_bgm_promo_modo">
                    <option value=""        <?php selected( $promo_modo, '' ); ?>><?php esc_html_e( 'Heredar global (según categorías)', 'beautygirlmg-mayorista' ); ?></option>
                    <option value="custom"  <?php selected( $promo_modo, 'custom' ); ?>><?php esc_html_e( 'Personalizado (valor propio)', 'beautygirlmg-mayorista' ); ?></option>
                    <option value="excluir" <?php selected( $promo_modo, 'excluir' ); ?>><?php esc_html_e( 'Excluir (nunca recibe la promo)', 'beautygirlmg-mayorista' ); ?></option>
                </select>
                <span class="bgm-max-info">
                    <span class="bgm-badge-modo <?php echo esc_attr( $badge_clase ); ?>"><?php echo esc_html( $badge_texto ); ?></span>
                    <?php echo esc_html( $badge_desc ); ?>
                </span>
            </p>

            <p class="form-field bgm-field-row bgm-promo-valor-row" id="bgm-promo-valor-row" style="<?php echo $promo_modo === 'custom' ? '' : 'display:none;'; ?>">
                <label for="_bgm_promo_valor">
                    <?php echo $es_monto ? esc_html__( 'Descuento promo $', 'beautygirlmg-mayorista' ) : esc_html__( 'Descuento promo %', 'beautygirlmg-mayorista' ); ?>
                </label>
                <input type="number"
                       id="_bgm_promo_valor"
                       name="_bgm_promo_valor"
                       class="bgm-input-promo"
                       value="<?php echo esc_attr( $promo_valor ); ?>"
                       placeholder="<?php echo esc_attr( $promo_valor_global ); ?>"
                       min="0" step="1" <?php echo $es_monto ? '' : 'max="100"'; ?> />
                <span class="bgm-preview-precio bgm-preview-promo"></span>
            </p>

            <p class="description bgm-promo-help">
                <?php esc_html_e( 'Heredar = usa el global si el producto está en una categoría en promo. Personalizado = define su propio descuento abajo. Excluir = lo deja fuera aunque su categoría esté en promo. El tipo (% o monto) se define en Ajustes globales.', 'beautygirlmg-mayorista' ); ?>
            </p>
        </div>

        <?php if ( $es_variable ) : ?>
        <!-- ─── Tolerancia de diferencia (solo variables) ────────────────── -->
        <?php
        $tolerancia_producto = get_post_meta( $post->ID, '_bgm_tolerancia_porcentaje', true );
        $tolerancia_global   = absint( bgm_get_setting( 'bgm_tolerancia_porcentaje', defined( 'BGM_DEFAULT_TOLERANCIA' ) ? BGM_DEFAULT_TOLERANCIA : 15 ) );
        $tolerancia_aplicada = bgm_get_tolerancia_porcentaje( $post->ID );
        ?>
        <div class="options_group bgm-grupo bgm-grupo-tolerancia">
            <h4 class="bgm-tier-titulo"><?php esc_html_e( 'Tolerancia del surtido', 'beautygirlmg-mayorista' ); ?></h4>

            <p class="form-field bgm-field-row">
                <label for="_bgm_tolerancia_porcentaje"><?php esc_html_e( 'Tolerancia (%)', 'beautygirlmg-mayorista' ); ?></label>
                <input type="number"
                       id="_bgm_tolerancia_porcentaje"
                       name="_bgm_tolerancia_porcentaje"
                       value="<?php echo esc_attr( $tolerancia_producto ); ?>"
                       placeholder="<?php echo esc_attr( $tolerancia_global ); ?>"
                       min="1" max="100" step="1" />
                <span class="bgm-max-info">
                    <?php if ( $tolerancia_producto !== '' ) : ?>
                        <span class="bgm-badge-modo bgm-badge-estatico"><?php esc_html_e( 'Override', 'beautygirlmg-mayorista' ); ?></span>
                        <?php printf( esc_html__( 'Este producto: %d%%', 'beautygirlmg-mayorista' ), absint( $tolerancia_producto ) ); ?>
                    <?php else : ?>
                        <span class="bgm-badge-modo bgm-badge-global"><?php esc_html_e( 'Global', 'beautygirlmg-mayorista' ); ?></span>
                        <?php printf( esc_html__( 'Aplicado: %d%% (default global)', 'beautygirlmg-mayorista' ), $tolerancia_global ); ?>
                    <?php endif; ?>
                </span>
                <span class="description bgm-max-help">
                    <?php esc_html_e( 'Diferencia máxima entre la variación con más y la con menos unidades en el pedido (como % del total). Vacío = usa el default global. Para mayorista el cliente debe distribuir entre variaciones de forma balanceada.', 'beautygirlmg-mayorista' ); ?>
                </span>
            </p>
        </div>
        <?php endif; ?>

        <!-- ─── Tabla resumen ────────────────────────────────────────────── -->
        <div class="options_group bgm-grupo bgm-grupo-resumen">
            <h4 class="bgm-tier-titulo"><?php esc_html_e( 'Resumen', 'beautygirlmg-mayorista' ); ?></h4>
            <?php bgm_render_tabla_resumen( $post->ID, $precio_base ); ?>
        </div>

    </div>
    <?php
}

// ─── Render de la tabla resumen comparativa ──────────────────────────────────
function bgm_render_tabla_resumen( $product_id, $precio_base ) {
    $min_1     = bgm_get_min_1( $product_id );
    $min_2     = bgm_get_min_2( $product_id );
    $desc_1    = bgm_get_descuento_1( $product_id );
    $desc_2    = bgm_get_descuento_2( $product_id );

    if ( $precio_base <= 0 ) {
        echo '<p class="bgm-sin-precio">' . esc_html__( 'Establece el precio regular del producto para ver el resumen.', 'beautygirlmg-mayorista' ) . '</p>';
        return;
    }
    ?>
    <table class="bgm-tabla-resumen widefat" id="bgm-tabla-resumen">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Modo', 'beautygirlmg-mayorista' ); ?></th>
                <th><?php esc_html_e( 'Desde', 'beautygirlmg-mayorista' ); ?></th>
                <th><?php esc_html_e( 'Precio unitario', 'beautygirlmg-mayorista' ); ?></th>
                <th><?php esc_html_e( 'Ahorro', 'beautygirlmg-mayorista' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr class="bgm-fila-detalle">
                <td><?php esc_html_e( 'Detalle', 'beautygirlmg-mayorista' ); ?></td>
                <td>1 ud.</td>
                <td><strong><?php echo wc_price( $precio_base ); ?></strong></td>
                <td>—</td>
            </tr>

            <tr class="bgm-fila-tier1 <?php echo $desc_1 > 0 ? '' : 'bgm-tier-inactivo'; ?>">
                <td><?php esc_html_e( 'Mayorista 1', 'beautygirlmg-mayorista' ); ?></td>
                <td><?php echo esc_html( $min_1 ); ?> ud.</td>
                <td>
                    <?php if ( $desc_1 > 0 ) : ?>
                        <strong><?php echo wc_price( max( 0, $precio_base - $desc_1 ) ); ?></strong>
                    <?php else : ?>
                        <span class="bgm-no-config"><?php esc_html_e( 'no configurado', 'beautygirlmg-mayorista' ); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ( $desc_1 > 0 ) : ?>
                        <?php echo wc_price( $desc_1 ); ?>
                        <span class="bgm-pct">(<?php echo esc_html( round( ( $desc_1 / $precio_base ) * 100, 1 ) ); ?>%)</span>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>

            <tr class="bgm-fila-tier2 <?php echo $desc_2 > 0 ? '' : 'bgm-tier-inactivo'; ?>">
                <td><?php esc_html_e( 'Mayorista 2', 'beautygirlmg-mayorista' ); ?></td>
                <td><?php echo esc_html( $min_2 ); ?> ud.</td>
                <td>
                    <?php if ( $desc_2 > 0 ) : ?>
                        <strong><?php echo wc_price( max( 0, $precio_base - $desc_2 ) ); ?></strong>
                    <?php else : ?>
                        <span class="bgm-no-config"><?php esc_html_e( 'no configurado', 'beautygirlmg-mayorista' ); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ( $desc_2 > 0 ) : ?>
                        <?php echo wc_price( $desc_2 ); ?>
                        <span class="bgm-pct">(<?php echo esc_html( round( ( $desc_2 / $precio_base ) * 100, 1 ) ); ?>%)</span>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

// ─── Guardar campos al guardar producto ──────────────────────────────────────
add_action( 'woocommerce_process_product_meta', 'bgm_guardar_panel_mayorista' );
function bgm_guardar_panel_mayorista( $post_id ) {
    if ( ! isset( $_POST['bgm_panel_nonce'] ) ) return;
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bgm_panel_nonce'] ) ), 'bgm_guardar_panel' ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_product', $post_id ) ) return;

    $product = wc_get_product( $post_id );
    if ( ! $product ) return;

    // Modo descuento (solo variables)
    if ( $product->is_type( 'variable' ) && isset( $_POST['_bgm_modo_descuento'] ) ) {
        $modo_raw = sanitize_key( wp_unslash( $_POST['_bgm_modo_descuento'] ) );
        $modo     = $modo_raw === 'individual' ? 'individual' : 'unico';
        update_post_meta( $post_id, '_bgm_modo_descuento', $modo );
    }

    // Selector visual de variaciones (solo variables)
    // Activo por defecto. Guardamos siempre '0' o '1' para distinguir "decisión
    // explícita de desactivar" de "nunca tocado". El helper bgm_usar_swatches()
    // trata el meta vacío como default true.
    if ( $product->is_type( 'variable' ) ) {
        $valor = ( isset( $_POST['_bgm_usar_swatches'] ) && $_POST['_bgm_usar_swatches'] === '1' ) ? '1' : '0';
        update_post_meta( $post_id, '_bgm_usar_swatches', $valor );
    }

    // Modo de promo por producto: '' (heredar) | custom | excluir
    if ( isset( $_POST['_bgm_promo_modo'] ) ) {
        $promo_modo = sanitize_key( wp_unslash( $_POST['_bgm_promo_modo'] ) );
        if ( $promo_modo === 'custom' || $promo_modo === 'excluir' ) {
            update_post_meta( $post_id, '_bgm_promo_modo', $promo_modo );
        } else {
            delete_post_meta( $post_id, '_bgm_promo_modo' ); // heredar = sin meta
        }
    }

    // Campos numéricos: vacío = borrar meta (usa default global)
    $campos = [ '_bgm_min_1', '_bgm_descuento_1', '_bgm_min_2', '_bgm_descuento_2', '_bgm_tolerancia_porcentaje', '_bgm_promo_valor' ];

    foreach ( $campos as $campo ) {
        if ( ! isset( $_POST[ $campo ] ) ) continue;

        $valor = wc_clean( wp_unslash( $_POST[ $campo ] ) );

        if ( $valor === '' ) {
            delete_post_meta( $post_id, $campo );
        } elseif ( is_numeric( $valor ) && floatval( $valor ) >= 0 ) {
            update_post_meta( $post_id, $campo, floatval( $valor ) );
        }
    }

    bgm_log( 'admin', 'Panel mayorista guardado', [
        'product_id' => $post_id,
        'tipo'       => $product->get_type(),
    ] );
}
