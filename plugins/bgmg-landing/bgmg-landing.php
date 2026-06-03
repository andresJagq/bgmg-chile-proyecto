<?php
/**
 * Plugin Name: BeautyGirlMG Landing
 * Description: Landing page completa con WooCommerce — sin Elementor ni WPCode.
 * Version:     6.5.6
 */

if (!defined('ABSPATH')) exit;

// Versión del plugin. Úsala como cache-buster en wp_enqueue_style/script para no
// hardcodear el número en cada asset. Mantener sincronizada con el header de arriba.
define( 'BGMG_LANDING_VERSION', '6.5.6' );

// ─── Módulos del plugin ────────────────────────────────────────────────────
require_once plugin_dir_path( __FILE__ ) . 'inc/customizer.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/category-meta.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/account-renders.php';

// Deshabilitar zoom de galería de producto (causa imagen ampliada en esquina)
add_filter('woocommerce_single_product_zoom_enabled', '__return_false');

/**
 * Rebrand "Dirección de facturación" → "Dirección de envío" en frontend.
 *
 * El sitio consolidó billing y shipping en un solo cuadro de dirección.
 * Para la UX del cliente, mostramos todo como "Envío". El admin sigue viendo
 * los términos nativos de WC para distinguir contextos al gestionar pedidos.
 *
 * Mantenemos intactos los strings relacionados a "factura" como documento
 * tributario (toggle "Necesito factura", "Datos para boleta/factura", etc.):
 * no son sobre direcciones.
 */
add_filter( 'gettext', 'bgmg_landing_rebrand_billing_strings', 20, 3 );
function bgmg_landing_rebrand_billing_strings( $translation, $text, $domain ) {
    if ( is_admin() || 'woocommerce' !== $domain ) {
        return $translation;
    }
    static $map = array(
        'Dirección de facturación'  => 'Dirección de envío',
        'Dirección de facturación:' => 'Dirección de envío:',
        'Billing address'           => 'Dirección de envío',
        'Billing address:'          => 'Dirección de envío:',
    );
    return $map[ $translation ] ?? $translation;
}

// Exponer AJAX URL y nonces al frontend
add_action('wp_enqueue_scripts', function() {
    wp_localize_script('jquery', 'bgmgAjax', array(
        'url'        => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('bgmg_search'),
        'shopNonce'  => wp_create_nonce('bgmg_shop'),
        'cartNonce'  => wp_create_nonce('bgmg_cart'),
    ));
});

// Actualizar contador + mini cart via AJAX cuando se agrega un producto
add_filter('woocommerce_add_to_cart_fragments', function($fragments) {
    $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $fragments['span.bgmg-cart-count'] = '<span class="bgmg-cart-count">' . ($count > 0 ? $count : '') . '</span>';
    ob_start();
    bgmg_minicart_inner();
    $fragments['#bgmg-minicart-inner'] = ob_get_clean();
    return $fragments;
});

function bgmg_minicart_inner() {
    $cart = WC()->cart;
    echo '<div id="bgmg-minicart-inner">';
    if (!$cart || $cart->is_empty()) {
        echo '<div class="bgmg-mc-empty">';
        echo '<span class="bgmg-mc-empty-icon">🛒</span>';
        echo '<p>Tu carrito está vacío</p>';
        echo '<a href="' . esc_url(get_permalink(wc_get_page_id('shop'))) . '" class="bgmg-mc-go-shop">Ver productos</a>';
        echo '</div>';
    } else {
        // Pre-calcular fees negativos (descuentos mayorista u otros plugins)
        $total_fee_discount  = 0;
        $total_line_subtotal = 0;
        foreach ($cart->get_fees() as $fee) {
            if ($fee->amount < 0) $total_fee_discount += abs($fee->amount);
        }
        foreach ($cart->get_cart() as $ci) {
            $total_line_subtotal += (float) $ci['line_subtotal'];
        }

        echo '<ul class="bgmg-mc-list">';
        foreach ($cart->get_cart() as $key => $item) {
            $product = $item['data'];
            $pid     = $item['product_id'];
            $img     = get_the_post_thumbnail_url($pid, 'thumbnail') ?: wc_placeholder_img_src();
            $name    = esc_html($product->get_name());
            $qty     = $item['quantity'];
            $rm_url  = esc_url(wc_get_cart_remove_url($key));
            $p_url   = esc_url(get_permalink($pid));

            // Calcular descuento por ítem (mismo algoritmo que bgmg_update_cart)
            $line_subtotal = (float) $item['line_subtotal'];
            $line_total    = (float) $item['line_total'];
            $reg_total     = (float) $product->get_regular_price() * $qty;
            $fee_share     = ($total_line_subtotal > 0)
                ? round($total_fee_discount * ($line_subtotal / $total_line_subtotal), 2)
                : 0;
            $saving      = max(0, $reg_total - $line_total + $fee_share);
            $unit_reg    = (float) $product->get_regular_price();
            $unit_actual = $qty > 0 ? round(($reg_total - $saving) / $qty, 2) : $unit_reg;
            $pct         = ($reg_total > 0 && $saving > 0) ? round(($saving / $reg_total) * 100) : 0;

            if ($saving > 0 && $unit_reg > $unit_actual) {
                $price_html = '<del class="bgmg-mc-price-orig">' . wc_price($unit_reg) . '</del>'
                            . '<span class="bgmg-mc-price-now">' . wc_price($unit_actual) . '</span>'
                            . ($pct > 0 ? '<span class="bgmg-mc-pct">−' . $pct . '%</span>' : '');
            } else {
                $price_html = '<span class="bgmg-mc-price-now">' . $product->get_price_html() . '</span>';
            }

            // Chip de aviso del plugin BGM (solo aparece si el item tiene
            // bgm_origen y el grupo no califica para mayorista)
            $bgm_chip = function_exists( 'bgm_render_chip_minicart' )
                ? bgm_render_chip_minicart( $item )
                : '';

            // ¿Item de surtido? (vino de Sorpréndeme o Manual del plugin BGM)
            $is_surtido    = ! empty( $item['bgm_origen'] );
            $surtido_class = $is_surtido ? ' bgm-item-surtido' : '';

            // Stock máximo disponible para limitar el botón "+"
            $stock_max = 0; // 0 = sin límite
            if ($product && $product->managing_stock() && ! $product->backorders_allowed()) {
                $stock_max = max(1, (int) $product->get_stock_quantity());
            }
            $plus_attrs = '';
            if ($stock_max > 0) {
                $plus_attrs .= ' data-max="' . esc_attr($stock_max) . '"';
                if ($qty >= $stock_max) $plus_attrs .= ' disabled aria-disabled="true"';
            }

            echo '<li class="bgmg-mc-item' . $surtido_class . '" data-key="' . esc_attr($key) . '">';
            echo   '<a href="' . $p_url . '"><img src="' . esc_url($img) . '" alt="' . esc_attr($name) . '" class="bgmg-mc-img"></a>';
            echo   '<div class="bgmg-mc-info">';
            echo     '<a href="' . $p_url . '" class="bgmg-mc-name">' . $name . '</a>' . $bgm_chip;
            echo     '<div class="bgmg-mc-price-row">' . $price_html . '</div>';
            echo     '<div class="bgmg-mc-controls">';
            echo       '<button class="bgmg-mc-qty-btn bgmg-mc-minus" data-key="' . esc_attr($key) . '"' . ($qty <= 1 ? ' disabled aria-disabled="true"' : '') . ' aria-label="Quitar uno">−</button>';
            echo       '<span class="bgmg-mc-qty-val">' . $qty . '</span>';
            echo       '<button class="bgmg-mc-qty-btn bgmg-mc-plus" data-key="' . esc_attr($key) . '"' . $plus_attrs . ' aria-label="Agregar uno">+</button>';
            echo     '</div>';
            echo   '</div>';
            echo   '<button type="button" class="bgmg-mc-rm" data-key="' . esc_attr($key) . '" aria-label="Eliminar">×</button>';
            echo '</li>';
        }
        echo '</ul>';

        // Ahorro total del carrito
        $reg_total_cart     = 0;
        foreach ($cart->get_cart() as $ci) {
            $p = $ci['data'];
            $reg_total_cart += (float) $p->get_regular_price() * $ci['quantity'];
        }
        $cart_total_ex_ship = (float) $cart->get_cart_contents_total() + (float) $cart->get_fee_total();
        $total_savings      = max(0, $reg_total_cart - $cart_total_ex_ship - (float) $cart->get_discount_total());

        if ($total_savings > 0) {
            echo '<div class="bgmg-mc-savings">🎉 Ahorrás ' . wc_price($total_savings) . '</div>';
        }

        echo '<div class="bgmg-mc-subtotal">';
        echo   '<span>Subtotal</span>';
        echo   '<strong>' . $cart->get_cart_subtotal() . '</strong>';
        echo '</div>';
        echo '<div class="bgmg-mc-actions">';
        echo   '<a href="' . esc_url(wc_get_cart_url()) . '" class="bgmg-mc-btn-secondary">Ver carrito</a>';
        echo   '<a href="' . esc_url(wc_get_checkout_url()) . '" class="bgmg-mc-btn-primary">Pagar →</a>';
        echo '</div>';
        echo '<button type="button" class="bgmg-mc-clear" aria-label="Vaciar carrito">Vaciar carrito</button>';
    }
    echo '</div>';
}

