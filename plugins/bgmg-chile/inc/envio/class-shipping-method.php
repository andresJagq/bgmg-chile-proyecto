<?php
/**
 * Método de envío "Envío BeautyGirlMG (Chile)".
 *
 * Un único método de envío que decide qué tarifa cobrar según la comuna:
 *
 *   - Si la comuna pertenece a la Región Metropolitana (RM) y está en la
 *     tabla de tarifas fijas → cobra ese precio fijo.
 *   - En cualquier otro caso (RM sin tarifa fija, o cualquier otra región) →
 *     se entrega como "Por pagar": cliente paga el despacho al recibir el
 *     paquete (Starken / Chilexpress / otro courier). Costo en checkout: 0.
 *
 * Esto evita tener que mantener decenas de shipping zones manualmente: WC
 * solo necesita UNA zona (Chile) con este método activo.
 *
 * Notas:
 *   - El método se carga en la zona genérica "Chile" o en cualquier zona
 *     que la dueña arme. WC se encarga de invocar calculate_shipping cuando
 *     el país del cliente coincide.
 *   - No hay coste oculto: si la comuna no tiene tarifa fija, el cliente VE
 *     "$0" pero la etiqueta dice claramente "Por pagar al recibir".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registramos el método con WC en woocommerce_shipping_init para asegurar
 * que WC_Shipping_Method ya está cargado.
 */
add_action( 'woocommerce_shipping_init', 'bgmg_chile_register_shipping_method_class' );

