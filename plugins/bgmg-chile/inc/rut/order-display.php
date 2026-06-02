<?php
/**
 * Persistencia y visualización del RUT (y datos de empresa) en la orden.
 *
 * Cubre:
 *   - Guardado normalizado en order_meta cuando se crea la orden desde checkout.
 *   - Sincronización al user_meta para usuarios logueados (así se autocompleta
 *     el RUT en próximas compras).
 *   - Render en admin de orden (HPOS-friendly).
 *   - Render en emails de WC (Procesando, Completado, etc.).
 *   - Render en "Mi cuenta → Detalles de la orden".
 *   - Inclusión en el formato de dirección de facturación para que aparezca
 *     en la orden con la dirección.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  1. GUARDAR EN ORDER_META AL FINALIZAR EL CHECKOUT
 * ------------------------------------------------------------------------- */

/**
 * Hook compatible con HPOS: woocommerce_checkout_create_order.
 * Recibe la $order antes de guardarla, así update_meta_data se persiste en una sola pasada.
 */
add_action( 'woocommerce_checkout_create_order', 'bgmg_chile_save_order_meta', 20, 2 );

function bgmg_chile_save_order_meta( $order, $data ) {

	// RUT (siempre presente porque es required en checkout).
	$rut_crudo = isset( $data['billing_bgmg_rut'] ) ? (string) $data['billing_bgmg_rut'] : '';
	if ( BGMG_Chile_RUT_Validator::is_valid( $rut_crudo ) ) {
		$rut_formato = BGMG_Chile_RUT_Validator::format( $rut_crudo );
		$rut_norm    = BGMG_Chile_RUT_Validator::normalize( $rut_crudo );
		$tipo        = BGMG_Chile_RUT_Validator::tipo( $rut_crudo );

		$order->update_meta_data( '_bgmg_rut', $rut_formato );
		$order->update_meta_data( '_bgmg_rut_normalizado', $rut_norm );
		$order->update_meta_data( '_bgmg_rut_tipo', $tipo );

		// Sincroniza al user_meta si el cliente está logueado: próxima compra autocompleta.
		$user_id = $order->get_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, '_bgmg_rut', $rut_formato );
			update_user_meta( $user_id, '_bgmg_rut_normalizado', $rut_norm );
			update_user_meta( $user_id, '_bgmg_rut_tipo', $tipo );
		}
	}

	// Toggle factura + datos empresa.
	$necesita_factura = ! empty( $data['billing_bgmg_necesita_factura'] );
	$order->update_meta_data( '_bgmg_necesita_factura', $necesita_factura ? 'si' : 'no' );

	$razon_social  = $necesita_factura ? bgmg_chile_sanitize_text( $data['billing_bgmg_razon_social'] ?? '' ) : '';
	$giro          = $necesita_factura ? bgmg_chile_sanitize_text( $data['billing_bgmg_giro'] ?? '' ) : '';
	$direccion_com = $necesita_factura ? bgmg_chile_sanitize_text( $data['billing_bgmg_direccion_comercial'] ?? '', 200 ) : '';

	if ( $necesita_factura ) {
		$order->update_meta_data( '_bgmg_razon_social', $razon_social );
		$order->update_meta_data( '_bgmg_giro', $giro );
		$order->update_meta_data( '_bgmg_direccion_comercial', $direccion_com );
	} else {
		// Si NO marcaron factura limpiamos los meta de empresa (por si vienen heredados de user_meta).
		$order->delete_meta_data( '_bgmg_razon_social' );
		$order->delete_meta_data( '_bgmg_giro' );
		$order->delete_meta_data( '_bgmg_direccion_comercial' );
	}

	// Persistir la preferencia de facturación al user_meta del cliente logueado.
	// account-fields.php pre-llena el checkout desde _billing_bgmg_* (ver
	// bgmg_chile_fill_billing_fields_from_user_meta), así que escribimos en ese
	// namespace para que la próxima compra autocomplete el toggle y los datos.
	// Para invitados ($user_id === 0) no aplica — solo guardamos en la orden.
	$user_id = $order->get_user_id();
	if ( $user_id ) {
		update_user_meta( $user_id, '_billing_bgmg_necesita_factura', $necesita_factura ? '1' : '' );
		update_user_meta( $user_id, '_billing_bgmg_razon_social', $razon_social );
		update_user_meta( $user_id, '_billing_bgmg_giro', $giro );
		update_user_meta( $user_id, '_billing_bgmg_direccion_comercial', $direccion_com );
	}
}

