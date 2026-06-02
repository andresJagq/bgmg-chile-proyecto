<?php
/**
 * Método de envío "Retiro en tienda" para BeautyGirlMG.
 *
 * Reglas:
 *   - Solo aparece como opción si:
 *       a) El país es CL.
 *       b) La región es la Metropolitana (RM).
 *       c) La comuna está marcada como "Permite retiro en tienda" en la
 *          pantalla WooCommerce → Envíos Chile (RM).
 *   - Costo siempre $0 (decisión del 2026-05-16: gratis).
 *   - Los datos de la tienda (dirección, horario, WhatsApp, instrucciones)
 *     se editan en WC → Ajustes → Envío → Zona Chile → Editar método
 *     "Retiro en tienda". El plugin trae valores por defecto pre-cargados
 *     con la dirección actual de la dueña.
 *
 * Convive con el método "Envío BeautyGirlMG (Chile)": ambos se ofrecen
 * simultáneamente y el cliente elige cuál prefiere.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_shipping_init', 'bgmg_chile_register_retiro_method_class' );

function bgmg_chile_register_retiro_method_class() {

	if ( ! class_exists( 'WC_Shipping_Method' ) ) {
		return;
	}

	class BGMG_Chile_Shipping_Retiro extends WC_Shipping_Method {

		// Datos de la sucursal (editables desde admin del método).
		public $direccion     = '';
		public $horario       = '';
		public $whatsapp      = '';
		public $instrucciones = '';

		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'bgmg_chile_retiro';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'Retiro en tienda (BeautyGirlMG)', 'bgmg-chile' );
			$this->method_description = __( 'Retiro gratis en la tienda física. Solo se ofrece a las comunas de RM marcadas como "Permite retiro" en WooCommerce → Envíos Chile (RM).', 'bgmg-chile' );

			$this->supports = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);

			$this->init();
		}

		public function init() {
			$this->init_form_fields();
			$this->init_settings();

			$this->title         = $this->get_option( 'title', __( 'Retiro en tienda (gratis)', 'bgmg-chile' ) );
			$this->direccion     = $this->get_option( 'direccion', 'Antonio López de Bello 461, Recoleta' );
			$this->horario       = $this->get_option( 'horario', 'Coordina día y hora previamente por WhatsApp' );
			$this->whatsapp      = $this->get_option( 'whatsapp', '+56 9 4536 2142' );
			$this->instrucciones = $this->get_option( 'instrucciones', 'Escríbenos al WhatsApp para confirmar disponibilidad antes de venir a retirar.' );

			add_action(
				'woocommerce_update_options_shipping_' . $this->id,
				array( $this, 'process_admin_options' )
			);
		}

		public function init_form_fields() {
			$this->instance_form_fields = array(
				'title' => array(
					'title'       => __( 'Título mostrado al cliente', 'bgmg-chile' ),
					'type'        => 'text',
					'description' => __( 'Texto del método en el checkout.', 'bgmg-chile' ),
					'default'     => __( 'Retiro en tienda (gratis)', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
				'direccion' => array(
					'title'       => __( 'Dirección del local', 'bgmg-chile' ),
					'type'        => 'text',
					'default'     => 'Antonio López de Bello 461, Recoleta',
					'description' => __( 'Aparece en el checkout y en el email de la orden.', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
				'horario' => array(
					'title'       => __( 'Horario de retiro', 'bgmg-chile' ),
					'type'        => 'text',
					'default'     => 'Coordina día y hora previamente por WhatsApp',
					'description' => __( 'Ej: Lun-Vie 10:00-19:00 / Sáb 11:00-14:00', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
				'whatsapp' => array(
					'title'       => __( 'WhatsApp de coordinación', 'bgmg-chile' ),
					'type'        => 'text',
					'default'     => '+56 9 4536 2142',
					'description' => __( 'Se muestra como link clicable wa.me al cliente.', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
				'instrucciones' => array(
					'title'       => __( 'Instrucciones extra', 'bgmg-chile' ),
					'type'        => 'textarea',
					'default'     => 'Escríbenos al WhatsApp para confirmar disponibilidad antes de venir a retirar.',
					'description' => __( 'Cualquier nota adicional al cliente (avisos, restricciones, etc.).', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Solo agregamos el rate si país=CL, región=RM y la comuna acepta retiro.
		 */
		public function calculate_shipping( $package = array() ) {

			$country = isset( $package['destination']['country'] ) ? $package['destination']['country'] : '';
			if ( 'CL' !== $country ) {
				return;
			}

			$region = isset( $package['destination']['state'] ) ? (string) $package['destination']['state'] : '';
			$comuna = isset( $package['destination']['city'] ) ? (string) $package['destination']['city'] : '';

			// Restringimos a RM aunque el helper también filtra (defensa doble).
			if ( 'RM' !== $region ) {
				return;
			}

			if ( ! bgmg_chile_comuna_acepta_retiro( $comuna ) ) {
				return;
			}

			$this->add_rate(
				array(
					'id'        => $this->get_rate_id( 'local' ),
					'label'     => $this->title,
					'cost'      => 0,
					'package'   => $package,
					'meta_data' => array(
						'bgmg_es_retiro'      => 'si',
						'bgmg_direccion'      => $this->direccion,
						'bgmg_horario'        => $this->horario,
						'bgmg_whatsapp'       => $this->whatsapp,
						'bgmg_instrucciones'  => $this->instrucciones,
						'bgmg_comuna_slug'    => $comuna,
						'bgmg_comuna_nombre'  => bgmg_chile_get_comuna_nombre( $comuna ),
					),
				)
			);
		}
	}
}