function bgmg_chile_register_shipping_method_class() {

	if ( ! class_exists( 'WC_Shipping_Method' ) ) {
		return;
	}

	class BGMG_Chile_Shipping_Method extends WC_Shipping_Method {

		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'bgmg_chile_envio';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'Envío BeautyGirlMG (Chile)', 'bgmg-chile' );
			$this->method_description = __( 'Tarifa fija configurable por comuna para Región Metropolitana, "Por pagar" para el resto.', 'bgmg-chile' );

			$this->supports = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);

			$this->init();
		}

		/**
		 * Inicializa settings de instancia (los que se editan al añadir el
		 * método dentro de una zona de envío).
		 */
		public function init() {
			$this->init_form_fields();
			$this->init_settings();

			$this->title                      = $this->get_option( 'title', __( 'Envío Chile', 'bgmg-chile' ) );
			$this->label_por_pagar_starken    = $this->get_option(
				'label_por_pagar_starken',
				__( 'Por pagar — Starken (pagas el flete al recibir)', 'bgmg-chile' )
			);
			$this->label_por_pagar_bluexpress = $this->get_option(
				'label_por_pagar_bluexpress',
				__( 'Por pagar — Bluexpress (pagas el flete al recibir)', 'bgmg-chile' )
			);
			$this->envio_gratis_min = (float) $this->get_option( 'envio_gratis_min', '0' );
			$this->default_rm       = (float) $this->get_option( 'default_rm', '3500' );

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
					'description' => __( 'Texto del método de envío en el checkout.', 'bgmg-chile' ),
					'default'     => __( 'Envío Chile', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
				'label_por_pagar_starken' => array(
					'title'       => __( 'Etiqueta para "Por pagar — Starken"', 'bgmg-chile' ),
					'type'        => 'text',
					'description' => __( 'Se muestra como opción de despacho cuando la comuna no tiene tarifa fija. Costo $0 en el checkout; el cliente paga el flete al courier al recibir.', 'bgmg-chile' ),
					'default'     => __( 'Por pagar — Starken (pagas el flete al recibir)', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
				'label_por_pagar_bluexpress' => array(
					'title'       => __( 'Etiqueta para "Por pagar — Bluexpress"', 'bgmg-chile' ),
					'type'        => 'text',
					'description' => __( 'Segunda opción de courier "por pagar" para que el cliente elija. Mismo costo $0 en checkout.', 'bgmg-chile' ),
					'default'     => __( 'Por pagar — Bluexpress (pagas el flete al recibir)', 'bgmg-chile' ),
					'desc_tip'    => true,
				),
				'envio_gratis_min' => array(
					'title'       => __( 'Envío gratis a partir de (CLP)', 'bgmg-chile' ),
					'type'        => 'number',
					'description' => __( 'Para temporadas (ej. Cyber, Navidad): si el subtotal supera este monto, el despacho pasa a $0 con etiqueta "gratis" en el checkout. Solo aplica a comunas con tarifa fija configurada — nunca a "Por pagar". Cuando termina la temporada, pon este campo en 0 (o vacío) para desactivar todo el flujo y el aviso "Te faltan $X" deja de aparecer.', 'bgmg-chile' ),
					'default'     => '0',
					'placeholder' => '50000',
					'desc_tip'    => false,
					'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
				),
				'default_rm' => array(
					'title'       => __( 'Tarifa default RM (CLP)', 'bgmg-chile' ),
					'type'        => 'number',
					'description' => __( 'Tarifa que se cobra automáticamente a cualquier comuna de la Región Metropolitana que NO esté listada en la tabla de tarifas RM. Si lo pones en 0 (o vacío), las comunas no listadas vuelven a ser "Por pagar". Para marcar una comuna específica como "Por pagar" aunque esté en RM, agregarla en la tabla de tarifas con el toggle "Activo" desactivado.', 'bgmg-chile' ),
					'default'     => '3500',
					'placeholder' => '3500',
					'desc_tip'    => false,
					'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
				),
			);
		}

		/**
		 * Núcleo del método: calcula tarifas para el paquete actual.
		 *
		 * @param array $package
		 */
		public function calculate_shipping( $package = array() ) {

			// Sin país o no es CL: no aplicamos.
			$country = isset( $package['destination']['country'] ) ? $package['destination']['country'] : '';
			if ( 'CL' !== $country ) {
				return;
			}

			$region_code = isset( $package['destination']['state'] ) ? (string) $package['destination']['state'] : '';
			$comuna_slug = isset( $package['destination']['city'] ) ? (string) $package['destination']['city'] : '';

			$tarifa_fija = bgmg_chile_get_tarifa_fija( $comuna_slug, $region_code );

			if ( null !== $tarifa_fija ) {
				// Comuna RM con tarifa fija configurada.
				$nombre_comuna = bgmg_chile_get_comuna_nombre( $comuna_slug );

				// Envío gratis sobre monto: solo si está configurado (>0) y
				// el subtotal del paquete lo supera. Solo aplica a tarifas
				// fijas, nunca a "Por pagar" (el courier cobra al cliente).
				$es_gratis = false;
				if ( $this->envio_gratis_min > 0 ) {
					$subtotal = (float) ( $package['contents_cost'] ?? 0 );
					if ( $subtotal >= $this->envio_gratis_min ) {
						$es_gratis = true;
					}
				}

				if ( $es_gratis ) {
					$label = sprintf(
						/* translators: 1: nombre de la comuna */
						__( '%1$s — despacho GRATIS 🎉', 'bgmg-chile' ),
						$nombre_comuna ? $nombre_comuna : __( 'Despacho', 'bgmg-chile' )
					);
					$this->add_rate(
						array(
							'id'      => $this->get_rate_id( 'gratis' ),
							'label'   => $this->title . ' — ' . $label,
							'cost'    => 0,
							'package' => $package,
							'meta_data' => array(
								'bgmg_tarifa_tipo'     => 'gratis',
								'bgmg_tarifa_original' => (float) $tarifa_fija,
								'bgmg_comuna_slug'     => $comuna_slug,
								'bgmg_comuna_nombre'   => $nombre_comuna,
							),
						)
					);
					return;
				}

				$label = sprintf(
					/* translators: 1: comuna */
					__( '%1$s — tarifa fija', 'bgmg-chile' ),
					$nombre_comuna ? $nombre_comuna : __( 'Despacho', 'bgmg-chile' )
				);

				$this->add_rate(
					array(
						'id'      => $this->get_rate_id( 'fijo' ),
						'label'   => $this->title . ' — ' . $label,
						'cost'    => (float) $tarifa_fija,
						'package' => $package,
						'meta_data' => array(
							'bgmg_tarifa_tipo'    => 'fija',
							'bgmg_comuna_slug'    => $comuna_slug,
							'bgmg_comuna_nombre'  => $nombre_comuna,
						),
					)
				);
				return;
			}

			// Sin tarifa específica configurada. Hay dos sub-casos:
			//   1. Es RM y la comuna NO tiene registro manual → usar default RM.
			//   2. Es RM con override manual "activo=0" → respetar "Por pagar".
			//   3. No es RM → "Por pagar".
			$es_rm = ( 'RM' === $region_code );
			if ( ! $es_rm && '' === $region_code && $comuna_slug ) {
				$es_rm = ( 'RM' === bgmg_chile_get_region_de_comuna( $comuna_slug ) );
			}

			if ( $es_rm && $this->default_rm > 0 && ! bgmg_chile_tiene_override_rm( $comuna_slug ) ) {
				// Default RM: comuna RM no listada en la tabla → cobramos la tarifa default.
				$nombre_comuna = bgmg_chile_get_comuna_nombre( $comuna_slug );

				// Envío gratis si está activado y aplica al subtotal.
				$es_gratis = false;
				if ( $this->envio_gratis_min > 0 ) {
					$subtotal = (float) ( $package['contents_cost'] ?? 0 );
					if ( $subtotal >= $this->envio_gratis_min ) {
						$es_gratis = true;
					}
				}

				if ( $es_gratis ) {
					$label = sprintf(
						/* translators: 1: nombre de la comuna */
						__( '%1$s — despacho GRATIS 🎉', 'bgmg-chile' ),
						$nombre_comuna ? $nombre_comuna : __( 'Despacho', 'bgmg-chile' )
					);
					$this->add_rate(
						array(
							'id'      => $this->get_rate_id( 'gratis' ),
							'label'   => $this->title . ' — ' . $label,
							'cost'    => 0,
							'package' => $package,
							'meta_data' => array(
								'bgmg_tarifa_tipo'     => 'gratis',
								'bgmg_tarifa_original' => (float) $this->default_rm,
								'bgmg_comuna_slug'     => $comuna_slug,
								'bgmg_comuna_nombre'   => $nombre_comuna,
								'bgmg_tarifa_fuente'   => 'default_rm',
							),
						)
					);
					return;
				}

				$label = sprintf(
					/* translators: 1: comuna */
					__( '%1$s — tarifa fija', 'bgmg-chile' ),
					$nombre_comuna ? $nombre_comuna : __( 'Despacho', 'bgmg-chile' )
				);
				$this->add_rate(
					array(
						'id'      => $this->get_rate_id( 'fijo' ),
						'label'   => $this->title . ' — ' . $label,
						'cost'    => (float) $this->default_rm,
						'package' => $package,
						'meta_data' => array(
							'bgmg_tarifa_tipo'   => 'fija',
							'bgmg_comuna_slug'   => $comuna_slug,
							'bgmg_comuna_nombre' => $nombre_comuna,
							'bgmg_tarifa_fuente' => 'default_rm',
						),
					)
				);
				return;
			}

			// "Por pagar": costo 0 en el checkout, paga al recibir.
			// Aplica a: comunas no-RM, o comunas RM marcadas explícitamente como
			// por pagar. Ofrecemos dos couriers (Starken y Bluexpress) como
			// opciones separadas para que el cliente elija; el courier elegido
			// se guarda como meta y se copia al campo "Método" del tracking,
			// así la dueña no tiene que re-tipear quién va a despachar.
			$nombre_comuna = bgmg_chile_get_comuna_nombre( $comuna_slug );

			foreach ( $this->get_couriers_por_pagar() as $courier_slug => $courier_label ) {
				$this->add_rate(
					array(
						'id'      => $this->get_rate_id( 'por_pagar_' . $courier_slug ),
						'label'   => $this->title . ' — ' . $courier_label,
						'cost'    => 0,
						'package' => $package,
						'meta_data' => array(
							'bgmg_tarifa_tipo'   => 'por_pagar',
							'bgmg_courier'       => $courier_slug,
							'bgmg_comuna_slug'   => $comuna_slug,
							'bgmg_comuna_nombre' => $nombre_comuna,
						),
					)
				);
			}
		}

		/**
		 * Mapa de couriers ofrecidos cuando la tarifa es "por pagar".
		 * Slug interno → etiqueta visible (configurada por la dueña).
		 *
		 * @return array<string,string>
		 */
		private function get_couriers_por_pagar() {
			return array(
				'starken'    => $this->label_por_pagar_starken,
				'bluexpress' => $this->label_por_pagar_bluexpress,
			);
		}

		// Propiedades públicas accedidas desde calculate_shipping (PHP 8.2+
		// no permite dynamic properties sin esta declaración).
		public $label_por_pagar_starken    = '';
		public $label_por_pagar_bluexpress = '';
		public $envio_gratis_min            = 0.0;
		public $default_rm                  = 0.0;
	}
}

