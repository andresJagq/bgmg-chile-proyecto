<?php
/**
 * Asistente de operativa diaria: tracking de envíos + etiquetas de despacho.
 *
 * Pantalla informativa con foco operacional. Muestra:
 *
 *   1. Tracking de envío:
 *      - Cuántas órdenes tienen código de tracking cargado.
 *      - Cuántas están marcadas "despachado" pero sin código (alerta).
 *      - Cuántas llevan "preparando" más de 3 días (alerta).
 *      - Cuántos emails de tracking se enviaron al cliente (últimos 30 días).
 *
 *   2. Etiquetas de despacho:
 *      - Cuántas órdenes están pagadas sin estado de despacho asignado.
 *      - Cómo se imprime una etiqueta (metabox en el editor de orden).
 *      - Acción masiva "Imprimir etiquetas BGMG" desde la lista de pedidos.
 *
 * Submenú dentro de "Despachos BGMG" — sigue el patrón de los otros wizards.
 *
 * @package BGMG_Chile
 * @since 1.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BGMG_CHILE_WIZARD_OPERATIVA_SLUG' ) ) {
	define( 'BGMG_CHILE_WIZARD_OPERATIVA_SLUG', 'bgmg-despachos-operativa' );
}

/* ------------------------------------------------------------------------- *
 *  REGISTRO DEL SUBMENÚ
 * ------------------------------------------------------------------------- */

add_action( 'admin_menu', 'bgmg_chile_wizard_operativa_register_submenu', 82 );

function bgmg_chile_wizard_operativa_register_submenu() {
	// Helper con fallback a top-level. Ver inc/helpers.php.
	bgmg_chile_wizard_register_submenu(
		__( 'Operativa diaria — BGMG Chile', 'bgmg-chile' ),
		__( '📦 Operativa diaria', 'bgmg-chile' ),
		'manage_woocommerce',
		BGMG_CHILE_WIZARD_OPERATIVA_SLUG,
		'bgmg_chile_wizard_operativa_render'
	);
}