/* ------------------------------------------------------------------------- *
 *  2. RENDER EN ADMIN DE ORDEN (HPOS-friendly)
 * ------------------------------------------------------------------------- */

/**
 * Caja con RUT y datos de empresa en el lateral derecho del editor de orden.
 * Se muestra debajo de la dirección de facturación.
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'bgmg_chile_admin_order_billing' );

function bgmg_chile_admin_order_billing( $order ) {
	$rut              = $order->get_meta( '_bgmg_rut' );
	$necesita_factura = $order->get_meta( '_bgmg_necesita_factura' );
	$razon_social     = $order->get_meta( '_bgmg_razon_social' );
	$giro             = $order->get_meta( '_bgmg_giro' );
	$direccion_com    = $order->get_meta( '_bgmg_direccion_comercial' );

	echo '<div class="bgmg-chile-admin-order">';
	echo '<h4>' . esc_html__( 'Datos Chile (RUT)', 'bgmg-chile' ) . '</h4>';

	bgmg_chile_render_meta_row( __( 'RUT', 'bgmg-chile' ), (string) $rut );

	if ( 'si' === $necesita_factura ) {
		bgmg_chile_render_meta_row( __( 'Necesita factura', 'bgmg-chile' ), __( 'Sí', 'bgmg-chile' ) );
		bgmg_chile_render_meta_row( __( 'Razón social', 'bgmg-chile' ), (string) $razon_social );
		bgmg_chile_render_meta_row( __( 'Giro', 'bgmg-chile' ), (string) $giro );
		bgmg_chile_render_meta_row( __( 'Dirección comercial', 'bgmg-chile' ), (string) $direccion_com );
	} else {
		echo '<p><em>' . esc_html__( 'Boleta (no requiere factura)', 'bgmg-chile' ) . '</em></p>';
	}

	echo '</div>';
}

/* ------------------------------------------------------------------------- *
 *  3+4. RENDER EN EMAILS Y EN "MI CUENTA → DETALLE DE ORDEN"
 *
 *  Removidos por decisión del cliente (2026-05-27): el bloque
 *  "Datos para boleta/factura" no debe mostrarse al cliente final.
 *  La dueña sigue viendo el RUT en wp-admin → Pedidos (sección 2).
 *  El RUT también aparece dentro del bloque de dirección de facturación
 *  vía el filtro woocommerce_order_formatted_billing_address (sección 5).
 * ------------------------------------------------------------------------- */

/* ------------------------------------------------------------------------- *
 *  5. EXPONER EL RUT EN EL FORMATO DE DIRECCIÓN DE FACTURACIÓN
 *     (lo agrega bajo la dirección, útil para exportar y para plugins de
 *      facturación electrónica futuros que leen el address formateado).
 * ------------------------------------------------------------------------- */

add_filter( 'woocommerce_order_formatted_billing_address', 'bgmg_chile_inject_rut_in_billing_address', 10, 2 );

function bgmg_chile_inject_rut_in_billing_address( $address, $order ) {
	$rut = $order->get_meta( '_bgmg_rut' );
	if ( $rut ) {
		$address['bgmg_rut_line'] = 'RUT: ' . $rut;
	}
	return $address;
}

add_filter( 'woocommerce_localisation_address_formats', 'bgmg_chile_address_formats' );

function bgmg_chile_address_formats( $formats ) {
	// Solo personalizamos Chile. Otros países quedan intactos.
	if ( isset( $formats['CL'] ) ) {
		$formats['CL'] .= "\n{bgmg_rut_line}";
	} else {
		$formats['CL'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{country}\n{bgmg_rut_line}";
	}
	return $formats;
}

add_filter( 'woocommerce_formatted_address_replacements', 'bgmg_chile_address_replacements', 10, 2 );

function bgmg_chile_address_replacements( $replacements, $args ) {
	$replacements['{bgmg_rut_line}'] = isset( $args['bgmg_rut_line'] ) ? $args['bgmg_rut_line'] : '';
	return $replacements;
}