/**
 * ¿La comuna tiene un registro en la tabla de tarifas RM?
 *
 * Distingue entre "no configurada" (no existe registro → cae al default RM)
 * y "configurada pero desactivada" (existe registro con activo=0 → "Por pagar"
 * manual). La función bgmg_chile_get_tarifa_fija no permite distinguir esos
 * dos casos: en ambos retorna null.
 *
 * @param string $comuna_slug
 * @return bool true si existe registro (activo o inactivo), false si no.
 */
function bgmg_chile_tiene_override_rm( $comuna_slug ) {
	if ( ! function_exists( 'bgmg_chile_load_all_tarifas_rm' ) ) {
		return false;
	}
	$todas = bgmg_chile_load_all_tarifas_rm();
	return isset( $todas[ $comuna_slug ] );
}

/**
 * Damos de alta el método en WC_Shipping::shipping_methods.
 */
add_filter( 'woocommerce_shipping_methods', 'bgmg_chile_add_shipping_method' );

function bgmg_chile_add_shipping_method( $methods ) {
	$methods['bgmg_chile_envio'] = 'BGMG_Chile_Shipping_Method';
	return $methods;
}

/**
 * Aviso "Te faltan $X para envío gratis a tu comuna".
 *
 * Se muestra SOLO en el checkout (no en carrito) porque ahí ya hay comuna
 * seleccionada y podemos confirmar si el cliente califica antes de mostrar
 * el mensaje. Mostrar este aviso a quien no califica sería decepcionante.
 *
 * Condiciones para que aparezca:
 *   - El método bgmg_chile_envio existe en alguna zona con envio_gratis_min > 0
 *     (es decir, la dueña activó "temporada de envío gratis" en el admin).
 *   - La comuna del cliente tiene tarifa fija configurada (a las "Por pagar"
 *     no se les regala envío porque el courier cobra al cliente).
 *   - Le falta menos de la mitad del umbral para llegar (ej. si el umbral es
 *     $50.000, mostramos cuando ya superó $25.000). Por debajo de eso es ruido.
 *
 * Es la pista visible que diferencia este plugin del WC nativo: la cliente
 * ve un nudge claro antes de pagar.
 */
