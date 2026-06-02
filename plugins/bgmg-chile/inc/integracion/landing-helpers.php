<?php
/**
 * Helpers públicos para que bgmg-landing (y cualquier otro tema/plugin)
 * pueda consumir los datos chilenos de una orden sin tener que conocer la
 * estructura interna de meta keys ni reimplementar lógica.
 *
 * Estos helpers son la "API pública" de bgmg-chile: están pensados para
 * ser llamados desde templates custom — típicamente la thank you page
 * (order-received) o un dashboard de cuenta que la dueña diseñe en
 * bgmg-landing.
 *
 * Convención: todas las funciones de esta API empiezan con
 * `bgmg_chile_render_*` (echo HTML) o `bgmg_chile_get_*` (devuelven datos).
 *
 * El plugin NO depende de bgmg-landing — funciona sin él. bgmg-landing es
 * el cliente opcional que puede consumir esta API si quiere mostrar la
 * info en lugares custom.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  1. RESUMEN COMPLETO PARA MOSTRAR AL CLIENTE
 *
 *  Junta todos los bloques chilenos que el cliente debe ver después de
 *  comprar: estado del despacho, tracking, datos de retiro, RUT/factura.
 *
 *  Llamada típica desde un template custom de bgmg-landing:
 *
 *      <?php bgmg_chile_render_order_summary( $order ); ?>
 *
 *  Con `$args` se pueden esconder bloques que ya estén en otro lado del
 *  template:
 *
 *      bgmg_chile_render_order_summary( $order, array(
 *          'mostrar_estado'    => true,
 *          'mostrar_tracking'  => true,
 *          'mostrar_factura'   => false,  // ya lo muestro arriba
 *          'mostrar_retiro'    => true,
 *      ) );
 * ------------------------------------------------------------------------- */

/**
 * @param WC_Order $order
 * @param array    $args
 */
function bgmg_chile_render_order_summary( $order, $args = array() ) {

	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'mostrar_estado'   => true,
			'mostrar_tracking' => true,
			'mostrar_factura'  => false,
			'mostrar_retiro'   => true,
		)
	);

	echo '<div class="bgmg-chile-order-summary">';

	if ( $args['mostrar_estado'] ) {
		bgmg_chile_render_estado_box( $order );
	}

	if ( $args['mostrar_tracking'] ) {
		bgmg_chile_render_tracking_block( $order );
	}

	if ( $args['mostrar_retiro'] && bgmg_chile_orden_es_retiro( $order ) ) {
		bgmg_chile_render_retiro_block_publico( $order );
	}

	if ( $args['mostrar_factura'] ) {
		bgmg_chile_render_factura_block_publico( $order );
	}

	echo '</div>';
}

/* ------------------------------------------------------------------------- *
 *  2. BLOQUES INDIVIDUALES (cada uno puede usarse aislado)
 * ------------------------------------------------------------------------- */

/**
 * Solo el badge del estado del despacho, en formato destacado para frontend.
 * Útil para ponerlo en grande al inicio de la thank you page.
 *
 * @param WC_Order $order
 * @param string   $size 'normal' | 'big'
 */
function bgmg_chile_render_estado_box( $order, $size = 'normal' ) {

	if ( ! $order instanceof WC_Order ) {
		return;
	}
	$estado = $order->get_meta( '_bgmg_estado_despacho' );
	if ( ! $estado ) {
		return;
	}

	$badge = bgmg_chile_render_estado_badge( $estado, 'frontend' );

	$style = ( 'big' === $size )
		? 'font-size:1.4em;padding:14px 22px;'
		: '';

	echo '<div class="bgmg-chile-estado-box" style="margin:16px 0;text-align:center;' . esc_attr( $style ) . '">';
	// El badge ya viene escapado por su helper.
	echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
}

/**
 * Bloque con método + código de tracking + botón copiar.
 * Solo renderiza si la orden tiene tracking cargado.
 *
 * @param WC_Order $order
 */
