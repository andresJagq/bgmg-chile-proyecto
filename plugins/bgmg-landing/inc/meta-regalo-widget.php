<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ventanita flotante "Te faltan $X para tu regalo" — Fase 2 de la meta de regalo.
 *
 * Consume bgm_meta_regalo_estado() del plugin mayorista (con function_exists, así
 * que si el plugin no está, simplemente no se muestra nada).
 *
 * Se refresca SIN recargar:
 *  - Add desde tarjetas (add-to-cart nativo de WC) → fragment '#bgm-meta-widget-root'.
 *  - Endpoints AJAX propios del side-cart (add producto / update qty / vaciar) →
 *    devuelven 'meta_widget_html' y el JS llama window.bgmMetaSwap().
 *
 * Aparece solo cuando el carrito está CERCA del próximo nivel (o al desbloquear).
 * Descartable por sesión (sessionStorage) con firma del estado: si cambia el nivel,
 * vuelve a mostrarse.
 */

/**
 * Contenido interno del widget (la tarjeta) o '' si no debe mostrarse.
 * Lleva data-sig (firma del estado) para el descarte por sesión.
 */
function bgmg_meta_widget_inner() {
	if ( ! function_exists( 'bgm_meta_regalo_estado' ) ) return '';

	$e = bgm_meta_regalo_estado();
	if ( empty( $e['activa'] ) || empty( $e['tiene_items'] ) ) return '';

	$mode = '';
	if ( ! empty( $e['proximo'] ) && ! empty( $e['cerca'] ) ) {
		$mode = 'progress';
	} elseif ( ! empty( $e['desbloqueado'] ) ) {
		$mode = 'unlocked';
	}
	if ( $mode === '' ) return '';

	if ( $mode === 'progress' ) {
		$px     = $e['proximo'];
		$umbral = (int) $px['umbral'];
		$sub    = (int) $e['subtotal'];
		$falta  = (int) $px['falta'];
		$pct    = $umbral > 0 ? min( 100, max( 3, (int) round( $sub / $umbral * 100 ) ) ) : 0;
		$sig    = 'p:' . $umbral;
		$titulo = '¡Te faltan <strong>' . esc_html( wp_strip_all_tags( wc_price( $falta ) ) ) . '</strong> para tu regalo!';
		$nombre = (string) $px['nombre'];
	} else {
		$d      = $e['desbloqueado'];
		$pct    = 100;
		$sig    = 'u:' . (int) $d['umbral'];
		$titulo = '¡Desbloqueaste tu regalo! 🎁';
		$nombre = (string) $d['nombre'];
	}

	ob_start();
	?>
	<div class="bgm-meta-card<?php echo $mode === 'unlocked' ? ' is-unlocked' : ''; ?>" data-sig="<?php echo esc_attr( $sig ); ?>">
		<button type="button" class="bgm-meta-close" aria-label="Cerrar">&times;</button>
		<div class="bgm-meta-emoji">🎁</div>
		<div class="bgm-meta-body">
			<div class="bgm-meta-title"><?php echo wp_kses_post( $titulo ); ?></div>
			<?php if ( $nombre !== '' ) : ?>
				<div class="bgm-meta-gift"><?php echo esc_html( $nombre ); ?></div>
			<?php endif; ?>
			<div class="bgm-meta-bar"><span style="width:<?php echo (int) $pct; ?>%;"></span></div>
		</div>
	</div>
	<?php
	return trim( ob_get_clean() );
}

/**
 * Elemento raíz estable: target del fragment de WC y contenedor del swap por AJAX.
 */
function bgmg_meta_widget_html() {
	return '<div id="bgm-meta-widget-root">' . bgmg_meta_widget_inner() . '</div>';
}

// Refresco vía fragments de WC (add-to-cart de las tarjetas).
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['#bgm-meta-widget-root'] = bgmg_meta_widget_html();
	return $fragments;
} );

// Render en el footer (todo el sitio menos checkout) + JS de swap/descarte.
add_action( 'wp_footer', 'bgmg_meta_widget_footer', 30 );
function bgmg_meta_widget_footer() {
	$is_checkout = function_exists( 'is_checkout' ) && is_checkout();
	if ( ! $is_checkout ) {
		echo bgmg_meta_widget_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
<script id="bgm-meta-widget-js">
(function(){
  var KEY = 'bgmMetaDismissed';
  function card(){ return document.querySelector('#bgm-meta-widget-root .bgm-meta-card'); }
  function apply(){
    var c = card();
    if (!c) return;
    var sig = c.getAttribute('data-sig') || '';
    c.style.display = (sig && sessionStorage.getItem(KEY) === sig) ? 'none' : '';
  }
  // Lo llaman los handlers del side-cart tras actualizar el carrito por AJAX.
  window.bgmMetaSwap = function(html){
    var root = document.getElementById('bgm-meta-widget-root');
    if (root && typeof html === 'string') root.innerHTML = html;
    apply();
  };
  document.addEventListener('click', function(e){
    var btn = e.target.closest('#bgm-meta-widget-root .bgm-meta-close');
    if (!btn) return;
    var c = card();
    if (c){ var sig = c.getAttribute('data-sig') || ''; if (sig) sessionStorage.setItem(KEY, sig); c.style.display = 'none'; }
  });
  // Add desde tarjetas (fragments nativos de WC).
  if (window.jQuery) jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded', apply);
  if (document.readyState !== 'loading') apply();
  else document.addEventListener('DOMContentLoaded', apply);
})();
</script>
	<?php
}
