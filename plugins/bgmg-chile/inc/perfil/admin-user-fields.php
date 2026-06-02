<?php
/**
 * Sección "Datos Chile" en wp-admin → Usuarios → Editar usuario.
 *
 * Permite a la dueña ver y editar los datos chilenos de cualquier cliente
 * sin tener que abrir una orden. Útil para soporte: cuando un cliente
 * pregunta por algo y necesitas rápido su RUT/teléfono/datos factura.
 *
 * Persistencia: los mismos user_meta que ya usa el plugin
 *   _bgmg_rut, _bgmg_rut_normalizado, _bgmg_rut_tipo,
 *   _billing_phone (formato +56 9 XXXX XXXX),
 *   _billing_bgmg_necesita_factura, _billing_bgmg_razon_social,
 *   _billing_bgmg_giro, _billing_bgmg_direccion_comercial.
 *
 * Reutiliza los validadores existentes para coherencia.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  1. RENDER DE LA SECCIÓN
 *     Hooks: show_user_profile (perfil propio) + edit_user_profile (admin
 *     editando a otro usuario). Ambos reciben el WP_User.
 * ------------------------------------------------------------------------- */

add_action( 'show_user_profile', 'bgmg_chile_render_user_datos_chile' );
add_action( 'edit_user_profile', 'bgmg_chile_render_user_datos_chile' );