// Renderiza el header unificado — usado por todos los templates
function bgmg_render_header( $args = [] ) {
    $args = wp_parse_args( $args, [
        'show_nav'    => true,
        'show_search' => true,
        'show_cart'   => true,
    ] );

    $logo_id    = (int) get_theme_mod('custom_logo');
    $shop_url   = function_exists('wc_get_page_id') ? get_permalink( wc_get_page_id('shop') ) : home_url('/tienda/');
    $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    $parent_cats = [];
    if ( $args['show_nav'] ) {
        $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'orderby' => 'menu_order']);
        if ( ! is_wp_error($cats) ) {
            $parent_cats = array_filter( $cats, fn($t) => $t->slug !== 'uncategorized' );
        }
    }
    ?>
    <header class="bgmg-header">
      <div class="bgmg-header-inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="bgmg-logo-link">
          <?php if ( $logo_id ) :
            echo wp_get_attachment_image( $logo_id, 'medium', false, ['class' => 'bgmg-logo-img', 'loading' => 'eager'] );
          else : ?>
            <span class="bgmg-logo-text">Beauty<em>Girl</em>MG</span>
          <?php endif; ?>
        </a>

        <?php if ( $args['show_nav'] ) : ?>
        <nav>
          <ul class="bgmg-dnav">
            <li><a href="<?php echo esc_url(home_url('/')); ?>">Inicio</a></li>
            <li><a href="<?php echo esc_url($shop_url); ?>">Tienda</a></li>
            <?php if ( ! empty($parent_cats) ) : ?>
            <li>
              <a href="<?php echo esc_url($shop_url); ?>">Categorías <span>▾</span></a>
              <div class="bgmg-drop">
                <?php foreach ( $parent_cats as $pcat ) :
                  $kids = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $pcat->term_id]);
                  $kids = is_wp_error($kids) ? [] : $kids;
                ?>
                <div class="bgmg-drop-col">
                  <h4><a href="<?php echo esc_url(get_term_link($pcat)); ?>"><?php echo esc_html($pcat->name); ?></a></h4>
                  <?php if ( ! empty($kids) ) : ?>
                  <ul>
                    <?php foreach ( $kids as $kid ) : ?>
                    <li><a href="<?php echo esc_url(get_term_link($kid)); ?>"><?php echo esc_html($kid->name); ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            </li>
            <?php endif; ?>
          </ul>
        </nav>
        <?php endif; ?>

        <div class="bgmg-header-right">
          <?php if ( $args['show_search'] ) : ?>
          <button class="bgmg-search-btn" id="bgmg-search-btn" aria-label="Buscar">
            <svg width="26" height="26" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="17" y1="17" x2="22" y2="22"/></svg>
          </button>
          <?php endif; ?>
          <a href="<?php echo esc_url( wc_get_page_permalink('myaccount') ); ?>" class="bgmg-account-btn" aria-label="Mi cuenta">
            <?php if ( is_user_logged_in() ) : ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4Z"/></svg>
            <?php else : ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            <?php endif; ?>
          </a>
          <?php if ( $args['show_cart'] ) : ?>
          <button class="bgmg-cart-btn" id="bgmg-cart-btn" aria-label="Carrito">
            🛒<span class="bgmg-cart-count"><?php echo $cart_count > 0 ? $cart_count : ''; ?></span>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <?php if ( $args['show_search'] ) : ?>
    <div class="bgmg-search-overlay" id="bgmg-search-overlay">
      <div class="bgmg-search-inner">
        <div class="bgmg-search-results-wrap">
          <form class="bgmg-search-form" role="search" method="get" action="<?php echo esc_url($shop_url); ?>">
            <svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/></svg>
            <input type="search" name="s" id="bgmg-search-input" placeholder="Buscar productos..." autocomplete="off">
            <button type="submit" class="bgmg-search-submit" aria-label="Buscar">
              <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </form>
          <div class="bgmg-search-results" id="bgmg-search-results"></div>
        </div>
        <button class="bgmg-search-close" id="bgmg-search-close" aria-label="Cerrar">
          <svg width="26" height="26" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>
    <div class="bgmg-search-backdrop" id="bgmg-search-backdrop"></div>
    <?php endif;

    // Mini-cart panel (global — BL-01c): antes duplicado inline en los 7 templates.
    // Se rinde junto al header (donde vive #bgmg-cart-btn). El panel es position:fixed,
    // así que su posición en el DOM no afecta el layout; el JS lo engancha por id.
    if ( $args['show_cart'] ) {
        bgmg_render_minicart_panel();
    }
}

