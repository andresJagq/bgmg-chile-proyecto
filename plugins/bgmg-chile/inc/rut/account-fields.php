<?php
/**
 * Campo RUT en el formulario de registro y en "Mi cuenta → Detalles".
 *
 * En registro el RUT es OPCIONAL (decisión del 2026-05-16). Si lo ingresan
 * lo validamos y guardamos como user_meta. En "Mi cuenta → Detalles de
 * facturación" mostramos los mismos campos del checkout (incluido toggle
 * factura) para que el cliente edite sus datos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  REGISTRO (formulario público de WooCommerce)
 * ------------------------------------------------------------------------- */

/**
 * Pinta el campo RUT debajo del email en el registro.
 */
add_action( 'woocommerce_register_form', 'bgmg_chile_register_form_rut_field' );

function bgmg_chile_register_form_rut_field() {
	$value = ! empty( $_POST['bgmg_rut'] ) ? sanitize_text_field( wp_unslash( $_POST['bgmg_rut'] ) ) : '';
	?>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide bgmg-chile-rut-field">
		<label for="bgmg_rut"><?php esc_html_e( 'RUT (opcional)', 'bgmg-chile' ); ?></label>
		<input
			type="text"
			class="woocommerce-Input woocommerce-Input--text input-text"
			name="bgmg_rut"
			id="bgmg_rut"
			placeholder="<?php esc_attr_e( '12.345.678-9', 'bgmg-chile' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			autocomplete="off"
		/>
		<small><?php esc_html_e( 'Si nos lo dejas ahora, no tendrás que escribirlo en cada compra.', 'bgmg-chile' ); ?></small>
	</p>
	<?php
}

/**
 * Validación: si lo escribieron, debe ser válido. Si lo dejaron vacío, ok.
 * Detección de duplicados se hace en /inc/rut/duplicates.php (otro hook).
 */
add_filter( 'woocommerce_registration_errors', 'bgmg_chile_validate_register_rut', 10, 3 );

function bgmg_chile_validate_register_rut( $errors, $username, $email ) {
	if ( empty( $_POST['bgmg_rut'] ) ) {
		return $errors;
	}

	$rut = sanitize_text_field( wp_unslash( $_POST['bgmg_rut'] ) );
	if ( '' === trim( $rut ) ) {
		return $errors;
	}

	if ( ! BGMG_Chile_RUT_Validator::is_valid( $rut ) ) {
		$errors->add(
			'bgmg_chile_rut_invalido',
			__( '<strong>Error:</strong> el RUT ingresado no es válido.', 'bgmg-chile' )
		);
	}
	return $errors;
}

/**
 * Guarda el RUT como user_meta al crear la cuenta (si pasó validación).
 */
add_action( 'woocommerce_created_customer', 'bgmg_chile_save_register_rut', 10, 3 );

function bgmg_chile_save_register_rut( $customer_id, $new_customer_data, $password_generated ) {
	if ( empty( $_POST['bgmg_rut'] ) ) {
		return;
	}
	$rut = sanitize_text_field( wp_unslash( $_POST['bgmg_rut'] ) );
	if ( BGMG_Chile_RUT_Validator::is_valid( $rut ) ) {
		update_user_meta( $customer_id, '_bgmg_rut', BGMG_Chile_RUT_Validator::format( $rut ) );
		update_user_meta( $customer_id, '_bgmg_rut_normalizado', BGMG_Chile_RUT_Validator::normalize( $rut ) );
		update_user_meta( $customer_id, '_bgmg_rut_tipo', BGMG_Chile_RUT_Validator::tipo( $rut ) );
	}
}

/* ------------------------------------------------------------------------- *
 *  MI CUENTA → DETALLES DE FACTURACIÓN
 *  (woocommerce_form_field hooks de billing address)
 * ------------------------------------------------------------------------- */