function bgmg_chile_render_user_datos_chile( $user ) {

	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	$rut              = get_user_meta( $user->ID, '_bgmg_rut', true );
	$telefono         = get_user_meta( $user->ID, 'billing_phone', true );
	$necesita_factura = get_user_meta( $user->ID, '_billing_bgmg_necesita_factura', true );
	$razon_social     = get_user_meta( $user->ID, '_billing_bgmg_razon_social', true );
	$giro             = get_user_meta( $user->ID, '_billing_bgmg_giro', true );
	$direccion_com    = get_user_meta( $user->ID, '_billing_bgmg_direccion_comercial', true );

	wp_nonce_field( 'bgmg_chile_save_user_datos', 'bgmg_chile_user_nonce' );
	?>

	<h2 style="margin-top:32px;padding-top:16px;border-top:1px solid #c4c4c4;">
		🇨🇱 <?php esc_html_e( 'Datos Chile', 'bgmg-chile' ); ?>
	</h2>
	<p class="description">
		<?php esc_html_e( 'Información que el cliente entregó en su última compra o registro. Puedes corregirla aquí si te la pide por soporte.', 'bgmg-chile' ); ?>
	</p>

	<table class="form-table" role="presentation">
		<tr>
			<th><label for="bgmg_user_rut"><?php esc_html_e( 'RUT', 'bgmg-chile' ); ?></label></th>
			<td>
				<input
					type="text"
					id="bgmg_user_rut"
					name="bgmg_user_rut"
					value="<?php echo esc_attr( $rut ); ?>"
					class="regular-text"
					placeholder="<?php esc_attr_e( '12.345.678-9', 'bgmg-chile' ); ?>"
					autocomplete="off"
				/>
				<p class="description">
					<?php esc_html_e( 'Se valida con módulo 11 al guardar. Si dejas el campo vacío se borra.', 'bgmg-chile' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="bgmg_user_telefono"><?php esc_html_e( 'Teléfono móvil', 'bgmg-chile' ); ?></label></th>
			<td>
				<input
					type="text"
					id="bgmg_user_telefono"
					name="bgmg_user_telefono"
					value="<?php echo esc_attr( $telefono ); ?>"
					class="regular-text"
					placeholder="<?php esc_attr_e( '+56 9 1234 5678', 'bgmg-chile' ); ?>"
					autocomplete="off"
				/>
				<p class="description">
					<?php esc_html_e( 'Móvil chileno. Se normaliza a +56 9 XXXX XXXX al guardar.', 'bgmg-chile' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th>
				<label for="bgmg_user_necesita_factura">
					<?php esc_html_e( 'Cliente requiere factura', 'bgmg-chile' ); ?>
				</label>
			</th>
			<td>
				<label>
					<input
						type="checkbox"
						id="bgmg_user_necesita_factura"
						name="bgmg_user_necesita_factura"
						value="1"
						<?php checked( 'si', $necesita_factura ); ?>
					/>
					<?php esc_html_e( 'Sí, este cliente compra como empresa y necesita factura', 'bgmg-chile' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><label for="bgmg_user_razon_social"><?php esc_html_e( 'Razón social', 'bgmg-chile' ); ?></label></th>
			<td>
				<input
					type="text"
					id="bgmg_user_razon_social"
					name="bgmg_user_razon_social"
					value="<?php echo esc_attr( $razon_social ); ?>"
					class="regular-text"
				/>
			</td>
		</tr>
		<tr>
			<th><label for="bgmg_user_giro"><?php esc_html_e( 'Giro comercial', 'bgmg-chile' ); ?></label></th>
			<td>
				<input
					type="text"
					id="bgmg_user_giro"
					name="bgmg_user_giro"
					value="<?php echo esc_attr( $giro ); ?>"
					class="regular-text"
				/>
			</td>
		</tr>
		<tr>
			<th>
				<label for="bgmg_user_direccion_comercial">
					<?php esc_html_e( 'Dirección comercial', 'bgmg-chile' ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					id="bgmg_user_direccion_comercial"
					name="bgmg_user_direccion_comercial"
					value="<?php echo esc_attr( $direccion_com ); ?>"
					class="regular-text"
				/>
			</td>
		</tr>
	</table>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  2. VALIDAR Y GUARDAR DESDE EL PERFIL
 *
 *  Importante: WP dispara `user_profile_update_errors` ANTES de
 *  `edit_user_profile_update` / `personal_options_update`. Si la validación
 *  se intentaba "agregar al vuelo" desde dentro del save handler, el hook
 *  ya había pasado y el mensaje de error nunca aparecía en pantalla.
 *
 *  Solución: validar en `user_profile_update_errors` (donde sí se ven los
 *  errores), y guardar solo los campos que pasaron validación en el save
 *  handler. Compartimos el resultado de la validación vía estática para
 *  no parsear dos veces los $_POST.
 * ------------------------------------------------------------------------- */

/**
 * Estado compartido entre la validación y el guardado para el mismo request.
 * Estructura:
 *   array(
 *     'rut_ok'      => bool,
 *     'rut_value'   => string|null  // valor crudo si pasó validación, null si vacío
 *     'tel_ok'      => bool,
 *     'tel_value'   => string|null
 *   )
 */
function &bgmg_chile_user_profile_state() {
	static $state = null;
	if ( null === $state ) {
		$state = array(
			'rut_ok'    => true,  // por defecto "todo ok" para no bloquear si no se tocó
			'rut_value' => null,
			'tel_ok'    => true,
			'tel_value' => null,
		);
	}
	return $state;
}

/**
 * Validación. Corre antes del save. Si añade errores con $errors->add(),
 * WP detiene el wp_update_user() y muestra el mensaje arriba del formulario.
 */
add_action( 'user_profile_update_errors', 'bgmg_chile_validate_user_datos_chile', 10, 3 );

function bgmg_chile_validate_user_datos_chile( $errors, $update, $user ) {

	// Solo nos importa si el formulario incluyó nuestro nonce (es decir, si
	// se renderizó nuestra sección — el hook también se dispara en flujos
	// donde nuestra sección no está, p.ej. creación de usuario por API).
	if ( ! isset( $_POST['bgmg_chile_user_nonce'] ) ||
		! wp_verify_nonce( wp_unslash( $_POST['bgmg_chile_user_nonce'] ), 'bgmg_chile_save_user_datos' )
	) {
		return;
	}

	$state = &bgmg_chile_user_profile_state();

	/* --- RUT --- */
	if ( isset( $_POST['bgmg_user_rut'] ) ) {
		$rut_in = bgmg_chile_sanitize_text( wp_unslash( $_POST['bgmg_user_rut'] ), 20 );
		if ( '' === trim( $rut_in ) ) {
			$state['rut_value'] = '';
			$state['rut_ok']    = true;
		} elseif ( BGMG_Chile_RUT_Validator::is_valid( $rut_in ) ) {
			$state['rut_value'] = $rut_in;
			$state['rut_ok']    = true;
		} else {
			$state['rut_ok'] = false;
			$errors->add(
				'bgmg_chile_rut_invalido_user',
				__( '<strong>RUT inválido:</strong> verifica el dígito verificador. No se guardó ese campo.', 'bgmg-chile' )
			);
		}
	}

	/* --- Teléfono --- */
	if ( isset( $_POST['bgmg_user_telefono'] ) ) {
		$tel_in = bgmg_chile_sanitize_text( wp_unslash( $_POST['bgmg_user_telefono'] ), 30 );
		if ( '' === trim( $tel_in ) ) {
			$state['tel_value'] = '';
			$state['tel_ok']    = true;
		} elseif ( BGMG_Chile_Telefono_Validator::is_valid_movil( $tel_in ) ) {
			$state['tel_value'] = $tel_in;
			$state['tel_ok']    = true;
		} else {
			$state['tel_ok'] = false;
			$errors->add(
				'bgmg_chile_telefono_invalido_user',
				__( '<strong>Teléfono inválido:</strong> debe ser un móvil chileno (+56 9 XXXX XXXX). No se guardó ese campo.', 'bgmg-chile' )
			);
		}
	}
}

/**
 * Guardado. Corre después de la validación. Persistimos solo los campos
 * que pasaron validación (los inválidos quedan tal como estaban).
 */
add_action( 'personal_options_update', 'bgmg_chile_save_user_datos_chile' );
add_action( 'edit_user_profile_update', 'bgmg_chile_save_user_datos_chile' );

function bgmg_chile_save_user_datos_chile( $user_id ) {

	if ( ! isset( $_POST['bgmg_chile_user_nonce'] ) ||
		! wp_verify_nonce( wp_unslash( $_POST['bgmg_chile_user_nonce'] ), 'bgmg_chile_save_user_datos' )
	) {
		return;
	}
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	$state = &bgmg_chile_user_profile_state();

	/* --- RUT --- */
	if ( $state['rut_ok'] && null !== $state['rut_value'] ) {
		if ( '' === $state['rut_value'] ) {
			// Vacío explícito: limpiamos.
			delete_user_meta( $user_id, '_bgmg_rut' );
			delete_user_meta( $user_id, '_bgmg_rut_normalizado' );
			delete_user_meta( $user_id, '_bgmg_rut_tipo' );
		} else {
			$rut = $state['rut_value'];
			update_user_meta( $user_id, '_bgmg_rut', BGMG_Chile_RUT_Validator::format( $rut ) );
			update_user_meta( $user_id, '_bgmg_rut_normalizado', BGMG_Chile_RUT_Validator::normalize( $rut ) );
			update_user_meta( $user_id, '_bgmg_rut_tipo', BGMG_Chile_RUT_Validator::tipo( $rut ) );
		}
	}

	/* --- Teléfono --- */
	if ( $state['tel_ok'] && null !== $state['tel_value'] ) {
		if ( '' === $state['tel_value'] ) {
			delete_user_meta( $user_id, 'billing_phone' );
		} else {
			update_user_meta(
				$user_id,
				'billing_phone',
				BGMG_Chile_Telefono_Validator::format_internacional( $state['tel_value'] )
			);
		}
	}

	/* --- Toggle factura + campos empresa (sin validación, solo sanitización) --- */
	$necesita_factura = ! empty( $_POST['bgmg_user_necesita_factura'] ) ? 'si' : 'no';
	update_user_meta( $user_id, '_billing_bgmg_necesita_factura', $necesita_factura );

	$campos_empresa = array(
		'_billing_bgmg_razon_social'        => 'bgmg_user_razon_social',
		'_billing_bgmg_giro'                => 'bgmg_user_giro',
		'_billing_bgmg_direccion_comercial' => 'bgmg_user_direccion_comercial',
	);
	foreach ( $campos_empresa as $meta_key => $post_key ) {
		if ( isset( $_POST[ $post_key ] ) ) {
			$valor = bgmg_chile_sanitize_text( wp_unslash( $_POST[ $post_key ] ), 200 );
			if ( '' === $valor ) {
				delete_user_meta( $user_id, $meta_key );
			} else {
				update_user_meta( $user_id, $meta_key, $valor );
			}
		}
	}
}

/* ------------------------------------------------------------------------- *
 *  3. COLUMNA "RUT" EN LA LISTA DE USUARIOS (bonus de UX)
 *     Aparece como columna en wp-admin → Usuarios. Permite ordenar por RUT.
 * ------------------------------------------------------------------------- */

add_filter( 'manage_users_columns', 'bgmg_chile_users_list_column' );

function bgmg_chile_users_list_column( $columns ) {
	// Insertamos "RUT" después de "Email".
	$nuevas = array();
	foreach ( $columns as $key => $label ) {
		$nuevas[ $key ] = $label;
		if ( 'email' === $key ) {
			$nuevas['bgmg_rut'] = __( 'RUT', 'bgmg-chile' );
		}
	}
	return $nuevas;
}

add_filter( 'manage_users_custom_column', 'bgmg_chile_users_list_column_value', 10, 3 );

function bgmg_chile_users_list_column_value( $value, $column_name, $user_id ) {
	if ( 'bgmg_rut' !== $column_name ) {
		return $value;
	}
	$rut = get_user_meta( $user_id, '_bgmg_rut', true );
	return $rut ? esc_html( $rut ) : '—';
}