/**
 * Markup del panel del minicart. Global (BL-01c) — reemplaza las 7 copias inline.
 * El contenido interno (lista + footer + vaciar) lo rinde bgmg_minicart_inner().
 * CSS estructural en assets/bgmg-global.css; JS de cantidades/vaciar en el wp_footer global.
 */
function bgmg_render_minicart_panel() { ?>
<!-- Mini Cart Panel (global — BL-01c) -->
<div class="bgmg-mc-panel" id="bgmg-mc-panel" aria-label="Carrito">
  <div class="bgmg-mc-header">
    <span class="bgmg-mc-title">Tu carrito</span>
    <button class="bgmg-mc-close" id="bgmg-mc-close" aria-label="Cerrar">×</button>
  </div>
  <div class="bgmg-mc-body">
    <?php bgmg_minicart_inner(); ?>
  </div>
</div>
<div class="bgmg-mc-backdrop" id="bgmg-mc-backdrop"></div>
<?php }

// CSS global del tema (header + minicart + buscador + tab bar): extraido a
// assets/bgmg-global.css para que sea cacheable (antes iba inline en wp_head, se
// reenviaba en cada page load). Las variables de color (--pink, etc.) las aporta el
// :root de cada template; se consolidaran a un base.css al extraer los templates.
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'bgmg-global', plugin_dir_url( __FILE__ ) . 'assets/bgmg-global.css', array(), BGMG_LANDING_VERSION );
}, 99 );

