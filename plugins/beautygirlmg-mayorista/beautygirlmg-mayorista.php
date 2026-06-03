<?php
/**
 * Plugin Name: BeautyGirlMG Mayorista v2
 * Description: Sistema de precios mayorista con tiered pricing (2 niveles) y surtido automático/manual para beautygirlmg.cl
 * Version: 2.6.0
 * Author: BeautyGirlMG
 * Text Domain: beautygirlmg-mayorista
 * Requires WooCommerce: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Constantes globales ─────────────────────────────────────────────────────
define( 'BGM_VERSION',  '2.6.0' );
define( 'BGM_PATH',     plugin_dir_path( __FILE__ ) );
define( 'BGM_URL',      plugin_dir_url( __FILE__ ) );
define( 'BGM_BASENAME', plugin_basename( __FILE__ ) );

// ─── Defaults globales (usados solo si no hay opción guardada) ───────────────
define( 'BGM_DEFAULT_MIN_1',           3 );
define( 'BGM_DEFAULT_MIN_2',           12 );
define( 'BGM_DEFAULT_TOLERANCIA',      15 ); // % de diferencia máxima entre variaciones
define( 'BGM_DEFAULT_MODO_SURTIDO',    'ambos' );  // auto | manual | ambos
define( 'BGM_DEFAULT_DEBUG',           '0' );

// ─── Núcleo (siempre activo) ─────────────────────────────────────────────────
require_once BGM_PATH . 'includes/core/logger.php';
require_once BGM_PATH . 'includes/core/helpers.php';
require_once BGM_PATH . 'includes/core/promo.php';
require_once BGM_PATH . 'includes/core/ajax-helpers.php';
require_once BGM_PATH . 'includes/core/settings.php';

// ─── Módulos admin (solo en backoffice) ──────────────────────────────────────
if ( is_admin() ) {
    require_once BGM_PATH . 'includes/admin/pestana-mayorista.php';
    require_once BGM_PATH . 'includes/admin/editor-variaciones.php';
}

// ─── Frontend (siempre cargado para que el carrito funcione en AJAX/admin) ──
require_once BGM_PATH . 'includes/frontend/producto-simple.php';
require_once BGM_PATH . 'includes/frontend/producto-variable.php';
require_once BGM_PATH . 'includes/frontend/carrito.php';
require_once BGM_PATH . 'includes/frontend/swatches.php';
require_once BGM_PATH . 'includes/frontend/avisos-carrito.php';

// ─── Modos de surtido (cargados según ajuste global) ─────────────────────────
add_action( 'plugins_loaded', 'bgm_cargar_modos_surtido', 20 );
function bgm_cargar_modos_surtido() {
    $modo = bgm_get_modo_surtido();

    if ( $modo === 'auto' || $modo === 'ambos' ) {
        require_once BGM_PATH . 'includes/modos/modo-auto.php';
        require_once BGM_PATH . 'includes/ajax/ajax-auto.php';
    }

    if ( $modo === 'manual' || $modo === 'ambos' ) {
        require_once BGM_PATH . 'includes/modos/modo-manual.php';
        require_once BGM_PATH . 'includes/ajax/ajax-manual.php';
        require_once BGM_PATH . 'includes/ajax/ajax-evaluar.php';
    }
}

// ─── Activación: crear opciones default ──────────────────────────────────────
register_activation_hook( __FILE__, 'bgm_activar_plugin' );
function bgm_activar_plugin() {
    add_option( 'bgm_min_global_1',             BGM_DEFAULT_MIN_1 );
    add_option( 'bgm_min_global_2',             BGM_DEFAULT_MIN_2 );
    add_option( 'bgm_tolerancia_porcentaje',    BGM_DEFAULT_TOLERANCIA );
    add_option( 'bgm_modo_surtido',             BGM_DEFAULT_MODO_SURTIDO );
    add_option( 'bgm_debug_activo',             BGM_DEFAULT_DEBUG );

    // Promo minorista (apagada por defecto)
    add_option( 'bgm_promo_activa',             'no' );
    add_option( 'bgm_promo_tipo',               'porcentaje' );
    add_option( 'bgm_promo_valor',              0 );
    add_option( 'bgm_promo_qty_min',            1 );
    add_option( 'bgm_promo_qty_max',            2 );

    bgm_logger_crear_directorio();
    bgm_log( 'core', 'Plugin activado', [ 'version' => BGM_VERSION ] );
}

// ─── Desactivación: solo log, no borra datos ─────────────────────────────────
register_deactivation_hook( __FILE__, 'bgm_desactivar_plugin' );
function bgm_desactivar_plugin() {
    bgm_log( 'core', 'Plugin desactivado', [ 'version' => BGM_VERSION ] );
}

// ─── Aviso si WooCommerce no está activo ─────────────────────────────────────
add_action( 'admin_notices', 'bgm_aviso_woocommerce' );
function bgm_aviso_woocommerce() {
    if ( class_exists( 'WooCommerce' ) ) return;
    echo '<div class="notice notice-error"><p><strong>BeautyGirlMG Mayorista v2</strong> requiere WooCommerce activo.</p></div>';
}
