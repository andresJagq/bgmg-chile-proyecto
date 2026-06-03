<?php
defined('ABSPATH') || exit;

// Remove WooCommerce summary hooks we render manually
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title',   5);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating',  10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price',   10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta',    40);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

// Page setup
if (!have_posts()) { wp_redirect(home_url()); exit; }
the_post();

global $product;
$product = wc_get_product(get_the_ID());
if (!$product) { wp_redirect(home_url()); exit; }

$product_id = get_the_ID();
$shop_url   = get_permalink(wc_get_page_id('shop'));
$cart       = WC()->cart;
$logo_id    = (int) get_theme_mod('custom_logo');

// Gallery
$main_id  = (int) get_post_thumbnail_id($product_id);
$gall_ids = array_filter(array_map('intval', $product->get_gallery_image_ids()));
$all_ids  = $main_id ? array_merge([$main_id], $gall_ids) : array_values($gall_ids);
$multi    = count($all_ids) > 1;

// Category
$cats = get_the_terms($product_id, 'product_cat');
$cat  = ($cats && !is_wp_error($cats)) ? $cats[0] : null;

// Content
$short_desc = $product->get_short_description();
$long_desc  = $product->get_description();
$attrs      = $product->get_attributes();
$vis_attrs  = array_filter($attrs, fn($a) => $a->get_visible());
$has_tabs   = $long_desc || !empty($vis_attrs);



// Capturar output de woocommerce_before_single_product sin romper el HTML
ob_start();
do_action('woocommerce_before_single_product');
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php the_title(); ?> — BeautyGirlMG</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<?php wp_head(); ?>
<style>
/* ── RESET + BASE ─────────────────────────────── */
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
html, body { overflow-x: clip; }
html { scroll-behavior: smooth; }
body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--dark); }
img { max-width: 100%; height: auto; display: block; }

/* ── MINICART ── CSS estructural movido a assets/bgmg-global.css (BL-01c). ── */

/* ── WooCommerce notices — estilos en bgmg-landing.php (global) ── */
.woocommerce-notices-wrapper { padding: 0 20px; max-width: 1200px; margin: 80px auto 0; }
.woocommerce-notices-wrapper:empty { display: none; }
@media (min-width: 768px) { .woocommerce-notices-wrapper { margin-top: 88px; padding: 0 40px; } }

/* (minicart estructural → assets/bgmg-global.css, BL-01c) */

/* ── PRODUCT WRAP ─────────────────────────────── */
.bgmg-product-wrap { padding-top: 64px; max-width: 1100px; margin: 0 auto; padding-left: 20px; padding-right: 20px; padding-bottom: 80px; }

/* ── BREADCRUMB ───────────────────────────────── */
.bgmg-breadcrumb { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; padding: 20px 0 0; font-size: 13px; color: var(--mid); }
.bgmg-breadcrumb a { color: var(--mid); text-decoration: none; transition: color .2s; }
.bgmg-breadcrumb a:hover { color: var(--pink-dark); }
.bgmg-breadcrumb span { color: var(--border); }
.bgmg-breadcrumb strong { color: var(--dark); font-weight: 400; }

/* ── HERO LAYOUT ──────────────────────────────── */
.bgmg-prod-hero { display: grid; grid-template-columns: 1fr; margin-top: 24px; }

