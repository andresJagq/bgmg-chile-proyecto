<?php
/**
 * Pantalla admin: tarifas fijas por comuna de la Región Metropolitana.
 *
 *   wp-admin → Despachos BGMG → Tarifas RM
 *
 * Funcionalidad v1.11.1:
 *   - Lista las 52 comunas RM con estado claro: Default RM / Custom / Por pagar.
 *   - Buscador instantáneo + filtros por estado.
 *   - Contadores en vivo del resumen.
 *   - Badge visual por fila para escanear de un vistazo.
 *   - Save por POST consolidado (todas las filas a la vez).
 *
 * La API pública (las funciones que consume el shipping method y otros módulos)
 * se mantiene sin cambios: bgmg_chile_load_all_tarifas_rm, bgmg_chile_get_tarifa_fija,
 * bgmg_chile_comuna_acepta_retiro, bgmg_chile_upsert_tarifa_rm.
 *
 * El menú se registra desde inc/envio/admin-despachos-menu.php (sección
 * "Despachos BGMG" top-level). Acá solo definimos el callback de render.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  1. RENDER DE LA PANTALLA
 * ------------------------------------------------------------------------- */

function bgmg_chile_render_admin_tarifas_rm() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pantalla.', 'bgmg-chile' ) );
	}

	// Procesar POST si llega.
	$mensaje = '';
	if ( isset( $_POST['bgmg_chile_tarifas_nonce'] ) &&
		wp_verify_nonce( wp_unslash( $_POST['bgmg_chile_tarifas_nonce'] ), 'bgmg_chile_save_tarifas_rm' )
	) {
		$mensaje = bgmg_chile_save_tarifas_rm_post();
	}

	$tarifas_actuales = bgmg_chile_load_all_tarifas_rm();
	$comunas_rm       = bgmg_chile_get_comunas_por_region();
	$comunas_rm       = isset( $comunas_rm['RM'] ) ? $comunas_rm['RM'] : array();
	$default_rm       = bgmg_chile_get_default_rm();

	// Orden alfabético por nombre.
	usort( $comunas_rm, function ( $a, $b ) {
		return strnatcasecmp( $a['nombre'], $b['nombre'] );
	} );

	// Contadores iniciales para el resumen.
	$counts = array( 'custom' => 0, 'default' => 0, 'por_pagar' => 0, 'retiro' => 0 );
	foreach ( $comunas_rm as $c ) {
		$reg = isset( $tarifas_actuales[ $c['slug'] ] ) ? $tarifas_actuales[ $c['slug'] ] : null;
		$tipo = bgmg_chile_calc_tipo( $reg );
		$counts[ $tipo ]++;
		if ( $reg && ! empty( $reg['retiro_disponible'] ) ) {
			$counts['retiro']++;
		}
	}

	?>
	<div class="wrap bgmg-tarifas-wrap">
		<h1 class="bgmg-tarifas-title">
			<span class="dashicons dashicons-location-alt" style="font-size:26px;width:26px;height:26px;vertical-align:middle;color:#C4728A;"></span>
			<?php esc_html_e( 'Tarifas Región Metropolitana', 'bgmg-chile' ); ?>
		</h1>
		<p class="bgmg-tarifas-sub">
			<?php esc_html_e( 'Configura excepciones por comuna. Lo que no toques acá cobra el default RM.', 'bgmg-chile' ); ?>
		</p>

		<?php if ( $mensaje ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $mensaje ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Resumen con contadores -->
		<div class="bgmg-tarifas-summary">
			<div class="bgmg-summary-card">
				<span class="lbl"><?php esc_html_e( 'Tarifa custom', 'bgmg-chile' ); ?></span>
				<strong class="num" data-count="custom"><?php echo (int) $counts['custom']; ?></strong>
				<span class="hint"><?php esc_html_e( 'precio específico', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-summary-card">
				<span class="lbl"><?php esc_html_e( 'Default RM', 'bgmg-chile' ); ?></span>
				<strong class="num" data-count="default"><?php echo (int) $counts['default']; ?></strong>
				<span class="hint"><?php echo esc_html( bgmg_chile_clp_short( $default_rm ) ); ?></span>
			</div>
			<div class="bgmg-summary-card">
				<span class="lbl"><?php esc_html_e( 'Por pagar', 'bgmg-chile' ); ?></span>
				<strong class="num" data-count="por_pagar"><?php echo (int) $counts['por_pagar']; ?></strong>
				<span class="hint"><?php esc_html_e( 'override manual', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-summary-card">
				<span class="lbl"><?php esc_html_e( 'Con retiro en tienda', 'bgmg-chile' ); ?></span>
				<strong class="num" data-count="retiro"><?php echo (int) $counts['retiro']; ?></strong>
				<span class="hint"><?php esc_html_e( 'comunas habilitadas', 'bgmg-chile' ); ?></span>
			</div>
		</div>

		<!-- Toolbar: filtros + buscador -->
		<div class="bgmg-tarifas-toolbar">
			<div class="bgmg-tarifas-filters">
				<button type="button" class="bgmg-filter is-active" data-filter="all">
					<?php esc_html_e( 'Todas', 'bgmg-chile' ); ?> <span class="cnt"><?php echo (int) count( $comunas_rm ); ?></span>
				</button>
				<button type="button" class="bgmg-filter" data-filter="custom">
					<?php esc_html_e( 'Custom', 'bgmg-chile' ); ?> <span class="cnt" data-count="custom"><?php echo (int) $counts['custom']; ?></span>
				</button>
				<button type="button" class="bgmg-filter" data-filter="default">
					<?php esc_html_e( 'Default', 'bgmg-chile' ); ?> <span class="cnt" data-count="default"><?php echo (int) $counts['default']; ?></span>
				</button>
				<button type="button" class="bgmg-filter" data-filter="por_pagar">
					<?php esc_html_e( 'Por pagar', 'bgmg-chile' ); ?> <span class="cnt" data-count="por_pagar"><?php echo (int) $counts['por_pagar']; ?></span>
				</button>
			</div>
			<div class="bgmg-tarifas-search">
				<input type="search" id="bgmg-tarifas-search-input" placeholder="<?php esc_attr_e( 'Buscar comuna…', 'bgmg-chile' ); ?>" autocomplete="off">
			</div>
		</div>

		<form method="post" action="" id="bgmg-tarifas-form">
			<?php wp_nonce_field( 'bgmg_chile_save_tarifas_rm', 'bgmg_chile_tarifas_nonce' ); ?>

			<table class="widefat bgmg-tarifas-tabla">
				<thead>
					<tr>
						<th class="col-comuna"><?php esc_html_e( 'Comuna', 'bgmg-chile' ); ?></th>
						<th class="col-tipo"><?php esc_html_e( 'Tipo de tarifa', 'bgmg-chile' ); ?></th>
						<th class="col-precio"><?php esc_html_e( 'Precio (CLP)', 'bgmg-chile' ); ?></th>
						<th class="col-retiro"><?php esc_html_e( 'Retiro en tienda', 'bgmg-chile' ); ?></th>
						<th class="col-estado"><?php esc_html_e( 'Estado actual', 'bgmg-chile' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $comunas_rm as $comuna ) :
						$slug       = $comuna['slug'];
						$nombre     = $comuna['nombre'];
						$registro   = isset( $tarifas_actuales[ $slug ] ) ? $tarifas_actuales[ $slug ] : null;
						$tipo       = bgmg_chile_calc_tipo( $registro );
						$precio_val = $registro ? (int) $registro['precio'] : '';
						$retiro_val = $registro ? (int) $registro['retiro_disponible'] : 0;
						$search_key = remove_accents( strtolower( $nombre ) );
						?>
						<tr data-slug="<?php echo esc_attr( $slug ); ?>" data-tipo="<?php echo esc_attr( $tipo ); ?>" data-search="<?php echo esc_attr( $search_key ); ?>">
							<td class="col-comuna">
								<strong><?php echo esc_html( $nombre ); ?></strong>
							</td>
							<td class="col-tipo">
								<div class="bgmg-radio-group">
									<label>
										<input type="radio" name="tarifas[<?php echo esc_attr( $slug ); ?>][tipo]" value="default" <?php checked( $tipo, 'default' ); ?> class="bgmg-tipo-radio">
										<span><?php esc_html_e( 'Default', 'bgmg-chile' ); ?></span>
									</label>
									<label>
										<input type="radio" name="tarifas[<?php echo esc_attr( $slug ); ?>][tipo]" value="custom" <?php checked( $tipo, 'custom' ); ?> class="bgmg-tipo-radio">
										<span><?php esc_html_e( 'Custom', 'bgmg-chile' ); ?></span>
									</label>
									<label>
										<input type="radio" name="tarifas[<?php echo esc_attr( $slug ); ?>][tipo]" value="por_pagar" <?php checked( $tipo, 'por_pagar' ); ?> class="bgmg-tipo-radio">
										<span><?php esc_html_e( 'Por pagar', 'bgmg-chile' ); ?></span>
									</label>
								</div>
							</td>
							<td class="col-precio">
								<input
									type="number"
									step="1"
									min="0"
									name="tarifas[<?php echo esc_attr( $slug ); ?>][precio]"
									value="<?php echo esc_attr( '' === $precio_val ? '' : (int) $precio_val ); ?>"
									placeholder="<?php echo esc_attr( (int) $default_rm ); ?>"
									class="bgmg-precio-input"
									<?php disabled( $tipo, 'default' ); ?>
									<?php disabled( $tipo, 'por_pagar' ); ?>
								/>
							</td>
							<td class="col-retiro">
								<label class="bgmg-retiro-label">
									<input type="checkbox" name="tarifas[<?php echo esc_attr( $slug ); ?>][retiro]" value="1" <?php checked( 1, $retiro_val ); ?> class="bgmg-retiro-cb">
									<span><?php esc_html_e( 'Ofrecer', 'bgmg-chile' ); ?></span>
								</label>
							</td>
							<td class="col-estado">
								<span class="bgmg-badge bgmg-badge-<?php echo esc_attr( $tipo ); ?>" data-badge>
									<?php echo bgmg_chile_render_badge_label( $tipo, $precio_val, $default_rm ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="bgmg-tarifas-empty" style="display:none;">
				<p><?php esc_html_e( 'No hay comunas que coincidan con la búsqueda / filtro.', 'bgmg-chile' ); ?></p>
			</div>

			<p class="submit bgmg-tarifas-save">
				<button type="submit" class="button button-primary button-large">
					<?php esc_html_e( 'Guardar cambios', 'bgmg-chile' ); ?>
				</button>
				<span class="bgmg-tarifas-save-hint">
					<?php esc_html_e( 'Los cambios solo se aplican al hacer click en Guardar.', 'bgmg-chile' ); ?>
				</span>
			</p>
		</form>

		<div class="bgmg-tarifas-help">
			<h2><?php esc_html_e( '¿Cómo se interpretan los tipos?', 'bgmg-chile' ); ?></h2>
			<div class="bgmg-help-grid">
				<div class="bgmg-help-item">
					<span class="bgmg-badge bgmg-badge-default"><?php esc_html_e( 'Default', 'bgmg-chile' ); ?></span>
					<p><?php
					printf(
						/* translators: %s: monto formateado */
						esc_html__( 'La comuna cobra el default RM (%s). Lo más común — solo configurás excepciones.', 'bgmg-chile' ),
						esc_html( bgmg_chile_clp_short( $default_rm ) )
					);
					?></p>
				</div>
				<div class="bgmg-help-item">
					<span class="bgmg-badge bgmg-badge-custom"><?php esc_html_e( 'Custom', 'bgmg-chile' ); ?></span>
					<p><?php esc_html_e( 'Esta comuna cobra un precio específico distinto al default. Usalo cuando el courier te cobra más (comunas alejadas) o menos (comunas cercanas).', 'bgmg-chile' ); ?></p>
				</div>
				<div class="bgmg-help-item">
					<span class="bgmg-badge bgmg-badge-por_pagar"><?php esc_html_e( 'Por pagar', 'bgmg-chile' ); ?></span>
					<p><?php esc_html_e( 'El cliente paga el flete al recibir. Útil para comunas RM donde NO querés cobrar tarifa fija (riesgo de pérdida).', 'bgmg-chile' ); ?></p>
				</div>
				<div class="bgmg-help-item">
					<span class="bgmg-badge bgmg-badge-retiro"><?php esc_html_e( 'Retiro', 'bgmg-chile' ); ?></span>
					<p><?php esc_html_e( 'Independiente del tipo: el cliente puede elegir retirar el pedido en tienda como alternativa al despacho.', 'bgmg-chile' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  2. HELPERS DE RENDER
 * ------------------------------------------------------------------------- */

/**
 * Calcula el "tipo" lógico desde el registro de la tabla.
 *   - null (sin registro) → 'default'
 *   - activo=1 + precio>0 → 'custom'
 *   - activo=0 (o precio=0 con activo=1, estado raro) → 'por_pagar'
 */
function bgmg_chile_calc_tipo( $registro ) {
	if ( ! $registro ) {
		return 'default';
	}
	if ( ! empty( $registro['activo'] ) && (float) ( $registro['precio'] ?? 0 ) > 0 ) {
		return 'custom';
	}
	return 'por_pagar';
}

/**
 * Texto que va dentro del badge según el tipo.
 */
function bgmg_chile_render_badge_label( $tipo, $precio_val, $default_rm ) {
	switch ( $tipo ) {
		case 'custom':
			return esc_html( bgmg_chile_clp_short( $precio_val ) );
		case 'por_pagar':
			return esc_html__( 'Por pagar', 'bgmg-chile' );
		case 'default':
		default:
			return esc_html( bgmg_chile_clp_short( $default_rm ) ) . ' <span style="opacity:0.7;font-size:0.9em;">' . esc_html__( '(default)', 'bgmg-chile' ) . '</span>';
	}
}

/**
 * Formato corto CLP ($3.500).
 */
function bgmg_chile_clp_short( $monto ) {
	$monto = (int) $monto;
	if ( $monto <= 0 ) {
		return '—';
	}
	return '$' . number_format( $monto, 0, ',', '.' );
}

/**
 * Lee la tarifa default RM configurada en el shipping method.
 * Si no hay shipping method o tiene default_rm = 0, retorna 0.
 *
 * @return float
 */
function bgmg_chile_get_default_rm() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return 0.0;
	}
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'bgmg_chile_envio' === $method->id && isset( $method->default_rm ) ) {
				return (float) $method->default_rm;
			}
		}
	}
	return 0.0;
}