// JS para controles +/− del minicart (inyectado en wp_footer — aplica a todas las templates)
add_action('wp_footer', function() { ?>
<script id="bgmg-mc-qty-js">
(function(){
  var panel = document.getElementById('bgmg-mc-panel');
  if (!panel) return;

  function getMcBody(){ return panel.querySelector('.bgmg-mc-body'); }

  function mcQtyAjax(key, qty){
    var body = getMcBody();
    if (body) body.classList.add('is-loading');

    var nonce = window.bgmgAjax ? window.bgmgAjax.cartNonce : '';
    var url   = window.bgmgAjax ? window.bgmgAjax.url : '/wp-admin/admin-ajax.php';
    var fd    = new FormData();
    fd.append('action', 'bgmg_update_cart');
    fd.append('nonce',  nonce);
    fd.append('key',    key);
    fd.append('qty',    qty);

    fetch(url, { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(resp){
        if (!resp.success){ if(body) body.classList.remove('is-loading'); return; }

        // Reemplazar inner del minicart con HTML fresco del servidor
        if (resp.data.minicart_html){
          var inner = document.getElementById('bgmg-minicart-inner');
          if (inner){
            var tmp = document.createElement('div');
            tmp.innerHTML = resp.data.minicart_html;
            inner.replaceWith(tmp.firstElementChild);
          }
        }

        // Actualizar contador del header
        document.querySelectorAll('.bgmg-cart-count').forEach(function(el){
          el.textContent = resp.data.count > 0 ? resp.data.count : '';
        });

        if(body) body.classList.remove('is-loading');
      })
      .catch(function(){ if(body) body.classList.remove('is-loading'); });
  }

  // Delegación en el panel (sobrevive re-renders del inner)
  panel.addEventListener('click', function(e){
    // ─── Eliminar item (×) con fade-out ─────────────────────────────
    var rmBtn = e.target.closest('.bgmg-mc-rm');
    if (rmBtn) {
      e.preventDefault();
      if (rmBtn.disabled) return;
      var key = rmBtn.dataset.key;
      if (!key) return;
      rmBtn.disabled = true;
      var item = panel.querySelector('.bgmg-mc-item[data-key="'+key+'"]');
      if (item) item.classList.add('is-removing');
      // Esperar a que termine la animación antes del AJAX
      setTimeout(function(){ mcQtyAjax(key, 0); }, 220);
      return;
    }

    // ─── Vaciar carrito completo ────────────────────────────────────
    var clearBtn = e.target.closest('.bgmg-mc-clear');
    if (clearBtn) {
      e.preventDefault();
      if (clearBtn.disabled) return;
      if (!window.confirm('¿Vaciar todo el carrito?')) return;

      clearBtn.disabled = true;
      var body = getMcBody();
      if (body) body.classList.add('is-loading');
      // Fade-out de todos los items
      panel.querySelectorAll('.bgmg-mc-item').forEach(function(it){ it.classList.add('is-removing'); });

      setTimeout(function(){
        var nonce = window.bgmgAjax ? window.bgmgAjax.cartNonce : '';
        var url   = window.bgmgAjax ? window.bgmgAjax.url : '/wp-admin/admin-ajax.php';
        var fd    = new FormData();
        fd.append('action', 'bgmg_clear_cart');
        fd.append('nonce',  nonce);
        fetch(url, { method:'POST', body:fd })
          .then(function(r){ return r.json(); })
          .then(function(resp){
            if (resp && resp.success && resp.data && resp.data.minicart_html) {
              var inner = document.getElementById('bgmg-minicart-inner');
              if (inner) {
                var tmp = document.createElement('div');
                tmp.innerHTML = resp.data.minicart_html;
                inner.replaceWith(tmp.firstElementChild);
              }
              document.querySelectorAll('.bgmg-cart-count').forEach(function(el){ el.textContent = ''; });
            }
            if (body) body.classList.remove('is-loading');
          })
          .catch(function(){ if (body) body.classList.remove('is-loading'); });
      }, 220);
      return;
    }

    // ─── Botones +/- de cantidad ────────────────────────────────────
    var btn = e.target.closest('.bgmg-mc-qty-btn');
    if (!btn || btn.disabled) return;

    var key   = btn.dataset.key;
    var item  = panel.querySelector('.bgmg-mc-item[data-key="'+key+'"]');
    var valEl = item ? item.querySelector('.bgmg-mc-qty-val') : null;
    if (!valEl) return;

    var current = parseInt(valEl.textContent, 10) || 1;
    var isPlus  = btn.classList.contains('bgmg-mc-plus');

    // Respetar stock máximo si el botón tiene data-max
    var max = parseInt(btn.dataset.max, 10);
    if (isPlus && !isNaN(max) && max > 0 && current >= max) return;

    var newQty = isPlus ? current + 1 : Math.max(1, current - 1);
    if (newQty === current) return;

    mcQtyAjax(key, newQty);
  });
})();
</script>
<?php }, 20);

// JS global del header: lupa (buscador) + abrir/cerrar minicart (BL-01c Fase 2).
// UNA sola copia para TODAS las páginas (antes inline en cada template + el 404).
// Corre en wp_footer SIN excluir checkout; engancha por id/delegación lo que
// bgmg_render_header() rinde, así funciona en cualquier página (incluido el 404).
add_action('wp_footer', function() { ?>
<script id="bgmg-header-ui-js">
(function(){
  // ── Buscador (lupa) ──────────────────────────────────────────
  var sBtn = document.getElementById('bgmg-search-btn');
  var sOv  = document.getElementById('bgmg-search-overlay');
  var sCls = document.getElementById('bgmg-search-close');
  var sBkd = document.getElementById('bgmg-search-backdrop');
  var sInp = document.getElementById('bgmg-search-input');
  function openSearch(){ if (sOv) sOv.classList.add('is-open'); if (sBkd) sBkd.classList.add('is-open'); setTimeout(function(){ if (sInp) sInp.focus(); }, 320); }
  function closeSearch(){ if (sOv) sOv.classList.remove('is-open'); if (sBkd) sBkd.classList.remove('is-open'); }
  if (sBtn) sBtn.addEventListener('click', openSearch);
  if (sCls) sCls.addEventListener('click', closeSearch);
  if (sBkd) sBkd.addEventListener('click', closeSearch);

  // Live search preview
  var sRes = document.getElementById('bgmg-search-results');
  var sTimer;
  if (sInp && sRes) {
    sInp.addEventListener('input', function(){
      var q = this.value.trim();
      clearTimeout(sTimer);
      if (q.length < 2) { sRes.classList.remove('is-visible'); return; }
      sRes.innerHTML = '<div class="bgmg-search-msg">Buscando...</div>';
      sRes.classList.add('is-visible');
      sTimer = setTimeout(function(){
        var url = (window.bgmgAjax ? window.bgmgAjax.url : '/wp-admin/admin-ajax.php')
          + '?action=bgmg_search&q=' + encodeURIComponent(q)
          + '&nonce=' + (window.bgmgAjax ? window.bgmgAjax.nonce : '');
        fetch(url).then(function(r){ return r.json(); }).then(function(data){
          if (!data.results || !data.results.length) { sRes.innerHTML = '<div class="bgmg-search-msg">Sin resultados para "' + q + '"</div>'; return; }
          var html = '';
          data.results.forEach(function(p){
            html += '<a href="' + p.url + '" class="bgmg-search-result-item">';
            html += '<img class="bgmg-search-result-img" src="' + p.img + '" alt="' + p.name + '" loading="lazy">';
            html += '<div><div class="bgmg-search-result-name">' + p.name + '</div>';
            html += '<div class="bgmg-search-result-price">' + p.price + '</div></div></a>';
          });
          html += '<a href="' + data.search_url + '" class="bgmg-search-view-all">Ver todos los resultados (' + data.total + ') →</a>';
          sRes.innerHTML = html;
        }).catch(function(){ sRes.classList.remove('is-visible'); });
      }, 350);
    });
    document.addEventListener('click', function(e){ if (sRes && sOv && !sOv.contains(e.target)) sRes.classList.remove('is-visible'); });
  }

  // ── Minicart: abrir/cerrar (global, todas las páginas incl. checkout) ──
  var mcPanel = document.getElementById('bgmg-mc-panel');
  var mcBkd   = document.getElementById('bgmg-mc-backdrop');
  function openCart(){ if (mcPanel) mcPanel.classList.add('is-open'); if (mcBkd) mcBkd.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
  function closeCart(){ if (mcPanel) mcPanel.classList.remove('is-open'); if (mcBkd) mcBkd.classList.remove('is-open'); document.body.style.overflow = ''; }

  // Abrir desde el botón del header (#bgmg-cart-btn) y el de la tab bar
  // (#bgmg-tab-cart). Delegación en document → no depende del orden de render.
  document.addEventListener('click', function(e){
    if (e.target.closest('#bgmg-cart-btn') || e.target.closest('#bgmg-tab-cart')) { e.preventDefault(); openCart(); return; }
    if (e.target.closest('#bgmg-mc-close')) { closeCart(); return; }
  });
  if (mcBkd) mcBkd.addEventListener('click', closeCart);

  // Escape cierra buscador y minicart.
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { closeSearch(); closeCart(); } });

  // Abrir el minicart tras "añadir al carrito" + refrescar fragmentos WC al cargar.
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('added_to_cart', function(){ openCart(); });
    jQuery(function($){ $(document.body).trigger('wc_fragment_refresh'); });
  }

  // ── "Añadir al carrito" del producto por AJAX (no recarga + abre el side-cart) ──
  // Intercepta el form.cart estándar de WC (producto simple o variable). El surtido
  // del mayorista usa otra ruta (no es form.cart con .single_add_to_cart_button), así
  // que no se afecta. Ante cualquier falla, cae al submit normal de WC (sin perder nada).
  document.addEventListener('submit', function(e){
    var form = e.target;
    if (!form || !form.classList || !form.classList.contains('cart')) return;
    if (form.classList.contains('grouped_form') || form.classList.contains('cart_group')) return;
    if (!window.bgmgAjax) return; // sin nonce, dejamos el submit normal
    var btn = form.querySelector('.single_add_to_cart_button');
    if (!btn) return; // no es el add-to-cart estándar (ej. surtido) → no tocar
    // Botón deshabilitado (falta elegir variación / sin stock): que WC muestre su aviso.
    if (btn.classList.contains('disabled') || btn.classList.contains('wc-variation-selection-needed')) return;

    e.preventDefault();
    if (btn.classList.contains('loading')) return;
    btn.classList.add('loading');

    var pidEl = form.querySelector('[name="add-to-cart"]');
    var qtyEl = form.querySelector('[name="quantity"]');
    var vidEl = form.querySelector('[name="variation_id"]');

    var fd = new FormData();
    fd.append('action', 'bgmg_add_to_cart');
    fd.append('nonce', window.bgmgAjax.cartNonce || '');
    fd.append('product_id', (pidEl && pidEl.value) || btn.value || '');
    fd.append('quantity', qtyEl ? qtyEl.value : 1);
    fd.append('variation_id', vidEl ? vidEl.value : 0);
    form.querySelectorAll('[name^="attribute_"]').forEach(function(el){
      fd.append('variation[' + el.name + ']', el.value);
    });

    fetch(window.bgmgAjax.url, { method:'POST', body:fd, credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(resp){
        if (!resp || !resp.success) { form.submit(); return; } // fallback nativo de WC
        btn.classList.remove('loading');
        btn.classList.add('added');
        if (resp.data.minicart_html) {
          var inner = document.getElementById('bgmg-minicart-inner');
          if (inner) {
            var tmp = document.createElement('div');
            tmp.innerHTML = resp.data.minicart_html;
            if (tmp.firstElementChild) inner.replaceWith(tmp.firstElementChild);
          }
        }
        document.querySelectorAll('.bgmg-cart-count').forEach(function(el){
          el.textContent = resp.data.count > 0 ? resp.data.count : '';
        });
        openCart();
      })
      .catch(function(){ form.submit(); });
  });
})();
</script>
<?php }, 20);