/* ------------------------------------------------------------------------- *
 *  RENDER
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_operativa_render() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos.', 'bgmg-chile' ) );
	}

	if ( ! bgmg_chile_wizard_preflight_check() ) {
		return;
	}

	$stats = bgmg_chile_wizard_operativa_stats();

	?>
	<div class="wrap bgmg-wizard">
		<h1 class="bgmg-wizard-title">
			📦 <?php esc_html_e( 'Operativa diaria', 'bgmg-chile' ); ?>
		</h1>
		<p class="bgmg-wizard-intro">
			<?php esc_html_e( 'Estado del día a día de despachos: tracking pendiente, etiquetas por imprimir y avisos al cliente. Se actualiza en vivo cada vez que abres esta pantalla.', 'bgmg-chile' ); ?>
		</p>

		<?php if ( $stats['alertas'] ) : ?>
			<div class="bgmg-wizard-alertas">
				<h3>⚠️ <?php esc_html_e( 'Atención', 'bgmg-chile' ); ?></h3>
				<ul>
					<?php foreach ( $stats['alertas'] as $alerta ) : ?>
						<li>
							<?php echo wp_kses_post( $alerta['texto'] ); ?>
							<?php if ( ! empty( $alerta['url'] ) ) : ?>
								<a href="<?php echo esc_url( $alerta['url'] ); ?>">
									<?php echo esc_html( $alerta['label'] ?? __( 'Revisar', 'bgmg-chile' ) ); ?> →
								</a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php bgmg_chile_wizard_operativa_render_seccion_tracking( $stats ); ?>
		<?php bgmg_chile_wizard_operativa_render_seccion_etiquetas( $stats ); ?>
	</div>

	<?php bgmg_chile_wizard_operativa_render_styles(); ?>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  SECCIÓN: TRACKING
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_operativa_render_seccion_tracking( $stats ) {
	?>
	<section class="bgmg-wizard-paso ok">
		<h2>
			<span class="bgmg-wizard-num">📦</span>
			<?php esc_html_e( 'Tracking de envíos', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-ok"><?php esc_html_e( 'Activo', 'bgmg-chile' ); ?></span>
		</h2>
		<p>
			<?php esc_html_e( 'En cada orden tienes un metabox "📦 Tracking de envío" donde cargas: estado del despacho (Preparando / Despachado / Listo para retiro), método/courier y código de seguimiento. Al marcar "Avisar al cliente" se le manda un email custom con los datos.', 'bgmg-chile' ); ?>
		</p>

		<div class="bgmg-wizard-stats">
			<div class="bgmg-wizard-stat">
				<strong><?php echo (int) $stats['tracking']['con_codigo_30d']; ?></strong>
				<span><?php esc_html_e( 'órdenes con código cargado (30 días)', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-wizard-stat <?php echo $stats['tracking']['despachadas_sin_codigo'] > 0 ? 'bgmg-wizard-stat-warn' : ''; ?>">
				<strong><?php echo (int) $stats['tracking']['despachadas_sin_codigo']; ?></strong>
				<span><?php esc_html_e( 'marcadas "despachado" sin código', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-wizard-stat <?php echo $stats['tracking']['preparando_atrasadas'] > 0 ? 'bgmg-wizard-stat-warn' : ''; ?>">
				<strong><?php echo (int) $stats['tracking']['preparando_atrasadas']; ?></strong>
				<span><?php esc_html_e( '"preparando" hace más de 3 días', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-wizard-stat">
				<strong><?php echo (int) $stats['tracking']['emails_enviados_30d']; ?></strong>
				<span><?php esc_html_e( 'avisos enviados al cliente (30 días)', 'bgmg-chile' ); ?></span>
			</div>
		</div>

		<div class="bgmg-wizard-info">
			<strong><?php esc_html_e( 'Recordatorio:', 'bgmg-chile' ); ?></strong>
			<?php esc_html_e( 'Los 3 estados internos son "Preparando", "Despachado" y "Listo para retiro" (este último solo en órdenes de retiro). No reemplazan el estado de WC (pago/orden), son un sub-estado operativo.', 'bgmg-chile' ); ?>
		</div>

		<div class="bgmg-wizard-acciones">
			<a class="button" href="<?php echo esc_url( bgmg_chile_admin_orders_url() ); ?>">
				<?php esc_html_e( 'Ver todos los pedidos', 'bgmg-chile' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=bgmg_chile_email_tracking' ) ); ?>">
				<?php esc_html_e( 'Configurar email de aviso', 'bgmg-chile' ); ?>
			</a>
		</div>
	</section>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  SECCIÓN: ETIQUETAS DE DESPACHO
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_operativa_render_seccion_etiquetas( $stats ) {
	?>
	<section class="bgmg-wizard-paso ok">
		<h2>
			<span class="bgmg-wizard-num">🏷️</span>
			<?php esc_html_e( 'Etiquetas de despacho', 'bgmg-chile' ); ?>
			<span class="bgmg-wizard-badge bgmg-wizard-badge-ok"><?php esc_html_e( 'Activo', 'bgmg-chile' ); ?></span>
		</h2>
		<p>
			<?php esc_html_e( 'En cada orden tienes un metabox "🏷️ Datos de despacho" con los 8 campos en el orden estándar (Nombre, RUT, Dirección, Comuna, Región, Correo, Método, ID), un botón "Copiar todo" para pegar en Starken/Bluexpress, y un botón "Imprimir etiqueta" que abre una vista limpia print-friendly.', 'bgmg-chile' ); ?>
		</p>

		<div class="bgmg-wizard-stats">
			<div class="bgmg-wizard-stat <?php echo $stats['etiquetas']['pagadas_sin_estado'] > 0 ? 'bgmg-wizard-stat-warn' : ''; ?>">
				<strong><?php echo (int) $stats['etiquetas']['pagadas_sin_estado']; ?></strong>
				<span><?php esc_html_e( 'pagadas sin estado de despacho asignado', 'bgmg-chile' ); ?></span>
			</div>
			<div class="bgmg-wizard-stat">
				<strong><?php echo (int) $stats['etiquetas']['en_preparacion']; ?></strong>
				<span><?php esc_html_e( 'en preparación (listas para etiqueta)', 'bgmg-chile' ); ?></span>
			</div>
		</div>

		<div class="bgmg-wizard-info">
			<strong>💡 <?php esc_html_e( 'Tip:', 'bgmg-chile' ); ?></strong>
			<?php esc_html_e( 'Para imprimir varias etiquetas a la vez, ve a la lista de pedidos, marca los que quieras y elige la acción masiva "Imprimir etiquetas BGMG" en el desplegable de arriba.', 'bgmg-chile' ); ?>
		</div>

		<div class="bgmg-wizard-acciones">
			<a class="button button-primary" href="<?php echo esc_url( bgmg_chile_admin_orders_url( 'processing' ) ); ?>">
				<?php esc_html_e( 'Ver pedidos pagados pendientes', 'bgmg-chile' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( bgmg_chile_admin_orders_url() ); ?>">
				<?php esc_html_e( 'Ver todos los pedidos', 'bgmg-chile' ); ?>
			</a>
		</div>
	</section>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  ESTADÍSTICAS
 *
 *  Una sola pasada por las órdenes recientes para calcular todos los stats
 *  + acumular alertas. Limitamos a últimos 60 días (suficiente para detectar
 *  pedidos "preparando" atrasados y mantener la query liviana).
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_operativa_stats() {

	$out = array(
		'tracking' => array(
			'con_codigo_30d'         => 0,
			'despachadas_sin_codigo' => 0,
			'preparando_atrasadas'   => 0,
			'emails_enviados_30d'    => 0,
		),
		'etiquetas' => array(
			'pagadas_sin_estado' => 0,
			'en_preparacion'     => 0,
		),
		'alertas' => array(),
	);

	if ( ! function_exists( 'wc_get_orders' ) ) {
		return $out;
	}

	// time() devuelve UTC. WC_DateTime::getTimestamp() también devuelve UTC,
	// así que la comparación es consistente. Antes usábamos
	// current_time('timestamp') que está deprecated desde WP 5.3 (mezcla UTC
	// con offset del sitio y emite warning con WP_DEBUG).
	$now           = time();
	$desde_60d     = gmdate( 'Y-m-d', strtotime( '-60 days' ) );
	$umbral_30d    = $now - ( 30 * DAY_IN_SECONDS );
	$umbral_atraso = $now - ( 3 * DAY_IN_SECONDS );

	$pedidos = wc_get_orders( array(
		'limit'        => -1,
		'date_created' => '>=' . $desde_60d,
		'status'       => array( 'processing', 'completed' ),
		'return'       => 'objects',
	) );

	foreach ( $pedidos as $order ) {

		$fecha    = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0;
		$es_30d   = ( $fecha >= $umbral_30d );
		$estado   = (string) $order->get_meta( '_bgmg_estado_despacho' );
		$codigo   = (string) $order->get_meta( '_bgmg_tracking_codigo' );
		$metodo   = (string) $order->get_meta( '_bgmg_tracking_metodo' );
		$enviado  = (int) $order->get_meta( '_bgmg_tracking_email_enviado' );

		/* — Tracking — */

		if ( $es_30d && ( $codigo || $metodo ) ) {
			$out['tracking']['con_codigo_30d']++;
		}
		if ( 'despachado' === $estado && '' === $codigo ) {
			$out['tracking']['despachadas_sin_codigo']++;
		}
		if ( 'preparando' === $estado && $fecha > 0 && $fecha < $umbral_atraso ) {
			$out['tracking']['preparando_atrasadas']++;
		}
		if ( $enviado > 0 && $enviado >= $umbral_30d ) {
			$out['tracking']['emails_enviados_30d']++;
		}

		/* — Etiquetas — */

		// "Pagada sin estado" = orden processing sin _bgmg_estado_despacho.
		// Esos son los pedidos en que la dueña aún no marcó nada.
		if ( 'processing' === $order->get_status() && '' === $estado ) {
			$out['etiquetas']['pagadas_sin_estado']++;
		}
		if ( 'preparando' === $estado ) {
			$out['etiquetas']['en_preparacion']++;
		}
	}

	/* — Alertas calculadas a partir de los stats — */

	if ( $out['tracking']['despachadas_sin_codigo'] > 0 ) {
		$out['alertas'][] = array(
			'texto' => sprintf(
				/* translators: %d: cantidad de pedidos */
				_n(
					'<strong>%d pedido</strong> está marcado "despachado" pero no tiene código de seguimiento cargado.',
					'<strong>%d pedidos</strong> están marcados "despachado" pero no tienen código de seguimiento cargado.',
					$out['tracking']['despachadas_sin_codigo'],
					'bgmg-chile'
				),
				$out['tracking']['despachadas_sin_codigo']
			),
			'url'   => bgmg_chile_admin_orders_url(),
			'label' => __( 'Ver pedidos', 'bgmg-chile' ),
		);
	}
	if ( $out['tracking']['preparando_atrasadas'] > 0 ) {
		$out['alertas'][] = array(
			'texto' => sprintf(
				/* translators: %d: cantidad de pedidos */
				_n(
					'<strong>%d pedido</strong> lleva más de 3 días en "preparando".',
					'<strong>%d pedidos</strong> llevan más de 3 días en "preparando".',
					$out['tracking']['preparando_atrasadas'],
					'bgmg-chile'
				),
				$out['tracking']['preparando_atrasadas']
			),
			'url'   => bgmg_chile_admin_orders_url(),
			'label' => __( 'Ver pedidos', 'bgmg-chile' ),
		);
	}
	if ( $out['etiquetas']['pagadas_sin_estado'] > 0 ) {
		$out['alertas'][] = array(
			'texto' => sprintf(
				/* translators: %d: cantidad de pedidos */
				_n(
					'<strong>%d pedido</strong> está pagado sin estado de despacho asignado todavía.',
					'<strong>%d pedidos</strong> están pagados sin estado de despacho asignado todavía.',
					$out['etiquetas']['pagadas_sin_estado'],
					'bgmg-chile'
				),
				$out['etiquetas']['pagadas_sin_estado']
			),
			'url'   => bgmg_chile_admin_orders_url( 'processing' ),
			'label' => __( 'Ver pendientes', 'bgmg-chile' ),
		);
	}

	return $out;
}

