<?php
/**
 * Email custom "Tu pedido fue despachado" para BeautyGirlMG.
 *
 * Se dispara solo cuando la dueña marca el checkbox "Avisar al cliente"
 * en el metabox de tracking de la orden. Aparece en
 * WC → Ajustes → Emails como cualquier email nativo de WC, así la dueña
 * puede customizar subject/heading desde wp-admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_email_classes', 'bgmg_chile_register_tracking_email_class' );

function bgmg_chile_register_tracking_email_class( $emails ) {
	if ( ! class_exists( 'BGMG_Chile_Email_Tracking' ) ) {
		bgmg_chile_define_tracking_email_class();
	}
	$emails['BGMG_Chile_Email_Tracking'] = new BGMG_Chile_Email_Tracking();
	return $emails;
}

/**
 * Definimos la clase dentro de una función para asegurar que WC_Email ya
 * está cargada cuando se evalúa el `extends`.
 */
function bgmg_chile_define_tracking_email_class() {

	if ( ! class_exists( 'WC_Email' ) ) {
		return;
	}

	class BGMG_Chile_Email_Tracking extends WC_Email {

		public function __construct() {

			$this->id             = 'bgmg_chile_tracking';
			$this->customer_email = true;
			$this->title          = __( 'BeautyGirlMG — Pedido despachado', 'bgmg-chile' );
			$this->description    = __( 'Aviso al cliente cuando agregas tracking en una orden y marcas "Avisar al cliente".', 'bgmg-chile' );

			$this->template_html  = ''; // usamos render inline para no requerir archivos de tema.
			$this->template_plain = '';

			$this->placeholders = array(
				'{order_number}' => '',
				'{order_date}'   => '',
				'{site_title}'   => $this->get_blogname(),
			);

			parent::__construct();
		}

		/**
		 * Settings que aparecen en WC → Ajustes → Emails → este email.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Habilitar/Deshabilitar', 'bgmg-chile' ),
					'type'    => 'checkbox',
					'label'   => __( 'Activar este email', 'bgmg-chile' ),
					'default' => 'yes',
				),
				'subject' => array(
					'title'       => __( 'Asunto', 'bgmg-chile' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => __( 'Placeholders disponibles: {site_title}, {order_number}, {order_date}', 'bgmg-chile' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading' => array(
					'title'       => __( 'Encabezado', 'bgmg-chile' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => __( 'Texto principal del email.', 'bgmg-chile' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'email_type' => array(
					'title'       => __( 'Tipo de email', 'bgmg-chile' ),
					'type'        => 'select',
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}

		public function get_default_subject() {
			// El subject default es genérico; en trigger() lo sobrescribimos
			// según el estado del despacho de la orden.
			return __( 'Actualización de tu pedido #{order_number}', 'bgmg-chile' );
		}

		public function get_default_heading() {
			return __( 'Actualización de tu pedido', 'bgmg-chile' );
		}

		/**
		 * Subject/heading según estado del despacho. Si la dueña personalizó
		 * el subject/heading desde WC → Ajustes → Emails, lo respetamos.
		 * Si está en default, calculamos un texto coherente con el estado.
		 */
		protected function bgmg_subject_heading_para_estado( $estado ) {
			$mapa = array(
				'preparando' => array(
					'subject' => __( 'Estamos preparando tu pedido #{order_number}', 'bgmg-chile' ),
					'heading' => __( 'Estamos preparando tu pedido', 'bgmg-chile' ),
				),
				'despachado' => array(
					'subject' => __( '¡Tu pedido #{order_number} fue despachado!', 'bgmg-chile' ),
					'heading' => __( 'Tu pedido va en camino', 'bgmg-chile' ),
				),
				'listo_retiro' => array(
					'subject' => __( 'Tu pedido #{order_number} está listo para retirar', 'bgmg-chile' ),
					'heading' => __( 'Tu pedido está listo para retirar', 'bgmg-chile' ),
				),
			);
			if ( isset( $mapa[ $estado ] ) ) {
				return $mapa[ $estado ];
			}
			// Sin estado o estado desconocido: usamos el default genérico.
			return array(
				'subject' => $this->get_default_subject(),
				'heading' => $this->get_default_heading(),
			);
		}

		/**
		 * Disparar el email.
		 *
		 * @param int           $order_id
		 * @param WC_Order|null $order
		 */
		public function trigger( $order_id, $order = false ) {

			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $order->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
				$this->placeholders['{order_number}'] = $order->get_order_number();

				// Si el subject/heading están en blanco (= default), elegimos
				// uno acorde al estado del despacho de la orden. Si la dueña
				// los personalizó desde WC → Emails, respetamos su elección.
				$estado = (string) $order->get_meta( '_bgmg_estado_despacho' );
				$mapa   = $this->bgmg_subject_heading_para_estado( $estado );
				if ( '' === trim( (string) $this->settings['subject'] ) ) {
					$this->settings['subject'] = $mapa['subject'];
				}
				if ( '' === trim( (string) $this->settings['heading'] ) ) {
					$this->settings['heading'] = $mapa['heading'];
				}
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send(
					$this->get_recipient(),
					$this->get_subject(),
					$this->get_content(),
					$this->get_headers(),
					$this->get_attachments()
				);
			}

			$this->restore_locale();
		}

		/**
		 * Cuerpo HTML del email. Render inline. El texto del párrafo de intro
		 * cambia según el estado del despacho de la orden.
		 *
		 * Tip de diseño: usamos solo tablas, inline-CSS y colores hex puros
		 * porque Gmail/Outlook ignoran web fonts y reglas CSS avanzadas.
		 */
		public function get_content_html() {
			ob_start();
			$order  = $this->object;
			$codigo = $order ? $order->get_meta( '_bgmg_tracking_codigo' ) : '';
			$metodo = $order ? $order->get_meta( '_bgmg_tracking_metodo' ) : '';
			$estado = $order ? (string) $order->get_meta( '_bgmg_estado_despacho' ) : '';

			// Texto de intro según estado. Para "despachado" personalizamos
			// con el courier/método si está cargado: "Ya despachamos tu pedido
			// por Starken" suena más concreto que "por courier".
			$intro_despachado = $metodo
				? sprintf(
					/* translators: %s: courier o método de despacho (Starken, Bluexpress, moto propia, etc.) */
					__( 'Ya despachamos tu pedido por %s. Acá están los datos para que lo sigas:', 'bgmg-chile' ),
					$metodo
				)
				: __( 'Ya despachamos tu pedido. Acá están los datos para que lo sigas:', 'bgmg-chile' );

			$intros = array(
				'preparando'   => __( 'Estamos preparando tu pedido. Te avisaremos en cuanto salga.', 'bgmg-chile' ),
				'despachado'   => $intro_despachado,
				'listo_retiro' => __( 'Tu pedido ya está disponible para retirar. Pasa cuando quieras dentro del horario.', 'bgmg-chile' ),
			);
			$intro = isset( $intros[ $estado ] )
				? $intros[ $estado ]
				: __( 'Hay novedades sobre tu pedido:', 'bgmg-chile' );

			do_action( 'woocommerce_email_header', $this->get_heading(), $this );
			?>
			<p>
				<?php
				printf(
					/* translators: %s: nombre del cliente */
					esc_html__( 'Hola %s,', 'bgmg-chile' ),
					esc_html( $order ? $order->get_billing_first_name() : '' )
				);
				?>
			</p>
			<p><?php echo esc_html( $intro ); ?></p>

			<?php if ( $estado ) : ?>
				<p style="margin:16px 0;">
					<?php echo bgmg_chile_render_estado_badge( $estado, 'email' ); /* phpcs:ignore WordPress.Security.EscapeOutput */ ?>
				</p>
			<?php endif; ?>

			<?php if ( $metodo || $codigo ) : ?>
				<table cellspacing="0" cellpadding="6" border="1" style="width:100%;border-collapse:collapse;border-color:#e5e5e5;margin:16px 0;">
					<?php if ( $metodo ) : ?>
						<tr>
							<th align="left" style="background:#FBF0F2;color:#1A1015;font-family:'DM Sans',Arial,sans-serif;">
								<?php esc_html_e( 'Método de envío', 'bgmg-chile' ); ?>
							</th>
							<td style="font-family:'DM Sans',Arial,sans-serif;color:#1A1015;">
								<?php echo esc_html( $metodo ); ?>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( $codigo ) : ?>
						<tr>
							<th align="left" style="background:#FBF0F2;color:#1A1015;font-family:'DM Sans',Arial,sans-serif;">
								<?php esc_html_e( 'Código de seguimiento', 'bgmg-chile' ); ?>
							</th>
							<td style="font-family:'Courier New',Courier,monospace;color:#1A1015;font-size:16px;letter-spacing:1px;background:#FFFDE7;padding:8px 10px;">
								<strong><?php echo esc_html( $codigo ); ?></strong>
							</td>
						</tr>
					<?php endif; ?>
				</table>
				<?php if ( $codigo ) : ?>
					<p style="margin:8px 0 16px;color:#7A5060;font-size:13px;">
						<?php esc_html_e( 'Mantén presionado el código para copiarlo en tu teléfono, o selecciónalo con clic para copiarlo en tu computador.', 'bgmg-chile' ); ?>
					</p>
				<?php endif; ?>
			<?php endif; ?>

			<p style="color:#7A5060;">
				<?php esc_html_e( 'Si tienes alguna duda escríbenos respondiendo este email o por nuestras redes.', 'bgmg-chile' ); ?>
			</p>
			<?php
			do_action( 'woocommerce_email_footer', $this );
			return ob_get_clean();
		}

		/**
		 * Cuerpo plano del email. Se adapta al estado igual que el HTML.
		 */
		public function get_content_plain() {
			$order  = $this->object;
			$codigo = $order ? $order->get_meta( '_bgmg_tracking_codigo' ) : '';
			$metodo = $order ? $order->get_meta( '_bgmg_tracking_metodo' ) : '';
			$estado = $order ? (string) $order->get_meta( '_bgmg_estado_despacho' ) : '';

			$intro_despachado = $metodo
				? sprintf(
					/* translators: %s: courier o método de despacho */
					__( 'Ya despachamos tu pedido por %s. Datos para que lo sigas:', 'bgmg-chile' ),
					$metodo
				)
				: __( 'Ya despachamos tu pedido. Datos para que lo sigas:', 'bgmg-chile' );

			$intros = array(
				'preparando'   => __( 'Estamos preparando tu pedido. Te avisaremos en cuanto salga.', 'bgmg-chile' ),
				'despachado'   => $intro_despachado,
				'listo_retiro' => __( 'Tu pedido ya está disponible para retirar.', 'bgmg-chile' ),
			);
			$intro = isset( $intros[ $estado ] ) ? $intros[ $estado ] : __( 'Hay novedades sobre tu pedido:', 'bgmg-chile' );

			$lines   = array();
			$lines[] = sprintf( __( 'Hola %s,', 'bgmg-chile' ), $order ? $order->get_billing_first_name() : '' );
			$lines[] = '';
			$lines[] = $intro;
			$lines[] = '';
			if ( $estado ) {
				$lines[] = __( 'Estado', 'bgmg-chile' ) . ': ' . bgmg_chile_get_estado_despacho_label( $estado );
			}
			if ( $metodo ) {
				$lines[] = __( 'Método de envío', 'bgmg-chile' ) . ': ' . $metodo;
			}
			if ( $codigo ) {
				$lines[] = __( 'Código de seguimiento', 'bgmg-chile' ) . ': ' . $codigo;
			}
			$lines[] = '';
			$lines[] = __( 'Si tienes dudas, responde este email.', 'bgmg-chile' );
			return implode( "\n", $lines );
		}
	}
}
