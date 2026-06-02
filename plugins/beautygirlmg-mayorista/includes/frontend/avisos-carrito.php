<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO FRONTEND: AVISOS DE ESTADO EN MINICART Y /CART/
 *
 * Renderiza dos tipos de aviso para grupos de variaciones que vinieron
 * del modo Auto/Manual (items con `bgm_origen` en cart_item_data):
 *
 *   B. Chip pequeño inline en el minicart, al lado del nombre del item,
 *      cuando ese item pertenece a un grupo que NO califica para mayorista.
 *
 *   C. Sección de tarjetas en la página /cart/ (una por cada grupo con
 *      items bgm_origen), mostrando estado actual: ✓ aplicado o ⚠ qué
 *      falta para equilibrar.
 *
 * NUNCA muestra avisos para items de compra detalle normal (sin flag).
 *
 * El tema (bgmg-landing) llama a `bgm_render_chip_minicart()` desde
 * `bgmg_minicart_inner()` y a `bgm_render_avisos_grupos_cart()` desde
 * `bgmg-cart.php` (después del loop de items).
 * =========================================================
 */

/**
 * Evalúa y cachea el estado de un grupo de variaciones del mismo padre.
 *
 * @param int $padre_id
 * @return array|null  ['califica', 'razon', 'mensaje', 'qty_total', 'min_1',
 *                      'min_2', 'tier', 'tiene_flag_origen', 'por_variacion']
 *                     o null si no hay items del padre en el cart
 */
function bgm_estado_grupo_variaciones( $padre_id ) {
    static $cache = [];
    if ( isset( $cache[ $padre_id ] ) ) return $cache[ $padre_id ];

    $cart = function_exists( 'WC' ) ? WC()->cart : null;
    if ( ! $cart ) return $cache[ $padre_id ] = null;

    $items             = $cart->get_cart();
    $por_variacion     = [];
    $qty_total         = 0;
    $tiene_flag_origen = false;

    foreach ( $items as $item ) {
        if ( empty( $item['data'] ) ) continue;
        if ( ! $item['data']->is_type( 'variation' ) ) continue;
        if ( (int) $item['data']->get_parent_id() !== (int) $padre_id ) continue;

        $qty = (int) $item['quantity'];
        $vid = (int) $item['variation_id'];

        $qty_total              += $qty;
        $por_variacion[ $vid ]   = ( $por_variacion[ $vid ] ?? 0 ) + $qty;
        if ( ! empty( $item['bgm_origen'] ) ) $tiene_flag_origen = true;
    }

    if ( empty( $por_variacion ) ) return $cache[ $padre_id ] = null;

    // Evaluar la regla unificada (la misma que aplica el precio)
    $padre  = wc_get_product( $padre_id );
    $stocks = $padre ? bgm_capacidades_variaciones( $padre ) : null;

    $evaluacion = bgm_evaluar_distribucion( $padre_id, $por_variacion, null, $stocks );

    $min_1     = bgm_get_min_1( $padre_id );
    $min_2     = bgm_get_min_2( $padre_id );
    $desc_1    = bgm_get_descuento_1( $padre_id );
    $desc_2    = bgm_get_descuento_2( $padre_id );
    $califica  = ! is_wp_error( $evaluacion );

    // Tier que se aplicaría si el grupo califica
    $tier = 0;
    if ( $califica ) {
        if ( $desc_2 > 0 && $qty_total >= $min_2 ) {
            $tier = 2;
        } elseif ( $desc_1 > 0 && $qty_total >= $min_1 ) {
            $tier = 1;
        }
    }

    return $cache[ $padre_id ] = [
        'califica'          => $califica,
        'razon'             => $califica ? 'ok' : $evaluacion->get_error_code(),
        'mensaje'           => $califica ? '' : $evaluacion->get_error_message(),
        'qty_total'         => $qty_total,
        'min_1'             => $min_1,
        'min_2'             => $min_2,
        'tier'              => $tier,
        'tiene_flag_origen' => $tiene_flag_origen,
        'por_variacion'     => $por_variacion,
    ];
}

/**
 * Chip inline para mostrar en el minicart al lado del nombre del item.
 * Devuelve '' si no aplica (item sin flag o grupo califica).
 *
 * @param array $cart_item
 * @return string
 */