/* ------------------------------------------------------------------------- *
 *  3. PERSISTENCIA: GUARDAR DESDE POST
 * ------------------------------------------------------------------------- */

/**
 * Procesa el POST de la pantalla admin y persiste cada fila.
 * Devuelve un mensaje legible para mostrar como notice.
 */
function bgmg_chile_save_tarifas_rm_post() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return '';
	}

	$rows = isset( $_POST['tarifas'] ) && is_array( $_POST['tarifas'] )
		? wp_unslash( $_POST['tarifas'] )
		: array();

	$cambios       = 0;
	$comunas_rm    = bgmg_chile_get_comunas_por_region();
	$comunas_rm    = isset( $comunas_rm['RM'] ) ? $comunas_rm['RM'] : array();
	$slugs_validos = array_column( $comunas_rm, 'slug' );

	foreach ( $rows as $slug => $datos ) {

		$slug = sanitize_key( $slug );
		if ( ! in_array( $slug, $slugs_validos, true ) ) {
			continue;
		}

		$tipo   = isset( $datos['tipo'] ) ? sanitize_key( $datos['tipo'] ) : 'default';
		$precio = isset( $datos['precio'] ) ? (float) $datos['precio'] : 0.0;
		$retiro = ! empty( $datos['retiro'] ) ? 1 : 0;
		$nombre = bgmg_chile_get_comuna_nombre( $slug );

		switch ( $tipo ) {

			case 'custom':
				// Tarifa específica: activo=1 + precio>0.
				if ( $precio <= 0 ) {
					// Inválido: caen a default silenciosamente (no es legítimo "custom" sin precio).
					if ( $retiro ) {
						// Mantenemos registro solo por retiro_disponible.
						bgmg_chile_upsert_tarifa_rm( $slug, $nombre, 0, 0, $retiro );
					} else {
						bgmg_chile_delete_tarifa_rm( $slug );
					}
				} else {
					bgmg_chile_upsert_tarifa_rm( $slug, $nombre, $precio, 1, $retiro );
				}
				$cambios++;
				break;

			case 'por_pagar':
				// "Por pagar" manual: activo=0 + precio=0.
				bgmg_chile_upsert_tarifa_rm( $slug, $nombre, 0, 0, $retiro );
				$cambios++;
				break;

			case 'default':
			default:
				// Volver a default: si tiene retiro, mantener el registro solo
				// para guardar el flag de retiro. Si NO tiene retiro, eliminar
				// el registro (no_registro = default RM puro).
				if ( $retiro ) {
					bgmg_chile_upsert_tarifa_rm( $slug, $nombre, 0, 0, $retiro );
				} else {
					bgmg_chile_delete_tarifa_rm( $slug );
				}
				$cambios++;
				break;
		}
	}

	bgmg_chile_clear_tarifas_cache();

	return sprintf(
		/* translators: %d: cantidad de filas */
		_n( 'Se actualizó %d comuna.', 'Se actualizaron %d comunas.', $cambios, 'bgmg-chile' ),
		$cambios
	);
}

