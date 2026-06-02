<?php
/**
 * Asistente de Checkout: RUT/facturación + teléfono móvil.
 *
 * Pantalla informativa que muestra el estado de los módulos de checkout
 * que el plugin agrega:
 *   - RUT (obligatorio en checkout + opcional en registro) + toggle factura.
 *   - Teléfono móvil chileno obligatorio en checkout.
 *
 * A diferencia del asistente de envíos (que crea zonas, métodos, etc.),
 * estos módulos no requieren configuración: están siempre activos cuando el
 * plugin lo está. La pantalla cumple un rol de visibilidad y onboarding:
 *   - Le muestra a la dueña que están activos.
 *   - Le da estadísticas reales (cuántos clientes tienen RUT, cuántas órdenes
 *     pidieron factura, etc.).
 *   - Le indica dónde se ve y se gestiona.
 *
 * Si en el futuro hacen falta toggles (ej. "permitir compra sin RUT", "aceptar
 * fijos", etc.) se agregan acá sin tocar el resto del plugin.
 *
 * Se registra como submenú dentro de "Despachos BGMG" para mantener la
 * navegación consolidada (admin-despachos-menu.php prio 70, este prio 80,
 * después del Asistente de Envíos prio 80).
 *
 * @package BGMG_Chile
 * @since 1.14.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BGMG_CHILE_WIZARD_CHECKOUT_SLUG' ) ) {
	define( 'BGMG_CHILE_WIZARD_CHECKOUT_SLUG', 'bgmg-despachos-checkout' );
}

/* ------------------------------------------------------------------------- *
 *  REGISTRO DEL SUBMENÚ
 * ------------------------------------------------------------------------- */

add_action( 'admin_menu', 'bgmg_chile_wizard_checkout_register_submenu', 81 );

function bgmg_chile_wizard_checkout_register_submenu() {
	// Helper con fallback a top-level. Ver inc/helpers.php.
	bgmg_chile_wizard_register_submenu(
		__( 'Asistente Checkout — BGMG Chile', 'bgmg-chile' ),
		__( '🛒 Asistente Checkout', 'bgmg-chile' ),
		'manage_woocommerce',
		BGMG_CHILE_WIZARD_CHECKOUT_SLUG,
		'bgmg_chile_wizard_checkout_render'
	);
}