// Rate-limiting básico para endpoints AJAX públicos (nopriv).
// Devuelve true si la IP excedió el límite (en cuyo caso el endpoint debería abortar).
// Usa transients (un slot por IP+endpoint con TTL corto).
function bgmg_rate_limit_exceeded( $endpoint_key, $max_per_minute = 30 ) {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
    $ip = preg_replace( '/[^0-9a-fA-F:.]/', '', $ip ); // sanitiza IP
    $key = 'bgmg_rl_' . md5( $endpoint_key . '|' . $ip );
    $count = (int) get_transient( $key );
    if ( $count >= $max_per_minute ) return true;
    set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
    return false;
}

// AJAX live search de productos + categorías
add_action('wp_ajax_bgmg_search',        'bgmg_ajax_search');
add_action('wp_ajax_nopriv_bgmg_search', 'bgmg_ajax_search');
function bgmg_ajax_search() {
    check_ajax_referer('bgmg_search', 'nonce');
    if ( bgmg_rate_limit_exceeded( 'search', 30 ) ) {
        wp_send_json_error( array( 'message' => 'Demasiadas búsquedas. Espera un momento.' ), 429 );
    }
    $q = sanitize_text_field(isset($_GET['q']) ? wp_unslash($_GET['q']) : '');
    if (strlen($q) < 2) { wp_send_json(array('results' => array(), 'total' => 0)); }

    // 1. Categorías que coincidan con la búsqueda (van primero)
    $cat_results    = array();
    $matching_cats  = get_terms(array(
        'taxonomy'   => 'product_cat',
        'search'     => $q,
        'hide_empty' => true,
        'number'     => 3,
        'exclude'    => array(get_option('default_product_cat')),
    ));
    if (!is_wp_error($matching_cats)) {
        foreach ($matching_cats as $cat) {
            $thumb_id = get_term_meta($cat->term_id, 'thumbnail_id', true);
            $img      = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';
            $cat_results[] = array(
                'name'   => $cat->name,
                'price'  => $cat->count . ' producto' . ($cat->count !== 1 ? 's' : ''),
                'img'    => $img ?: wc_placeholder_img_src(),
                'url'    => get_term_link($cat),
                'is_cat' => true,
            );
        }
    }

    // 2. Productos que coincidan por título/contenido
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 4,
        's'              => $q,
    );
    $query = new WP_Query($args);

    $prod_results = array();
    while ($query->have_posts()) {
        $query->the_post();
        $id      = get_the_ID();
        $product = wc_get_product($id);
        if (!$product) continue;
        $prod_results[] = array(
            'name'  => get_the_title(),
            'price' => wp_strip_all_tags($product->get_price_html()),
            'img'   => get_the_post_thumbnail_url($id, 'thumbnail') ?: wc_placeholder_img_src(),
            'url'   => get_permalink($id),
        );
    }
    wp_reset_postdata();

    $results    = array_merge($cat_results, $prod_results);
    $search_url = add_query_arg(array('s' => $q, 'post_type' => 'product'), home_url('/'));
    wp_send_json(array('results' => $results, 'total' => $query->found_posts, 'search_url' => $search_url));
}

// Registrar ubicación de menú del header
add_action('init', function() {
    register_nav_menu('bgmg-header', 'BeautyGirlMG — Header');
});

// Fallback si aún no se asigna un menú
function bgmg_nav_fallback() {
    $shop = function_exists('wc_get_page_id') ? get_permalink(wc_get_page_id('shop')) : '#';
    echo '<ul class="bgmg-nav" id="bgmg-nav">';
    echo '<li><a href="' . esc_url(home_url('/')) . '">Inicio</a></li>';
    echo '<li><a href="' . esc_url($shop) . '">Tienda</a></li>';
    echo '</ul>';
}

// Registrar el template
add_filter('theme_page_templates', function($templates) {
    $templates['bgmg-template.php'] = 'BGMG Landing';
    return $templates;
});

// Servir templates desde el directorio del plugin (prioridad 99 para correr después de WooCommerce)
add_filter('template_include', function($template) {
    if (is_page() && get_page_template_slug() === 'bgmg-template.php') {
        $t = plugin_dir_path(__FILE__) . 'bgmg-template.php';
        if (file_exists($t)) return $t;
    }
    if (is_shop()) {
        $t = plugin_dir_path(__FILE__) . 'bgmg-shop.php';
        if (file_exists($t)) return $t;
    }
    if (is_product_category() || is_product_tag()) {
        $t = plugin_dir_path(__FILE__) . 'bgmg-category.php';
        if (file_exists($t)) return $t;
    }
    if (is_cart()) {
        $t = plugin_dir_path(__FILE__) . 'bgmg-cart.php';
        if (file_exists($t)) return $t;
    }
    if (is_checkout()) {
        $t = plugin_dir_path(__FILE__) . 'bgmg-checkout.php';
        if (file_exists($t)) return $t;
    }
    if (is_product()) {
        $t = plugin_dir_path(__FILE__) . 'bgmg-product.php';
        if (file_exists($t)) return $t;
    }
    if (is_account_page()) {
        $t = plugin_dir_path(__FILE__) . 'bgmg-account.php';
        if (file_exists($t)) return $t;
    }
    if (is_404()) {
        $t = plugin_dir_path(__FILE__) . 'bgmg-404.php';
        if (file_exists($t)) return $t;
    }
    return $template;
}, 99);

// AJAX vaciar el carrito completo
add_action('wp_ajax_bgmg_clear_cart',        'bgmg_clear_cart');
add_action('wp_ajax_nopriv_bgmg_clear_cart', 'bgmg_clear_cart');
function bgmg_clear_cart() {
    check_ajax_referer('bgmg_cart', 'nonce');
    $cart = WC()->cart;
    if ($cart) {
        $cart->empty_cart();
    }
    ob_start();
    bgmg_minicart_inner();
    $minicart_html = ob_get_clean();
    wp_send_json_success(array(
        'count'         => 0,
        'minicart_html' => $minicart_html,
    ));
}