/* ------------------------------------------------------------------------- *
 *  ESTILOS INLINE
 * ------------------------------------------------------------------------- */

function bgmg_chile_wizard_operativa_render_styles() {
	?>
	<style>
		.bgmg-wizard { max-width: 920px; }
		.bgmg-wizard-title { font-size: 26px; margin: 16px 0 6px; color: #1A1015; }
		.bgmg-wizard-intro { font-size: 14px; color: #555; max-width: 760px; line-height: 1.5; margin-bottom: 24px; }

		.bgmg-wizard-alertas {
			background: #FFF8E1;
			border: 1px solid #FFE082;
			border-left: 4px solid #F57C00;
			border-radius: 8px;
			padding: 14px 20px;
			margin-bottom: 20px;
		}
		.bgmg-wizard-alertas h3 {
			margin: 0 0 8px;
			color: #E65100;
			font-size: 15px;
		}
		.bgmg-wizard-alertas ul { margin: 0; padding-left: 20px; }
		.bgmg-wizard-alertas li { color: #6E4115; line-height: 1.6; }
		.bgmg-wizard-alertas a { color: #C4728A; font-weight: 600; text-decoration: none; }
		.bgmg-wizard-alertas a:hover { text-decoration: underline; }

		.bgmg-wizard-paso {
			background: #fff;
			border: 1px solid #e8d8dd;
			border-left: 4px solid #4caf50;
			border-radius: 8px;
			padding: 20px 24px;
			margin-bottom: 16px;
			box-shadow: 0 1px 2px rgba(26,16,21,.03);
		}
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
		.bgmg-wizard-acciones .button-primary {
			background: #C4728A; border-color: #A85B73; color: #fff;
		}
		.bgmg-wizard-acciones .button-primary:hover {
			background: #A85B73; border-color: #8B4A5F;
		}
	</style>
	<?php
}