/* ------------------------------------------------------------------------- *
 *  RENDER PRINCIPAL
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_checkout_render() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bgmg-chile' ) );
	}

	if ( ! bgmg_chile_wizard_preflight_check() ) {
		return;
	}

	$rut_stats     = bgmg_chile_wizard_checkout_rut_stats();
	$telefono_stats = bgmg_chile_wizard_checkout_telefono_stats();
	$checkout_url  = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '';

	?>
	<div class="wrap bgmg-wizard">
		<h1 class="bgmg-wizard-title">
			🛒 <?php esc_html_e( 'Asistente Checkout', 'bgmg-chile' ); ?>
		</h1>
		<p class="bgmg-wizard-intro">
			<?php esc_html_e( 'Estado de los módulos del checkout: RUT/facturación y teléfono móvil. Estos módulos están siempre activos mientras el plugin lo está — la pantalla los muestra para que sepas qué está pasando en tu checkout.', 'bgmg-chile' ); ?>
		</p>

		<?php bgmg_chile_wizard_checkout_render_seccion_rut( $rut_stats, $checkout_url ); ?>
		<?php bgmg_chile_wizard_checkout_render_seccion_telefono( $telefono_stats, $checkout_url ); ?>

		<div class="bgmg-wizard-paso" style="border-left-color: #C4728A;">
			<h2>
				<span class="bgmg-wizard-num" style="background:#FBF0F2;color:#C4728A;">💡</span>
				<?php esc_html_e( '¿Qué más se configura en el checkout?', 'bgmg-chile' ); ?>
			</h2>
			<p>
				<?php esc_html_e( 'La región y la comuna en cascada se manejan automáticamente (no requieren configuración). El método de envío, tarifas RM y retiro se gestionan en el otro asistente.', 'bgmg-chile' ); ?>
			</p>
			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bgmg-despachos-asistente' ) ); ?>">
					🪄 <?php esc_html_e( 'Ir al Asistente de Envíos', 'bgmg-chile' ); ?>
				</a>
			</p>
		</div>
	</div>

	<?php bgmg_chile_wizard_checkout_render_styles(); ?>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  SECCIÓN: RUT y facturación
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_checkout_render_seccion_rut( $stats, $checkout_url ) {
	?>
	<section class="bgmg-wizard-paso ok">
		<h2>
			<span class="bgmg-wizard-num">✓</span>
			<?php esc_html_e( 'RUT y facturación', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-ok"><?php esc_html_e( 'Activo', 'bgmg-chile' ); ?></span>
		</h2>
		<p>
			<?php esc_html_e( 'En el checkout aparece el campo "RUT" obligatorio (con validación módulo 11 en JS y PHP) y un toggle "Necesito factura" que despliega razón social, giro y dirección comercial cuando el cliente lo marca.', 'bgmg-chile' ); ?>
		</p>

		<div class="bgmg-wizard-stats">
			<div class="bgmg-wizard-stat">
				<strong><?php echo (int) $stats['clientes_con_rut']; ?></strong>
				<span><?php esc_html_e( 'clientes con RUT guardado', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-wizard-stat">
				<strong><?php echo (int) $stats['ordenes_con_factura_30d']; ?></strong>
				<span><?php esc_html_e( 'órdenes pidieron factura (últimos 30 días)', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-wizard-stat">
				<strong><?php echo (int) $stats['ordenes_con_rut_30d']; ?></strong>
				<span><?php esc_html_e( 'órdenes con RUT (últimos 30 días)', 'bgmg-chile' ); ?></span>
			</div>
		</div>

		<div class="bgmg-wizard-info">
			<strong><?php esc_html_e( 'Cómo se procesa:', 'bgmg-chile' ); ?></strong>
			<?php esc_html_e( 'La facturación electrónica es manual — emites boleta o factura según el toggle del cliente. El plugin guarda RUT y datos de empresa en la orden y en el perfil del cliente para que no los reingrese.', 'bgmg-chile' ); ?>
		</div>

		<div class="bgmg-wizard-acciones">
			<?php if ( $checkout_url ) : ?>
				<a class="button" href="<?php echo esc_url( $checkout_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Ver el checkout ↗', 'bgmg-chile' ); ?>
				</a>
			<?php endif; ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
				<?php esc_html_e( 'Ver clientes (columna RUT)', 'bgmg-chile' ); ?>
			</a>
		</div>
	</section>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  SECCIÓN: Teléfono móvil
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_checkout_render_seccion_telefono( $stats, $checkout_url ) {
	?>
	<section class="bgmg-wizard-paso ok">
		<h2>
			<span class="bgmg-wizard-num">✓</span>
			<?php esc_html_e( 'Teléfono móvil', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-ok"><?php esc_html_e( 'Activo', 'bgmg-chile' ); ?></span>
		</h2>
		<p>
			<?php esc_html_e( 'El campo "Teléfono móvil" aparece obligatorio en checkout. Solo se aceptan móviles chilenos (empieza con 9, 9 dígitos). Se normaliza al formato +56 9 XXXX XXXX antes de guardar.', 'bgmg-chile' ); ?>
		</p>

		<div class="bgmg-wizard-stats">
			<div class="bgmg-wizard-stat">
				<strong><?php echo (int) $stats['ordenes_con_telefono_30d']; ?></strong>
				<span><?php esc_html_e( 'órdenes con móvil (últimos 30 días)', 'bgmg-chile' ); ?></span>
			</div>
			<?php if ( $stats['ordenes_con_telefono_invalido_30d'] > 0 ) : ?>
				<div class="bgmg-wizard-stat bgmg-wizard-stat-warn">
					<strong><?php echo (int) $stats['ordenes_con_telefono_invalido_30d']; ?></strong>
					<span><?php esc_html_e( 'con formato sospechoso (anteriores al plugin)', 'bgmg-chile' ); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<div class="bgmg-wizard-info">
			<strong><?php esc_html_e( 'Por qué obligatorio:', 'bgmg-chile' ); ?></strong>
			<?php esc_html_e( 'El móvil se usa para coordinar despachos por WhatsApp con el courier o con el cliente. Los fijos quedan rechazados porque no sirven para WhatsApp ni para SMS.', 'bgmg-chile' ); ?>
		</div>

		<div class="bgmg-wizard-acciones">
			<?php if ( $checkout_url ) : ?>
				<a class="button" href="<?php echo esc_url( $checkout_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Ver el checkout ↗', 'bgmg-chile' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  ESTADÍSTICAS
 *
 *  Queries baratas usando wc_get_orders + get_users. Se calculan en cada
 *  carga (no cacheamos): la pantalla se abre pocas veces y los datos en
 *  vivo evitan que la dueña vea info desactualizada.
 * ------------------------------------------------------------------------- */

/**
 * Estadísticas del módulo RUT/factura.
 *
 * @return array{clientes_con_rut:int, ordenes_con_factura_30d:int, ordenes_con_rut_30d:int}
 */
function bgmg_chile_wizard_checkout_rut_stats() {
	$out = array(
		'clientes_con_rut'       => 0,
		'ordenes_con_factura_30d' => 0,
		'ordenes_con_rut_30d'    => 0,
	);

	// Clientes con RUT: usamos meta_query sobre user_meta '_bgmg_rut'.
	$users = get_users( array(
		'meta_key'   => '_bgmg_rut',
		'meta_compare' => 'EXISTS',
		'fields'     => 'ID',
		'number'     => 9999,
	) );
	$out['clientes_con_rut'] = count( $users );

	// Órdenes últimos 30 días con factura / con RUT.
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return $out;
	}
	$desde   = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
	$pedidos = wc_get_orders( array(
		'limit'        => -1,
		'date_created' => '>=' . $desde,
		'status'       => array( 'processing', 'completed', 'on-hold' ),
		'return'       => 'objects',
	) );

	foreach ( $pedidos as $order ) {
		if ( 'CL' !== $order->get_billing_country() ) {
			continue;
		}
		$rut = $order->get_meta( '_bgmg_rut' );
		if ( $rut ) {
			$out['ordenes_con_rut_30d']++;
		}
		if ( 'si' === $order->get_meta( '_bgmg_necesita_factura' ) ) {
			$out['ordenes_con_factura_30d']++;
		}
	}

	return $out;
}