// AJAX agregar al carrito (producto simple o variable) sin recargar la página.
// Lo usa la intercepción del form.cart en bgmg-header-ui-js. El endpoint nativo de
// WC (wc-ajax=add_to_cart) NO maneja productos variables; este sí, pasando
// variation_id + atributos. NO interfiere con el surtido del mayorista (otra ruta).
add_action('wp_ajax_bgmg_add_to_cart',        'bgmg_add_to_cart');
add_action('wp_ajax_nopriv_bgmg_add_to_cart', 'bgmg_add_to_cart');
function bgmg_add_to_cart() {
    check_ajax_referer('bgmg_cart', 'nonce');
    if ( bgmg_rate_limit_exceeded('add_to_cart', 30) ) {
        wp_send_json_error(array('message' => 'Demasiadas solicitudes. Espera un momento.'), 429);
    }

    $product_id   = absint($_POST['product_id'] ?? 0);
    $quantity     = max(1, absint($_POST['quantity'] ?? 1));
    $variation_id = absint($_POST['variation_id'] ?? 0);

    $variation = array();
    if ( ! empty($_POST['variation']) && is_array($_POST['variation']) ) {
        foreach ( wp_unslash($_POST['variation']) as $k => $v ) {
            if ( is_scalar($v) ) {
                $variation[ sanitize_text_field($k) ] = sanitize_text_field($v);
            }
        }
    }

    if ( ! $product_id || ! WC()->cart ) {
        wp_send_json_error(array('message' => 'Producto inválido.'));
    }

    // add_to_cart corre las validaciones de WC (stock, variación válida). Si falla,
    // deja un "notice" de error; lo capturamos para devolver un mensaje claro.
    $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);

    if ( ! $added ) {
        $msg     = 'No se pudo agregar al carrito.';
        $notices = function_exists('wc_get_notices') ? wc_get_notices('error') : array();
        if ( ! empty($notices) ) {
            $first = $notices[0];
            $msg   = wp_strip_all_tags( is_array($first) ? ( $first['notice'] ?? $msg ) : $first );
        }
        if ( function_exists('wc_clear_notices') ) wc_clear_notices();
        wp_send_json_error(array('message' => $msg));
    }

    // Limpiamos el notice de éxito de WC (la apertura del side-cart ya es el feedback).
    if ( function_exists('wc_clear_notices') ) wc_clear_notices();

    ob_start();
    bgmg_minicart_inner();
    $minicart_html = ob_get_clean();

    wp_send_json_success(array(
        'count'         => WC()->cart->get_cart_contents_count(),
        'minicart_html' => $minicart_html,
    ));
}

// AJAX actualizar cantidad / eliminar item del carrito
add_action('wp_ajax_bgmg_update_cart',        'bgmg_update_cart');
add_action('wp_ajax_nopriv_bgmg_update_cart', 'bgmg_update_cart');
function bgmg_update_cart() {
    check_ajax_referer('bgmg_cart', 'nonce');
    $key = sanitize_text_field($_POST['key'] ?? '');
    $qty = max(0, intval($_POST['qty'] ?? 0));
    $cart = WC()->cart;

    if (!$key || !isset($cart->get_cart()[$key])) {
        wp_send_json_error('invalid_key');
    }

    if ($qty === 0) {
        $cart->remove_cart_item($key);
    } else {
        // Validar contra stock disponible y capear si excede.
        // set_quantity por sí solo no valida stock; lo hacemos manualmente.
        $cart_items = $cart->get_cart();
        $product    = isset($cart_items[$key]['data']) ? $cart_items[$key]['data'] : null;

        $stock_max = PHP_INT_MAX;
        if ($product && $product->managing_stock() && ! $product->backorders_allowed()) {
            $stock_max = max(1, (int) $product->get_stock_quantity());
        }

        $qty_final = min(max(1, $qty), $stock_max);
        $cart->set_quantity($key, $qty_final, true);
    }

    $cart->calculate_totals();

    // Calcular datos por ítem para actualizar cards en tiempo real
    $total_fee_discount  = 0;
    $total_line_subtotal = 0;
    foreach ($cart->get_fees() as $fee) {
        if ($fee->amount < 0) $total_fee_discount += abs($fee->amount);
    }
    foreach ($cart->get_cart() as $ci) {
        $total_line_subtotal += (float) $ci['line_subtotal'];
    }
    $items_data = array();
    foreach ($cart->get_cart() as $k => $ci) {
        $p              = $ci['data'];
        $iqty           = $ci['quantity'];
        $i_subtotal     = (float) $ci['line_subtotal'];
        $i_total        = (float) $ci['line_total'];
        $i_reg_total    = (float) $p->get_regular_price() * $iqty;
        $i_fee_share    = ($total_line_subtotal > 0) ? round($total_fee_discount * ($i_subtotal / $total_line_subtotal), 2) : 0;
        $i_saving       = max(0, $i_reg_total - $i_total + $i_fee_share);
        $i_unit_reg     = (float) $p->get_regular_price();
        $i_unit_actual  = $iqty > 0 ? round(($i_reg_total - $i_saving) / $iqty, 2) : $i_unit_reg;
        $i_pct          = ($i_reg_total > 0 && $i_saving > 0) ? round(($i_saving / $i_reg_total) * 100) : 0;
        if ($i_saving > 0 && $i_unit_reg > $i_unit_actual) {
            $price_html = '<del style="font-size:12px;color:#7A5060;font-weight:300;">' . wc_price($i_unit_reg) . '</del>'
                        . '<span style="color:#C4728A;font-weight:600;">' . wc_price($i_unit_actual) . '</span>';
        } else {
            $price_html = $p->get_price_html();
        }
        $items_data[$k] = array(
            'price_html' => $price_html,
            'badge_pct'  => $i_pct,
        );
    }

    // Calcular ahorro total (cupones + precios rebajados)
    $regular_total = 0;
    foreach ($cart->get_cart() as $ci) {
        $p = $ci['data'];
        $regular_total += (float) $p->get_regular_price() * $ci['quantity'];
    }
    $cart_total_ex_shipping = (float) $cart->get_cart_contents_total() + (float) $cart->get_fee_total();
    $savings = $regular_total - $cart_total_ex_shipping - (float) $cart->get_discount_total();
    $savings = max(0, $savings);

    $coupon_rows = '';
    foreach ($cart->get_applied_coupons() as $code) {
        $disc = wc_price($cart->get_coupon_discount_amount($code, $cart->display_prices_including_tax()));
        // nonce contra CSRF para evitar que un atacante haga al cliente
        // remover un cupón sin que se entere.
        $rm   = esc_url( wp_nonce_url(
            add_query_arg( 'remove_coupon', rawurlencode( $code ), wc_get_cart_url() ),
            'woocommerce-remove-coupon_' . $code
        ) );
        $coupon_rows .= '<div class="bgmg-cart-row is-discount">'
            . '<span>🏷️ Cupón <strong>' . esc_html(strtoupper($code)) . '</strong> <a href="' . $rm . '" class="bgmg-rm-coupon" title="Quitar">×</a></span>'
            . '<span>−' . $disc . '</span>'
            . '</div>';
    }
    foreach ($cart->get_fees() as $fee) {
        if ($fee->amount >= 0) continue;
        $coupon_rows .= '<div class="bgmg-cart-row is-discount">'
            . '<span>🎁 ' . esc_html($fee->name) . '</span>'
            . '<span>' . wc_price($fee->amount) . '</span>'
            . '</div>';
    }

    // HTML fresco del minicart (para actualizarlo sin fragment refresh adicional)
    ob_start();
    bgmg_minicart_inner();
    $minicart_html = ob_get_clean();

    wp_send_json_success(array(
        'subtotal'      => $cart->get_cart_subtotal(),
        'total'         => $cart->get_cart_total(),
        'count'         => $cart->get_cart_contents_count(),
        'empty'         => $cart->is_empty(),
        'coupon_rows'   => $coupon_rows,
        'savings'       => $savings > 0 ? wc_price($savings) : '',
        'items_data'    => $items_data,
        'minicart_html' => $minicart_html,
    ));
}

