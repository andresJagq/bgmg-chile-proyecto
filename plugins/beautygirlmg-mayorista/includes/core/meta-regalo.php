<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: META DE REGALO (free gift por monto de carrito)
 *
 * Si el carrito alcanza cierto monto (umbral), se agrega AUTOMÁTICAMENTE un
 * producto de regalo a $0. Soporta hasta 3 NIVELES (escalera): se entrega el
 * regalo del nivel MÁS ALTO alcanzado (uno solo, no acumulativo). Si el carrito
 * baja del umbral, el regalo se quita solo.
 *
 * Aplica a minoristas Y mayoristas: el umbral se mide sobre el SUBTOTAL real del
 * carrito (con los precios ya descontados por mayorista/promo), excluyendo el
 * propio regalo (no cuenta para su umbral → sin loop).
 *
 * Config global en wp_options con prefijo `bgm_meta_`. La parte VISUAL (ventanita
 * flotante) vive en bgmg-landing y consume `bgm_meta_regalo_estado()` (Fase 2).
 *
 * AISLADO: la línea del regalo lleva el flag `bgm_regalo` en cart_item_data y los
 * hooks de mayorista/promo la ignoran (ver carrito.php). Precio forzado a 0 aquí.
 * =========================================================
 */

/* ─────────────────────────────────────────────────────────────────────────────
 * LECTURA DE CONFIGURACIÓN
 * ───────────────────────────────────────────────────────────────────────────── */

// ¿Interruptor maestro encendido?
function bgm_meta_activa() {
	return bgm_get_setting( 'bgm_meta_activa', 'no' ) === 'yes';
}

// Monto "cerca" (CLP): la ventanita aparece cuando falte ESTO o menos para el próximo nivel.
function bgm_meta_cerca_monto() {
	return max( 0, (int) bgm_get_setting( 'bgm_meta_cerca_monto', 5000 ) );
}

/**
 * Niveles activos y válidos, ordenados ascendente por umbral.
 * Un nivel es válido si: activo === 'yes' Y umbral > 0 Y producto > 0.
 * Cacheado por request (el carrito recalcula varias veces).
 *
 * @return array Lista de [ 'i' => int, 'umbral' => int, 'producto' => int ]
 */
function bgm_meta_niveles_activos() {
	static $cache = null;
	if ( $cache !== null ) return $cache;

	$niveles = [];
	for ( $i = 1; $i <= 3; $i++ ) {
		$activo = bgm_get_setting( "bgm_meta_nivel{$i}_activo", 'no' ) === 'yes';
		$umbral = (int) bgm_get_setting( "bgm_meta_nivel{$i}_umbral", 0 );
		$pid    = (int) bgm_get_setting( "bgm_meta_nivel{$i}_producto", 0 );

		if ( ! $activo || $umbral <= 0 || $pid <= 0 ) continue;

		$niveles[] = [ 'i' => $i, 'umbral' => $umbral, 'producto' => $pid ];
	}

	usort( $niveles, function ( $a, $b ) { return $a['umbral'] <=> $b['umbral']; } );
	return $cache = $niveles;
}

/**
 * ¿El producto configurado sirve como regalo? Debe ser simple, comprable y con stock.
 */
function bgm_meta_producto_valido_regalo( $pid ) {
	$p = wc_get_product( (int) $pid );
	if ( ! $p )                    return false;
	if ( ! $p->is_type( 'simple' ) ) return false;
	if ( ! $p->is_purchasable() )  return false;
	if ( ! $p->is_in_stock() )     return false;
	return true;
}

/**
 * Subtotal del carrito SIN contar las líneas de regalo (usa los precios vigentes,
 * ya descontados por mayorista/promo en prioridad 99).
 */
function bgm_meta_subtotal_carrito( $cart ) {
	$sub = 0.0;
	foreach ( $cart->get_cart() as $item ) {
		if ( ! empty( $item['bgm_regalo'] ) ) continue;
		$p = $item['data'];
		if ( ! $p ) continue;
		$sub += floatval( $p->get_price() ) * (int) $item['quantity'];
	}
	return $sub;
}

/**
 * Nivel MÁS ALTO alcanzado para un subtotal dado (o null si ninguno).
 */
function bgm_meta_nivel_alcanzado( $subtotal ) {
	$alcanzado = null;
	foreach ( bgm_meta_niveles_activos() as $n ) { // ascendente
		if ( $subtotal >= $n['umbral'] ) $alcanzado = $n;
	}
	return $alcanzado;
}