/**
 * Estadísticas del módulo teléfono.
 *
 * @return array{ordenes_con_telefono_30d:int, ordenes_con_telefono_invalido_30d:int}
 */
function bgmg_chile_wizard_checkout_telefono_stats() {
	$out = array(
		'ordenes_con_telefono_30d'         => 0,
		'ordenes_con_telefono_invalido_30d' => 0,
	);

	if ( ! function_exists( 'wc_get_orders' ) ) {
		return $out;
	}
	$desde   = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
	$pedidos = wc_get_orders( array(
		'limit'        => -1,
		'date_created' => '>=' . $desde,
		'status'       => array( 'processing', 'completed', 'on-hold' ),
		'return'       => 'objects',
	) );

	$validador_disponible = class_exists( 'BGMG_Chile_Telefono_Validator' );

	foreach ( $pedidos as $order ) {
		if ( 'CL' !== $order->get_billing_country() ) {
			continue;
		}
		$telefono = (string) $order->get_billing_phone();
		if ( '' === trim( $telefono ) ) {
			continue;
		}

		if ( $validador_disponible && BGMG_Chile_Telefono_Validator::is_valid_movil( $telefono ) ) {
			$out['ordenes_con_telefono_30d']++;
		} else {
			// Órdenes antiguas (anteriores al plugin) o con datos importados:
			// pueden tener teléfono pero no en formato móvil chileno válido.
			$out['ordenes_con_telefono_invalido_30d']++;
		}
	}

	return $out;
}

/* ------------------------------------------------------------------------- *
 *  ESTILOS INLINE
 *
 *  Reutilizan los estilos del wizard de envíos donde aplica + agregan los
 *  específicos de stats/grid. Como el wizard de envíos solo carga sus estilos
 *  en su propia pantalla, los redeclaramos acá para no acoplar archivos.
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_checkout_render_styles() {
	?>
	<style>
		.bgmg-wizard { max-width: 920px; }
		.bgmg-wizard-title { font-size: 26px; margin: 16px 0 6px; color: #1A1015; }
		.bgmg-wizard-intro { font-size: 14px; color: #555; max-width: 760px; line-height: 1.5; margin-bottom: 24px; }

		.bgmg-wizard-paso {
			background: #fff;
			border: 1px solid #e8d8dd;
			border-left: 4px solid #ccc;
			border-radius: 8px;
			padding: 20px 24px;
			margin-bottom: 16px;
			box-shadow: 0 1px 2px rgba(26,16,21,.03);
		}
		.bgmg-wizard-paso.ok { border-left-color: #4caf50; }

		.bgmg-wizard-paso h2 {
			display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
			margin: 0 0 10px;
			font-size: 17px;
			color: #1A1015;
		}
		.bgmg-wizard-num {
			width: 28px; height: 28px; border-radius: 50%;
			background: #E8F5E9; color: #2E7D32;
			display: inline-flex; align-items: center; justify-content: center;
			font-size: 14px; font-weight: 700;
			flex-shrink: 0;
		}
		.bgmg-wizard-badge {
			margin-left: auto;
			font-size: 11px; padding: 3px 10px; border-radius: 12px;
			font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
		}
		.bgmg-wizard-badge-ok { background: #E8F5E9; color: #2E7D32; }
		.bgmg-wizard-paso p { margin: 8px 0; color: #555; line-height: 1.5; font-size: 14px; }

		.bgmg-wizard-stats {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
			gap: 12px;
			margin: 14px 0;
		}
		.bgmg-wizard-stat {
			background: #FBF0F2;
			padding: 14px 16px;
			border-radius: 6px;
			text-align: center;
		}
		.bgmg-wizard-stat strong {
			display: block;
			font-size: 28px;
			color: #C4728A;
			line-height: 1;
			font-weight: 700;
		}
		.bgmg-wizard-stat span {
			display: block;
			margin-top: 6px;
			font-size: 12px;
			color: #7A5060;
			line-height: 1.3;
		}
		.bgmg-wizard-stat-warn { background: #FFF3E0; }
		.bgmg-wizard-stat-warn strong { color: #A0561B; }
		.bgmg-wizard-stat-warn span { color: #6E4115; }

		.bgmg-wizard-info {
			background: #F1F8E9;
			padding: 12px 16px;
			border-radius: 6px;
			font-size: 13px;
			color: #1A1015;
			margin: 14px 0;
		}
		.bgmg-wizard-info strong { color: #2E7D32; display: inline-block; margin-right: 4px; }

		.bgmg-wizard-acciones { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
	</style>
	<?php
}
