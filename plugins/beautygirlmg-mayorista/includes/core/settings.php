<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: AJUSTES GLOBALES
 *
 * Pantalla en WooCommerce → Ajustes → Mayorista
 *
 * La clase BGM_Settings_Page se define DENTRO del callback
 * del filtro, garantizando que WC_Settings_Page ya existe
 * en el momento exacto en que se necesita.
 * =========================================================
 */

add_filter( 'woocommerce_get_settings_pages', 'bgm_registrar_pagina_settings' );
function bgm_registrar_pagina_settings( $pages ) {
    if ( ! class_exists( 'WC_Settings_Page' ) ) return $pages;

    if ( ! class_exists( 'BGM_Settings_Page' ) ) {

        class BGM_Settings_Page extends WC_Settings_Page {

            public function __construct() {
                $this->id    = 'bgm_mayorista';
                $this->label = __( 'Mayorista', 'beautygirlmg-mayorista' );
                parent::__construct();
            }

            public function get_settings() {
                // Opciones de categorías para el multiselect de la promo
                $cat_options = [];
                $terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
                if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
                    foreach ( $terms as $t ) { $cat_options[ $t->term_id ] = $t->name; }
                }

                // Resumen de productos afectados por la promo (contador + solapamiento)
                $af = function_exists( 'bgm_promo_contar_afectados' )
                    ? bgm_promo_contar_afectados()
                    : [ 'total' => 0, 'por_categoria' => 0, 'personalizados' => 0, 'excluidos' => 0, 'cat_con_custom' => 0, 'cat_con_excluir' => 0 ];

                $resumen_desc = sprintf(
                    /* translators: 1: total, 2: por categoría, 3: personalizados, 4: excluidos */
                    __( '<strong>%1$d productos</strong> recibirían esta promo (categorías ∪ personalizados − excluidos).<br>Detalle: %2$d por categoría · %3$d personalizados · %4$d excluidos.', 'beautygirlmg-mayorista' ),
                    (int) $af['total'], (int) $af['por_categoria'], (int) $af['personalizados'], (int) $af['excluidos']
                );
                if ( $af['cat_con_custom'] > 0 || $af['cat_con_excluir'] > 0 ) {
                    $resumen_desc .= '<br>⚠️ ' . sprintf(
                        /* translators: 1: con valor propio, 2: excluidos */
                        __( 'Solapamiento: %1$d productos de las categorías usan su <strong>valor personalizado</strong> y %2$d están <strong>excluidos</strong> (el ajuste del producto manda sobre el global).', 'beautygirlmg-mayorista' ),
                        (int) $af['cat_con_custom'], (int) $af['cat_con_excluir']
                    );
                }

                return [
                    // ─── Defaults globales ───────────────────────────
                    [
                        'title' => __( 'Defaults globales', 'beautygirlmg-mayorista' ),
                        'type'  => 'title',
                        'desc'  => __( 'Valores por defecto que se usan cuando un producto no tiene configuración propia.', 'beautygirlmg-mayorista' ),
                        'id'    => 'bgm_section_defaults',
                    ],
                    [
                        'title'             => __( 'Mínimo nivel 1', 'beautygirlmg-mayorista' ),
                        'desc'              => __( 'Cantidad mínima para precio mayorista nivel 1.', 'beautygirlmg-mayorista' ),
                        'id'                => 'bgm_min_global_1',
                        'type'              => 'number',
                        'default'           => defined( 'BGM_DEFAULT_MIN_1' ) ? BGM_DEFAULT_MIN_1 : 3,
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'min' => '1', 'step' => '1' ],
                    ],
                    [
                        'title'             => __( 'Mínimo nivel 2', 'beautygirlmg-mayorista' ),
                        'desc'              => __( 'Cantidad mínima para precio mayorista nivel 2.', 'beautygirlmg-mayorista' ),
                        'id'                => 'bgm_min_global_2',
                        'type'              => 'number',
                        'default'           => defined( 'BGM_DEFAULT_MIN_2' ) ? BGM_DEFAULT_MIN_2 : 12,
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'min' => '1', 'step' => '1' ],
                    ],
                    [
                        'title'             => __( 'Tolerancia de diferencia (%)', 'beautygirlmg-mayorista' ),
                        'desc'              => __( 'Diferencia máxima permitida entre variaciones del pedido (como % del total). Default 15%. Por ejemplo, si el cliente pide 12 unidades y la tolerancia es 15%, la diferencia entre la variación con más y la con menos no puede superar 2 unidades.', 'beautygirlmg-mayorista' ),
                        'id'                => 'bgm_tolerancia_porcentaje',
                        'type'              => 'number',
                        'default'           => defined( 'BGM_DEFAULT_TOLERANCIA' ) ? BGM_DEFAULT_TOLERANCIA : 15,
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'min' => '1', 'max' => '100', 'step' => '1' ],
                    ],
                    [ 'type' => 'sectionend', 'id' => 'bgm_section_defaults' ],

                    // ─── Modo de surtido ─────────────────────────────
                    [
                        'title' => __( 'Modo de surtido para variables', 'beautygirlmg-mayorista' ),
                        'type'  => 'title',
                        'desc'  => __( 'Qué interfaz ve el cliente para armar pedidos mayoristas de productos con variaciones.', 'beautygirlmg-mayorista' ),
                        'id'    => 'bgm_section_modo',
                    ],
                    [
                        'title'    => __( 'Modo activo', 'beautygirlmg-mayorista' ),
                        'desc'     => __( 'Sorpréndeme = el sistema reparte. Manual = el cliente arma. Ambos = el cliente decide.', 'beautygirlmg-mayorista' ),
                        'id'       => 'bgm_modo_surtido',
                        'type'     => 'select',
                        'default'  => defined( 'BGM_DEFAULT_MODO_SURTIDO' ) ? BGM_DEFAULT_MODO_SURTIDO : 'ambos',
                        'desc_tip' => true,
                        'options'  => [
                            'auto'   => __( 'Solo automático (Sorpréndeme)', 'beautygirlmg-mayorista' ),
                            'manual' => __( 'Solo manual (cliente arma)', 'beautygirlmg-mayorista' ),
                            'ambos'  => __( 'Ambos (cliente decide)', 'beautygirlmg-mayorista' ),
                        ],
                    ],
                    [ 'type' => 'sectionend', 'id' => 'bgm_section_modo' ],

                    // ─── Etiqueta de oferta (badge de precio rebajado) ───
                    [
                        'title' => __( 'Etiqueta de oferta', 'beautygirlmg-mayorista' ),
                        'type'  => 'title',
                        'desc'  => __( 'Texto del badge que se muestra en los productos con precio rebajado (oferta nativa de WooCommerce). Reemplaza el texto fijo "Oferta" en todo el sitio: tarjetas, listados y sobre la imagen del producto (donde además aparece el % de descuento).', 'beautygirlmg-mayorista' ),
                        'id'    => 'bgm_section_oferta',
                    ],
                    [
                        'title'             => __( 'Nombre del badge de oferta', 'beautygirlmg-mayorista' ),
                        'desc'              => __( 'Ejemplos: Oferta, Cyber, Liquidación, Black Friday. Se muestra en productos rebajados. Vacío = "Oferta".', 'beautygirlmg-mayorista' ),
                        'id'                => 'bgm_oferta_etiqueta',
                        'type'              => 'text',
                        'default'           => 'Oferta',
                        'desc_tip'          => true,
                        'css'               => 'min-width:260px;',
                        'custom_attributes' => [ 'maxlength' => '30' ],
                    ],
                    [ 'type' => 'sectionend', 'id' => 'bgm_section_oferta' ],

                    // ─── Descuento promocional minorista ─────────────
                    [
                        'title' => __( 'Descuento promocional minorista', 'beautygirlmg-mayorista' ),
                        'type'  => 'title',
                        'desc'  => __( 'Descuento por ocasión especial (ej. Cyber) para compras al detalle. Solo aplica BAJO el umbral mayorista: si el cliente alcanza la cantidad mayorista, gana el precio mayorista (no se suman). Estos valores son el DEFAULT global; cada producto puede heredarlo, personalizarlo o excluirse desde su pestaña Mayorista. Aplica a productos simples y variables.', 'beautygirlmg-mayorista' ),
                        'id'    => 'bgm_section_promo',
                    ],
                    [
                        'title'   => __( 'Activar promoción', 'beautygirlmg-mayorista' ),
                        'desc'    => __( 'Interruptor maestro. Además debe estar dentro del rango de fechas para aplicar.', 'beautygirlmg-mayorista' ),
                        'id'      => 'bgm_promo_activa',
                        'type'    => 'checkbox',
                        'default' => 'no',
                    ],
                    [
                        'title'    => __( 'Fecha de inicio', 'beautygirlmg-mayorista' ),
                        'desc'     => __( 'Desde las 00:00 de este día (zona horaria del sitio). Vacío = sin límite inferior.', 'beautygirlmg-mayorista' ),
                        'id'       => 'bgm_promo_fecha_inicio',
                        'type'     => 'date',
                        'desc_tip' => true,
                    ],
                    [
                        'title'    => __( 'Fecha de fin', 'beautygirlmg-mayorista' ),
                        'desc'     => __( 'Hasta las 23:59 de este día, inclusive. Vacío = sin límite superior.', 'beautygirlmg-mayorista' ),
                        'id'       => 'bgm_promo_fecha_fin',
                        'type'     => 'date',
                        'desc_tip' => true,
                    ],
                    [
                        'title'    => __( 'Tipo de descuento', 'beautygirlmg-mayorista' ),
                        'desc'     => __( 'Siempre se calcula sobre el precio normal del producto.', 'beautygirlmg-mayorista' ),
                        'id'       => 'bgm_promo_tipo',
                        'type'     => 'select',
                        'default'  => 'porcentaje',
                        'desc_tip' => true,
                        'options'  => [
                            'porcentaje' => __( 'Porcentaje (%)', 'beautygirlmg-mayorista' ),
                            'monto'      => __( 'Monto fijo ($)', 'beautygirlmg-mayorista' ),
                        ],
                    ],
                    [
                        'title'             => __( 'Valor del descuento (global)', 'beautygirlmg-mayorista' ),
                        'desc'              => __( 'Valor por defecto. Porcentaje: 1–100. Monto fijo: pesos a descontar por unidad. Cada producto puede sobreescribirlo (modo Personalizado en su pestaña Mayorista).', 'beautygirlmg-mayorista' ),
                        'id'                => 'bgm_promo_valor',
                        'type'              => 'number',
                        'default'           => 0,
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'min' => '0', 'step' => '1' ],
                    ],
                    [
                        'title'             => __( 'Cantidad mínima', 'beautygirlmg-mayorista' ),
                        'desc'              => __( 'Cantidad mínima del ítem para aplicar la promo. Default 1.', 'beautygirlmg-mayorista' ),
                        'id'                => 'bgm_promo_qty_min',
                        'type'              => 'number',
                        'default'           => 1,
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'min' => '1', 'step' => '1' ],
                    ],
                    [
                        'title'             => __( 'Cantidad máxima', 'beautygirlmg-mayorista' ),
                        'desc'              => __( 'Tope de unidades para la promo, sumando variaciones en productos variables. Es un descuento al detalle (recomendado: 2). 0 = sin límite.', 'beautygirlmg-mayorista' ),
                        'id'                => 'bgm_promo_qty_max',
                        'type'              => 'number',
                        'default'           => 2,
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'min' => '0', 'step' => '1' ],
                    ],
                    [
                        'title'    => __( 'Categorías en promo', 'beautygirlmg-mayorista' ),
                        'desc'     => __( 'Todos los productos de estas categorías entran en la promo.', 'beautygirlmg-mayorista' ),
                        'id'       => 'bgm_promo_categorias',
                        'type'     => 'multiselect',
                        'class'    => 'wc-enhanced-select',
                        'options'  => $cat_options,
                        'desc_tip' => true,
                    ],
                    [ 'type' => 'sectionend', 'id' => 'bgm_section_promo' ],

                    // ─── Resumen de la promo (solo lectura) ──────────
                    [
                        'title' => __( 'Resumen de la promo', 'beautygirlmg-mayorista' ),
                        'type'  => 'title',
                        'desc'  => $resumen_desc,
                        'id'    => 'bgm_section_promo_resumen',
                    ],
                    [ 'type' => 'sectionend', 'id' => 'bgm_section_promo_resumen' ],

                    // ─── Debug ───────────────────────────────────────
                    [
                        'title' => __( 'Debug', 'beautygirlmg-mayorista' ),
                        'type'  => 'title',
                        'desc'  => __( 'Activa logs para diagnosticar comportamiento. Mantener apagado en producción.', 'beautygirlmg-mayorista' ),
                        'id'    => 'bgm_section_debug',
                    ],
                    [
                        'title'   => __( 'Activar registro de logs', 'beautygirlmg-mayorista' ),
                        'desc'    => __( 'Escribe operaciones a un archivo en uploads/bgm-logs/.', 'beautygirlmg-mayorista' ),
                        'id'      => 'bgm_debug_activo',
                        'type'    => 'checkbox',
                        'default' => '0',
                    ],
                    [ 'type' => 'sectionend', 'id' => 'bgm_section_debug' ],
                ];
            }

            // Override del output: agrega panel de logs debajo de los campos
            public function output() {
                $settings = $this->get_settings();
                WC_Admin_Settings::output_fields( $settings );

                if ( function_exists( 'bgm_logger_file' ) ) {
                    $this->render_panel_debug();
                }
            }

            private function render_panel_debug() {
                $tamano = bgm_logger_tamano_kb();
                $ruta   = str_replace( ABSPATH, '/', bgm_logger_file() );

                $url_ver    = wp_nonce_url( add_query_arg( 'bgm_action', 'ver_logs' ),    'bgm_action' );
                $url_vaciar = wp_nonce_url( add_query_arg( 'bgm_action', 'vaciar_logs' ), 'bgm_action' );

                $mostrar = isset( $_GET['bgm_action'] )
                    && sanitize_key( wp_unslash( $_GET['bgm_action'] ) ) === 'ver_logs'
                    && isset( $_GET['_wpnonce'] )
                    && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bgm_action' );
                ?>
                <div class="bgm-panel-debug">
                    <h3><?php esc_html_e( 'Visor de logs', 'beautygirlmg-mayorista' ); ?></h3>
                    <p>
                        <strong><?php esc_html_e( 'Archivo:', 'beautygirlmg-mayorista' ); ?></strong>
                        <code><?php echo esc_html( $ruta ); ?></code>
                        (<?php echo esc_html( $tamano ); ?> KB)
                    </p>
                    <p>
                        <a href="<?php echo esc_url( $url_ver );    ?>" class="button"><?php esc_html_e( 'Ver logs',    'beautygirlmg-mayorista' ); ?></a>
                        <a href="<?php echo esc_url( $url_vaciar ); ?>" class="button" onclick="return confirm('<?php esc_attr_e( '¿Vaciar el archivo de logs?', 'beautygirlmg-mayorista' ); ?>');">
                            <?php esc_html_e( 'Vaciar logs', 'beautygirlmg-mayorista' ); ?>
                        </a>
                    </p>
                    <?php if ( $mostrar ) :
                        $contenido = bgm_logger_leer( 200 ); ?>
                        <div class="bgm-logs-viewer">
                            <?php echo $contenido !== '' ? esc_html( $contenido ) : esc_html__( '(archivo vacío)', 'beautygirlmg-mayorista' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
    }

    $pages[] = new BGM_Settings_Page();
    return $pages;
}

