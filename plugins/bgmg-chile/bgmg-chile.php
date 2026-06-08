<?php
/**
 * Plugin Name:       BGMG Chile — RUT y Comunas
 * Plugin URI:        https://new.beautygirlmg.cl
 * Description:       Localización chilena para BeautyGirlMG: validación de RUT (módulo 11),
 *                    selector en cascada de regiones y comunas oficiales, método de envío
 *                    "Por pagar" y administración de tarifas fijas para la Región Metropolitana.
 * Version:           1.18.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            BeautyGirlMG
 * Author URI:        https://new.beautygirlmg.cl
 * Text Domain:       bgmg-chile
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * WC requires at least: 7.0
 * WC tested up to:      9.5
 *
 * Plugin propio. No tocar archivos de bgmg-landing ni beautygirlmg-mayorista:
 * son referencias externas, este plugin es 100% autónomo.
 */

// Salida directa: bloqueamos acceso fuera de WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Constantes globales del plugin.
 * Las usamos en cualquier submódulo de /inc/ sin recalcular rutas.
 */
define( 'BGMG_CHILE_VERSION', '1.18.3' );
define( 'BGMG_CHILE_FILE', __FILE__ );
define( 'BGMG_CHILE_DIR', plugin_dir_path( __FILE__ ) );
define( 'BGMG_CHILE_URL', plugin_dir_url( __FILE__ ) );
define( 'BGMG_CHILE_BASENAME', plugin_basename( __FILE__ ) );

/*
 * Compatibilidad con HPOS (High-Performance Order Storage) de WooCommerce.
 * Sin esta declaración WC marca el plugin como "incompatible" en wp-admin.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				BGMG_CHILE_FILE,
				true
			);
		}
	}
);

/*
 * Bootstrap diferido a plugins_loaded para asegurar que WooCommerce ya cargó.
 * Si WC no está activo dejamos un aviso en admin y abortamos limpiamente.
 */
add_action( 'plugins_loaded', 'bgmg_chile_bootstrap' );

function bgmg_chile_bootstrap() {

	// Traducciones (.mo en /languages/, dominio bgmg-chile).
	load_plugin_textdomain( 'bgmg-chile', false, dirname( BGMG_CHILE_BASENAME ) . '/languages' );

	// Si WooCommerce no está disponible, el plugin queda inactivo en silencio
	// con un aviso visible solo para administradores.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'bgmg_chile_woocommerce_missing_notice' );
		return;
	}

	// Cargador modular: cada feature en su propio archivo de /inc/.
	require_once BGMG_CHILE_DIR . 'inc/helpers.php';
	require_once BGMG_CHILE_DIR . 'inc/data/regiones-comunas.php';

	// Módulo RUT (Fase 1).
	require_once BGMG_CHILE_DIR . 'inc/rut/class-rut-validator.php';
	require_once BGMG_CHILE_DIR . 'inc/rut/checkout-fields.php';
	require_once BGMG_CHILE_DIR . 'inc/rut/account-fields.php';
	require_once BGMG_CHILE_DIR . 'inc/rut/order-display.php';
	require_once BGMG_CHILE_DIR . 'inc/rut/duplicates.php';

	// Módulo Regiones y Comunas (Fase 2).
	require_once BGMG_CHILE_DIR . 'inc/regiones/states-filter.php';
	require_once BGMG_CHILE_DIR . 'inc/regiones/checkout-cascade.php';
	require_once BGMG_CHILE_DIR . 'inc/regiones/validator.php';
	require_once BGMG_CHILE_DIR . 'inc/regiones/address-format.php';

	// Módulo Envío (Fase 3).
	require_once BGMG_CHILE_DIR . 'inc/envio/class-shipping-method.php';
	require_once BGMG_CHILE_DIR . 'inc/envio/class-shipping-retiro.php'; // v1.4.0
	require_once BGMG_CHILE_DIR . 'inc/envio/admin-tarifas-rm.php';
	require_once BGMG_CHILE_DIR . 'inc/envio/admin-despachos-menu.php'; // v1.11.0

	// Módulo Teléfono (v1.1.0): móvil chileno obligatorio en checkout.
	require_once BGMG_CHILE_DIR . 'inc/telefono/class-telefono-validator.php';
	require_once BGMG_CHILE_DIR . 'inc/telefono/checkout-fields.php';

	// Módulo Tracking de envío (v1.5.0): metabox + email custom + display.
	require_once BGMG_CHILE_DIR . 'inc/tracking/class-email-tracking.php';
	require_once BGMG_CHILE_DIR . 'inc/tracking/order-tracking.php';

	// Módulo Perfil (v1.5.0): sección Datos Chile en editor usuario WP.
	require_once BGMG_CHILE_DIR . 'inc/perfil/admin-user-fields.php';

	// Módulo Etiqueta de despacho (v1.8.0): metabox + vista imprimible.
	require_once BGMG_CHILE_DIR . 'inc/etiqueta/admin-etiqueta-despacho.php';

	// Módulo Integración (v1.10.0): helpers públicos para bgmg-landing.
	require_once BGMG_CHILE_DIR . 'inc/integracion/landing-helpers.php';

	// Módulo Checkout — emails post-pago (v1.11.3): personaliza asunto/heading
	// del email "en espera" para transferencia bancaria + inserta aviso destacado
	// alineado con el mensaje de la thank-you en on-hold y processing.
	require_once BGMG_CHILE_DIR . 'inc/checkout/email-pago-pendiente.php';

	// Módulo Wizard de envíos (v1.13.0): asistente paso a paso para configurar
	// zona, métodos, retiro, tarifas RM y comunas con retiro.
	require_once BGMG_CHILE_DIR . 'inc/wizard/wizard-envios.php';

	// Módulo Wizard de checkout (v1.14.0): pantalla informativa con estado y
	// estadísticas de los módulos RUT/factura y teléfono móvil.
	require_once BGMG_CHILE_DIR . 'inc/wizard/wizard-checkout.php';

	// Módulo Wizard de operativa (v1.15.0): tracking pendiente + etiquetas por
	// imprimir + alertas (pedidos atrasados, sin código, etc.).
	require_once BGMG_CHILE_DIR . 'inc/wizard/wizard-operativa.php';

	// Assets (frontend + admin).
	add_action( 'wp_enqueue_scripts', 'bgmg_chile_enqueue_frontend' );
	add_action( 'admin_enqueue_scripts', 'bgmg_chile_enqueue_admin' );
}

