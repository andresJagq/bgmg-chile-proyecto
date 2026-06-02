<?php
/**
 * Detección de RUT duplicado al registrarse.
 *
 * Política: un RUT solo puede pertenecer a un usuario activo. Si alguien
 * intenta crear cuenta nueva con un RUT que ya existe en user_meta
 * (_bgmg_rut_normalizado), bloqueamos el registro y le sugerimos recuperar
 * acceso a la cuenta original.
 *
 * NO bloqueamos en checkout: ahí el flujo es de invitado o compra autenticada
 * y el RUT puede repetirse legítimamente entre órdenes del mismo cliente.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifica si ya existe un usuario con el RUT dado (comparación normalizada).
 *
 * @param string   $rut
 * @param int|null $excluir_user_id Útil para edición de cuenta: no compararse a sí mismo.
 * @return int|false ID del usuario que ya lo tiene, o false si está libre.
 */
function bgmg_chile_rut_already_exists( $rut, $excluir_user_id = null ) {
	$normalizado = BGMG_Chile_RUT_Validator::normalize( $rut );
	if ( '' === $normalizado ) {
		return false;
	}

	$args = array(
		'meta_key'   => '_bgmg_rut_normalizado',
		'meta_value' => $normalizado,
		'fields'     => 'ID',
		'number'     => 1,
	);
	if ( $excluir_user_id ) {
		$args['exclude'] = array( (int) $excluir_user_id );
	}

	$users = get_users( $args );
	return empty( $users ) ? false : (int) $users[0];
}

/**
 * Hook de validación en registro: si el RUT ya está usado, error.
 * Se ejecuta DESPUÉS de bgmg_chile_validate_register_rut (que valida formato).
 */
add_filter( 'woocommerce_registration_errors', 'bgmg_chile_check_rut_duplicate_on_register', 20, 3 );

function bgmg_chile_check_rut_duplicate_on_register( $errors, $username, $email ) {
	if ( empty( $_POST['bgmg_rut'] ) ) {
		return $errors;
	}

	$rut = sanitize_text_field( wp_unslash( $_POST['bgmg_rut'] ) );
	if ( ! BGMG_Chile_RUT_Validator::is_valid( $rut ) ) {
		// Si no es válido el filtro anterior ya generó el error: salimos.
		return $errors;
	}

	$existing_user_id = bgmg_chile_rut_already_exists( $rut );
	if ( $existing_user_id ) {
		$lost_url = wp_lostpassword_url();
		$errors->add(
			'bgmg_chile_rut_duplicado',
			sprintf(
				/* translators: %s: URL a "olvidé mi contraseña" */
				__( '<strong>Error:</strong> ya existe una cuenta con este RUT. Si es tuya puedes <a href="%s">recuperar el acceso</a>.', 'bgmg-chile' ),
				esc_url( $lost_url )
			)
		);
	}
	return $errors;
}

/**
 * Hook adicional para WP nativo (registro fuera de WC, p.ej. wp-login.php?action=register).
 * Útil si la dueña en algún momento expone registro de WP sin pasar por WC.
 */
add_filter( 'registration_errors', 'bgmg_chile_check_rut_duplicate_on_wp_register', 10, 3 );

function bgmg_chile_check_rut_duplicate_on_wp_register( $errors, $sanitized_user_login, $user_email ) {
	if ( empty( $_POST['bgmg_rut'] ) ) {
		return $errors;
	}
	$rut = sanitize_text_field( wp_unslash( $_POST['bgmg_rut'] ) );
	if ( BGMG_Chile_RUT_Validator::is_valid( $rut ) && bgmg_chile_rut_already_exists( $rut ) ) {
		$errors->add(
			'bgmg_chile_rut_duplicado',
			__( '<strong>Error:</strong> ya existe una cuenta con este RUT.', 'bgmg-chile' )
		);
	}
	return $errors;
}
