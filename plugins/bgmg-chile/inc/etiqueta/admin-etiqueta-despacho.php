<?php
/**
 * Bloque "Datos de despacho" para imprimir / copiar etiqueta.
 *
 * Pensado para cuando la dueña tiene que pegar los datos del cliente en
 * Starken / Chilexpress / mostrador del courier. En lugar de andar
 * copiando campo por campo del editor de orden, este metabox los junta en
 * el orden exacto que necesita y le da dos atajos:
 *
 *   1. "Copiar todo" → al portapapeles en formato plano (una línea por dato).
 *   2. "Imprimir etiqueta" → abre una vista limpia y aislada en otra pestaña,
 *      lista para Ctrl+P (sin chrome de wp-admin, solo los datos).
 *
 * Orden de los 8 campos (decidido por la dueña, 2026-05-18):
 *   1. Nombre
 *   2. RUT
 *   3. Dirección de la calle
 *   4. Comuna
 *   5. Región
 *   6. Correo
 *   7. Método de envío
 *   8. ID del pedido
 *
 * Compatible con HPOS y editor legacy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- *
 *  HELPER: arma el array asociativo con los 8 campos para una orden.
 *  Centralizado acá para reusar entre metabox, vista imprimible y copy-paste.
 * ------------------------------------------------------------------------- */

/**
 * @param WC_Order $order
 * @return array{nombre:string,rut:string,calle:string,comuna:string,region:string,correo:string,metodo:string,id:string}
 */
function bgmg_chile_get_datos_despacho( $order ) {

	if ( ! $order instanceof WC_Order ) {
		return array();
	}

	// Nombre: priorizamos shipping (es la persona que recibe). Fallback a billing.
	$nombre = trim( $order->get_formatted_shipping_full_name() );
	if ( '' === $nombre ) {
		$nombre = trim( $order->get_formatted_billing_full_name() );
	}

	// RUT: del meta del plugin.
	$rut = (string) $order->get_meta( '_bgmg_rut' );

	// Calle: address_1 + address_2 (si hay).
	$addr1 = $order->get_shipping_address_1();
	$addr2 = $order->get_shipping_address_2();
	if ( '' === $addr1 && '' === $addr2 ) {
		// Fallback a billing (caso retiro en tienda no tiene shipping).
		$addr1 = $order->get_billing_address_1();
		$addr2 = $order->get_billing_address_2();
	}
	$calle = trim( $addr1 . ( $addr2 ? ', ' . $addr2 : '' ) );

	// Comuna: en el city WC tiene el slug; convertimos a nombre legible.
	$city_slug = $order->get_shipping_city();
	if ( '' === $city_slug ) {
		$city_slug = $order->get_billing_city();
	}
	$comuna = bgmg_chile_get_comuna_nombre( $city_slug );
	if ( '' === $comuna ) {
		$comuna = $city_slug; // fallback al raw por si la orden trae algo no estándar.
	}

	// Región: el state es el código (ej. "RM"); convertimos a nombre.
	$state_code = $order->get_shipping_state();
	if ( '' === $state_code ) {
		$state_code = $order->get_billing_state();
	}
	$regiones = bgmg_chile_get_regiones();
	$region   = isset( $regiones[ $state_code ] ) ? $regiones[ $state_code ] : $state_code;

	// Correo.
	$correo = (string) $order->get_billing_email();

	// Teléfono: priorizamos shipping (el del receptor). Fallback a billing.
	// Es crítico para que el courier llame antes de entregar.
	$telefono = '';
	if ( is_callable( array( $order, 'get_shipping_phone' ) ) ) {
		$telefono = (string) $order->get_shipping_phone();
	}
	if ( '' === $telefono ) {
		$telefono = (string) $order->get_billing_phone();
	}

	// Método de envío: si la dueña ya cargó "Método/Courier" en el metabox
	// de tracking, ese gana (es el courier real). Si no, usamos el nombre
	// del método de envío que el cliente eligió en checkout.
	$metodo = trim( (string) $order->get_meta( '_bgmg_tracking_metodo' ) );
	if ( '' === $metodo ) {
		$nombres = array();
		foreach ( $order->get_shipping_methods() as $item ) {
			$nombres[] = $item->get_method_title();
		}
		$metodo = implode( ', ', array_filter( $nombres ) );
	}

	// ID del pedido (formato visible al cliente: con prefijo).
	$id = '#' . $order->get_order_number();

	return array(
		'nombre'   => $nombre,
		'rut'      => $rut,
		'calle'    => $calle,
		'comuna'   => $comuna,
		'region'   => $region,
		'telefono' => $telefono,
		'correo'   => $correo,
		'metodo'   => $metodo,
		'id'       => $id,
	);
}