add_action( 'woocommerce_review_order_before_shipping', 'bgmg_chile_aviso_envio_gratis_progreso' );

function bgmg_chile_aviso_envio_gratis_progreso() {

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	// Buscamos el primer método bgmg_chile_envio activo con monto configurado.
	$umbral = 0.0;
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'bgmg_chile_envio' === $method->id && isset( $method->envio_gratis_min ) ) {
				$val = (float) $method->envio_gratis_min;
				if ( $val > 0 ) {
					$umbral = $val;
					break 2;
				}
			}
		}
	}
	if ( $umbral <= 0 ) {
		return;
	}

	// Comuna del cliente: si no tiene tarifa fija aplicable (explícita o
	// default RM), este aviso no aplica (no le vamos a regalar lo que el
	// courier le cobra al recibir).
	$comuna = WC()->customer ? WC()->customer->get_shipping_city() : '';
	if ( ! $comuna ) {
		$comuna = WC()->customer ? WC()->customer->get_billing_city() : '';
	}
	if ( ! $comuna ) {
		return;
	}

	$tiene_tarifa_aplicable = ( null !== bgmg_chile_get_tarifa_fija( $comuna ) );

	if ( ! $tiene_tarifa_aplicable ) {
		// Sin tarifa explícita. ¿Cae al default RM? Solo si: es RM, no tiene
		// override manual, y el método activo tiene default_rm > 0.
		$es_rm = ( function_exists( 'bgmg_chile_get_region_de_comuna' )
			&& 'RM' === bgmg_chile_get_region_de_comuna( $comuna ) );
		if ( $es_rm && ! bgmg_chile_tiene_override_rm( $comuna ) ) {
			foreach ( WC_Shipping_Zones::get_zones() as $z ) {
				foreach ( $z['shipping_methods'] as $m ) {
					if ( 'bgmg_chile_envio' === $m->id && isset( $m->default_rm ) && (float) $m->default_rm > 0 ) {
						$tiene_tarifa_aplicable = true;
						break 2;
					}
				}
			}
		}
	}

	if ( ! $tiene_tarifa_aplicable ) {
		return;
	}

	$subtotal = (float) WC()->cart->get_subtotal();
	$falta    = $umbral - $subtotal;

	if ( $subtotal >= $umbral ) {
		// Ya califica: mensaje de éxito.
		$msg = __( '🎉 ¡Tu pedido tiene <strong>envío gratis</strong> a tu comuna!', 'bgmg-chile' );
		$tono = '#2e7d32';
		$fondo = '#e8f5e9';
	} elseif ( $falta < ( $umbral / 2 ) ) {
		// Le falta menos de la mitad: vale la pena empujar.
		$msg = sprintf(
			/* translators: 1: monto que falta formateado en CLP */
			__( '✨ Te faltan <strong>%1$s</strong> para envío <strong>gratis</strong> a tu comuna.', 'bgmg-chile' ),
			wc_price( $falta )
		);
		$tono = '#7A5060';
		$fondo = '#FBF0F2';
	} else {
		// Le falta más de la mitad: no spammeamos.
		return;
	}

	echo '<div class="bgmg-chile-envio-gratis-progreso" style="margin:12px 0;padding:12px 16px;background:' . esc_attr( $fondo ) . ';border-left:3px solid ' . esc_attr( $tono ) . ';color:' . esc_attr( $tono ) . ';font-family:\'DM Sans\',system-ui,sans-serif;">'
		. wp_kses_post( $msg )
		. '</div>';
}

