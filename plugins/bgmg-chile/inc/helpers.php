<?php
/**
 * Helpers comunes del plugin.
 *
 * Funciones utilitarias usadas por más de un submódulo. Cualquier cosa específica
 * a RUT/regiones/envío debe vivir en su carpeta correspondiente.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slug del país que usamos como ancla en filtros de WooCommerce.
 * Hardcodeado a Chile porque todo el plugin asume CL.
 */
function bgmg_chile_country_code() {
	return 'CL';
}

/**
 * ¿La orden o checkout actual es de Chile?
 *
 * @param string|null $country Código ISO 2. Si es null lo inferimos del checkout.
 * @return bool
 */
function bgmg_chile_is_country( $country = null ) {

	if ( null === $country ) {
		// Durante el checkout WC expone el país en la sesión.
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$country = WC()->customer->get_billing_country();
		}
	}

	return strtoupper( (string) $country ) === bgmg_chile_country_code();
}

/**
 * Wrapper para sanitizar un meta de texto con longitud máxima razonable.
 * RUT, razón social y giro caben holgadamente en 120 caracteres.
 *
 * @param string $valor
 * @param int    $max
 * @return string
 */
function bgmg_chile_sanitize_text( $valor, $max = 120 ) {
	$valor = is_scalar( $valor ) ? (string) $valor : '';
	$valor = sanitize_text_field( $valor );
	if ( mb_strlen( $valor ) > $max ) {
		$valor = mb_substr( $valor, 0, $max );
	}
	return $valor;
}

/**
 * Renderiza un par etiqueta/valor para el área admin de la orden.
 * Centralizado acá para no repetir HTML en varios sitios.
 *
 * @param string $label
 * @param string $valor
 */
function bgmg_chile_render_meta_row( $label, $valor ) {
	if ( '' === $valor ) {
		return;
	}
	echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $valor ) . '</p>';
}

/**
 * Devuelve la URL admin correcta para la lista de pedidos, detectando si HPOS
 * (High-Performance Order Storage) está activo. Las URLs legacy
 * (edit.php?post_type=shop_order) NO funcionan bien con HPOS — los filtros
 * de estado cambian de `post_status=wc-X` a `status=X` y el screen es distinto.
 *
 * @param string $status Estado WC sin prefijo "wc-" (ej. "processing"). Vacío
 *                       para listar todos los pedidos.
 * @return string URL absoluta de admin.
 */
function bgmg_chile_admin_orders_url( $status = '' ) {

	$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

	if ( $hpos ) {
		$base = admin_url( 'admin.php?page=wc-orders' );
		return $status
			? $base . '&status=' . rawurlencode( $status )
			: $base;
	}

	// Legacy: shop_order como CPT.
	$base = admin_url( 'edit.php?post_type=shop_order' );
	return $status
		? $base . '&post_status=wc-' . rawurlencode( $status )
		: $base;
}

/**
 * Definición canónica de los campos de facturación chilenos que el plugin
 * inyecta tanto en el checkout como en "Mi cuenta → Editar dirección".
 *
 * Tener una sola fuente evita que se desincronicen (placeholders, prioridades,
 * required, clases CSS) entre los dos contextos.
 *
 * @return array Estructura WC: clave = nombre del campo, valor = array de config.
 */
function bgmg_chile_get_billing_extra_fields_definition() {
	return array(
		'billing_bgmg_rut' => array(
			'type'         => 'text',
			'label'        => __( 'RUT', 'bgmg-chile' ),
			'placeholder'  => __( '12.345.678-9', 'bgmg-chile' ),
			'required'     => true,
			'class'        => array( 'form-row-wide', 'bgmg-chile-rut-field' ),
			'priority'     => 25,
			'autocomplete' => 'off',
		),
		'billing_bgmg_necesita_factura' => array(
			'type'     => 'checkbox',
			'label'    => __( 'Necesito factura (empresa)', 'bgmg-chile' ),
			'required' => false,
			'class'    => array( 'form-row-wide', 'bgmg-chile-factura-toggle' ),
			'priority' => 26,
		),
		'billing_bgmg_razon_social' => array(
			'type'        => 'text',
			'label'       => __( 'Razón social', 'bgmg-chile' ),
			'placeholder' => __( 'Empresa SpA', 'bgmg-chile' ),
			'required'    => false,
			'class'       => array( 'form-row-wide', 'bgmg-chile-empresa-field' ),
			'priority'    => 27,
		),
		'billing_bgmg_giro' => array(
			'type'        => 'text',
			'label'       => __( 'Giro comercial', 'bgmg-chile' ),
			'placeholder' => __( 'Venta al por menor de productos de belleza', 'bgmg-chile' ),
			'required'    => false,
			'class'       => array( 'form-row-wide', 'bgmg-chile-empresa-field' ),
			'priority'    => 28,
		),
		'billing_bgmg_direccion_comercial' => array(
			'type'        => 'text',
			'label'       => __( 'Dirección comercial (solo si difiere de la de envío)', 'bgmg-chile' ),
			'placeholder' => __( 'Av. Apoquindo 1234, Of. 501', 'bgmg-chile' ),
			'required'    => false,
			'class'       => array( 'form-row-wide', 'bgmg-chile-empresa-field' ),
			'priority'    => 29,
		),
	);
}