function bgmg_chile_render_tracking_block( $order ) {

	if ( ! $order instanceof WC_Order ) {
		return;
	}
	$codigo = $order->get_meta( '_bgmg_tracking_codigo' );
	$metodo = $order->get_meta( '_bgmg_tracking_metodo' );
	if ( ! $codigo && ! $metodo ) {
		return;
	}

	?>
	<section class="bgmg-chile-tracking-public" style="margin:24px 0;padding:16px 18px;background:#FBF0F2;border-left:3px solid #C4728A;">
		<h3 style="margin:0 0 10px;font-family:'Cormorant Garamond',Georgia,serif;color:#1A1015;font-size:1.2em;">
			📦 <?php esc_html_e( 'Seguimiento de tu despacho', 'bgmg-chile' ); ?>
		</h3>
		<?php if ( $metodo ) : ?>
			<p style="margin:4px 0;color:#1A1015;">
				<strong><?php esc_html_e( 'Método:', 'bgmg-chile' ); ?></strong>
				<?php echo esc_html( $metodo ); ?>
			</p>
		<?php endif; ?>
		<?php if ( $codigo ) : ?>
			<p style="margin:4px 0;color:#1A1015;">
				<strong><?php esc_html_e( 'Código:', 'bgmg-chile' ); ?></strong>
				<span style="font-family:monospace;font-size:1.05em;letter-spacing:0.5px;">
					<?php echo esc_html( $codigo ); ?>
				</span>
				<button
					type="button"
					class="bgmg-chile-copiar-tracking"
					data-codigo="<?php echo esc_attr( $codigo ); ?>"
					style="margin-left:8px;padding:4px 10px;border:1px solid #C4728A;background:#fff;color:#1A1015;border-radius:4px;font-size:0.85em;cursor:pointer;"
				>📋 <?php esc_html_e( 'Copiar', 'bgmg-chile' ); ?></button>
			</p>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Bloque público con los datos del retiro en tienda (dirección, horario,
 * WhatsApp). Solo renderiza si la orden fue por retiro.
 *
 * @param WC_Order $order
 */
function bgmg_chile_render_retiro_block_publico( $order ) {

	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( ! bgmg_chile_orden_es_retiro( $order ) ) {
		return;
	}
	if ( ! function_exists( 'bgmg_chile_obtener_datos_retiro_actual' ) ) {
		return;
	}

	$datos   = bgmg_chile_obtener_datos_retiro_actual();
	$wa_link = bgmg_chile_whatsapp_link( $datos['whatsapp'] );
	?>
	<section class="bgmg-chile-retiro-public" style="margin:24px 0;padding:16px 18px;background:#FBF0F2;border-left:3px solid #C4728A;">
		<h3 style="margin:0 0 10px;font-family:'Cormorant Garamond',Georgia,serif;color:#1A1015;font-size:1.2em;">
			📍 <?php esc_html_e( 'Datos para tu retiro', 'bgmg-chile' ); ?>
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
					<a href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener" style="color:#C4728A;">
						<?php echo esc_html( $datos['whatsapp'] ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( $datos['whatsapp'] ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Bloque con los datos de boleta/factura (RUT, razón social, etc).
 * Solo renderiza si la orden es de CL y tiene RUT guardado.
 *
 * @param WC_Order $order
 */
function bgmg_chile_render_factura_block_publico( $order ) {

	if ( ! $order instanceof WC_Order ) {
		return;
	}
	if ( 'CL' !== $order->get_billing_country() ) {
		return;
	}
	$rut = $order->get_meta( '_bgmg_rut' );
	if ( ! $rut ) {
		return;
	}
	$necesita_factura = $order->get_meta( '_bgmg_necesita_factura' );
	?>
	<section class="bgmg-chile-factura-public" style="margin:24px 0;padding:16px 18px;background:#FBF0F2;border-left:3px solid #C4728A;">
		<h3 style="margin:0 0 10px;font-family:'Cormorant Garamond',Georgia,serif;color:#1A1015;font-size:1.2em;">
			🧾 <?php esc_html_e( 'Datos para boleta/factura', 'bgmg-chile' ); ?>
		</h3>
		<p style="margin:4px 0;color:#1A1015;">
			<strong><?php esc_html_e( 'RUT:', 'bgmg-chile' ); ?></strong>
			<?php echo esc_html( $rut ); ?>
		</p>
		<?php if ( 'si' === $necesita_factura ) :
			$rs    = $order->get_meta( '_bgmg_razon_social' );
			$giro  = $order->get_meta( '_bgmg_giro' );
			$dc    = $order->get_meta( '_bgmg_direccion_comercial' );
			?>
			<?php if ( $rs ) : ?>
				<p style="margin:4px 0;color:#1A1015;">
					<strong><?php esc_html_e( 'Razón social:', 'bgmg-chile' ); ?></strong>
					<?php echo esc_html( $rs ); ?>
				</p>
			<?php endif; ?>
			<?php if ( $giro ) : ?>
				<p style="margin:4px 0;color:#1A1015;">
					<strong><?php esc_html_e( 'Giro:', 'bgmg-chile' ); ?></strong>
					<?php echo esc_html( $giro ); ?>
				</p>
			<?php endif; ?>
			<?php if ( $dc ) : ?>
				<p style="margin:4px 0;color:#1A1015;">
					<strong><?php esc_html_e( 'Dirección comercial:', 'bgmg-chile' ); ?></strong>
					<?php echo esc_html( $dc ); ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</section>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  3. MENSAJE PERSONALIZADO PARA "GRACIAS POR TU PEDIDO"
 *
 *  WC tiene un mensaje genérico ("Thank you. Your order has been received.")
 *  que aparece en la thank you page. Como en Chile hay tres flujos muy
 *  distintos (despacho con tarifa fija, "Por pagar", retiro en tienda)
 *  ofrecemos un mensaje específico por flujo.
 *
 *  Lo aplicamos por filtro a la thank you page nativa (auto-mejora sin
 *  tocar nada), y lo exponemos como helper para que bgmg-landing pueda
 *  llamarlo desde su template custom.
 * ------------------------------------------------------------------------- */

/**
 * Devuelve el texto del "Gracias por tu pedido" según el flujo de la orden.
 *
 * @param WC_Order $order
 * @return string Texto plano (sin HTML) para mostrar.
 */
function bgmg_chile_get_thankyou_message( $order ) {

	if ( ! $order instanceof WC_Order ) {
		return __( '¡Gracias por tu compra!', 'bgmg-chile' );
	}

	// Estado de pago primero. Cuando la orden NO está pagada (failed, pending,
	// on-hold, cancelled) el mensaje no debe prometer "vamos a despachar" — eso
	// solo aplica cuando el cobro está confirmado (processing / completed).
	// Caso típico: Transferencia bancaria (BACS) deja la orden en on-hold hasta
	// que la dueña verifica el depósito; Transbank rechazado deja la orden en
	// failed. Antes el cliente veía "¡Gracias, vamos a preparar tu pedido!"
	// con cuadro verde y se generaba falsa expectativa de pago confirmado.
	$status = $order->get_status();

	if ( 'failed' === $status ) {
		return __(
			'Tu pago no se pudo completar. Puedes reintentar desde "Mi cuenta → Pedidos" o elegir otro medio de pago.',
			'bgmg-chile'
		);
	}

	if ( 'cancelled' === $status ) {
		return __(
			'Este pedido fue cancelado. Si fue un error, puedes hacer una nueva compra cuando quieras.',
			'bgmg-chile'
		);
	}

	if ( 'pending' === $status ) {
		return __(
			'Recibimos tu pedido pero aún no se confirma el pago. Si elegiste Transbank, vuelve a intentarlo; si elegiste transferencia, revisa los datos de cuenta más abajo.',
			'bgmg-chile'
		);
	}

	if ( 'on-hold' === $status ) {
		// Transferencia bancaria u otro método que deja la orden en espera.
		// WC nativo agrega el bloque con los datos de cuenta debajo del mensaje.
		$base = __(
			'Recibimos tu pedido. Para confirmarlo, realiza la transferencia con los datos que aparecen abajo. Cuando verifiquemos el pago empezaremos a preparar tu envío.',
			'bgmg-chile'
		);
		if ( bgmg_chile_orden_es_retiro( $order ) ) {
			return $base . ' ' . __( 'Tu pedido es para retiro en tienda — te avisaremos cuando esté listo.', 'bgmg-chile' );
		}
		if ( 'por_pagar' === bgmg_chile_orden_tipo_tarifa( $order ) ) {
			$nombre_courier = bgmg_chile_orden_courier_nombre( bgmg_chile_orden_courier( $order ) );
			if ( '' !== $nombre_courier ) {
				return $base . ' ' . sprintf(
					/* translators: %s: nombre del courier (Starken / Bluexpress) */
					__( 'Después de confirmar el pago despachamos por %s; el flete a tu comuna se paga al recibir.', 'bgmg-chile' ),
					$nombre_courier
				);
			}
			return $base . ' ' . __( 'Después de confirmar el pago despachamos por courier; el flete a tu comuna se paga al recibir.', 'bgmg-chile' );
		}
		return $base;
	}

	// De aquí en adelante asumimos pago confirmado (processing / completed o
	// estados custom equivalentes): mensaje según el flujo de envío.

	if ( bgmg_chile_orden_es_retiro( $order ) ) {
		return __(
			'¡Gracias por tu compra! Estamos preparando tu pedido y te avisaremos por email apenas esté listo para retirar.',
			'bgmg-chile'
		);
	}

	$tipo_tarifa = bgmg_chile_orden_tipo_tarifa( $order );
	$courier     = bgmg_chile_orden_courier( $order );

	$msg_tarifa_fija = __(
		'¡Gracias por tu compra! Vamos a preparar tu pedido y te avisaremos cuando salga por courier con su código de seguimiento.',
		'bgmg-chile'
	);
	$msg_por_pagar = __(
		'¡Gracias por tu compra! Despachamos por courier a todo Chile. El flete a tu comuna se paga al recibir el paquete; te avisaremos cuando salga con su código de seguimiento.',
		'bgmg-chile'
	);

	if ( 'fija' === $tipo_tarifa ) {
		return $msg_tarifa_fija;
	}
	if ( 'por_pagar' === $tipo_tarifa ) {
		// Si el cliente eligió courier (Starken / Bluexpress) lo mencionamos
		// específicamente en el mensaje; refuerza que sabemos lo que pidió y
		// le pone nombre al "courier" genérico.
		$nombre_courier = bgmg_chile_orden_courier_nombre( $courier );
		if ( '' !== $nombre_courier ) {
			return sprintf(
				/* translators: %s: nombre del courier (Starken / Bluexpress) */
				__( '¡Gracias por tu compra! Despachamos tu pedido por %s a todo Chile. El flete a tu comuna se paga directamente al courier al recibir el paquete; te avisaremos cuando salga con su código de seguimiento.', 'bgmg-chile' ),
				$nombre_courier
			);
		}
		return $msg_por_pagar;
	}

	// Sin meta (órdenes previas al meta o método de envío externo): si el
	// cliente pagó algo por shipping, el envío está cubierto → mensaje "fija".
	if ( (float) $order->get_shipping_total() > 0 ) {
		return $msg_tarifa_fija;
	}

	// Último fallback por comuna (comportamiento original).
	$comuna = $order->get_shipping_city();
	if ( '' === $comuna ) {
		$comuna = $order->get_billing_city();
	}
	if ( null !== bgmg_chile_get_tarifa_fija( $comuna ) ) {
		return $msg_tarifa_fija;
	}

	return $msg_por_pagar;
}

/**
 * Devuelve el tipo de tarifa del shipping de la orden ('fija', 'por_pagar',
 * 'gratis' o '' si no hay meta). Lo lee del shipping item, que es la fuente de
 * verdad: queda congelado al hacer el pedido y sobrevive a cambios futuros en
 * la tabla de tarifas.
 *
 * @param WC_Order $order
 * @return string
 */
function bgmg_chile_orden_tipo_tarifa( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}
	foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
		$meta = $shipping_item->get_meta( 'bgmg_tarifa_tipo' );
		if ( '' !== $meta ) {
			return (string) $meta;
		}
	}
	return '';
}

/**
 * Devuelve el courier elegido por el cliente para órdenes "por pagar"
 * ('starken' | 'bluexpress' | '' si la orden es anterior a v1.12.0 o no
 * tuvo elección de courier).
 *
 * @param WC_Order $order
 * @return string
 */
function bgmg_chile_orden_courier( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}
	foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
		$courier = $shipping_item->get_meta( 'bgmg_courier' );
		if ( '' !== $courier ) {
			return (string) $courier;
		}
	}
	return '';
}

/**
 * Mapea slug interno de courier a su nombre comercial. Si el slug no es uno
 * de los conocidos, devuelve string vacío (no inventar nombres).
 *
 * @param string $slug
 * @return string
 */
function bgmg_chile_orden_courier_nombre( $slug ) {
	$mapa = array(
		'starken'    => 'Starken',
		'bluexpress' => 'Bluexpress',
	);
	return $mapa[ $slug ] ?? '';
}

/**
 * Filtro automático del mensaje nativo de WC en la thank you page.
 * Si bgmg-landing tiene un template custom y prefiere otro texto, puede
 * desactivar este filtro o sobrescribirlo con prioridad mayor a 10.
 */
add_filter( 'woocommerce_thankyou_order_received_text', 'bgmg_chile_filter_thankyou_text', 10, 2 );

function bgmg_chile_filter_thankyou_text( $texto, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $texto;
	}
	if ( 'CL' !== $order->get_billing_country() ) {
		return $texto; // no es chilena: dejamos el texto WC default.
	}
	return bgmg_chile_get_thankyou_message( $order );
}