/* ── GALERÍA ──────────────────────────────────── */
.bgmg-gallery { width: 100%; }
.bgmg-swiper-wrap { position: relative; width: 100%; padding-bottom: 100%; height: 0; border-radius: 20px; overflow: hidden; background: var(--pink-soft); box-shadow: 0 4px 32px rgba(196,114,138,.10); }
.bgmg-swiper { position: absolute; inset: 0; width: 100%; height: 100%; }
.bgmg-swiper .swiper-slide { width: 100%; height: 100%; overflow: hidden; }
.bgmg-swiper .swiper-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
.bgmg-swiper .swiper-pagination-bullet { background: var(--pink); opacity: 1; width: 7px; height: 7px; }
.bgmg-swiper .swiper-pagination-bullet-active { background: var(--pink-dark); width: 20px; border-radius: 4px; }
.bgmg-swiper .swiper-button-prev,
.bgmg-swiper .swiper-button-next { width: 36px; height: 36px; background: rgba(26,16,21,.32); border-radius: 50%; color: #fff; margin-top: -18px; }
.bgmg-swiper .swiper-button-prev::after,
.bgmg-swiper .swiper-button-next::after { font-size: 13px; font-weight: 700; }
.bgmg-swiper .swiper-button-prev:hover,
.bgmg-swiper .swiper-button-next:hover { background: rgba(26,16,21,.6); }
.bgmg-thumbs { display: flex; gap: 8px; margin-top: 10px; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
.bgmg-thumbs::-webkit-scrollbar { display: none; }
.bgmg-thumb { width: 72px; height: 72px; flex-shrink: 0; border-radius: 12px; object-fit: cover; cursor: pointer; border: 2px solid transparent; opacity: .6; transition: border-color .2s, opacity .2s; }
.bgmg-thumb.active, .bgmg-thumb:hover { border-color: var(--pink-dark); opacity: 1; }

/* ── INFO DEL PRODUCTO ────────────────────────── */
.bgmg-prod-info { display: flex; flex-direction: column; gap: 18px; padding-top: 28px; }
.bgmg-prod-cat { font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 1.5px; color: var(--pink-dark); text-decoration: none; }
.bgmg-prod-title { font-family: 'Cormorant Garamond', serif; font-size: 34px; font-weight: 400; line-height: 1.15; color: var(--dark); }
.bgmg-prod-price { font-size: 26px; font-weight: 500; color: var(--dark); line-height: 1.1; }
.bgmg-prod-price del { font-size: 17px; color: var(--mid); font-weight: 300; margin-right: 6px; }
.bgmg-prod-price ins { text-decoration: none; }
.bgmg-prod-price-label { font-size: 11px; color: var(--mid); font-weight: 500; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
.bgmg-prod-short-desc { font-size: 14px; color: var(--mid); line-height: 1.7; }
.bgmg-prod-short-desc p { margin-bottom: 8px; }
.bgmg-prod-short-desc p:last-child { margin-bottom: 0; }

/* ── MAYORISTA AVISO ──────────────────────────── */
.bgm-aviso-mayorista { display: flex; align-items: flex-start; gap: 10px; background: linear-gradient(135deg, var(--pink-soft), #fff6ef); border: 1px dashed var(--pink-dark); border-radius: 14px; padding: 14px 16px; }
.bgm-aviso-icono { color: var(--pink-dark); font-size: 16px; flex-shrink: 0; margin-top: 2px; }
.bgm-aviso-texto { display: flex; flex-direction: column; gap: 3px; }
.bgm-aviso-texto strong { font-size: 13px; color: var(--dark); font-weight: 600; line-height: 1.4; }
.bgm-aviso-texto span { font-size: 12px; color: var(--mid); }

/* ── FORM ADD TO CART ─────────────────────────── */
.woocommerce div.product form.cart { display: flex; flex-direction: column; gap: 14px; }
.woocommerce div.product form.cart .quantity { display: flex; align-items: center; border: 1.5px solid var(--border); border-radius: 30px; width: fit-content; overflow: hidden; }
.woocommerce div.product form.cart .quantity input.qty { width: 52px; text-align: center; border: none; outline: none; background: transparent; font-family: 'DM Sans', sans-serif; font-size: 15px; color: var(--dark); padding: 10px 0; -moz-appearance: textfield; }
.woocommerce div.product form.cart .quantity input.qty::-webkit-inner-spin-button,
.woocommerce div.product form.cart .quantity input.qty::-webkit-outer-spin-button { -webkit-appearance: none; }
.bgmg-qty-btn { width: 44px; height: 44px; background: var(--pink-soft); border: none; font-size: 20px; cursor: pointer; color: var(--dark); transition: background .2s; font-family: inherit; display: flex; align-items: center; justify-content: center; line-height: 1; flex-shrink: 0; }
.bgmg-qty-btn:hover { background: var(--pink); }
.woocommerce div.product form.cart .single_add_to_cart_button,
.woocommerce div.product form.cart button.single_add_to_cart_button.alt { width: 100%; padding: 16px 24px; background: var(--dark); color: #fff; border: none; border-radius: 30px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .2s, opacity .2s, transform .15s; letter-spacing: .3px; text-align: center; line-height: 1.2; min-height: 52px; }
.woocommerce div.product form.cart .single_add_to_cart_button:hover,
.woocommerce div.product form.cart button.single_add_to_cart_button.alt:hover { background: var(--pink-dark); color: #fff; opacity: 1; transform: translateY(-1px); }
.woocommerce div.product form.cart .single_add_to_cart_button.loading { opacity: .6; pointer-events: none; }
.woocommerce div.product form.cart .single_add_to_cart_button.added { background: var(--pink-dark); }
.woocommerce div.product form.cart .single_add_to_cart_button.disabled,
.woocommerce div.product form.cart .single_add_to_cart_button.wc-variation-selection-needed { background: var(--mid); opacity: .6; cursor: not-allowed; }
.woocommerce-variation-availability .stock { font-size: 13px; color: var(--mid); }
.woocommerce-variation-price { display: none; }
/* Variación (ej. "Tono"): label + swatches centrados, más simétrico. La tabla
   .variations de WC viene como label(th) a la izquierda + valor(td) a la derecha;
   la pasamos a bloque centrado. */
.woocommerce div.product form.cart .variations { margin: 0 0 6px; }
.woocommerce div.product form.cart .variations tbody,
.woocommerce div.product form.cart .variations tr { display: block; }
.woocommerce div.product form.cart .variations th.label,
.woocommerce div.product form.cart .variations td.value { display: block; width: auto; padding: 0; text-align: center; }
.woocommerce div.product form.cart .variations th.label { margin-bottom: 8px; }
.woocommerce div.product form.cart .variations th.label label { margin: 0; font-weight: 600; }
.woocommerce div.product form.cart .variations td.value .bgm-swatches { justify-content: center; }

/* ── ACORDEÓN ─────────────────────────────────── */
.bgmg-accordion { margin-top: 40px; border-top: 1px solid var(--border); }
.bgmg-acc-item { border-bottom: 1px solid var(--border); }
.bgmg-acc-btn { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 18px 0; background: none; border: none; cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; color: var(--dark); text-align: left; }
.bgmg-acc-icon { font-size: 22px; color: var(--mid); transition: transform .25s; line-height: 1; flex-shrink: 0; }
.bgmg-acc-item.open .bgmg-acc-icon { transform: rotate(45deg); }
.bgmg-acc-body { display: none; padding-bottom: 24px; font-size: 14px; color: var(--mid); line-height: 1.7; }
.bgmg-acc-item.open .bgmg-acc-body { display: block; }
.bgmg-acc-body p { margin-bottom: 10px; }
.bgmg-acc-body p:last-child { margin-bottom: 0; }
.bgmg-acc-body ul, .bgmg-acc-body ol { padding-left: 20px; margin-bottom: 10px; }
.bgmg-acc-body table { width: 100%; border-collapse: collapse; }
.bgmg-acc-body table th { text-align: left; padding: 9px 12px 9px 0; font-size: 13px; font-weight: 500; color: var(--dark); border-bottom: 1px solid var(--border); width: 40%; }
.bgmg-acc-body table td { padding: 9px 0; font-size: 13px; color: var(--mid); border-bottom: 1px solid var(--border); }

/* ── PRODUCTOS RELACIONADOS ───────────────────── */
.bgmg-related-wrap { margin-top: 48px; }
.bgmg-related-title { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 400; color: var(--dark); margin-bottom: 20px; }
.bgmg-rel-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }

/* ── CARD (igual que landing) ─────────────────── */
.bgmg-card { display: flex; align-items: center; gap: 12px; padding: 12px; background: #fff; border: 1px solid var(--border); border-radius: 16px; position: relative; overflow: hidden; }
.bgmg-card-img { width: 80px; height: 80px; border-radius: 12px; flex-shrink: 0; object-fit: cover; }
.bgmg-card-link { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; text-decoration: none; color: inherit; }
.bgmg-card-link:hover .bgmg-card-name { color: var(--pink-dark); }
.bgmg-card-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.bgmg-badge { display: inline-block; font-size: 10px; font-weight: 500; color: var(--pink-dark); background: var(--pink-soft); padding: 2px 8px; border-radius: 20px; align-self: flex-start; }
.bgmg-badge-oferta { display: inline-block; font-size: 10px; font-weight: 500; color: #fff; background: var(--pink-dark); padding: 2px 8px; border-radius: 20px; align-self: flex-start; }
.bgmg-card-name { font-size: 14px; color: var(--dark); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; }
.bgmg-card-price { font-size: 15px; font-weight: 500; color: var(--dark); }
.bgmg-card-price del { font-size: 12px; color: var(--mid); margin-left: 4px; font-weight: 300; }
.bgmg-btn-add { width: 38px; height: 38px; border-radius: 50%; border: 1.5px solid var(--pink); background: #fff; color: var(--pink-dark); font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-decoration: none; line-height: 1; transition: background .2s, color .2s, border-color .2s; cursor: pointer; }
.bgmg-btn-add:hover { background: var(--pink-dark); color: #fff; border-color: var(--pink-dark); }

/* ── RESPONSIVE ───────────────────────────────── */
@media (min-width: 768px) {
  .bgmg-header { height: 72px; }
  .bgmg-header-inner { padding: 0 40px; }
  .bgmg-dnav { display: flex; }
  .bgmg-hamburger { display: none; }
  .bgmg-product-wrap { padding-top: 88px; padding-left: 40px; padding-right: 40px; }
  .bgmg-prod-hero { grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 48px; align-items: start; }
  .bgmg-gallery { position: sticky; top: 88px; }
  .bgmg-prod-info { padding-top: 0; }
  .bgmg-rel-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 767px) {
  .bgmg-cart-btn { display: none !important; }
}

/* ── TABS PRINCIPALES Detalle / Comprar por mayor ──────────────── */
.bgm-main-tabs { display: flex; gap: 0; margin-top: 8px; border-bottom: 1.5px solid var(--border); position: relative; }
.bgm-main-tab { flex: 1; padding: 14px 8px; background: transparent; border: none; font-family: inherit; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; color: var(--dark); cursor: pointer; position: relative; transition: color .2s ease, background .2s ease; text-align: center; }
.bgm-main-tab .tab-label { display: block; }
.bgm-main-tab .tab-sublabel { display: block; font-size: 11px; font-weight: 500; text-transform: none; letter-spacing: 0; color: var(--mid); margin-top: 3px; }
.bgm-main-tab:hover { color: var(--pink-dark); background: rgba(196, 114, 138, .04); }
.bgm-main-tab:hover .tab-sublabel { color: var(--pink-dark); }
.bgm-main-tab.is-active { color: var(--pink-dark); }
.bgm-main-tab.is-active .tab-sublabel { color: var(--pink-dark); }
.bgm-main-tab:focus-visible { outline: 2px solid var(--pink-dark); outline-offset: -2px; border-radius: 4px; }
.bgm-main-tab-indicator { position: absolute; bottom: -1.5px; left: 0; height: 3px; background: var(--pink-dark); width: 50%; border-radius: 2px 2px 0 0; transition: transform .3s cubic-bezier(.4,0,.2,1); }
.bgm-main-tabs[data-active="mayor"] .bgm-main-tab-indicator { transform: translateX(100%); }
.bgm-main-tab-badge { display: inline-block; margin-left: 6px; padding: 2px 7px; background: var(--pink-dark); color: #fff; border-radius: 12px; font-size: 9px; font-weight: 700; letter-spacing: .5px; vertical-align: middle; }
.bgm-tab-panel { display: none; padding-top: 20px; animation: bgmgFade .25s ease; }
.bgm-tab-panel.is-active { display: block; }
@keyframes bgmgFade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

/* ── TRUST SIGNALS ─────────────────────────────────────────────── */
.bgmg-trust { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; font-size: 12px; color: var(--mid); }
.bgmg-trust span::before { content: '✓'; color: var(--pink-dark); font-weight: 700; margin-right: 5px; }
</style>
</head>
<body <?php body_class(); ?>>

<!-- HEADER -->
<?php bgmg_render_header(); ?>

<?php /* Mini-cart panel: ahora lo rinde bgmg_render_header() (BL-01c). */ ?>

<?php woocommerce_output_all_notices(); ?>

<!-- PRODUCTO -->
<div class="bgmg-product-wrap">

  <!-- Breadcrumb -->
  <nav class="bgmg-breadcrumb">
    <a href="<?php echo esc_url(home_url('/')); ?>">Inicio</a>
    <span>›</span>
    <a href="<?php echo esc_url($shop_url); ?>">Tienda</a>
    <?php if ($cat) : ?>
      <span>›</span>
      <a href="<?php echo esc_url(get_term_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a>
    <?php endif; ?>
    <span>›</span>
    <strong><?php the_title(); ?></strong>
  </nav>

  <!-- Hero: galería + info -->
  <div class="bgmg-prod-hero">

    <!-- Galería -->
    <div class="bgmg-gallery">
      <div class="bgmg-swiper-wrap">
        <div class="swiper bgmg-swiper">
          <div class="swiper-wrapper">
            <?php if (!empty($all_ids)) :
              foreach ($all_ids as $img_id) :
                $img_url = wp_get_attachment_image_url($img_id, 'large') ?: wc_placeholder_img_src();
                $img_alt = trim(get_post_meta($img_id, '_wp_attachment_image_alt', true)) ?: get_the_title();
            ?>
            <div class="swiper-slide">
              <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>" loading="eager">
            </div>
            <?php endforeach; else : ?>
            <div class="swiper-slide">
              <img src="<?php echo esc_url(wc_placeholder_img_src()); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
            </div>
            <?php endif; ?>
          </div>
          <?php if ($multi) : ?>
          <div class="swiper-pagination"></div>
          <div class="swiper-button-prev"></div>
          <div class="swiper-button-next"></div>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($multi) : ?>
      <div class="bgmg-thumbs">
        <?php foreach ($all_ids as $i => $img_id) :
          $t_url = wp_get_attachment_image_url($img_id, 'thumbnail') ?: wc_placeholder_img_src();
          $t_alt = trim(get_post_meta($img_id, '_wp_attachment_image_alt', true)) ?: get_the_title();
        ?>
        <img src="<?php echo esc_url($t_url); ?>"
             alt="<?php echo esc_attr($t_alt); ?>"
             class="bgmg-thumb<?php echo $i === 0 ? ' active' : ''; ?>"
             data-index="<?php echo $i; ?>"
             loading="lazy">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info del producto -->
    <div class="bgmg-prod-info">
      <?php if ($cat) : ?>
      <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="bgmg-prod-cat"><?php echo esc_html($cat->name); ?></a>
      <?php endif; ?>

      <h1 class="bgmg-prod-title"><?php the_title(); ?></h1>

      <?php
        $bgm_tiene_mayorista = false;
        if ( $product->is_type( 'simple' ) && function_exists( 'bgm_tiene_precio_mayorista' ) ) {
            $bgm_tiene_mayorista = bgm_tiene_precio_mayorista( $product->get_id() );
        } elseif ( $product->is_type( 'variable' ) && function_exists( 'bgm_variable_tiene_mayorista' ) ) {
            $bgm_tiene_mayorista = bgm_variable_tiene_mayorista( $product );
        }
      ?>
      <div class="bgmg-prod-price"><?php
        if ( function_exists( 'bgm_promo_badge_html' ) ) echo bgm_promo_badge_html( $product );
        echo $product->get_price_html();
      ?></div>
      <?php if ( $bgm_tiene_mayorista ) : ?>
      <div class="bgmg-prod-price-label">Precio detalle · por unidad</div>
      <?php endif; ?>

      <?php if ($short_desc) : ?>
      <div class="bgmg-prod-short-desc"><?php echo wp_kses_post($short_desc); ?></div>
      <?php endif; ?>

      <!-- Form add to cart + bloque mayorista (con tabs si aplica) -->
      <?php
        $bgm_is_variable_mayorista = $product->is_type('variable')
            && function_exists('bgm_variable_tiene_mayorista')
            && bgm_variable_tiene_mayorista($product);

        // Calcular % máximo de descuento para el badge del tab "Por mayor"
        $bgm_max_pct = 0;
        if ($bgm_is_variable_mayorista && function_exists('bgm_resumen_mayorista_variable') && function_exists('bgm_get_precio_base')) {
            $bgm_resumen      = bgm_resumen_mayorista_variable($product);
            $bgm_precio_base  = bgm_get_precio_base($product);
            $bgm_desc_max     = max((float)$bgm_resumen['desc_1_max'], (float)$bgm_resumen['desc_2_max']);
            if ($bgm_precio_base > 0 && $bgm_desc_max > 0) {
                $bgm_max_pct = (int) round(($bgm_desc_max / $bgm_precio_base) * 100);
            }
        }
      ?>
      <div class="woocommerce">
        <div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>

        <?php if ($bgm_is_variable_mayorista) : ?>

          <!-- Tabs principales: Detalle / Comprar por mayor -->
          <div class="bgm-main-tabs" data-active="mayor" id="bgm-main-tabs">
            <button type="button" class="bgm-main-tab" data-tab="detalle">
              <span class="tab-label">Detalle</span>
              <span class="tab-sublabel">Compra por unidad</span>
            </button>
            <button type="button" class="bgm-main-tab is-active" data-tab="mayor">
              <span class="tab-label">Por mayor<?php if ($bgm_max_pct > 0) : ?> <span class="bgm-main-tab-badge">−<?php echo (int) $bgm_max_pct; ?>%</span><?php endif; ?></span>
              <span class="tab-sublabel">Desde 3 ud · ahorrás más</span>
            </button>
            <div class="bgm-main-tab-indicator"></div>
          </div>

          <!-- Panel Detalle -->
          <div class="bgm-tab-panel" data-panel="detalle">
            <?php do_action('woocommerce_single_product_summary'); ?>
            <div class="bgmg-trust">
              <span>Envío 24-48h</span>
              <span>Pago seguro</span>
              <span>Atención por WhatsApp</span>
            </div>
          </div>

          <!-- Panel Comprar por mayor -->
          <div class="bgm-tab-panel is-active" data-panel="mayor">
            <?php bgm_render_mayorista_bloque_publico($product); ?>
            <div class="bgmg-trust">
              <span>Envío 24-48h</span>
              <span>Pago seguro</span>
              <span>Atención por WhatsApp</span>
            </div>
          </div>

        <?php else : ?>

          <!-- Producto simple o sin mayorista: flujo único -->
          <?php do_action('woocommerce_single_product_summary'); ?>
          <div class="bgmg-trust">
            <span>Envío 24-48h</span>
            <span>Pago seguro</span>
            <span>Atención por WhatsApp</span>
          </div>

        <?php endif; ?>

        </div>
      </div>
    </div>

  </div><!-- /bgmg-prod-hero -->

  <!-- Acordeón: descripción + atributos -->
  <?php if ($has_tabs) : ?>
  <div class="bgmg-accordion">

    <?php if ($long_desc) : ?>
    <div class="bgmg-acc-item open">
      <button class="bgmg-acc-btn" type="button">
        Descripción <span class="bgmg-acc-icon">+</span>
      </button>
      <div class="bgmg-acc-body"><?php echo wp_kses_post($long_desc); ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($vis_attrs)) : ?>
    <div class="bgmg-acc-item">
      <button class="bgmg-acc-btn" type="button">
        Información adicional <span class="bgmg-acc-icon">+</span>
      </button>
      <div class="bgmg-acc-body">
        <table>
          <?php foreach ($vis_attrs as $attr) :
            $label = wc_attribute_label($attr->get_name(), $product);
            if ($attr->is_taxonomy()) {
              $terms_obj = get_the_terms($product_id, $attr->get_name());
              $value = $terms_obj ? implode(', ', wp_list_pluck($terms_obj, 'name')) : '';
            } else {
              $value = implode(', ', $attr->get_options());
            }
            if (!$value) continue;
          ?>
          <tr>
            <th><?php echo esc_html($label); ?></th>
            <td><?php echo esc_html($value); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>

  <!-- Productos relacionados -->
  <?php
  $related_ids = wc_get_related_products($product_id, 8);
  if (!empty($related_ids)) :
  ?>
  <div class="bgmg-related-wrap">
    <h2 class="bgmg-related-title">También te puede gustar</h2>
    <div class="bgmg-rel-grid">
      <?php foreach ($related_ids as $rid) :
        $rp = wc_get_product($rid);
        if (!$rp) continue;
        $r_name  = esc_html($rp->get_name());
        $r_url   = esc_url(get_permalink($rid));
        $r_img   = get_the_post_thumbnail_url($rid, 'thumbnail') ?: wc_placeholder_img_src();
        $r_terms = get_the_terms($rid, 'product_cat');
        $r_cat   = ($r_terms && !is_wp_error($r_terms)) ? esc_html($r_terms[0]->name) : '';
        $r_badge = $rp->is_on_sale()
          ? '<span class="bgmg-badge-oferta">🔥 Oferta</span>'
          : ($r_cat ? '<span class="bgmg-badge">' . $r_cat . '</span>' : '');
      ?>
      <div class="bgmg-card">
        <a href="<?php echo $r_url; ?>" class="bgmg-card-link">
          <img class="bgmg-card-img" src="<?php echo esc_url($r_img); ?>" alt="<?php echo esc_attr($r_name); ?>" loading="lazy">
          <div class="bgmg-card-body">
            <?php echo $r_badge; ?>
            <div class="bgmg-card-name"><?php echo $r_name; ?></div>
            <div class="bgmg-card-price"><?php echo wp_strip_all_tags($rp->get_price_html()); ?></div>
          </div>
        </a>
        <a href="<?php echo esc_url($rp->add_to_cart_url()); ?>"
           class="bgmg-btn-add add_to_cart_button ajax_add_to_cart"
           data-product_id="<?php echo esc_attr($rid); ?>"
           data-product_type="<?php echo esc_attr($rp->get_type()); ?>"
           data-quantity="1" rel="nofollow">+</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /bgmg-product-wrap -->

<?php bgmg_footer(); ?>

<?php do_action('woocommerce_after_single_product'); ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
/* ── Search + Cart + Hamburger (independiente, siempre corre) ── */
(function () {
  // Abrir minicart: ahora GLOBAL (bgmg-header-ui-js en bgmg-landing.php, BL-01c Fase 2).

  // Hamburger
  var ham    = document.getElementById('bgmg-hamburger');
  var drawer = document.getElementById('bgmg-mdrawer');
  if (ham && drawer) ham.addEventListener('click', function () {
    ham.classList.toggle('is-open');
    drawer.classList.toggle('is-open');
  });

  // Buscador (lupa): ahora GLOBAL (bgmg-header-ui-js en bgmg-landing.php, BL-01c Fase 2).
})();

/* ── Minicart: actualizar tras add-to-cart del plugin mayorista ── */
(function () {
  window.bgmAfterAddToCart = function (data) {
    if (data.minicart_html) {
      var inner = document.getElementById("bgmg-minicart-inner");
      if (inner) {
        var tmp = document.createElement("div");
        tmp.innerHTML = data.minicart_html;
        if (tmp.firstElementChild) inner.replaceWith(tmp.firstElementChild);
      }
    }
    document.querySelectorAll(".bgmg-cart-count").forEach(function (el) {
      el.textContent = data.cart_count > 0 ? data.cart_count : "";
    });
    var panel = document.getElementById("bgmg-mc-panel");
    var bkd   = document.getElementById("bgmg-mc-backdrop");
    if (panel) { panel.classList.add("is-open"); if (bkd) bkd.classList.add("is-open"); document.body.style.overflow = "hidden"; }
  };
})();

/* ── Swiper + Thumbnails + Cantidad + Acordeón ── */
(function () {
  // Swiper
  var swiper = null;
  try {
    if (typeof Swiper !== 'undefined' && document.querySelector('.bgmg-swiper')) {
      swiper = new Swiper('.bgmg-swiper', {
        loop: false,
        pagination:  { el: '.swiper-pagination', clickable: true },
        navigation:  { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        keyboard:    { enabled: true },
      });
    }
  } catch (e) { console.warn('Swiper init error:', e); }

  // Thumbnails
  var thumbs = document.querySelectorAll('.bgmg-thumb');
  if (swiper && thumbs.length) {
    thumbs.forEach(function (t) {
      t.addEventListener('click', function () {
        swiper.slideTo(+t.dataset.index);
        thumbs.forEach(function (x) { x.classList.remove('active'); });
        t.classList.add('active');
      });
    });
    swiper.on('slideChange', function () {
      thumbs.forEach(function (t, i) { t.classList.toggle('active', i === swiper.realIndex); });
    });
  }

  // Cantidad +/−
  document.querySelectorAll('form.cart .quantity').forEach(function (wrapper) {
    var input = wrapper.querySelector('input.qty');
    if (!input) return;
    function make(label, fn) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'bgmg-qty-btn'; b.textContent = label;
      b.addEventListener('click', fn); return b;
    }
    wrapper.prepend(make('−', function () {
      var min = parseInt(input.getAttribute('min')) || 1;
      input.value = Math.max(min, (parseInt(input.value) || 1) - 1);
    }));
    wrapper.appendChild(make('+', function () {
      var max = parseInt(input.getAttribute('max')) || 9999;
      input.value = Math.min(max, (parseInt(input.value) || 1) + 1);
    }));
  });

  // Acordeón
  document.querySelectorAll('.bgmg-acc-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.bgmg-acc-item').classList.toggle('open');
    });
  });

  // ── Tabs principales Detalle / Por mayor ──
  var mainTabs = document.getElementById('bgm-main-tabs');
  if (mainTabs) {
    var tabs   = mainTabs.querySelectorAll('.bgm-main-tab');
    var panels = document.querySelectorAll('.bgm-tab-panel');
    tabs.forEach(function (t) {
      t.addEventListener('click', function () {
        var target = t.dataset.tab;
        tabs.forEach(function (x) { x.classList.toggle('is-active', x === t); });
        panels.forEach(function (p) { p.classList.toggle('is-active', p.dataset.panel === target); });
        mainTabs.dataset.active = target;
      });
    });

  }
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
