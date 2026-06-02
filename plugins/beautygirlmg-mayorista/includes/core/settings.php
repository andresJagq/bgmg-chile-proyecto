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