/**
 * Etiquetas humanas de cada campo (centralizado para no desincronizar
 * entre metabox, copy-text y vista imprimible).
 */
function bgmg_chile_get_datos_despacho_labels() {
	return array(
		'nombre'   => __( 'Nombre', 'bgmg-chile' ),
		'rut'      => __( 'RUT', 'bgmg-chile' ),
		'calle'    => __( 'Dirección', 'bgmg-chile' ),
		'comuna'   => __( 'Comuna', 'bgmg-chile' ),
		'region'   => __( 'Región', 'bgmg-chile' ),
		'telefono' => __( 'Teléfono', 'bgmg-chile' ),
		'correo'   => __( 'Correo', 'bgmg-chile' ),
		'metodo'   => __( 'Método de envío', 'bgmg-chile' ),
		'id'       => __( 'ID del pedido', 'bgmg-chile' ),
	);
}

/* ------------------------------------------------------------------------- *
 *  1. METABOX EN ADMIN DE ORDEN (HPOS + legacy)
 * ------------------------------------------------------------------------- */

add_action( 'add_meta_boxes', 'bgmg_chile_register_etiqueta_metabox' );

function bgmg_chile_register_etiqueta_metabox() {
	$screen = function_exists( 'wc_get_page_screen_id' )
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';

	add_meta_box(
		'bgmg-chile-etiqueta-despacho',
		__( '🏷️ Datos de despacho', 'bgmg-chile' ),
		'bgmg_chile_render_etiqueta_metabox',
		$screen,
		'side',
		'default'
	);
}

/**
 * Render del metabox: lista limpia + botones.
 */