// AJAX load more productos para la tienda
add_action('wp_ajax_bgmg_load_products',        'bgmg_load_products');
add_action('wp_ajax_nopriv_bgmg_load_products', 'bgmg_load_products');
function bgmg_load_products() {
    check_ajax_referer('bgmg_shop', 'nonce');
    if ( bgmg_rate_limit_exceeded( 'load_products', 60 ) ) {
        wp_send_json_error( array( 'message' => 'Demasiadas peticiones. Espera un momento.' ), 429 );
    }
    $page      = max(1, intval($_POST['page']      ?? 1));
    $cat       = intval($_POST['cat']              ?? 0);
    $min_price = floatval($_POST['min_price']      ?? 0);
    $max_price = floatval($_POST['max_price']      ?? 99999999);
    $orderby   = sanitize_text_field($_POST['orderby'] ?? 'date');
    $search    = sanitize_text_field($_POST['search']  ?? '');

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        'paged'          => $page,
        'meta_query'     => array(array(
            'key'     => '_price',
            'value'   => array($min_price, $max_price),
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        )),
    );
    if ($search !== '') { $args['s'] = $search; }
    if ($cat > 0) {
        $args['tax_query'] = array(array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $cat,
            'include_children' => true,
        ));
    }
    switch ($orderby) {
        case 'price':
            $args['meta_key'] = '_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC'; break;
        case 'price-desc':
            $args['meta_key'] = '_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break;
        case 'popularity':
            $args['meta_key'] = 'total_sales'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; break;
        default:
            $args['orderby'] = 'date'; $args['order'] = 'DESC';
    }

    $query    = new WP_Query($args);
    $html     = '';
    $has_more = $query->max_num_pages > $page;
    while ($query->have_posts()) {
        $query->the_post();
        $html .= bgmg_product_card_html(get_the_ID());
    }
    wp_reset_postdata();
    wp_send_json(array('html' => $html, 'has_more' => $has_more));
}

function bgmg_product_card_html($p_id) {
    $prod = wc_get_product($p_id);
    if (!$prod) return '';
    $name    = esc_html(get_the_title($p_id));
    $img     = get_the_post_thumbnail_url($p_id, 'thumbnail') ?: wc_placeholder_img_src();
    $p_terms = get_the_terms($p_id, 'product_cat');
    $cat     = ($p_terms && !is_wp_error($p_terms)) ? esc_html($p_terms[0]->name) : '';
    $p_url   = esc_url(get_permalink($p_id));
    $badge   = $prod->is_on_sale()
        ? '<span class="bgmg-badge-oferta">🔥 Oferta</span>'
        : ($cat ? '<span class="bgmg-badge">' . $cat . '</span>' : '');
    $h  = '<div class="bgmg-card">';
    $h .= '<a href="' . $p_url . '" class="bgmg-card-link">';
    $h .= '<img class="bgmg-card-img" src="' . esc_url($img) . '" alt="' . $name . '" loading="lazy">';
    $h .= '<div class="bgmg-card-body">' . $badge;
    $h .= '<div class="bgmg-card-name">' . $name . '</div>';
    $promo_badge = function_exists( 'bgm_promo_badge_html' ) ? bgm_promo_badge_html( $prod ) : '';
    $h .= '<div class="bgmg-card-price">' . $promo_badge . $prod->get_price_html() . '</div>';
    $h .= '</div></a>';
    $h .= '<a href="' . esc_url($prod->add_to_cart_url()) . '" class="bgmg-btn-add add_to_cart_button ajax_add_to_cart"';
    $h .= ' data-product_id="' . esc_attr($p_id) . '" data-product_type="' . esc_attr($prod->get_type()) . '" data-quantity="1" rel="nofollow">+</a>';
    $h .= '</div>';
    return $h;
}

// ── FOOTER ─────────────────────────────────────────────────────────────────
function bgmg_footer() {
    $logo_id  = get_theme_mod('custom_logo');
    $shop_url = get_permalink(wc_get_page_id('shop'));
    ?>
    <footer class="bgmg-footer">
        <div class="bgmg-footer-inner">

            <!-- Logo + tagline -->
            <div class="bgmg-footer-brand">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="bgmg-footer-logo-link">
                    <?php if ($logo_id) :
                        echo wp_get_attachment_image($logo_id, 'medium', false, array('class' => 'bgmg-footer-logo-img'));
                    else : ?>
                        <span class="bgmg-footer-logo-text">Beauty<em>Girl</em>MG</span>
                    <?php endif; ?>
                </a>
                <p class="bgmg-footer-tagline">Mayorista y detalle &middot; Cuidado personal, cabello, maquillaje y m&aacute;s</p>
            </div>

            <!-- Contacto + redes -->
            <div class="bgmg-footer-contact">
                <a href="https://goo.gl/maps/pXjfR3TuwAzR69N77" target="_blank" rel="noopener noreferrer" class="bgmg-footer-info-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                    Antonia L&oacute;pez de Bello 461, Recoleta
                </a>
                <a href="mailto:contacto@beautygirlmg.cl" class="bgmg-footer-info-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
                    contacto@beautygirlmg.cl
                </a>
                <a href="tel:+56945362142" class="bgmg-footer-info-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.56a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    +56 9 4536 2142
                </a>

                <!-- Redes sociales -->
                <div class="bgmg-footer-socials">
                    <a href="https://www.instagram.com/beautygirl_mg/" target="_blank" rel="noopener noreferrer" class="bgmg-footer-social" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor" stroke="none"/></svg>
                    </a>
                    <a href="https://www.tiktok.com/@beautygirlmg" target="_blank" rel="noopener noreferrer" class="bgmg-footer-social" aria-label="TikTok">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.76a4.85 4.85 0 0 1-1.01-.07z"/></svg>
                    </a>
                    <a href="https://wa.me/56945362142" target="_blank" rel="noopener noreferrer" class="bgmg-footer-social" aria-label="WhatsApp">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Links -->
            <div class="bgmg-footer-links">
                <a href="<?php echo esc_url($shop_url); ?>">Tienda</a>
                <a href="<?php echo esc_url(home_url('/preguntas-frecuentes/')); ?>">FAQ</a>
                <a href="<?php echo esc_url(home_url('/politica-de-envios/')); ?>">Env&iacute;os</a>
                <a href="<?php echo esc_url(home_url('/politica-de-devoluciones/')); ?>">Devoluciones</a>
                <a href="<?php echo esc_url(home_url('/terminos-y-condiciones/')); ?>">T&eacute;rminos</a>
            </div>

        </div>

        <div class="bgmg-footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> BeautyGirl MG &mdash; Todos los derechos reservados</span>
            <span class="bgmg-footer-credit">Desarrollado por <a href="https://garciawebstudio.com" target="_blank" rel="noopener noreferrer">GarciaWebStudio.com</a></span>
        </div>
    </footer>
    <?php
}