// ─── Manejar acción "vaciar logs" ────────────────────────────────────────────
add_action( 'admin_init', 'bgm_manejar_acciones_debug' );
function bgm_manejar_acciones_debug() {
    if ( ! isset( $_GET['bgm_action'] ) ) return;
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;
    if ( ! isset( $_GET['_wpnonce'] ) ) return;
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bgm_action' ) ) return;

    $action = sanitize_key( wp_unslash( $_GET['bgm_action'] ) );

    if ( $action === 'vaciar_logs' ) {
        bgm_logger_vaciar();
        $url = remove_query_arg( [ 'bgm_action', '_wpnonce' ] );
        $url = add_query_arg( 'bgm_logs_vaciados', '1', $url );
        wp_safe_redirect( $url );
        exit;
    }
}

// ─── Aviso "logs vaciados" ───────────────────────────────────────────────────
add_action( 'admin_notices', 'bgm_aviso_logs_vaciados' );
function bgm_aviso_logs_vaciados() {
    if ( ! isset( $_GET['bgm_logs_vaciados'] ) ) return;
    if ( sanitize_key( wp_unslash( $_GET['bgm_logs_vaciados'] ) ) !== '1' ) return;
    echo '<div class="notice notice-success is-dismissible"><p>' .
        esc_html__( 'Logs vaciados.', 'beautygirlmg-mayorista' ) .
        '</p></div>';
}

// ─── Estilos del visor (encola admin.css solo en la pestaña Mayorista) ──────
add_action( 'admin_enqueue_scripts', 'bgm_enqueue_estilos_pestana_mayorista' );
function bgm_enqueue_estilos_pestana_mayorista( $hook ) {
    if ( $hook !== 'woocommerce_page_wc-settings' ) return;
    if ( ! isset( $_GET['tab'] ) || sanitize_key( wp_unslash( $_GET['tab'] ) ) !== 'bgm_mayorista' ) return;

    wp_enqueue_style(
        'bgm-admin',
        BGM_URL . 'assets/admin.css',
        [],
        BGM_VERSION
    );
}