/**
 * Agrega los mismos campos del checkout a la pantalla
 * "Mi cuenta → Direcciones → Editar dirección de facturación".
 *
 * El filtro woocommerce_billing_fields aplica tanto al checkout como a la
 * edición de dirección, así que reutiliza la lógica de checkout-fields.php.
 * Para evitar duplicar campos lo agregamos SOLO si no están ya:
 */
add_filter( 'woocommerce_billing_fields', 'bgmg_chile_billing_fields_account', 20 );

function bgmg_chile_billing_fields_account( $fields ) {

	// Si ya los puso otro filtro (p.ej. el de checkout que también pasa por
	// billing_fields), no los duplicamos.
	if ( isset( $fields['billing_bgmg_rut'] ) ) {
		return $fields;
	}

	foreach ( bgmg_chile_get_billing_extra_fields_definition() as $key => $config ) {
		$fields[ $key ] = $config;
	}

	return $fields;
}

/**
 * Persistencia desde "Mi cuenta → Editar dirección".
 */
add_action( 'woocommerce_customer_save_address', 'bgmg_chile_save_account_billing_rut', 10, 2 );

function bgmg_chile_save_account_billing_rut( $user_id, $load_address ) {
	if ( 'billing' !== $load_address ) {
		return;
	}

	if ( ! empty( $_POST['billing_bgmg_rut'] ) ) {
		$rut = sanitize_text_field( wp_unslash( $_POST['billing_bgmg_rut'] ) );
		if ( BGMG_Chile_RUT_Validator::is_valid( $rut ) ) {
			update_user_meta( $user_id, '_bgmg_rut', BGMG_Chile_RUT_Validator::format( $rut ) );
			update_user_meta( $user_id, '_bgmg_rut_normalizado', BGMG_Chile_RUT_Validator::normalize( $rut ) );
			update_user_meta( $user_id, '_bgmg_rut_tipo', BGMG_Chile_RUT_Validator::tipo( $rut ) );
		}
	}

	// Checkbox "Necesito factura": un checkbox DESMARCADO no viaja en $_POST, así
	// que hay que escribirlo SIEMPRE. De lo contrario el cliente no podía desactivar
	// la factura desde "Mi cuenta → Editar dirección" (el meta conservaba el '1'
	// anterior). Guardamos '1'/'' coherente con el checkout (inc/rut/order-display.php)
	// y con el pre-llenado de más abajo (bgmg_chile_fill_billing_fields_from_user_meta).
	$necesita_factura = ! empty( $_POST['billing_bgmg_necesita_factura'] ) ? '1' : '';
	update_user_meta( $user_id, '_billing_bgmg_necesita_factura', $necesita_factura );

	// Campos de empresa (texto): se actualizan solo si llegan en el POST.
	$campos_empresa = array(
		'billing_bgmg_razon_social',
		'billing_bgmg_giro',
		'billing_bgmg_direccion_comercial',
	);
	foreach ( $campos_empresa as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			update_user_meta( $user_id, '_' . $key, $val );
		}
	}
}

/**
 * Pre-rellena los campos de Mi Cuenta con el user_meta guardado.
 */
add_filter( 'woocommerce_billing_fields', 'bgmg_chile_fill_billing_fields_from_user_meta', 30 );

function bgmg_chile_fill_billing_fields_from_user_meta( $fields ) {
	if ( ! is_user_logged_in() ) {
		return $fields;
	}

	$user_id = get_current_user_id();
	$map     = array(
		'billing_bgmg_rut'                  => '_bgmg_rut',
		'billing_bgmg_necesita_factura'     => '_billing_bgmg_necesita_factura',
		'billing_bgmg_razon_social'         => '_billing_bgmg_razon_social',
		'billing_bgmg_giro'                 => '_billing_bgmg_giro',
		'billing_bgmg_direccion_comercial'  => '_billing_bgmg_direccion_comercial',
	);
	foreach ( $map as $field_key => $meta_key ) {
		if ( isset( $fields[ $field_key ] ) ) {
			$val = get_user_meta( $user_id, $meta_key, true );
			if ( '' !== $val ) {
				$fields[ $field_key ]['default'] = $val;
			}
		}
	}
	return $fields;
}