/* ------------------------------------------------------------------------- *
 *  HELPERS PARA WIZARDS (introducidos en 1.17.0)
 * ------------------------------------------------------------------------- */

/**
 * Registro defensivo de un submenú bajo "Despachos BGMG".
 *
 * Reemplaza a add_submenu_page directo. Si el menú padre 'bgmg-despachos'
 * no existe en este punto (porque admin-despachos-menu.php no se cargó, o
 * porque algún plugin lo removió), registra el wizard como menú top-level
 * para que NO desaparezca silenciosamente de wp-admin.
 *
 * @param string   $page_title  Título <title> de la pantalla.
 * @param string   $menu_label  Label en el sidebar.
 * @param string   $capability  Permiso WP (típicamente 'manage_woocommerce').
 * @param string   $slug        Slug único del submenú.
 * @param callable $callback    Función que renderiza la pantalla.
 */
function bgmg_chile_wizard_register_submenu( $page_title, $menu_label, $capability, $slug, $callback ) {

	$padre_registrado = isset( $GLOBALS['admin_page_hooks']['bgmg-despachos'] );

	if ( $padre_registrado ) {
		add_submenu_page( 'bgmg-despachos', $page_title, $menu_label, $capability, $slug, $callback );
		return;
	}

	// Fallback: registramos como top-level. Mantiene la funcionalidad accesible
	// aunque visualmente quede separado del menú "Despachos BGMG".
	add_menu_page(
		$page_title,
		$menu_label,
		$capability,
		$slug,
		$callback,
		'dashicons-cart',
		57 // justo después del slot que ocuparía bgmg-despachos (56).
	);
}

/**
 * Pre-flight check para wizards.
 *
 * Verifica que WooCommerce esté activo y que existan las clases necesarias
 * para que el wizard funcione. Si algo falta, renderiza un mensaje claro
 * (no una pantalla blanca con fatal PHP) y devuelve false para que el caller
 * haga early return.
 *
 * @return bool true si todo OK; false si faltó algo (mensaje ya renderizado).
 */
function bgmg_chile_wizard_preflight_check() {

	$problemas = array();

	if ( ! class_exists( 'WooCommerce' ) ) {
		$problemas[] = __( 'WooCommerce no está activo. Activá WooCommerce para usar este asistente.', 'bgmg-chile' );
	}
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		$problemas[] = __( 'Las zonas de envío de WooCommerce no están disponibles.', 'bgmg-chile' );
	}

	if ( empty( $problemas ) ) {
		return true;
	}

	echo '<div class="wrap" style="max-width:720px;">';
	echo '<h1 style="font-size:22px;">' . esc_html__( 'No se puede mostrar el asistente', 'bgmg-chile' ) . '</h1>';
	echo '<div class="notice notice-error" style="padding:14px 16px;margin-top:16px;">';
	echo '<p><strong>' . esc_html__( 'Faltan dependencias:', 'bgmg-chile' ) . '</strong></p>';
	echo '<ul style="list-style:disc;margin-left:22px;">';
	foreach ( $problemas as $msg ) {
		echo '<li>' . esc_html( $msg ) . '</li>';
	}
	echo '</ul>';
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Ir a Plugins', 'bgmg-chile' ) . '</a></p>';
	echo '</div>';
	echo '</div>';
	return false;
}