/**
 * Registramos la clase como método disponible para añadirla a una zona.
 */
add_filter( 'woocommerce_shipping_methods', 'bgmg_chile_add_retiro_method' );

function bgmg_chile_add_retiro_method( $methods ) {
	$methods['bgmg_chile_retiro'] = 'BGMG_Chile_Shipping_Retiro';
	return $methods;
}

/* ------------------------------------------------------------------------- *
 *  AVISO EN CHECKOUT cuando el cliente elige "Retiro en tienda"
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_review_order_after_shipping', 'bgmg_chile_aviso_retiro_checkout', 20 );

function bgmg_chile_aviso_retiro_checkout() {

	$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();
	if ( empty( $chosen_methods ) ) {
		return;
	}
	$chosen = is_array( $chosen_methods ) ? reset( $chosen_methods ) : $chosen_methods;
	if ( false === strpos( (string) $chosen, 'bgmg_chile_retiro' ) ) {
		return;
	}

	// Recuperamos los datos guardados en el método (a través de una instancia
	// nueva con su option store: WC los conserva por instance_id).
	$datos = bgmg_chile_obtener_datos_retiro_actual();

	$whatsapp_link = bgmg_chile_whatsapp_link( $datos['whatsapp'] );

	?>
	<tr class="bgmg-chile-aviso-retiro">
		<td colspan="2" style="padding:14px;background:#FBF0F2;border-left:3px solid #C4728A;font-size:0.9em;line-height:1.5;">
			<strong style="display:block;margin-bottom:6px;font-family:'Cormorant Garamond',Georgia,serif;font-size:1.1em;color:#1A1015;">
				<?php esc_html_e( '📍 Datos para tu retiro:', 'bgmg-chile' ); ?>
			</strong>
			<div><strong><?php esc_html_e( 'Dirección:', 'bgmg-chile' ); ?></strong> <?php echo esc_html( $datos['direccion'] ); ?></div>
			<div><strong><?php esc_html_e( 'Horario:', 'bgmg-chile' ); ?></strong> <?php echo esc_html( $datos['horario'] ); ?></div>
			<?php if ( $datos['whatsapp'] ) : ?>
				<div>
					<strong><?php esc_html_e( 'WhatsApp:', 'bgmg-chile' ); ?></strong>
					<?php if ( $whatsapp_link ) : ?>
						<a href="<?php echo esc_url( $whatsapp_link ); ?>" target="_blank" rel="noopener" style="color:#C4728A;text-decoration:underline;">
							<?php echo esc_html( $datos['whatsapp'] ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $datos['whatsapp'] ); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( $datos['instrucciones'] ) : ?>
				<div style="margin-top:6px;font-style:italic;color:#7A5060;">
					<?php echo esc_html( $datos['instrucciones'] ); ?>
				</div>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Helper: obtiene los datos guardados del método retiro (cualquier instancia
 * activa). Si no encuentra una instancia configurada en una zona, cae a los
 * defaults definidos en init().
 */
function bgmg_chile_obtener_datos_retiro_actual() {

	// Buscamos la primera instancia configurada del método en cualquier zona.
	$zones    = WC_Shipping_Zones::get_zones();
	$instance = null;
	foreach ( $zones as $zone ) {
		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'bgmg_chile_retiro' === $method->id ) {
				$instance = $method;
				break 2;
			}
		}
	}
	// La "zona predeterminada" (resto del mundo) se obtiene aparte.
	if ( ! $instance ) {
		$default_zone = new WC_Shipping_Zone( 0 );
		foreach ( $default_zone->get_shipping_methods() as $method ) {
			if ( 'bgmg_chile_retiro' === $method->id ) {
				$instance = $method;
				break;
			}
		}
	}

	if ( $instance ) {
		return array(
			'direccion'     => $instance->direccion,
			'horario'       => $instance->horario,
			'whatsapp'      => $instance->whatsapp,
			'instrucciones' => $instance->instrucciones,
		);
	}

	// Fallback a defaults si el método no se ha añadido a ninguna zona aún
	// (no debería pasar en producción, pero protege la UX).
	return array(
		'direccion'     => 'Antonio López de Bello 461, Recoleta',
		'horario'       => 'Coordina día y hora previamente por WhatsApp',
		'whatsapp'      => '+56 9 4536 2142',
		'instrucciones' => 'Escríbenos al WhatsApp para confirmar disponibilidad antes de venir a retirar.',
	);
}