/* ─────────────────────────────────────────────────────────────────────────────
 * LÓGICA: agregar / quitar el regalo y forzar su precio a 0
 * ───────────────────────────────────────────────────────────────────────────── */

// Prioridad 100: corre DESPUÉS del mayorista/promo (99) para medir el subtotal ya
// descontado. Un guard estático evita recursión al agregar/quitar la línea.
add_action( 'woocommerce_before_calculate_totals', 'bgm_meta_sync_regalo', 100 );
function bgm_meta_sync_regalo( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
	if ( ! $cart || ! method_exists( $cart, 'get_cart' ) ) return;

	static $busy = false;
	if ( $busy ) return;

	// Mapear líneas de regalo actuales: [cart_item_key => product_id]
	$gift_keys = [];
	foreach ( $cart->get_cart() as $key => $item ) {
		if ( ! empty( $item['bgm_regalo'] ) && $item['data'] ) {
			$gift_keys[ $key ] = (int) $item['data']->get_id();
		}
	}

	// Feature apagada → quitar cualquier regalo que haya quedado.
	if ( ! bgm_meta_activa() ) {
		if ( $gift_keys ) {
			$busy = true;
			foreach ( array_keys( $gift_keys ) as $key ) $cart->remove_cart_item( $key );
			$busy = false;
		}
		return;
	}

	$subtotal    = bgm_meta_subtotal_carrito( $cart );
	$alcanzado   = bgm_meta_nivel_alcanzado( $subtotal );
	$deseado_pid = ( $alcanzado && bgm_meta_producto_valido_regalo( $alcanzado['producto'] ) )
		? (int) $alcanzado['producto']
		: 0;

	$busy = true;

	// Quitar regalos que NO son el deseado (cambió de nivel o ya no aplica).
	// Conservar UNA sola línea del producto deseado.
	$tiene_deseado = false;
	foreach ( $gift_keys as $key => $pid ) {
		if ( $deseado_pid && $pid === $deseado_pid && ! $tiene_deseado ) {
			$tiene_deseado = true;
		} else {
			$cart->remove_cart_item( $key );
		}
	}

	// Agregar el regalo deseado si falta.
	if ( $deseado_pid && ! $tiene_deseado ) {
		$cart->add_to_cart( $deseado_pid, 1, 0, [], [
			'bgm_regalo'       => 1,
			'bgm_regalo_nivel' => (int) $alcanzado['i'],
		] );
	}

	// Forzar precio 0 y cantidad 1 en todas las líneas de regalo presentes.
	foreach ( $cart->get_cart() as $key => $item ) {
		if ( empty( $item['bgm_regalo'] ) ) continue;
		if ( $item['data'] ) $item['data']->set_price( 0 );
		if ( (int) $item['quantity'] !== 1 ) {
			$cart->set_quantity( $key, 1, false ); // false = no recalcular (evita recursión)
		}
	}

	$busy = false;
}

/* ─────────────────────────────────────────────────────────────────────────────
 * PRESENTACIÓN DE LA LÍNEA DEL REGALO (carrito nativo de WC; baseline)
 * El template custom de bgmg-landing puede afinarlo más en Fase 2.
 * ───────────────────────────────────────────────────────────────────────────── */

// Etiqueta "🎁 Regalo" junto al nombre.
add_filter( 'woocommerce_cart_item_name', 'bgm_meta_cart_item_name', 10, 3 );
function bgm_meta_cart_item_name( $name, $cart_item, $cart_item_key ) {
	if ( ! empty( $cart_item['bgm_regalo'] ) ) {
		$name .= ' <span class="bgm-regalo-tag">🎁 ' . esc_html__( 'Regalo', 'beautygirlmg-mayorista' ) . '</span>';
	}
	return $name;
}

// Cantidad fija (sin selector) para el regalo.
add_filter( 'woocommerce_cart_item_quantity', 'bgm_meta_cart_item_quantity', 10, 3 );
function bgm_meta_cart_item_quantity( $product_quantity, $cart_item_key, $cart_item ) {
	if ( ! empty( $cart_item['bgm_regalo'] ) ) return '1';
	return $product_quantity;
}