// CSS del footer: extraido a assets/bgmg-footer.css para que sea cacheable por el
// navegador/CDN (antes iba inline en wp_head -> se reenviaba en cada page load).
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'bgmg-footer', plugin_dir_url( __FILE__ ) . 'assets/bgmg-footer.css', array(), BGMG_LANDING_VERSION );
}, 99 );

// Tab bar + Bottom sheet de categorías
add_action('wp_footer', function() {
    if (is_checkout()) return;

    $shop_url   = get_permalink(wc_get_page_id('shop'));
    $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    $cat_emojis = array(
        'maquillaje' => '💄', 'skin-care' => '🧴', 'skincare' => '🧴',
        'corporal'   => '🛁', 'cabello'   => '💆', 'unas'     => '💅',
        'kits'       => '🎁', 'accesorios'=> '👜', 'perfumes' => '🌹',
    );
    $cats = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'exclude'    => array(get_option('default_product_cat')),
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 12,
    ));
    $cats = is_wp_error($cats) ? array() : $cats;

    $active = is_front_page() ? 'home' : (is_shop() || is_product_category() || is_product() ? 'shop' : (is_cart() ? 'cart' : ''));
    ?>

<nav class="bgmg-tabbar" id="bgmg-tabbar" aria-label="Navegación principal">

  <a href="<?php echo esc_url(home_url('/')); ?>" class="bgmg-tab<?php echo $active==='home'?' is-active':''; ?>">
    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    <span class="bgmg-tab-label">Inicio</span>
  </a>

  <button class="bgmg-tab" id="bgmg-tab-cats" aria-label="Categorías">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    <span class="bgmg-tab-label">Categorías</span>
  </button>

  <a href="<?php echo esc_url($shop_url); ?>" class="bgmg-tab<?php echo $active==='shop'?' is-active':''; ?>">
    <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    <span class="bgmg-tab-label">Tienda</span>
  </a>

  <button class="bgmg-tab<?php echo $active==='cart'?' is-active':''; ?>" id="bgmg-tab-cart" aria-label="Carrito">
    <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <span class="bgmg-tab-count bgmg-cart-count"><?php echo $cart_count > 0 ? $cart_count : ''; ?></span>
    <span class="bgmg-tab-label">Carrito</span>
  </button>

  <a href="https://wa.me/56945362142" target="_blank" rel="noopener noreferrer" class="bgmg-tab bgmg-tab-wa" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" class="bgmg-tab-wa-icon"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
    <span class="bgmg-tab-label">WhatsApp</span>
  </a>

</nav>

<div class="bgmg-catsheet-back" id="bgmg-catsheet-back"></div>
<div class="bgmg-catsheet" id="bgmg-catsheet" role="dialog" aria-label="Categorías">
  <div class="bgmg-catsheet-handle"></div>
  <div class="bgmg-catsheet-head">
    <span class="bgmg-catsheet-title">Categorías</span>
    <button class="bgmg-catsheet-close" id="bgmg-catsheet-close" aria-label="Cerrar">×</button>
  </div>
  <div class="bgmg-catsheet-grid">
    <?php foreach ($cats as $cat) :
      $emoji = isset($cat_emojis[$cat->slug]) ? $cat_emojis[$cat->slug] : '🌸';
    ?>
    <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="bgmg-catsheet-item">
      <span class="bgmg-catsheet-emoji"><?php echo esc_html( $emoji ); ?></span>
      <span class="bgmg-catsheet-name"><?php echo esc_html($cat->name); ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <a href="<?php echo esc_url($shop_url); ?>" class="bgmg-catsheet-all">Ver todos los productos →</a>
</div>

<script id="bgmg-tabbar-js">
(function(){

  // ── Bottom sheet categorías ───────────────────────────────────
  var sheet    = document.getElementById('bgmg-catsheet');
  var sheetBk  = document.getElementById('bgmg-catsheet-back');
  var sheetCls = document.getElementById('bgmg-catsheet-close');
  var tabCats  = document.getElementById('bgmg-tab-cats');

  function openSheet(){
    sheet.classList.add('is-open');
    sheetBk.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }
  function closeSheet(){
    sheet.classList.remove('is-open');
    sheetBk.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  if (tabCats)  tabCats.addEventListener('click', function(){ sheet.classList.contains('is-open') ? closeSheet() : openSheet(); });
  if (sheetBk)  sheetBk.addEventListener('click', closeSheet);
  if (sheetCls) sheetCls.addEventListener('click', closeSheet);

  // Cerrar el sheet con Escape. (El minicart abrir/cerrar + el botón de carrito
  // de la tab bar #bgmg-tab-cart los maneja el bloque global bgmg-header-ui-js.)
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeSheet();
  });
})();
</script>
<?php }, 30);

// Al activar: crear página, asignar template, establecer como inicio
register_activation_hook(__FILE__, function() {
    $slug = 'bgmg-inicio';
    $existing = get_page_by_path($slug);

    if ($existing) {
        $id = $existing->ID;
    } else {
        $id = wp_insert_post([
            'post_title'   => 'Inicio',
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ]);
    }

    if ($id && !is_wp_error($id)) {
        update_post_meta($id, '_wp_page_template', 'bgmg-template.php');
        update_option('show_on_front', 'page');
        update_option('page_on_front', $id);
    }
});