/**
 * Convierte "+56 9 4536 2142" → "https://wa.me/56945362142"
 * Devuelve '' si el número no es válido como móvil chileno.
 */
function bgmg_chile_whatsapp_link( $whatsapp ) {
	if ( ! $whatsapp ) {
		return '';
	}
	// Reusamos el validador de teléfono si está cargado.
	if ( class_exists( 'BGMG_Chile_Telefono_Validator' ) ) {
		$e164 = BGMG_Chile_Telefono_Validator::format_e164( $whatsapp );
		if ( $e164 ) {
			return 'https://wa.me/' . ltrim( $e164, '+' );
		}
	}
	// Fallback: dejamos solo dígitos.
	$digits = preg_replace( '/\D+/', '', $whatsapp );
	return $digits ? 'https://wa.me/' . $digits : '';
}

/* ------------------------------------------------------------------------- *
 *  AVISO EN EMAIL cuando la orden fue de retiro
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_email_after_order_table', 'bgmg_chile_email_retiro_block', 30, 4 );

function bgmg_chile_email_retiro_block( $order, $sent_to_admin, $plain_text, $email ) {

	$es_retiro = false;
	foreach ( $order->get_shipping_methods() as $item ) {
		if ( 'bgmg_chile_retiro' === $item->get_method_id() ) {
			$es_retiro = true;
			break;
		}
	}
	if ( ! $es_retiro ) {
		return;
	}

	$datos = bgmg_chile_obtener_datos_retiro_actual();

	if ( $plain_text ) {
		echo "\n----------\n";
		echo esc_html__( 'RETIRO EN TIENDA', 'bgmg-chile' ) . "\n";
		echo esc_html__( 'Dirección', 'bgmg-chile' ) . ': ' . esc_html( $datos['direccion'] ) . "\n";
		echo esc_html__( 'Horario', 'bgmg-chile' ) . ': ' . esc_html( $datos['horario'] ) . "\n";
		if ( $datos['whatsapp'] ) {
			echo esc_html__( 'WhatsApp', 'bgmg-chile' ) . ': ' . esc_html( $datos['whatsapp'] ) . "\n";
		}
		if ( $datos['instrucciones'] ) {
			echo esc_html( $datos['instrucciones'] ) . "\n";
		}
		echo "\n";
		return;
	}

	$wa_link = bgmg_chile_whatsapp_link( $datos['whatsapp'] );
	?>
	<div style="margin-top:20px;padding:16px;background:#FBF0F2;border-left:3px solid #C4728A;font-family:'DM Sans',Arial,sans-serif;">
		<h3 style="margin:0 0 10px;font-family:'Cormorant Garamond',Georgia,serif;color:#1A1015;">
			📍 <?php esc_html_e( 'Datos para tu retiro en tienda', 'bgmg-chile' ); ?>
		</h3>
		<p style="margin:4px 0;color:#1A1015;">
			<strong><?php esc_html_e( 'Dirección:', 'bgmg-chile' ); ?></strong>
			<?php echo esc_html( $datos['direccion'] ); ?>
		</p>
		<p style="margin:4px 0;color:#1A1015;">
			<strong><?php esc_html_e( 'Horario:', 'bgmg-chile' ); ?></strong>
			<?php echo esc_html( $datos['horario'] ); ?>
		</p>
		<?php if ( $datos['whatsapp'] ) : ?>
			<p style="margin:4px 0;color:#1A1015;">
				<strong><?php esc_html_e( 'WhatsApp:', 'bgmg-chile' ); ?></strong>
				<?php if ( $wa_link ) : ?>
					<a href="<?php echo esc_url( $wa_link ); ?>" style="color:#C4728A;"><?php echo esc_html( $datos['whatsapp'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $datos['whatsapp'] ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<?php if ( $datos['instrucciones'] ) : ?>
			<p style="margin:8px 0 0;color:#7A5060;font-style:italic;">
				<?php echo esc_html( $datos['instrucciones'] ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