// Sin enlace de "quitar" para el regalo (lo gestiona el sistema).
add_filter( 'woocommerce_cart_item_remove_link', 'bgm_meta_cart_item_remove_link', 10, 2 );
function bgm_meta_cart_item_remove_link( $link, $cart_item_key ) {
	$cart = WC()->cart;
	if ( $cart && isset( $cart->cart_contents[ $cart_item_key ] ) && ! empty( $cart->cart_contents[ $cart_item_key ]['bgm_regalo'] ) ) {
		return '';
	}
	return $link;
}

/* ─────────────────────────────────────────────────────────────────────────────
 * ESTADO PARA EL TEMA (Fase 2: ventanita flotante)
 * ───────────────────────────────────────────────────────────────────────────── */

/**
 * Devuelve el estado de la meta para que el tema pinte la ventanita.
 *
 * @return array {
 *   activa        bool
 *   subtotal      int  (CLP, sin contar el regalo)
 *   niveles       array de [ umbral, producto_id, nombre, alcanzado ]
 *   proximo       null|[ umbral, nombre, falta ]   (siguiente nivel no alcanzado)
 *   desbloqueado  null|[ umbral, nombre ]          (nivel más alto alcanzado)
 *   cerca         bool (falta <= bgm_meta_cerca_monto)
 *   tiene_items   bool
 * }
 */
function bgm_meta_regalo_estado() {
	if ( ! bgm_meta_activa() ) return [ 'activa' => false ];
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) return [ 'activa' => false ];

	$cart     = WC()->cart;
	$subtotal = bgm_meta_subtotal_carrito( $cart );

	$niveles      = [];
	$proximo      = null;
	$desbloqueado = null;

	foreach ( bgm_meta_niveles_activos() as $n ) {
		$alc    = $subtotal >= $n['umbral'];
		$p      = wc_get_product( $n['producto'] );
		$nombre = $p ? $p->get_name() : '';

		$niveles[] = [
			'umbral'      => (int) $n['umbral'],
			'producto_id' => (int) $n['producto'],
			'nombre'      => $nombre,
			'alcanzado'   => $alc,
		];

		if ( $alc ) {
			$desbloqueado = [ 'umbral' => (int) $n['umbral'], 'nombre' => $nombre ];
		} elseif ( $proximo === null ) {
			$proximo = [
				'umbral' => (int) $n['umbral'],
				'nombre' => $nombre,
				'falta'  => (int) max( 0, ceil( $n['umbral'] - $subtotal ) ),
			];
		}
	}

	return [
		'activa'       => true,
		'subtotal'     => (int) round( $subtotal ),
		'niveles'      => $niveles,
		'proximo'      => $proximo,
		'desbloqueado' => $desbloqueado,
		'cerca'        => $proximo ? ( $proximo['falta'] <= bgm_meta_cerca_monto() ) : false,
		'tiene_items'  => $cart->get_cart_contents_count() > 0,
	];
}

/* ─────────────────────────────────────────────────────────────────────────────
 * HELPER ADMIN: opción actual para el <select> de búsqueda de productos
 * ───────────────────────────────────────────────────────────────────────────── */

/**
 * Texto de confirmación para el campo "producto regalo (ID)" en Ajustes.
 * Muestra el nombre del producto guardado y avisa si el ID no sirve como regalo
 * (no existe / no es simple / sin stock). Devuelve '' si la opción está vacía.
 */
function bgm_meta_desc_producto( $option_id ) {
	$pid = (int) get_option( $option_id, 0 );
	if ( $pid <= 0 ) return '';

	$p = wc_get_product( $pid );
	if ( ! $p ) {
		return ' <strong style="color:#b32d2e;">⚠️ ' . sprintf( esc_html__( 'ID %d: no existe ese producto.', 'beautygirlmg-mayorista' ), $pid ) . '</strong>';
	}

	$nombre = esc_html( wp_strip_all_tags( $p->get_name() ) );

	if ( ! $p->is_type( 'simple' ) ) {
		return ' <strong style="color:#b32d2e;">⚠️ ' . sprintf( esc_html__( '«%s» no es un producto simple.', 'beautygirlmg-mayorista' ), $nombre ) . '</strong>';
	}
	if ( ! $p->is_in_stock() ) {
		return ' <strong style="color:#b32d2e;">⚠️ ' . sprintf( esc_html__( '«%s» está sin stock.', 'beautygirlmg-mayorista' ), $nombre ) . '</strong>';
	}

	return ' <strong style="color:#1a7f37;">✓ ' . sprintf( esc_html__( 'Actual: «%s».', 'beautygirlmg-mayorista' ), $nombre ) . '</strong>';
}
