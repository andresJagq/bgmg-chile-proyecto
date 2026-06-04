<?php defined('ABSPATH') || exit; ?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tu carrito — BeautyGirlMG</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alice&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style>
/* ── RESET + BASE ───────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --pink:      #F2C4CE;
  --pink-soft: #FBF0F2;
  --pink-dark: #C4728A;
  --cream:     #FDF7F4;
  --dark:      #1A1015;
  --mid:       #7A5060;
  --border:    #f0e0e5;
}
html { scroll-behavior: smooth; }
body { font-family: 'Poppins', sans-serif; background: var(--cream); color: var(--dark); }

/* ── MINI CART ── CSS estructural movido a assets/bgmg-global.css (BL-01c). ── */

/* ── STICKY CTA ─────────────────────────────────────────────── */
.bgmg-sticky { position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999; background: #fff; border-top: 1px solid var(--border); padding: 10px 16px 14px; display: flex; gap: 10px; box-shadow: 0 -4px 20px rgba(0,0,0,.06); }
.bgmg-sticky-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 13px 10px; border-radius: 30px; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; text-decoration: none; transition: opacity .2s; }
.bgmg-sticky-btn:hover { opacity: .85; }
.bgmg-sticky-wa { background: #25D366; color: #fff; }
.bgmg-sticky-shop { background: var(--dark); color: #fff; }

/* ── CART PAGE ──────────────────────────────────────────────── */
.bgmg-cart-wrap { padding: 88px 20px 120px; max-width: 1100px; margin: 0 auto; }
.bgmg-cart-title { font-family: 'Alice', serif; font-size: 32px; font-weight: 400; color: var(--dark); margin-bottom: 24px; }

/* Empty state */
.bgmg-cart-empty { text-align: center; padding: 60px 20px; }
.bgmg-cart-empty-icon { font-size: 64px; display: block; margin-bottom: 16px; }
.bgmg-cart-empty h2 { font-family: 'Alice', serif; font-size: 28px; font-weight: 400; color: var(--dark); margin-bottom: 10px; }
.bgmg-cart-empty p { font-family: 'Poppins', sans-serif; font-size: 15px; color: var(--mid); margin-bottom: 28px; }
.bgmg-cart-empty-btn { display: inline-block; background: var(--dark); color: #fff; padding: 14px 32px; border-radius: 30px; text-decoration: none; font-family: 'Poppins', sans-serif; font-size: 15px; font-weight: 500; transition: opacity .2s; }
.bgmg-cart-empty-btn:hover { opacity: .85; }

/* Layout */
.bgmg-cart-layout { display: flex; flex-direction: column; gap: 20px; }

/* Items */
.bgmg-cart-items { display: flex; flex-direction: column; gap: 10px; }
.bgmg-cart-item {
  display: flex; align-items: center; gap: 12px; padding: 14px;
  background: #fff; border: 1px solid var(--border); border-radius: 16px;
  transition: opacity .3s, transform .3s;
}
.bgmg-cart-item.is-removing { opacity: 0; transform: translateX(20px); }
.bgmg-cart-item-img { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
.bgmg-cart-item-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.bgmg-cart-item-cat { font-family: 'Poppins', sans-serif; font-size: 10px; font-weight: 500; color: var(--pink-dark); background: var(--pink-soft); padding: 2px 8px; border-radius: 20px; align-self: flex-start; }
.bgmg-cart-item-name { font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--dark); text-decoration: none; line-height: 1.35; }
.bgmg-cart-item-name:hover { color: var(--pink-dark); }
.bgmg-cart-item-price { font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; color: var(--dark); }
.bgmg-item-discount-badge {
  display: inline-flex; align-items: center; gap: 3px;
  background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;
  font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 600;
  padding: 2px 8px; border-radius: 20px; align-self: flex-start;
}
.bgmg-cart-item-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
.bgmg-qty-wrap { display: flex; align-items: center; gap: 0; border: 1.5px solid var(--border); border-radius: 30px; overflow: hidden; }
.bgmg-qty-btn { width: 32px; height: 32px; border: none; background: #fff; color: var(--dark); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s, opacity .15s; flex-shrink: 0; }
.bgmg-qty-btn:hover:not(:disabled) { background: var(--pink-soft); }
.bgmg-qty-btn:disabled { opacity: .35; cursor: not-allowed; }
.bgmg-qty-input { width: 36px; text-align: center; border: none; border-left: 1px solid var(--border); border-right: 1px solid var(--border); font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--dark); background: #fff; padding: 6px 0; -moz-appearance: textfield; }
.bgmg-qty-input::-webkit-outer-spin-button,
.bgmg-qty-input::-webkit-inner-spin-button { -webkit-appearance: none; }
.bgmg-cart-rm { width: 30px; height: 30px; border-radius: 50%; background: var(--pink-soft); color: var(--mid); display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 16px; border: none; cursor: pointer; transition: background .2s, color .2s; line-height: 1; }
.bgmg-cart-rm:hover { background: var(--pink-dark); color: #fff; }

/* Summary */
.bgmg-cart-summary { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 24px; }
.bgmg-cart-summary-title { font-family: 'Alice', serif; font-size: 22px; font-weight: 600; color: var(--dark); margin-bottom: 20px; }
.bgmg-cart-row { display: flex; justify-content: space-between; align-items: center; font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--mid); padding: 8px 0; }
.bgmg-cart-row.is-total { border-top: 1px solid var(--border); margin-top: 8px; padding-top: 16px; font-size: 17px; font-weight: 500; color: var(--dark); }
.bgmg-cart-row.is-total strong { font-size: 20px; color: var(--pink-dark); }
.bgmg-cart-row.is-discount { color: #2e7d32; font-weight: 500; }
.bgmg-cart-row.is-discount a.bgmg-rm-coupon { color: #999; text-decoration: none; margin-left: 6px; font-size: 14px; }
.bgmg-cart-row.is-discount a.bgmg-rm-coupon:hover { color: #c62828; }
.bgmg-savings-banner {
  display: flex; align-items: center; gap: 8px;
  background: #f1f8e9; border: 1px solid #c5e1a5; border-radius: 12px;
  padding: 10px 14px; margin-bottom: 12px;
  font-family: 'Poppins', sans-serif; font-size: 13px; color: #2e7d32; font-weight: 500;
}
.bgmg-savings-banner.is-hidden { display: none; }
.bgmg-cart-shipping-note { font-family: 'Poppins', sans-serif; font-size: 12px; color: var(--mid); margin: 4px 0 16px; }

/* Coupon */
.bgmg-coupon-wrap { margin: 16px 0; border-top: 1px solid var(--border); padding-top: 16px; }
.bgmg-coupon-label { font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 1.5px; color: var(--mid); margin-bottom: 10px; display: block; }
.bgmg-coupon-row { display: flex; gap: 8px; }
.bgmg-coupon-input { flex: 1; padding: 11px 16px; border-radius: 30px; border: 1.5px solid var(--border); font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--dark); background: var(--pink-soft); outline: none; transition: border-color .2s; }
.bgmg-coupon-input:focus { border-color: var(--pink-dark); }
.bgmg-coupon-btn { padding: 11px 20px; border-radius: 30px; border: none; background: var(--dark); color: #fff; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer; transition: opacity .2s; white-space: nowrap; }
.bgmg-coupon-btn:hover { opacity: .85; }
.bgmg-applied-coupons { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.bgmg-applied-coupon { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px 4px 14px; background: var(--pink-soft); border: 1px solid var(--pink); border-radius: 20px; font-family: 'Poppins', sans-serif; font-size: 12px; color: var(--pink-dark); }
.bgmg-applied-coupon a { color: var(--pink-dark); text-decoration: none; font-size: 14px; line-height: 1; }
.bgmg-applied-coupon a:hover { color: var(--dark); }

/* Checkout button */
.bgmg-checkout-btn { display: block; width: 100%; margin-top: 16px; padding: 16px; border-radius: 30px; background: var(--dark); color: #fff; text-align: center; text-decoration: none; font-family: 'Poppins', sans-serif; font-size: 16px; font-weight: 500; transition: opacity .2s; }
.bgmg-checkout-btn:hover { opacity: .85; }
.bgmg-continue-link { display: block; text-align: center; margin-top: 14px; font-family: 'Poppins', sans-serif; font-size: 13px; color: var(--mid); text-decoration: none; transition: color .2s; }
.bgmg-continue-link:hover { color: var(--pink-dark); }

/* Update cart */
.bgmg-update-btn { display: block; width: 100%; margin-top: 12px; padding: 12px; border-radius: 30px; border: 1.5px solid var(--border); background: #fff; color: var(--mid); font-family: 'Poppins', sans-serif; font-size: 14px; cursor: pointer; transition: all .2s; }
.bgmg-update-btn:hover { border-color: var(--pink); background: var(--pink-soft); color: var(--dark); }
.bgmg-update-btn:disabled { opacity: .4; cursor: default; }

/* WooCommerce notices */
.woocommerce-notices-wrapper .woocommerce-message,
.woocommerce-notices-wrapper .woocommerce-error,
.woocommerce-notices-wrapper .woocommerce-info {
  padding: 14px 20px; border-radius: 12px; margin-bottom: 16px;
  font-family: 'Poppins', sans-serif; font-size: 14px; list-style: none;
}
.woocommerce-notices-wrapper .woocommerce-message { background: var(--pink-soft); color: var(--pink-dark); border: 1px solid var(--pink); }
.woocommerce-notices-wrapper .woocommerce-error { background: #fff0f0; color: #c62828; border: 1px solid #ffcdd2; }
.woocommerce-notices-wrapper .woocommerce-info { background: #e8f4fd; color: #1565c0; border: 1px solid #bbdefb; }
.woocommerce-notices-wrapper .woocommerce-message a { color: var(--pink-dark); }

/* ── RESPONSIVE ─────────────────────────────────────────────── */
@media (min-width: 768px) {
  .bgmg-header { height: 72px; }
  .bgmg-header-inner { padding: 0 40px; }
  .bgmg-dnav { display: flex; }
  .bgmg-hamburger { display: none; }
  .bgmg-search-overlay { top: 72px; padding: 18px 40px; }
  .bgmg-cart-wrap { padding-top: 104px; }
  .bgmg-cart-title { font-size: 40px; }
  .bgmg-cart-layout { flex-direction: row; align-items: flex-start; gap: 28px; }
  .bgmg-cart-items { flex: 1; }
  .bgmg-cart-summary { width: 360px; flex-shrink: 0; position: sticky; top: 92px; }
  .bgmg-sticky { display: none; }
}
</style>
</head>
<body <?php body_class('bgmg-cart-page'); ?>>
<?php wp_body_open(); ?>

<?php
$logo_id     = get_theme_mod('custom_logo');
$parent_cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'exclude' => array(get_option('default_product_cat')), 'orderby' => 'name'));
$parent_cats = is_wp_error($parent_cats) ? array() : $parent_cats;
$cart        = WC()->cart;
$cart_empty  = $cart->is_empty();
?>

<?php bgmg_render_header(); ?>

<?php /* Mini-cart panel: ahora lo rinde bgmg_render_header() (BL-01c). */ ?>

<!-- CART -->
<div class="bgmg-cart-wrap">
  <?php woocommerce_output_all_notices(); ?>

  <h1 class="bgmg-cart-title">Tu carrito</h1>

  <?php if ($cart_empty) : ?>
    <div class="bgmg-cart-empty">
      <span class="bgmg-cart-empty-icon">🛒</span>
      <h2>Tu carrito está vacío</h2>
      <p>Aún no has agregado ningún producto.</p>
      <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="bgmg-cart-empty-btn">Ver productos →</a>
    </div>

  <?php else : ?>
  <?php
  // Sección de avisos por grupo de surtido (BGM Mayorista, opcional)
  // Va FUERA del flex layout para ocupar el ancho completo en desktop y
  // no comprimir la columna de items.
  if ( function_exists( 'bgm_render_avisos_grupos_cart' ) ) {
      echo bgm_render_avisos_grupos_cart();
  }
  ?>
  <div class="bgmg-cart-layout">

    <!-- Items -->
    <?php
    // Pre-calcular fees negativos (descuentos a nivel carrito, ej. Advanced Discount Rules)
    $total_fee_discount  = 0;
    $total_line_subtotal = 0;
    foreach ($cart->get_fees() as $fee) {
        if ($fee->amount < 0) $total_fee_discount += abs($fee->amount);
    }
    foreach ($cart->get_cart() as $ci) {
        $total_line_subtotal += (float) $ci['line_subtotal'];
    }
    ?>
    <form class="woocommerce-cart-form bgmg-cart-items" id="bgmg-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
      <?php foreach ($cart->get_cart() as $key => $item) :
        $product  = $item['data'];
        $pid      = $item['product_id'];
        $qty      = $item['quantity'];
        $img      = get_the_post_thumbnail_url($pid, 'thumbnail') ?: wc_placeholder_img_src();
        $name     = esc_html($product->get_name());
        $p_url    = esc_url(get_permalink($pid));
        $p_terms  = get_the_terms($pid, 'product_cat');
        $cat_name = ($p_terms && !is_wp_error($p_terms)) ? esc_html($p_terms[0]->name) : '';
        $rm_url   = esc_url(wc_get_cart_remove_url($key));
      ?>
      <div class="bgmg-cart-item" id="item-<?php echo esc_attr($key); ?>">
        <a href="<?php echo $p_url; ?>"><img src="<?php echo esc_url($img); ?>" alt="<?php echo $name; ?>" class="bgmg-cart-item-img" loading="lazy"></a>
        <div class="bgmg-cart-item-info">
          <?php
          $line_subtotal      = (float) $item['line_subtotal'];
          $line_total         = (float) $item['line_total'];
          $regular_total_item = (float) $product->get_regular_price() * $qty;
          // Parte del fee proporcional a este ítem
          $fee_share = ($total_line_subtotal > 0)
              ? round($total_fee_discount * ($line_subtotal / $total_line_subtotal), 2)
              : 0;
          // Misma lógica que el resumen: regular - line_total + fee_share
          $item_saving = max(0, $regular_total_item - $line_total + $fee_share);
          $item_pct    = ($regular_total_item > 0 && $item_saving > 0)
              ? round(($item_saving / $regular_total_item) * 100)
              : 0;
          $unit_regular = (float) $product->get_regular_price();
          $unit_actual  = $qty > 0 ? round(($regular_total_item - $item_saving) / $qty, 2) : $unit_regular;
          ?>
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <?php if ($cat_name) : ?><span class="bgmg-cart-item-cat"><?php echo $cat_name; ?></span><?php endif; ?>
            <span class="bgmg-item-discount-badge" style="<?php echo $item_pct > 0 ? '' : 'display:none;'; ?>"><?php echo $item_pct > 0 ? '−' . $item_pct . '%' : ''; ?></span>
          </div>
          <a href="<?php echo $p_url; ?>" class="bgmg-cart-item-name"><?php echo $name; ?></a>
          <span class="bgmg-cart-item-price">
            <?php if ($item_saving > 0 && $unit_regular > $unit_actual) : ?>
              <del style="font-size:12px;color:var(--mid);font-weight:300;"><?php echo wc_price($unit_regular); ?></del>
              <span style="color:var(--pink-dark);font-weight:600;"><?php echo wc_price($unit_actual); ?></span>
            <?php else : ?>
              <?php echo $product->get_price_html(); ?>
            <?php endif; ?>
          </span>
        </div>
        <?php
          // Stock máximo disponible para limitar +/- y el input
          $stock_max_item = 99;
          if ($product && $product->managing_stock() && ! $product->backorders_allowed()) {
              $stock_max_item = max(1, (int) $product->get_stock_quantity());
          }
          $plus_disabled  = ($stock_max_item > 0 && $qty >= $stock_max_item);
        ?>
        <div class="bgmg-cart-item-actions">
          <div class="bgmg-qty-wrap">
            <button type="button" class="bgmg-qty-btn bgmg-qty-minus" data-key="<?php echo esc_attr($key); ?>"<?php if ($qty <= 1) echo ' disabled aria-disabled="true"'; ?>>−</button>
            <input type="number" class="bgmg-qty-input" name="cart[<?php echo esc_attr($key); ?>][qty]" value="<?php echo esc_attr($qty); ?>" min="1" max="<?php echo esc_attr($stock_max_item); ?>" data-key="<?php echo esc_attr($key); ?>">
            <button type="button" class="bgmg-qty-btn bgmg-qty-plus" data-key="<?php echo esc_attr($key); ?>" data-max="<?php echo esc_attr($stock_max_item); ?>"<?php if ($plus_disabled) echo ' disabled aria-disabled="true"'; ?>>+</button>
          </div>
          <button type="button" class="bgmg-cart-rm" data-remove="<?php echo $rm_url; ?>" data-key="<?php echo esc_attr($key); ?>" title="Eliminar">×</button>
        </div>
        <input type="hidden" name="cart[<?php echo esc_attr($key); ?>][key]" value="<?php echo esc_attr($key); ?>">
      </div>
      <?php endforeach; ?>

    </form>

    <!-- Summary -->
    <div class="bgmg-cart-summary">
      <h2 class="bgmg-cart-summary-title">Resumen</h2>

      <?php
      // Calcular ahorro total
      $regular_total = 0;
      foreach ($cart->get_cart() as $ci) {
          $p = $ci['data'];
          $regular_total += (float) $p->get_regular_price() * $ci['quantity'];
      }
      $cart_contents = (float) $cart->get_cart_contents_total() + (float) $cart->get_fee_total();
      $savings = max(0, $regular_total - $cart_contents - (float) $cart->get_discount_total());
      ?>

      <!-- Banner de ahorro -->
      <div class="bgmg-savings-banner<?php echo $savings > 0 ? '' : ' is-hidden'; ?>" id="bgmg-savings-banner">
        🎉 <span id="bgmg-savings-text">¡Estás ahorrando <?php echo wc_price($savings); ?>!</span>
      </div>

      <div class="bgmg-cart-row">
        <span>Subtotal</span>
        <span id="bgmg-subtotal"><?php echo $cart->get_cart_subtotal(); ?></span>
      </div>

      <!-- Filas de descuentos (cupones + plugin) -->
      <div id="bgmg-coupon-rows">
      <?php
      // Cupones
      foreach ($cart->get_applied_coupons() as $coupon_code) :
        $discount = wc_price($cart->get_coupon_discount_amount($coupon_code, $cart->display_prices_including_tax()));
        $rm_url   = esc_url( wp_nonce_url(
            add_query_arg( 'remove_coupon', rawurlencode( $coupon_code ), wc_get_cart_url() ),
            'woocommerce-remove-coupon_' . $coupon_code
        ) );
      ?>
      <div class="bgmg-cart-row is-discount">
        <span>🏷️ Cupón <strong><?php echo esc_html(strtoupper($coupon_code)); ?></strong> <a href="<?php echo $rm_url; ?>" class="bgmg-rm-coupon" title="Quitar">×</a></span>
        <span>−<?php echo $discount; ?></span>
      </div>
      <?php endforeach; ?>
      <?php
      // Descuentos del plugin (fees negativos)
      foreach ($cart->get_fees() as $fee) :
        if ($fee->amount >= 0) continue;
      ?>
      <div class="bgmg-cart-row is-discount">
        <span>🎁 <?php echo esc_html($fee->name); ?></span>
        <span><?php echo wc_price($fee->amount); ?></span>
      </div>
      <?php endforeach; ?>
      </div>

      <div class="bgmg-cart-row">
        <span>Envío</span>
        <span style="color:var(--mid);font-size:13px;">Se calcula al pagar</span>
      </div>

      <div class="bgmg-cart-row is-total">
        <span>Total</span>
        <strong id="bgmg-total"><?php echo $cart->get_cart_total(); ?></strong>
      </div>

      <!-- Cupón -->
      <div class="bgmg-coupon-wrap">
        <span class="bgmg-coupon-label">¿Tienes un cupón?</span>
        <form method="post" class="bgmg-coupon-row">
          <input type="text" name="coupon_code" class="bgmg-coupon-input" placeholder="Código de descuento">
          <?php wp_nonce_field('apply_coupon', 'woocommerce-coupon-nonce'); ?>
          <button type="submit" name="apply_coupon" class="bgmg-coupon-btn">Aplicar</button>
        </form>
      </div>

      <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="bgmg-checkout-btn">Ir al pago →</a>
      <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="bgmg-continue-link">← Seguir comprando</a>
    </div>

  </div>
  <?php endif; ?>
</div>

<!-- Sticky mobile -->

<script>
(function(){


  // Lupa + abrir/cerrar minicart: ahora GLOBALES (bgmg-header-ui-js en bgmg-landing.php, BL-01c Fase 2).

  // ── CART AJAX ────────────────────────────────────────────────
  function cartAjax(key, qty, onSuccess) {
    var fd = new FormData();
    fd.append('action', 'bgmg_update_cart');
    fd.append('nonce',  window.bgmgAjax ? window.bgmgAjax.cartNonce : '');
    fd.append('key',    key);
    fd.append('qty',    qty);
    fetch(window.bgmgAjax ? window.bgmgAjax.url : '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if(res.success) {
          var d = res.data;
          var sub    = document.getElementById('bgmg-subtotal');
          var tot    = document.getElementById('bgmg-total');
          var cnt    = document.querySelector('span.bgmg-cart-count');
          var banner = document.getElementById('bgmg-savings-banner');
          var savTxt = document.getElementById('bgmg-savings-text');
          var couRows= document.getElementById('bgmg-coupon-rows');
          if(sub)    sub.innerHTML    = d.subtotal;
          if(tot)    tot.innerHTML    = d.total;
          if(cnt)    cnt.textContent  = d.count > 0 ? d.count : '';
          if(couRows && d.coupon_rows !== undefined) couRows.innerHTML = d.coupon_rows;
          if(banner) {
            if(d.savings) {
              banner.classList.remove('is-hidden');
              if(savTxt) savTxt.innerHTML = '¡Estás ahorrando ' + d.savings + '!';
            } else {
              banner.classList.add('is-hidden');
            }
          }
          // Actualizar precio y badge por ítem en tiempo real
          if(d.items_data) {
            Object.keys(d.items_data).forEach(function(key) {
              var itemEl = document.getElementById('item-' + key);
              if(!itemEl) return;
              var priceEl = itemEl.querySelector('.bgmg-cart-item-price');
              var badgeEl = itemEl.querySelector('.bgmg-item-discount-badge');
              if(priceEl) priceEl.innerHTML = d.items_data[key].price_html;
              if(badgeEl) {
                var pct = d.items_data[key].badge_pct;
                if(pct > 0) { badgeEl.textContent = '−' + pct + '%'; badgeEl.style.display = ''; }
                else { badgeEl.style.display = 'none'; }
              }
            });
          }
          if(onSuccess) onSuccess(d);
        }
      });
  }

  // +/- buttons → AJAX inmediato (respeta stock máximo del producto)
  document.querySelectorAll('.bgmg-qty-minus').forEach(function(btn){
    btn.addEventListener('click', function(){
      if (this.disabled) return;
      var key   = this.dataset.key;
      var input = document.querySelector('.bgmg-qty-input[data-key="'+key+'"]');
      var current = parseInt(input.value, 10) || 1;
      if (current <= 1) return;
      var newQty = current - 1;
      input.value = newQty;
      cartAjax(key, newQty);
    });
  });
  document.querySelectorAll('.bgmg-qty-plus').forEach(function(btn){
    btn.addEventListener('click', function(){
      if (this.disabled) return;
      var key   = this.dataset.key;
      var input = document.querySelector('.bgmg-qty-input[data-key="'+key+'"]');
      var current = parseInt(input.value, 10) || 1;
      var max = parseInt(this.dataset.max, 10) || parseInt(input.max, 10) || 0;
      if (max > 0 && current >= max) return; // ya al tope
      var newQty = current + 1;
      input.value = newQty;
      cartAjax(key, newQty);
    });
  });
  // Input manual con debounce (capa qty al máximo del input)
  var qtyTimers = {};
  document.querySelectorAll('.bgmg-qty-input').forEach(function(inp){
    inp.addEventListener('change', function(){
      var key = this.dataset.key;
      var max = parseInt(this.max, 10) || 0;
      var qty = Math.max(1, parseInt(this.value, 10) || 1);
      if (max > 0) qty = Math.min(qty, max);
      this.value = qty;
      clearTimeout(qtyTimers[key]);
      qtyTimers[key] = setTimeout(function(){ cartAjax(key, qty); }, 400);
    });
  });

  // Remove → AJAX con qty=0
  document.querySelectorAll('.bgmg-cart-rm').forEach(function(btn){
    btn.addEventListener('click', function(){
      var key  = this.dataset.key;
      var item = document.getElementById('item-' + key);
      if(!item) return;
      item.classList.add('is-removing');
      cartAjax(key, 0, function(data){
        setTimeout(function(){
          item.remove();
          if(data.empty) window.location.reload();
        }, 300);
      });
    });
  });

  // Fragmentos WooCommerce
  if(typeof jQuery !== 'undefined'){
    jQuery(function($){ $(document.body).trigger('wc_fragment_refresh'); });
  }
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