/**
 * Aviso si WooCommerce no está activo.
 */
function bgmg_chile_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	echo esc_html__(
		'BGMG Chile requiere WooCommerce activo para funcionar. Por favor instala o activa WooCommerce.',
		'bgmg-chile'
	);
	echo '</p></div>';
}

/**
 * Encola CSS/JS en frontend solo en páginas donde se usa.
 * Evitamos cargar assets globalmente: pierde Lighthouse y suma a INP.
 */
function bgmg_chile_enqueue_frontend() {

	$es_checkout = function_exists( 'is_checkout' ) && is_checkout();
	$es_cuenta   = function_exists( 'is_account_page' ) && is_account_page();

	if ( ! $es_checkout && ! $es_cuenta ) {
		return;
	}

	wp_enqueue_style(
		'bgmg-chile-frontend',
		BGMG_CHILE_URL . 'assets/css/frontend.css',
		array(),
		BGMG_CHILE_VERSION
	);

	// rut-validator.js: utilidad pura, sin dependencias.
	wp_enqueue_script(
		'bgmg-chile-rut-validator',
		BGMG_CHILE_URL . 'assets/js/rut-validator.js',
		array(),
		BGMG_CHILE_VERSION,
		true
	);

	// checkout.js y regiones-comunas.js consumen el validador y los datos.
	if ( $es_checkout ) {
		wp_enqueue_script(
			'bgmg-chile-checkout',
			BGMG_CHILE_URL . 'assets/js/checkout.js',
			array( 'bgmg-chile-rut-validator', 'jquery' ),
			BGMG_CHILE_VERSION,
			true
		);

		wp_enqueue_script(
			'bgmg-chile-regiones',
			BGMG_CHILE_URL . 'assets/js/regiones-comunas.js',
			array( 'jquery' ),
			BGMG_CHILE_VERSION,
			true
		);

		// Inyectamos el dataset comunas-por-región para la cascada.
		wp_localize_script(
			'bgmg-chile-regiones',
			'BGMG_CHILE_COMUNAS',
			array(
				'comunasPorRegion' => bgmg_chile_get_comunas_por_region(),
				'i18n'             => array(
					'seleccionaRegion' => __( 'Selecciona una región', 'bgmg-chile' ),
					'seleccionaComuna' => __( 'Selecciona una comuna', 'bgmg-chile' ),
					'primeroRegion'    => __( 'Primero elige una región', 'bgmg-chile' ),
				),
			)
		);
	}
}