function bgm_render_chip_minicart( $cart_item ) {
    if ( empty( $cart_item['data'] ) ) return '';
    if ( ! $cart_item['data']->is_type( 'variation' ) ) return '';
    if ( empty( $cart_item['bgm_origen'] ) ) return '';

    $padre_id = (int) $cart_item['data']->get_parent_id();
    $estado   = bgm_estado_grupo_variaciones( $padre_id );

    if ( ! $estado ) return '';

    if ( $estado['califica'] && $estado['tier'] > 0 ) {
        // No mostramos chip OK en el minicart para no saturar visualmente.
        // El ahorro ya se muestra en el precio tachado del item.
        return '';
    }

    if ( ! $estado['califica'] ) {
        $titulo = esc_attr__( 'Sin precio mayorista — equilibra el surtido para activarlo', 'beautygirlmg-mayorista' );
        return '<span class="bgm-chip-aviso bgm-chip-warn" title="' . $titulo . '">⚠ ' . esc_html__( 'Sin mayorista', 'beautygirlmg-mayorista' ) . '</span>';
    }

    return '';
}

/**
 * Sección de tarjetas resumen para la página /cart/.
 * Una tarjeta por cada padre con items de bgm_origen. Devuelve '' si nada.
 *
 * @return string
 */
function bgm_render_avisos_grupos_cart() {
    $cart = function_exists( 'WC' ) ? WC()->cart : null;
    if ( ! $cart || $cart->is_empty() ) return '';

    // Recolectar todos los padres con items bgm_origen
    $padres = [];
    foreach ( $cart->get_cart() as $item ) {
        if ( empty( $item['data'] ) ) continue;
        if ( ! $item['data']->is_type( 'variation' ) ) continue;
        if ( empty( $item['bgm_origen'] ) ) continue;
        $padres[ (int) $item['data']->get_parent_id() ] = true;
    }

    if ( empty( $padres ) ) return '';

    $html = '<div class="bgm-avisos-grupos">';
    $html .= '<h3 class="bgm-avisos-titulo">' . esc_html__( 'Estado de tus surtidos mayoristas', 'beautygirlmg-mayorista' ) . '</h3>';

    foreach ( array_keys( $padres ) as $padre_id ) {
        $estado = bgm_estado_grupo_variaciones( $padre_id );
        if ( ! $estado ) continue;

        $padre = wc_get_product( $padre_id );
        if ( ! $padre ) continue;

        $nombre_padre = esc_html( $padre->get_name() );

        if ( $estado['califica'] && $estado['tier'] > 0 ) {
            $tier_label = $estado['tier'] === 2
                ? esc_html__( 'Mayoreo grande', 'beautygirlmg-mayorista' )
                : esc_html__( 'Mayorista', 'beautygirlmg-mayorista' );

            $html .= '<div class="bgm-aviso-grupo bgm-aviso-ok">';
            $html .= '<div class="bgm-aviso-icono" aria-hidden="true">✓</div>';
            $html .= '<div class="bgm-aviso-cuerpo">';
            $html .= '<strong>' . $nombre_padre . '</strong>';
            $html .= '<span>' . sprintf(
                /* translators: 1: tier name 2: qty total */
                esc_html__( 'Precio %1$s aplicado · %2$d unidades en total', 'beautygirlmg-mayorista' ),
                $tier_label,
                (int) $estado['qty_total']
            ) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
        } else {
            $sugerencia = bgm_sugerencia_grupo( $estado );

            $html .= '<div class="bgm-aviso-grupo bgm-aviso-warn">';
            $html .= '<div class="bgm-aviso-icono" aria-hidden="true">⚠</div>';
            $html .= '<div class="bgm-aviso-cuerpo">';
            $html .= '<strong>' . $nombre_padre . '</strong>';
            $html .= '<span>' . esc_html( $sugerencia ) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
        }
    }

    $html .= '</div>';
    return $html;
}

/**
 * Genera una sugerencia humana sobre cómo activar el mayorista
 * según la razón por la que no califica.
 *
 * @param array $estado  Resultado de bgm_estado_grupo_variaciones
 * @return string
 */
function bgm_sugerencia_grupo( $estado ) {
    $razon     = $estado['razon'] ?? '';
    $qty_total = (int) $estado['qty_total'];
    $min_1     = (int) $estado['min_1'];

    // Caso 1: pocas unidades para tier 1
    if ( $qty_total < $min_1 ) {
        $faltan = $min_1 - $qty_total;
        return sprintf(
            /* translators: %d: cantidad faltante */
            _n(
                'Agrega %d unidad más para precio mayorista',
                'Agrega %d unidades más para precio mayorista',
                $faltan,
                'beautygirlmg-mayorista'
            ),
            $faltan
        );
    }

    if ( $razon === 'pocas_variaciones' || $razon === 'faltan_variaciones' ) {
        return __( 'Para mayorista necesitas distribuir entre más variaciones del producto', 'beautygirlmg-mayorista' );
    }

    if ( $razon === 'diferencia_excede' ) {
        return __( 'Equilibra las cantidades entre variaciones para activar el descuento', 'beautygirlmg-mayorista' );
    }

    return $estado['mensaje'] !== ''
        ? $estado['mensaje']
        : __( 'Este surtido no cumple la regla mayorista', 'beautygirlmg-mayorista' );
}