/**
 * Inserta o actualiza una tarifa + flag de retiro para una comuna.
 *
 * @param string $slug
 * @param string $nombre
 * @param float  $precio
 * @param int    $activo
 * @param int    $retiro_disponible
 */
function bgmg_chile_upsert_tarifa_rm( $slug, $nombre, $precio, $activo, $retiro_disponible = 0 ) {
	global $wpdb;
	$tabla = $wpdb->prefix . 'bgmg_chile_tarifas_rm';

	$exists = $wpdb->get_var(
		$wpdb->prepare( "SELECT id FROM {$tabla} WHERE comuna_slug = %s LIMIT 1", $slug )
	);

	if ( $exists ) {
		$wpdb->update(
			$tabla,
			array(
				'comuna_nombre'     => $nombre,
				'precio'            => $precio,
				'activo'            => $activo,
				'retiro_disponible' => (int) $retiro_disponible,
			),
			array( 'id' => (int) $exists ),
			array( '%s', '%f', '%d', '%d' ),
			array( '%d' )
		);
	} else {
		$wpdb->insert(
			$tabla,
			array(
				'comuna_slug'       => $slug,
				'comuna_nombre'     => $nombre,
				'precio'            => $precio,
				'activo'            => $activo,
				'retiro_disponible' => (int) $retiro_disponible,
			),
			array( '%s', '%s', '%f', '%d', '%d' )
		);
	}
}