function bgmg_chile_render_etiqueta_metabox( $post_or_order ) {

	$order = is_a( $post_or_order, 'WC_Order' )
		? $post_or_order
		: wc_get_order( is_object( $post_or_order ) ? $post_or_order->ID : $post_or_order );
	if ( ! $order ) {
		echo '<p>' . esc_html__( 'No se puede leer la orden.', 'bgmg-chile' ) . '</p>';
		return;
	}

	$datos   = bgmg_chile_get_datos_despacho( $order );
	$labels  = bgmg_chile_get_datos_despacho_labels();
	$print_url = add_query_arg(
		array(
			'action'   => 'bgmg_chile_etiqueta',
			'order_id' => $order->get_id(),
			'_wpnonce' => wp_create_nonce( 'bgmg_chile_print_etiqueta_' . $order->get_id() ),
		),
		admin_url( 'admin-post.php' )
	);

	?>
	<div class="bgmg-chile-etiqueta-box">
		<table class="widefat" style="border:0;background:transparent;">
			<tbody>
				<?php foreach ( $labels as $key => $label ) :
					$val = isset( $datos[ $key ] ) ? $datos[ $key ] : '';
					?>
					<tr>
						<th style="width:38%;padding:4px 6px;border:0;background:transparent;color:#7A5060;font-weight:500;">
							<?php echo esc_html( $label ); ?>
						</th>
						<td style="padding:4px 6px;border:0;background:transparent;color:#1A1015;">
							<?php echo $val !== '' ? esc_html( $val ) : '<em style="color:#bbb;">—</em>'; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap;">
			<button
				type="button"
				class="button bgmg-chile-copiar-datos-despacho"
				data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"
			>
				📋 <?php esc_html_e( 'Copiar todo', 'bgmg-chile' ); ?>
			</button>
			<a
				href="<?php echo esc_url( $print_url ); ?>"
				target="_blank"
				rel="noopener"
				class="button button-primary"
			>
				🖨️ <?php esc_html_e( 'Imprimir etiqueta', 'bgmg-chile' ); ?>
			</a>
		</p>

		<?php
		// Texto plano oculto, listo para que el JS lo lea y lo mande al clipboard.
		// Formato: "Etiqueta — una línea por dato".
		$lineas = array();
		foreach ( $labels as $key => $label ) {
			$lineas[] = $label . ': ' . ( isset( $datos[ $key ] ) ? $datos[ $key ] : '' );
		}
		$texto_plano = implode( "\n", $lineas );
		?>
		<textarea
			class="bgmg-chile-etiqueta-texto-plano"
			data-for-order="<?php echo esc_attr( $order->get_id() ); ?>"
			readonly
			style="position:absolute;left:-9999px;width:1px;height:1px;"
			tabindex="-1"
			aria-hidden="true"
		><?php echo esc_textarea( $texto_plano ); ?></textarea>
	</div>
	<?php
}

/* ------------------------------------------------------------------------- *
 *  2. VISTA IMPRIMIBLE — handler en admin-post.php
 *
 *  Antes lo registrábamos como add_submenu_page con parent null. Eso tenía
 *  dos problemas:
 *    a) WP renderizaba el chrome de wp-admin alrededor y el CSS lo escondía
 *       mal, dejando la pantalla en blanco.
 *    b) Al eliminar el submenu_page para usar admin_init, WP corta con
 *       "Lo siento, no tienes permisos" porque el check de page slug ocurre
 *       ANTES del hook admin_init.
 *
 *  admin-post.php no requiere registrar página de menú, no tiene chrome de
 *  wp-admin, y permite GET/POST. Es la herramienta correcta para esto.
 *
 *  Soporta dos formatos vía ?formato=termico|a4 (default: termico, 60×80mm).
 * ------------------------------------------------------------------------- */

add_action( 'admin_post_bgmg_chile_etiqueta', 'bgmg_chile_dispatch_etiqueta_print' );

function bgmg_chile_dispatch_etiqueta_print() {
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_die( esc_html__( 'No tienes permisos para imprimir esta etiqueta.', 'bgmg-chile' ) );
	}

	$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	$nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! $order_id || ! wp_verify_nonce( $nonce, 'bgmg_chile_print_etiqueta_' . $order_id ) ) {
		wp_die( esc_html__( 'Enlace de impresión inválido o expirado.', 'bgmg-chile' ) );
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_die( esc_html__( 'Orden no encontrada.', 'bgmg-chile' ) );
	}

	$formato = isset( $_GET['formato'] ) ? sanitize_key( wp_unslash( $_GET['formato'] ) ) : 'termico';
	if ( ! in_array( $formato, array( 'termico', 'a4' ), true ) ) {
		$formato = 'termico';
	}

	bgmg_chile_render_etiqueta_standalone( $order, $formato );
	exit;
}

/**
 * CSS común para etiquetas (single + batch). Lo extraemos a una función
 * para reusarlo y aceptar parámetros (tamaño de página principalmente).
 */