/**
 * Assets de admin: solo en la pantalla de tarifas RM y en el editor de orden.
 */
function bgmg_chile_enqueue_admin( $hook ) {

	// Pantallas WP estándar (editor de orden, HPOS).
	$pantallas_validas = array(
		'post.php',                                // editor de orden (CPT shop_order)
		'post-new.php',
		'woocommerce_page_wc-orders',              // HPOS
	);

	// Pantalla de tarifas RM: el hook depende del menú padre y puede variar
	// según la versión del menú admin. Lo más confiable es chequear el slug
	// directamente vía $_GET['page'].
	$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	$es_tarifas_rm = ( 'bgmg-chile-tarifas-rm' === $current_page );

	if ( ! in_array( $hook, $pantallas_validas, true ) && ! $es_tarifas_rm ) {
		return;
	}

	wp_enqueue_style(
		'bgmg-chile-admin',
		BGMG_CHILE_URL . 'assets/css/admin.css',
		array(),
		BGMG_CHILE_VERSION
	);

	wp_enqueue_script(
		'bgmg-chile-admin',
		BGMG_CHILE_URL . 'assets/js/admin-tarifas-rm.js',
		array( 'jquery' ),
		BGMG_CHILE_VERSION,
		true
	);
}

/*
 * Hooks de ciclo de vida.
 */
register_activation_hook( __FILE__, 'bgmg_chile_on_activate' );
register_deactivation_hook( __FILE__, 'bgmg_chile_on_deactivate' );

/**
 * Schema SQL canónico de las tablas del plugin. Lo extraemos en su propia
 * función para que tanto la activación como las migraciones (al actualizar
 * el plugin sin desactivar) usen exactamente el mismo statement.
 *
 * dbDelta es idempotente:
 *   - Si la tabla no existe → la crea.
 *   - Si existe pero le faltan columnas → las añade.
 *   - Si una columna cambió de tipo → la altera.
 *
 * Por eso podemos llamar esta función a cada actualización sin riesgo de
 * romper datos existentes.
 */
function bgmg_chile_install_schema() {
	global $wpdb;

	$tabla   = $wpdb->prefix . 'bgmg_chile_tarifas_rm';
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$tabla} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		comuna_slug VARCHAR(64) NOT NULL,
		comuna_nombre VARCHAR(120) NOT NULL,
		precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
		activo TINYINT(1) NOT NULL DEFAULT 1,
		retiro_disponible TINYINT(1) NOT NULL DEFAULT 0,
		creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY comuna_slug (comuna_slug)
	) {$charset};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Activación del plugin: instala el schema, limpia rewrites y arma el redirect
 * al wizard de envíos.
 *
 * El transient lo consume bgmg_chile_wizard_envios_maybe_redirect() en el
 * siguiente admin_init. Si el plugin se activa en bulk (activate-multi) ese
 * handler se inhibe y no rompe la activación de los demás plugins.
 */
function bgmg_chile_on_activate() {
	bgmg_chile_install_schema();
	update_option( 'bgmg_chile_db_version', BGMG_CHILE_VERSION );
	flush_rewrite_rules();
	set_transient( 'bgmg_chile_wizard_envios_redirect', 1, 60 );
}

/**
 * Migraciones automáticas en cada carga: si la versión instalada en BD
 * es menor que la versión del código (porque el usuario actualizó el ZIP
 * sin desactivar y reactivar), corremos el schema una vez.
 *
 * Es barato porque dbDelta es no-op cuando ya está todo al día. La opción
 * bgmg_chile_db_version sirve de "agua" para no correr la migración en
 * cada request.
 */
add_action( 'plugins_loaded', 'bgmg_chile_maybe_run_migrations', 5 );

function bgmg_chile_maybe_run_migrations() {
	$stored = get_option( 'bgmg_chile_db_version', '0.0.0' );
	if ( version_compare( $stored, BGMG_CHILE_VERSION, '<' ) ) {
		bgmg_chile_install_schema();
		update_option( 'bgmg_chile_db_version', BGMG_CHILE_VERSION );
	}
}

/**
 * Desactivación: no borramos datos. La eliminación dura va en uninstall.php.
 */
function bgmg_chile_on_deactivate() {
	flush_rewrite_rules();
}