/**
 * Elimina el registro de una comuna (vuelve a default RM puro).
 *
 * @param string $slug
 * @return int Filas eliminadas (0 o 1).
 */
function bgmg_chile_delete_tarifa_rm( $slug ) {
	global $wpdb;
	$tabla = $wpdb->prefix . 'bgmg_chile_tarifas_rm';
	return (int) $wpdb->delete( $tabla, array( 'comuna_slug' => $slug ), array( '%s' ) );
}

/* ------------------------------------------------------------------------- *
 *  4. CONSULTA: usada por el shipping method
 * ------------------------------------------------------------------------- */

/**
 * Carga todas las tarifas en un array indexado por slug. Cacheado a nivel
 * de request.
 *
 * @return array<string, array{precio:float, activo:int, retiro_disponible:int}>
 */
function bgmg_chile_load_all_tarifas_rm() {
	global $bgmg_chile_tarifas_cache;

	if ( is_array( $bgmg_chile_tarifas_cache ) ) {
		return $bgmg_chile_tarifas_cache;
	}

	global $wpdb;
	$tabla = $wpdb->prefix . 'bgmg_chile_tarifas_rm';

	$rows = $wpdb->get_results( "SELECT comuna_slug, precio, activo, retiro_disponible FROM {$tabla}", ARRAY_A );
	$bgmg_chile_tarifas_cache = array();
	if ( $rows ) {
		foreach ( $rows as $r ) {
			$bgmg_chile_tarifas_cache[ $r['comuna_slug'] ] = array(
				'precio'            => (float) $r['precio'],
				'activo'            => (int) $r['activo'],
				'retiro_disponible' => (int) $r['retiro_disponible'],
			);
		}
	}
	return $bgmg_chile_tarifas_cache;
}