function bgmg_chile_etiqueta_styles( $formato ) {
	$page_size = ( 'a4' === $formato ) ? 'A4' : '60mm 80mm';
	ob_start();
	?>
	* { box-sizing: border-box; }
	html, body { margin: 0; padding: 0; background: #eee; font-family: Arial, 'Helvetica Neue', sans-serif; color: #000; }

	/* Barra superior (no se imprime). */
	.toolbar {
		background: #1A1015; color: #fff;
		padding: 10px 14px;
		display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
		font-size: 13px;
	}
	.toolbar .grow { flex: 1; }
	.toolbar a, .toolbar button {
		background: #C4728A; color: #fff; border: 0;
		padding: 6px 12px; border-radius: 4px;
		font-size: 13px; cursor: pointer; text-decoration: none;
		font-family: inherit;
	}
	.toolbar a.active { background: #fff; color: #1A1015; }
	.toolbar a.secondary { background: transparent; border: 1px solid #C4728A; color: #C4728A; }

	/* === Térmico 60×80mm === */
	.etiqueta-termico {
		width: 60mm; height: 80mm;
		margin: 20px auto;
		background: #fff;
		padding: 3mm;
		border: 1px solid #ccc;
		font-family: Arial, sans-serif;
		color: #000;
		line-height: 1.15;
		overflow: hidden;
	}
	.etiqueta-termico .id-pedido {
		text-align: center; font-size: 16pt; font-weight: 900;
		letter-spacing: 1px; border-bottom: 1.5px solid #000;
		padding-bottom: 1.5mm; margin-bottom: 2mm;
	}
	.etiqueta-termico .marca {
		font-size: 6.5pt; text-align: center; text-transform: uppercase;
		letter-spacing: 1px; margin-bottom: 1mm; color: #666;
	}
	.etiqueta-termico .campo { margin-bottom: 1.4mm; }
	.etiqueta-termico .campo .label {
		font-size: 6pt; text-transform: uppercase;
		letter-spacing: 0.5px; color: #555; font-weight: 700;
	}
	.etiqueta-termico .campo .valor { font-size: 9pt; font-weight: 700; word-wrap: break-word; }
	.etiqueta-termico .campo.destacado .valor { font-size: 11pt; }
	.etiqueta-termico .campo.compacto { display: flex; gap: 2mm; align-items: baseline; }
	.etiqueta-termico .campo.compacto .label { white-space: nowrap; }
	.etiqueta-termico .campo.compacto .valor { font-size: 8pt; font-weight: 600; }

	/* === A4 === */
	.etiqueta-a4 {
		width: 180mm; min-height: 240mm;
		margin: 20px auto; background: #fff;
		padding: 20mm 22mm; border: 1px solid #ccc;
		font-family: 'DM Sans', Arial, sans-serif; color: #1A1015;
	}
	.etiqueta-a4 h1 { font-family: Georgia, 'Cormorant Garamond', serif; font-size: 28pt; margin: 0 0 6px; letter-spacing: 1px; }
	.etiqueta-a4 .sub { font-size: 11pt; color: #7A5060; padding-bottom: 14px; margin: 0 0 20px; border-bottom: 1px dashed #C4728A; }
	.etiqueta-a4 .id-pedido { font-size: 24pt; font-weight: 900; text-align: center; letter-spacing: 2px; padding: 8mm 0; border: 2px solid #1A1015; margin-bottom: 8mm; }
	.etiqueta-a4 dl { margin: 0; }
	.etiqueta-a4 dt { font-size: 9pt; text-transform: uppercase; letter-spacing: 1px; color: #7A5060; margin-top: 12px; }
	.etiqueta-a4 dd { margin: 2px 0 0; font-size: 15pt; font-weight: 600; color: #1A1015; word-break: break-word; }

	/* === PRINT === */
	@page { size: <?php echo esc_html( $page_size ); ?>; margin: 0; }
	@media print {
		html, body { background: #fff !important; }
		.toolbar { display: none !important; }
		.etiqueta-termico, .etiqueta-a4 {
			margin: 0 !important; border: 0 !important; box-shadow: none !important;
		}
		.etiqueta-termico { padding: 2mm; }
		.etiqueta-a4 { padding: 15mm; }
		/* En batch print: cada etiqueta en su propia página. */
		.etiqueta-termico, .etiqueta-a4 {
			page-break-after: always;
			break-after: page;
		}
		.etiqueta-termico:last-child, .etiqueta-a4:last-child {
			page-break-after: auto;
			break-after: auto;
		}
	}
	<?php
	return ob_get_clean();
}

/**
 * Devuelve el HTML de UNA etiqueta (solo el <div>, sin <html>/<head>/<body>).
 * Reusable para single y batch printing.
 *
 * @param WC_Order $order
 * @param string $formato 'termico' o 'a4'
 * @return string HTML escapado
 */
function bgmg_chile_etiqueta_inner_html( $order, $formato ) {
	$datos  = bgmg_chile_get_datos_despacho( $order );
	$labels = bgmg_chile_get_datos_despacho_labels();
	ob_start();

	if ( 'termico' === $formato ) : ?>
		<div class="etiqueta-termico">
			<div class="marca">BeautyGirlMG</div>
			<div class="id-pedido"><?php echo esc_html( $datos['id'] ); ?></div>

			<div class="campo destacado">
				<div class="label"><?php echo esc_html( $labels['nombre'] ); ?></div>
				<div class="valor"><?php echo esc_html( $datos['nombre'] ?: '—' ); ?></div>
			</div>

			<div class="campo">
				<div class="label"><?php echo esc_html( $labels['calle'] ); ?></div>
				<div class="valor"><?php echo esc_html( $datos['calle'] ?: '—' ); ?></div>
			</div>

			<div class="campo">
				<div class="label"><?php echo esc_html( $labels['comuna'] ); ?> / <?php echo esc_html( $labels['region'] ); ?></div>
				<div class="valor"><?php echo esc_html( trim( $datos['comuna'] . ' — ' . $datos['region'], ' —' ) ); ?></div>
			</div>

			<?php if ( $datos['telefono'] ) : ?>
				<div class="campo destacado">
					<div class="label"><?php echo esc_html( $labels['telefono'] ); ?></div>
					<div class="valor"><?php echo esc_html( $datos['telefono'] ); ?></div>
				</div>
			<?php endif; ?>

			<?php if ( $datos['rut'] ) : ?>
				<div class="campo compacto">
					<div class="label"><?php echo esc_html( $labels['rut'] ); ?>:</div>
					<div class="valor"><?php echo esc_html( $datos['rut'] ); ?></div>
				</div>
			<?php endif; ?>

			<?php if ( $datos['metodo'] ) : ?>
				<div class="campo compacto">
					<div class="label">Envío:</div>
					<div class="valor"><?php echo esc_html( $datos['metodo'] ); ?></div>
				</div>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="etiqueta-a4">
			<h1>BeautyGirlMG</h1>
			<p class="sub">Etiqueta de despacho</p>
			<div class="id-pedido"><?php echo esc_html( $datos['id'] ); ?></div>

			<dl>
				<?php foreach ( $labels as $key => $label ) :
					if ( 'id' === $key ) { continue; }
					$val = isset( $datos[ $key ] ) ? $datos[ $key ] : '';
					?>
					<dt><?php echo esc_html( $label ); ?></dt>
					<dd><?php echo $val !== '' ? esc_html( $val ) : '—'; ?></dd>
				<?php endforeach; ?>
			</dl>
		</div>
	<?php endif;

	return ob_get_clean();
}

/**
 * Sirve la etiqueta como página HTML completa, fuera del chrome de wp-admin.
 * Optimizada para impresora térmica 60×80 mm; fallback a A4 con ?formato=a4.
 */
function bgmg_chile_render_etiqueta_standalone( $order, $formato ) {

	$datos = bgmg_chile_get_datos_despacho( $order );

	// URL para alternar formato sin perder nonce ni order.
	$base_url    = remove_query_arg( 'formato' );
	$url_termico = add_query_arg( 'formato', 'termico', $base_url );
	$url_a4      = add_query_arg( 'formato', 'a4', $base_url );

	// No-cache: la etiqueta no debe quedar cacheada por LiteSpeed/proxy.
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiqueta <?php echo esc_html( $datos['id'] ); ?></title>
<style><?php echo bgmg_chile_etiqueta_styles( $formato ); ?></style>
</head>
<body>

<div class="toolbar">
	<strong>Etiqueta <?php echo esc_html( $datos['id'] ); ?></strong>
	<span class="grow"></span>
	<a href="<?php echo esc_url( $url_termico ); ?>" class="<?php echo $formato === 'termico' ? 'active' : ''; ?>">60×80mm (térmica)</a>
	<a href="<?php echo esc_url( $url_a4 ); ?>" class="<?php echo $formato === 'a4' ? 'active' : ''; ?>">A4</a>
	<button type="button" onclick="window.print()">🖨️ Imprimir</button>
	<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ); ?>" class="secondary">← Volver</a>
</div>

<?php echo bgmg_chile_etiqueta_inner_html( $order, $formato ); ?>

<script>
	(function(){
		var params = new URLSearchParams(window.location.search);
		if (params.get('autoprint') === '1') {
			window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });
		}
	})();
</script>
</body>
</html>
<?php
}

/**
 * Render batch: múltiples etiquetas en UN solo documento, con page-break
 * entre cada una para que la impresora térmica las corte. Pensado para que
 * la dueña imprima 10 pedidos del día de una sola pasada.
 *
 * @param WC_Order[] $orders Array de órdenes válidas.
 * @param string $formato 'termico' o 'a4'.
 */
function bgmg_chile_render_etiquetas_batch( $orders, $formato ) {

	$count = count( $orders );
	$base_url    = remove_query_arg( 'formato' );
	$url_termico = add_query_arg( 'formato', 'termico', $base_url );
	$url_a4      = add_query_arg( 'formato', 'a4', $base_url );

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiquetas batch (<?php echo (int) $count; ?>) — BeautyGirlMG</title>
<style><?php echo bgmg_chile_etiqueta_styles( $formato ); ?></style>
</head>
<body>

<div class="toolbar">
	<strong><?php echo (int) $count; ?> etiquetas listas para imprimir</strong>
	<span class="grow"></span>
	<a href="<?php echo esc_url( $url_termico ); ?>" class="<?php echo $formato === 'termico' ? 'active' : ''; ?>">60×80mm (térmica)</a>
	<a href="<?php echo esc_url( $url_a4 ); ?>" class="<?php echo $formato === 'a4' ? 'active' : ''; ?>">A4</a>
	<button type="button" onclick="window.print()">🖨️ Imprimir todas</button>
	<a href="<?php echo esc_url( bgmg_chile_admin_orders_url() ); ?>" class="secondary">← Volver a pedidos</a>
</div>

<?php
foreach ( $orders as $order ) {
	echo bgmg_chile_etiqueta_inner_html( $order, $formato );
}
?>

<script>
	(function(){
		var params = new URLSearchParams(window.location.search);
		if (params.get('autoprint') === '1') {
			window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 500); });
		}
	})();
</script>
</body>
</html>
<?php
}

/* ------------------------------------------------------------------------- *
 *  BULK PRINT — acción masiva en la lista de pedidos (HPOS + legacy).
 *
 *  La dueña selecciona varios pedidos en WC → Pedidos, elige
 *  "Imprimir etiquetas BGMG" del dropdown de acciones masivas, y la
 *  redirección nos lleva al endpoint batch que renderiza todas las
 *  etiquetas de una.
 * ------------------------------------------------------------------------- */

add_filter( 'bulk_actions-edit-shop_order', 'bgmg_chile_register_bulk_print_action' );
add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'bgmg_chile_register_bulk_print_action' );

function bgmg_chile_register_bulk_print_action( $actions ) {
	$actions['bgmg_chile_print_labels'] = __( 'Imprimir etiquetas BGMG', 'bgmg-chile' );
	return $actions;
}

add_filter( 'handle_bulk_actions-edit-shop_order', 'bgmg_chile_handle_bulk_print_action', 10, 3 );
add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', 'bgmg_chile_handle_bulk_print_action', 10, 3 );

function bgmg_chile_handle_bulk_print_action( $redirect_url, $action, $order_ids ) {
	if ( 'bgmg_chile_print_labels' !== $action ) {
		return $redirect_url;
	}
	if ( empty( $order_ids ) || ! current_user_can( 'edit_shop_orders' ) ) {
		return $redirect_url;
	}

	$ids = array_filter( array_map( 'absint', (array) $order_ids ) );
	if ( empty( $ids ) ) {
		return $redirect_url;
	}

	$batch_url = add_query_arg(
		array(
			'action'    => 'bgmg_chile_etiquetas_batch',
			'order_ids' => implode( ',', $ids ),
			'_wpnonce'  => wp_create_nonce( 'bgmg_chile_batch_labels' ),
		),
		admin_url( 'admin-post.php' )
	);

	// El redirect se va a la URL del batch — el navegador entra a esa página,
	// muestra las etiquetas y la dueña aprieta imprimir.
	return $batch_url;
}

add_action( 'admin_post_bgmg_chile_etiquetas_batch', 'bgmg_chile_dispatch_etiquetas_batch' );

function bgmg_chile_dispatch_etiquetas_batch() {
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_die( esc_html__( 'No tienes permisos para imprimir etiquetas.', 'bgmg-chile' ) );
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'bgmg_chile_batch_labels' ) ) {
		wp_die( esc_html__( 'Enlace de impresión inválido o expirado.', 'bgmg-chile' ) );
	}

	$raw_ids = isset( $_GET['order_ids'] ) ? sanitize_text_field( wp_unslash( $_GET['order_ids'] ) ) : '';
	$ids     = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );
	if ( empty( $ids ) ) {
		wp_die( esc_html__( 'No hay pedidos seleccionados.', 'bgmg-chile' ) );
	}

	// Tope de seguridad: máximo 200 etiquetas por batch para no colgar el navegador.
	$ids = array_slice( $ids, 0, 200 );

	$orders = array();
	foreach ( $ids as $id ) {
		$order = wc_get_order( $id );
		if ( $order ) {
			$orders[] = $order;
		}
	}
	if ( empty( $orders ) ) {
		wp_die( esc_html__( 'No se pudieron cargar los pedidos seleccionados.', 'bgmg-chile' ) );
	}

	$formato = isset( $_GET['formato'] ) ? sanitize_key( wp_unslash( $_GET['formato'] ) ) : 'termico';
	if ( ! in_array( $formato, array( 'termico', 'a4' ), true ) ) {
		$formato = 'termico';
	}

	bgmg_chile_render_etiquetas_batch( $orders, $formato );
	exit;
}

/* ------------------------------------------------------------------------- *
 *  3. JS del botón "Copiar todo" — encolamos solo en editor de orden
 * ------------------------------------------------------------------------- */

add_action( 'admin_enqueue_scripts', 'bgmg_chile_enqueue_etiqueta_js', 30 );

function bgmg_chile_enqueue_etiqueta_js( $hook ) {
	$pantallas = array( 'post.php', 'post-new.php', 'woocommerce_page_wc-orders' );
	if ( ! in_array( $hook, $pantallas, true ) ) {
		return;
	}
	wp_enqueue_script(
		'bgmg-chile-etiqueta',
		BGMG_CHILE_URL . 'assets/js/etiqueta-despacho.js',
		array(),
		BGMG_CHILE_VERSION,
		true
	);
}