/**
 * Mensaje informativo en el checkout cuando la tarifa elegida es "Por pagar".
 * Se renderiza después de las opciones de envío para que el cliente entienda
 * por qué dice $0 y qué pagará después.
 */
add_action( 'woocommerce_review_order_after_shipping', 'bgmg_chile_aviso_por_pagar_checkout' );

function bgmg_chile_aviso_por_pagar_checkout() {

	$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();
	if ( empty( $chosen_methods ) ) {
		return;
	}

	$chosen = is_array( $chosen_methods ) ? reset( $chosen_methods ) : $chosen_methods;
	if ( false === strpos( (string) $chosen, 'bgmg_chile_envio' ) ) {
		return;
	}
	if ( false === strpos( (string) $chosen, 'por_pagar' ) ) {
		return;
	}

	// Cuando el cliente aún no completó comuna, WC elige el rate "por_pagar"
	// igual (es el único disponible para una dirección incompleta) pero hablar
	// de "el despacho a tu comuna" sin que el cliente haya seleccionado una
	// es confuso. Esperamos a que haya comuna real antes de mostrar el aviso.
	$comuna_actual = WC()->customer ? WC()->customer->get_shipping_city() : '';
	if ( '' === $comuna_actual ) {
		$comuna_actual = WC()->customer ? WC()->customer->get_billing_city() : '';
	}
	if ( '' === $comuna_actual ) {
		return;
	}

	// Identificamos el courier elegido para personalizar el texto del aviso.
	$courier_nombre = '';
	if ( false !== strpos( (string) $chosen, 'por_pagar_starken' ) ) {
		$courier_nombre = __( 'Starken', 'bgmg-chile' );
	} elseif ( false !== strpos( (string) $chosen, 'por_pagar_bluexpress' ) ) {
		$courier_nombre = __( 'Bluexpress', 'bgmg-chile' );
	}

	if ( '' !== $courier_nombre ) {
		$titulo = sprintf(
			/* translators: %s: nombre del courier (Starken / Bluexpress) */
			__( 'Sobre tu despacho por %s:', 'bgmg-chile' ),
			$courier_nombre
		);
		$texto = sprintf(
			/* translators: %s: nombre del courier (Starken / Bluexpress) */
			__( 'Te enviamos el paquete por %s y tú pagas el flete directamente al recibirlo. El monto que ves en el resumen es solo el de los productos.', 'bgmg-chile' ),
			$courier_nombre
		);
	} else {
		// Fallback (no debería ocurrir con los rates nuevos, pero queda por seguridad).
		$titulo = __( 'Sobre tu envío:', 'bgmg-chile' );
		$texto  = __( 'Te enviamos el paquete por courier (Starken o Bluexpress) y tú pagas el flete directamente al recibirlo. El monto que ves en el resumen es solo el de los productos.', 'bgmg-chile' );
	}
	?>
	<tr class="bgmg-chile-aviso-por-pagar">
		<td colspan="2" style="padding:12px;background:#FBF0F2;border-left:3px solid #C4728A;font-size:0.9em;">
			<strong><?php echo esc_html( $titulo ); ?></strong>
			<?php echo esc_html( $texto ); ?>
		</td>
	</tr>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  PRE-CARGAR EL COURIER EN EL TRACKING DE LA ORDEN
 *
 *  Al crear la orden, si el cliente eligió un rate "por_pagar_starken" o
 *  "por_pagar_bluexpress", copiamos el nombre del courier al meta del
 *  tracking (`_bgmg_tracking_metodo`). Así cuando la dueña abre el editor
 *  de la orden, el campo "Método / Courier" del metabox ya viene relleno y
 *  solo tiene que ingresar el código de seguimiento.
 *
 *  Si la dueña sobreescribe manualmente el campo (porque al final mandó por
 *  otro courier), eso queda guardado normalmente y este pre-llenado no
 *  vuelve a aplicarse.
 * ------------------------------------------------------------------------- */

add_action( 'woocommerce_checkout_create_order', 'bgmg_chile_precargar_courier_en_tracking', 30, 2 );

function bgmg_chile_precargar_courier_en_tracking( $order, $data ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
		$courier = $shipping_item->get_meta( 'bgmg_courier' );
		if ( ! $courier ) {
			continue;
		}
		$mapa = array(
			'starken'    => 'Starken',
			'bluexpress' => 'Bluexpress',
		);
		if ( isset( $mapa[ $courier ] ) ) {
			$order->update_meta_data( '_bgmg_tracking_metodo', $mapa[ $courier ] );
		}
		break; // primer shipping item es suficiente.
	}
}