/**
 * ¿La comuna está marcada como disponible para retiro en tienda?
 */
function bgmg_chile_comuna_acepta_retiro( $comuna_slug ) {
	if ( '' === $comuna_slug ) {
		return false;
	}
	$todas = bgmg_chile_load_all_tarifas_rm();
	if ( ! isset( $todas[ $comuna_slug ] ) ) {
		return false;
	}
	return ! empty( $todas[ $comuna_slug ]['retiro_disponible'] );
}

/**
 * Invalida la caché en memoria.
 */
function bgmg_chile_clear_tarifas_cache() {
	global $bgmg_chile_tarifas_cache;
	$bgmg_chile_tarifas_cache = null;
}

/**
 * Devuelve la tarifa fija (float CLP) para una comuna RM activa.
 * null si no hay tarifa fija aplicable (entonces va "Por pagar").
 *
 * @param string $comuna_slug
 * @param string $region_code Código de región. Aplica solo si es 'RM'.
 * @return float|null
 */
function bgmg_chile_get_tarifa_fija( $comuna_slug, $region_code = '' ) {

	if ( '' !== $region_code && 'RM' !== $region_code ) {
		return null;
	}
	if ( '' === $region_code && $comuna_slug ) {
		$inferida = bgmg_chile_get_region_de_comuna( $comuna_slug );
		if ( 'RM' !== $inferida ) {
			return null;
		}
	}

	$todas = bgmg_chile_load_all_tarifas_rm();

	if ( ! isset( $todas[ $comuna_slug ] ) ) {
		return null;
	}
	$row = $todas[ $comuna_slug ];
	if ( empty( $row['activo'] ) ) {
		return null;
	}
	if ( (float) $row['precio'] <= 0 ) {
		return null;
	}
	return (float) $row['precio'];
}

/* ------------------------------------------------------------------------- *
 *  5. ASSETS — CSS inline + script enqueued.
 *
 *  Solo se cargan en la pantalla de tarifas RM.
 * ------------------------------------------------------------------------- */

add_action( 'admin_head', 'bgmg_chile_tarifas_rm_inline_styles' );

function bgmg_chile_tarifas_rm_inline_styles() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || false === strpos( (string) $screen->id, 'bgmg-chile-tarifas-rm' ) ) {
		return;
	}
	?>
	<style>
		.bgmg-tarifas-wrap { max-width: 1280px; }
		.bgmg-tarifas-title { font-size: 24px; margin: 16px 0 4px; }
		.bgmg-tarifas-sub { color: #7A5060; margin: 0 0 20px; }

		.bgmg-tarifas-summary {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 12px;
			margin-bottom: 20px;
		}
		.bgmg-summary-card {
			background: #fff;
			border: 1px solid #f0e0e5;
			border-radius: 10px;
			padding: 16px 18px;
			display: flex; flex-direction: column; gap: 4px;
		}
		.bgmg-summary-card .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #7A5060; font-weight: 600; }
		.bgmg-summary-card .num { font-size: 32px; color: #C4728A; line-height: 1; font-weight: 700; }
		.bgmg-summary-card .hint { font-size: 12px; color: #999; }

		.bgmg-tarifas-toolbar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 12px;
			margin-bottom: 12px;
			flex-wrap: wrap;
		}
		.bgmg-tarifas-filters { display: flex; gap: 6px; flex-wrap: wrap; }
		.bgmg-filter {
			background: #fff;
			border: 1px solid #f0e0e5;
			padding: 7px 14px;
			border-radius: 99px;
			cursor: pointer;
			font-size: 13px;
			color: #7A5060;
			font-family: inherit;
			transition: all 0.15s;
		}
		.bgmg-filter:hover { color: #1A1015; border-color: #C4728A; }
		.bgmg-filter.is-active { background: #C4728A; color: #fff; border-color: #C4728A; font-weight: 600; }
		.bgmg-filter .cnt {
			margin-left: 4px;
			background: rgba(0,0,0,0.08);
			padding: 1px 7px;
			border-radius: 99px;
			font-size: 11px;
		}
		.bgmg-filter.is-active .cnt { background: rgba(255,255,255,0.25); }

		.bgmg-tarifas-search input {
			background: #fff;
			border: 1px solid #f0e0e5;
			border-radius: 99px;
			padding: 7px 16px;
			min-width: 240px;
			font-size: 13px;
		}
		.bgmg-tarifas-search input:focus { border-color: #C4728A; outline: 0; }

		.bgmg-tarifas-tabla {
			background: #fff;
			border-collapse: collapse;
			width: 100%;
			border: 1px solid #f0e0e5;
			border-radius: 10px;
			overflow: hidden;
		}
		.bgmg-tarifas-tabla thead th {
			background: #FBF0F2 !important;
			color: #1A1015;
			font-weight: 600;
			padding: 10px 14px;
			font-size: 12px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		.bgmg-tarifas-tabla tbody tr {
			transition: background 0.15s;
		}
		.bgmg-tarifas-tabla tbody tr:hover { background: #FDF7F4; }
		.bgmg-tarifas-tabla tbody tr.is-hidden { display: none; }
		.bgmg-tarifas-tabla td { padding: 10px 14px; vertical-align: middle; border-top: 1px solid #f5e6ea; }
		.bgmg-tarifas-tabla .col-comuna { width: 22%; }
		.bgmg-tarifas-tabla .col-tipo { width: 28%; }
		.bgmg-tarifas-tabla .col-precio { width: 16%; }
		.bgmg-tarifas-tabla .col-retiro { width: 14%; }
		.bgmg-tarifas-tabla .col-estado { width: 20%; }

		.bgmg-radio-group { display: flex; gap: 4px; flex-wrap: wrap; }
		.bgmg-radio-group label {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			padding: 4px 10px;
			border: 1px solid #f0e0e5;
			border-radius: 99px;
			cursor: pointer;
			background: #fff;
			font-size: 12px;
			transition: all 0.15s;
		}
		.bgmg-radio-group label:hover { border-color: #C4728A; }
		.bgmg-radio-group input[type="radio"] { margin: 0; }
		.bgmg-radio-group label:has(input:checked) { background: #C4728A; color: #fff; border-color: #C4728A; font-weight: 600; }

		.bgmg-precio-input {
			width: 100px;
			padding: 6px 10px;
			border: 1px solid #f0e0e5;
			border-radius: 6px;
			font-size: 13px;
		}
		.bgmg-precio-input:disabled {
			background: #f5f5f5;
			color: #999;
			border-color: #eee;
		}
		.bgmg-precio-input:focus { border-color: #C4728A; outline: 0; }

		.bgmg-retiro-label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; }

		.bgmg-badge {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 99px;
			font-size: 12px;
			font-weight: 600;
			white-space: nowrap;
		}
		.bgmg-badge-default { background: #e3f2fd; color: #0d47a1; }
		.bgmg-badge-custom { background: #e8f5e9; color: #1b5e20; }
		.bgmg-badge-por_pagar { background: #fff3e0; color: #e65100; }
		.bgmg-badge-retiro { background: #f3e5f5; color: #4a148c; }

		.bgmg-tarifas-empty {
			background: #fff;
			border: 1px dashed #f0d0d8;
			border-radius: 10px;
			padding: 32px;
			text-align: center;
			color: #7A5060;
			margin: 16px 0;
		}

		.bgmg-tarifas-save {
			margin-top: 20px;
			padding: 16px 20px;
			background: #FBF0F2;
			border-radius: 10px;
			display: flex; gap: 14px; align-items: center; flex-wrap: wrap;
		}
		.bgmg-tarifas-save .button-large {
			padding: 8px 24px !important;
			font-size: 14px !important;
		}
		.bgmg-tarifas-save-hint { font-size: 12px; color: #7A5060; }

		.bgmg-tarifas-help {
			margin-top: 28px;
			padding: 20px 24px;
			background: #fff;
			border: 1px solid #f0e0e5;
			border-radius: 10px;
		}
		.bgmg-tarifas-help h2 { font-size: 15px; margin: 0 0 14px; color: #1A1015; }
		.bgmg-help-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 14px;
		}
		.bgmg-help-item { display: flex; flex-direction: column; gap: 6px; }
		.bgmg-help-item p { margin: 0; color: #7A5060; font-size: 12px; line-height: 1.4; }

		@media (max-width: 900px) {
			.bgmg-tarifas-tabla thead { display: none; }
			.bgmg-tarifas-tabla tr { display: block; margin-bottom: 12px; border: 1px solid #f0e0e5; border-radius: 10px; padding: 8px; }
			.bgmg-tarifas-tabla td { display: block; border: 0; padding: 6px 8px; }
			.bgmg-tarifas-tabla td::before { content: attr(data-label); font-weight: 600; color: #7A5060; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 4px; }
		}
	</style>
	<?php
}
