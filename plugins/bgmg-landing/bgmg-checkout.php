<?php defined('ABSPATH') || exit; ?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finalizar compra — BeautyGirlMG</title>
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
html, body { overflow-x: clip; }
html { scroll-behavior: auto; }
body { font-family: 'Poppins', sans-serif; background: var(--cream); color: var(--dark); }
.bgmg-co-wrap, .bgmg-co-wrap * { max-width: 100%; }

/* ── MINI CART ── CSS estructural movido a assets/bgmg-global.css (BL-01c). ── */

/* ── WRAPPER PRINCIPAL ───────────────────────────────────────── */
.bgmg-co-wrap {
  padding: 84px 10px 80px;
  max-width: 720px;
  margin: 0 auto;
}
@media (min-width: 768px) {
  .bgmg-header { height: 72px; }
  .bgmg-header-inner { padding: 0 40px; }
  .bgmg-co-wrap { padding-top: 104px; }
}

/* ── BREADCRUMB ─────────────────────────────────────────────── */
.bgmg-breadcrumb {
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
  margin-bottom: 16px;
  font-family: 'Poppins', sans-serif; font-size: 13px; color: var(--mid);
}
.bgmg-breadcrumb a { color: var(--mid); text-decoration: none; transition: color .2s; }
.bgmg-breadcrumb a:hover { color: var(--pink-dark); }
.bgmg-breadcrumb span { color: var(--border); }
.bgmg-breadcrumb strong { color: var(--dark); font-weight: 400; }

/* ── BOTÓN VOLVER AL CARRITO ────────────────────────────────── */
.bgmg-co-back {
  display: inline-flex; align-items: center; gap: 6px;
  margin-bottom: 18px;
  padding: 8px 16px;
  border-radius: 24px;
  background: var(--pink-soft);
  color: var(--mid);
  font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 500;
  text-decoration: none;
  transition: background .2s, color .2s, transform .15s;
}
.bgmg-co-back:hover { background: var(--pink); color: var(--dark); transform: translateX(-2px); }
.bgmg-co-back:active { transform: translateX(0); }

/* ── TÍTULO ─────────────────────────────────────────────────── */
.bgmg-co-title {
  font-family: 'Alice', serif;
  font-size: 32px; font-weight: 400; color: var(--dark);
  margin-bottom: 28px;
}
@media (min-width: 768px) { .bgmg-co-title { font-size: 40px; } }

/* ── AVISOS WOOCOMMERCE ─────────────────────────────────────── */
.woocommerce-info::before,
.woocommerce-message::before,
.woocommerce-error::before,
ul.woocommerce-error::before { display: none !important; }

.woocommerce-info,
.woocommerce-message,
.woocommerce-error,
ul.woocommerce-error {
  font-family: 'Poppins', sans-serif !important;
  font-size: 14px !important; border-radius: 12px !important;
  padding: 14px 18px !important; margin-bottom: 16px !important;
  list-style: none !important; border-top: none !important;
}
.woocommerce-info { background: var(--pink-soft) !important; border: 1px solid var(--pink) !important; color: var(--mid) !important; }
.woocommerce-info a { color: var(--pink-dark) !important; font-weight: 500 !important; }
.woocommerce-message { background: #f1f8e9 !important; border: 1px solid #c5e1a5 !important; color: #2e7d32 !important; }
.woocommerce-error, ul.woocommerce-error { background: #fff0f0 !important; border: 1px solid #ffcdd2 !important; color: #c62828 !important; }
ul.woocommerce-error li { padding: 4px 0 !important; }

/* ── LAYOUT FORMULARIO — UNA COLUMNA (escritorio + mobile) ──────
   El usuario pidió que TODO el checkout se vea en una sola columna
   también en escritorio (antes era 2 col: detalles a la izq, resumen
   a la der). Cubrimos los dos modos de WooCommerce:
     - Legacy shortcode [woocommerce_checkout]  → form.woocommerce-checkout
     - WC Blocks checkout                       → .wp-block-woocommerce-checkout
   En mobile ya era 1 columna, no afecta. */
form.woocommerce-checkout,
.woocommerce form.checkout,
.woocommerce-checkout {
  display: flex !important;
  flex-direction: column !important;
  gap: 0;
  max-width: 720px;
  margin: 0 auto;
}
form.woocommerce-checkout > #customer_details,
form.woocommerce-checkout > #order_review,
form.woocommerce-checkout > #order_review_heading,
.woocommerce-checkout > #customer_details,
.woocommerce-checkout > #order_review {
  width: 100% !important;
  float: none !important;
  max-width: 100% !important;
}

/* WooCommerce Blocks (Gutenberg) — apila las dos columnas */
.wp-block-woocommerce-checkout,
.wc-block-checkout,
.wc-block-components-main-wrapper > .wp-block-woocommerce-checkout {
  display: block !important;
  flex-direction: column !important;
  max-width: 720px !important;
  margin: 0 auto !important;
}
.wp-block-woocommerce-checkout .wp-block-woocommerce-checkout-fields-block,
.wp-block-woocommerce-checkout .wp-block-woocommerce-checkout-totals-block,
.wc-block-components-sidebar-layout .wc-block-components-main,
.wc-block-components-sidebar-layout .wc-block-components-sidebar {
  width: 100% !important;
  max-width: 100% !important;
  flex: none !important;
  padding-right: 0 !important;
  padding-left: 0 !important;
}
.wc-block-components-sidebar-layout {
  display: block !important;
  grid-template-columns: none !important;
}

/* "col2-set" interno de WooCommerce (col-1 = facturación, col-2 = envío +
   "Notas del pedido"). Lo forzamos a UNA columna real (apilado) también en
   escritorio. Antes, desde 540px se ponían lado a lado: eso achicaba los
   campos de facturación a ~50% y mandaba las "Notas del pedido" a una columna
   derecha casi vacía (el envío está consolidado). Ahora va todo a ancho
   completo: facturación arriba, notas debajo. */
.col2-set { display: flex; flex-direction: column; }
.col2-set .col-1,
.col2-set .col-2 { width: 100% !important; max-width: 100% !important; float: none !important; }

/* ── ENCABEZADOS DE SECCIÓN ──────────────────────────────────── */
.woocommerce-billing-fields h3,
.woocommerce-shipping-fields h3,
.woocommerce-additional-fields h3,
#order_review_heading {
  font-family: 'Alice', serif !important;
  font-size: 22px !important; font-weight: 600 !important; color: var(--dark) !important;
  margin: 28px 0 16px !important; padding-bottom: 12px !important;
  border-bottom: 1px solid var(--border) !important;
}
.woocommerce-billing-fields h3:first-child { margin-top: 0 !important; }

/* ── LABELS ─────────────────────────────────────────────────── */
form.woocommerce-checkout label {
  font-family: 'Poppins', sans-serif !important;
  font-size: 12px !important; font-weight: 500 !important;
  text-transform: uppercase !important; letter-spacing: 1px !important;
  color: var(--mid) !important; margin-bottom: 6px !important;
  display: block !important; line-height: 1.4 !important;
}
form.woocommerce-checkout .required { color: var(--pink-dark) !important; }

/* Labels de checkbox — sin uppercase */
.woocommerce-form__label-for-checkbox {
  display: flex !important; align-items: center !important; gap: 10px !important;
  text-transform: none !important; letter-spacing: 0 !important;
  font-size: 14px !important; cursor: pointer !important;
}
.woocommerce-form__label-for-checkbox input[type="checkbox"] {
  width: 18px !important; height: 18px !important;
  accent-color: var(--pink-dark) !important; flex-shrink: 0; cursor: pointer !important;
}

/* ── INPUTS ──────────────────────────────────────────────────── */
form.woocommerce-checkout input[type="text"],
form.woocommerce-checkout input[type="email"],
form.woocommerce-checkout input[type="tel"],
form.woocommerce-checkout input[type="password"],
form.woocommerce-checkout input[type="number"],
form.woocommerce-checkout .input-text,
form.woocommerce-checkout textarea {
  width: 100% !important; padding: 13px 16px !important;
  border: 1.5px solid var(--border) !important; border-radius: 12px !important;
  font-family: 'Poppins', sans-serif !important; font-size: 16px !important;
  color: var(--dark) !important; background: #fff !important;
  outline: none !important; box-shadow: none !important;
  -webkit-appearance: none !important; appearance: none !important;
  transition: border-color .2s, box-shadow .2s !important; height: auto !important;
}
form.woocommerce-checkout input:focus,
form.woocommerce-checkout textarea:focus {
  border-color: var(--pink-dark) !important;
  box-shadow: 0 0 0 3px rgba(196,114,138,.1) !important;
}

/* ── SELECT NATIVO ───────────────────────────────────────────── */
form.woocommerce-checkout select {
  width: 100% !important; padding: 13px 16px !important;
  border: 1.5px solid var(--border) !important; border-radius: 12px !important;
  font-family: 'Poppins', sans-serif !important; font-size: 16px !important;
  color: var(--dark) !important; background: #fff !important;
  outline: none !important; cursor: pointer !important;
}

/* ── SELECT2 ─────────────────────────────────────────────────── */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
  border: 1.5px solid var(--border) !important; border-radius: 12px !important;
  height: 50px !important; background: #fff !important;
  display: flex !important; align-items: center !important; overflow: hidden !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  font-family: 'Poppins', sans-serif !important; font-size: 16px !important;
  color: var(--dark) !important; line-height: 50px !important;
  padding: 0 40px 0 16px !important;
  overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 50px !important; width: 36px !important; right: 4px !important;
  top: 0 !important; position: absolute !important;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
  border-color: var(--pink-dark) !important;
  box-shadow: 0 0 0 3px rgba(196,114,138,.1) !important;
}
.select2-dropdown { border: 1px solid var(--border) !important; border-radius: 12px !important; box-shadow: 0 8px 32px rgba(0,0,0,.10) !important; overflow: hidden !important; }
.select2-results__option { font-family: 'Poppins', sans-serif !important; font-size: 14px !important; padding: 10px 16px !important; }
.select2-results__option--highlighted { background: var(--pink-soft) !important; color: var(--dark) !important; }
.select2-search--dropdown .select2-search__field { border: 1.5px solid var(--border) !important; border-radius: 8px !important; padding: 8px 12px !important; font-family: 'Poppins', sans-serif !important; font-size: 14px !important; }

/* ── FORM ROWS ───────────────────────────────────────────────── */
.form-row { margin-bottom: 16px !important; }
.woocommerce-invalid .input-text,
.woocommerce-invalid input { border-color: #ef5350 !important; }

/* ── CUPÓN ───────────────────────────────────────────────────── */
.woocommerce-form-coupon-toggle {
  background: var(--pink-soft); border-radius: 12px;
  padding: 12px 16px; margin-bottom: 20px;
  font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--mid);
}
.woocommerce-form-coupon-toggle .showcoupon { color: var(--pink-dark); font-weight: 500; }
.woocommerce-form-coupon { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
.woocommerce-form-coupon p { font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--mid); margin-bottom: 12px; }
.woocommerce-form-coupon .form-row { display: flex; gap: 10px; margin: 0 !important; }
.woocommerce-form-coupon .button {
  padding: 13px 24px !important; border-radius: 30px !important;
  background: var(--dark) !important; color: #fff !important; border: none !important;
  font-family: 'Poppins', sans-serif !important; font-size: 14px !important;
  font-weight: 500 !important; cursor: pointer !important; white-space: nowrap !important; transition: opacity .2s !important;
}
.woocommerce-form-coupon .button:hover { opacity: .85 !important; }

/* ── RESUMEN DEL PEDIDO ──────────────────────────────────────── */
#order_review { background: #fff; border: 1px solid var(--border); border-radius: 20px; overflow: hidden; margin-top: 8px; }
#order_review_heading { padding: 20px 24px 0 !important; border-bottom: none !important; margin-top: 0 !important; }

.woocommerce-checkout-review-order-table { width: 100%; border-collapse: collapse; font-family: 'Poppins', sans-serif; }
.woocommerce-checkout-review-order-table thead th {
  padding: 12px 24px; font-size: 11px; font-weight: 500;
  text-transform: uppercase; letter-spacing: 1.5px; color: var(--mid);
  border-bottom: 1px solid var(--border); background: var(--pink-soft);
}
.woocommerce-checkout-review-order-table tbody td {
  padding: 14px 24px; font-size: 14px; color: var(--dark); border-bottom: 1px solid var(--border);
}
.woocommerce-checkout-review-order-table tbody td.product-name { font-weight: 500; line-height: 1.4; }
.woocommerce-checkout-review-order-table tbody td.product-name .product-quantity { color: var(--mid); font-size: 13px; font-weight: 400; }
.woocommerce-checkout-review-order-table tfoot th { padding: 10px 24px; font-size: 13px; color: var(--mid); font-weight: 400; }
.woocommerce-checkout-review-order-table tfoot td { padding: 10px 24px; font-size: 14px; color: var(--dark); font-weight: 500; border-top: 1px solid var(--border); }
.woocommerce-checkout-review-order-table tfoot .order-total th,
.woocommerce-checkout-review-order-table tfoot .order-total td { padding: 16px 24px; font-size: 17px; font-weight: 600; border-top: 2px solid var(--border); }
.woocommerce-checkout-review-order-table tfoot .order-total td { color: var(--pink-dark); }

/* ── MÉTODOS DE PAGO ─────────────────────────────────────────── */
#payment { background: var(--pink-soft); border-top: 1px solid var(--border); padding: 24px; }
#payment .payment_methods { list-style: none; margin-bottom: 20px; }
#payment .payment_methods li { padding: 14px 0; border-bottom: 1px solid var(--border); }
#payment .payment_methods li:last-child { border-bottom: none; }
#payment .payment_methods li label {
  display: flex !important; align-items: center !important; gap: 10px !important;
  font-family: 'Poppins', sans-serif !important; font-size: 15px !important;
  color: var(--dark) !important; cursor: pointer !important;
  text-transform: none !important; letter-spacing: 0 !important;
}
#payment .payment_methods li label img { height: 24px; width: auto; object-fit: contain; }
#payment .payment_methods input[type="radio"] { width: 18px !important; height: 18px !important; accent-color: var(--pink-dark) !important; }
#payment .payment_box { background: rgba(255,255,255,.6); border-radius: 10px; padding: 14px 16px; margin-top: 10px; font-family: 'Poppins', sans-serif; font-size: 13px; color: var(--mid); }
#payment .terms { font-family: 'Poppins', sans-serif; font-size: 13px; color: var(--mid); margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px; }
#payment .terms a { color: var(--pink-dark); }

/* ── BOTÓN PAGAR ─────────────────────────────────────────────── */
/* Force overrides porque el bloque global de bgmg-landing.php pinta
   todos los .woocommerce .button con !important en blanco; necesitamos
   ganar esa especificidad para que el botón de finalizar sea visible. */
.woocommerce #place_order,
.woocommerce-page #place_order,
#place_order.button,
#place_order.button.alt,
button#place_order {
  display: block !important;
  width: 100% !important;
  padding: 18px 24px !important;
  border-radius: 30px !important;
  background: var(--dark) !important;
  background-color: var(--dark) !important;
  color: #fff !important;
  border: none !important;
  font-family: 'Poppins', sans-serif !important;
  font-size: 16px !important;
  font-weight: 600 !important;
  cursor: pointer;
  transition: background .2s ease, transform .15s ease, box-shadow .2s ease, opacity .2s !important;
  text-align: center;
  letter-spacing: 0.5px;
  min-height: 56px;
  box-shadow: 0 4px 14px rgba(26, 16, 21, .15);
  text-decoration: none !important;
}
.woocommerce #place_order:hover,
.woocommerce-page #place_order:hover,
#place_order.button:hover,
#place_order.button.alt:hover,
button#place_order:hover {
  background: var(--pink-dark) !important;
  background-color: var(--pink-dark) !important;
  color: #fff !important;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(196, 114, 138, .35);
  opacity: 1 !important;
}
.woocommerce #place_order:active,
button#place_order:active {
  transform: translateY(0);
  box-shadow: 0 2px 8px rgba(26, 16, 21, .2);
}
.woocommerce #place_order:disabled,
button#place_order:disabled {
  opacity: .6 !important;
  cursor: not-allowed;
  transform: none;
}

/* ── BOTÓN FINALIZAR del Checkout Block de WC 8+ (Gutenberg) ───
   El checkout moderno usa otras clases. Aplicamos los mismos
   estilos para garantizar contraste sin importar qué checkout use
   el sitio (clásico vs block). */
.wc-block-components-checkout-place-order-button,
.wp-block-woocommerce-checkout .wc-block-components-button,
.wc-block-cart__submit-button,
.wc-block-components-button.contained {
  display: block !important;
  width: 100% !important;
  padding: 18px 24px !important;
  border-radius: 30px !important;
  background: #1A1015 !important;
  background-color: #1A1015 !important;
  color: #fff !important;
  border: none !important;
  font-family: 'Poppins', sans-serif !important;
  font-size: 16px !important;
  font-weight: 600 !important;
  cursor: pointer;
  transition: background .2s, transform .15s, box-shadow .2s, opacity .2s !important;
  text-align: center !important;
  letter-spacing: 0.5px;
  min-height: 56px !important;
  box-shadow: 0 4px 14px rgba(26, 16, 21, .15) !important;
  text-decoration: none !important;
  text-transform: none !important;
  -webkit-appearance: none !important;
  appearance: none !important;
  outline: none !important;
}
.wc-block-components-checkout-place-order-button:hover,
.wp-block-woocommerce-checkout .wc-block-components-button:hover,
.wc-block-cart__submit-button:hover,
.wc-block-components-button.contained:hover {
  background: #C4728A !important;
  background-color: #C4728A !important;
  color: #fff !important;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(196, 114, 138, .35) !important;
  opacity: 1 !important;
}
.wc-block-components-checkout-place-order-button:disabled,
.wp-block-woocommerce-checkout .wc-block-components-button:disabled,
.wc-block-cart__submit-button:disabled,
.wc-block-components-button.contained:disabled {
  opacity: .6 !important;
  cursor: not-allowed !important;
  transform: none !important;
}
/* El texto interno del botón block-based va en un span.wc-block-components-button__text */
.wc-block-components-checkout-place-order-button .wc-block-components-button__text,
.wp-block-woocommerce-checkout .wc-block-components-button .wc-block-components-button__text,
.wc-block-cart__submit-button .wc-block-components-button__text {
  color: #fff !important;
  font-weight: 600 !important;
}

/* ── BLOCKUI AJAX ────────────────────────────────────────────── */
.blockUI.blockOverlay { opacity: .15 !important; background: #fff !important; z-index: 999 !important; top: 64px !important; }
@media (min-width: 768px) { .blockUI.blockOverlay { top: 72px !important; } }

/* ── THANK YOU PAGE ──────────────────────────────────────────── */
.woocommerce-order { font-family: 'Poppins', sans-serif; }
.woocommerce-order-overview { list-style: none; display: flex; flex-wrap: wrap; gap: 16px; background: var(--pink-soft); border-radius: 16px; padding: 20px; margin-bottom: 24px; }
.woocommerce-order-overview li { font-size: 14px; color: var(--mid); }
.woocommerce-order-overview li strong { color: var(--dark); display: block; font-size: 16px; }
.woocommerce-thankyou-order-received { background: #f1f8e9; border: 1px solid #c5e1a5; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; font-size: 15px; color: #2e7d32; }
.woocommerce-order-details h2,
.woocommerce-customer-details h2,
.bgmg-chile-order-extra h2,
.bgmg-chile-order-tracking h2 { font-family: 'Alice', serif; font-size: 22px; font-weight: 600; color: var(--dark); margin: 24px 0 16px; }

/* Separación visual clara entre los bloques que bgmg-chile inyecta en la
   thank-you (RUT/factura, tracking, retiro). Sin esto pueden verse pegados. */
.bgmg-chile-order-extra,
.bgmg-chile-order-tracking,
.bgmg-chile-retiro-public,
.bgmg-chile-factura-public,
.bgmg-chile-tracking-public { margin-top: 32px !important; }
.bgmg-chile-order-extra + .bgmg-chile-order-tracking,
.bgmg-chile-order-tracking + .bgmg-chile-order-extra { margin-top: 24px !important; }
.woocommerce-table--order-details { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
.woocommerce-table--order-details th, .woocommerce-table--order-details td { padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
</style>
</head>
<body <?php body_class('bgmg-checkout-page'); ?>>
<?php wp_body_open(); ?>

<?php
$logo_id = get_theme_mod('custom_logo');
$cart    = WC()->cart;
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/tienda');
?>

<?php bgmg_render_header(['show_nav' => false, 'show_search' => false]); ?>

<?php /* Mini-cart panel: ahora lo rinde bgmg_render_header() (BL-01c). */ ?>

<!-- CHECKOUT -->
<div class="bgmg-co-wrap">

  <?php // Breadcrumb solo en thank-you (order-received). En el checkout
        // principal se muestra el botón "Volver al carrito" en su lugar. ?>
  <?php if (is_order_received_page()) : ?>
    <nav class="bgmg-breadcrumb" aria-label="Navegación">
      <a href="<?php echo esc_url(home_url('/')); ?>">Inicio</a>
      <span>›</span>
      <a href="<?php echo esc_url($shop_url); ?>">Tienda</a>
      <span>›</span>
      <a href="<?php echo esc_url(wc_get_checkout_url()); ?>">Finalizar compra</a>
      <span>›</span>
      <strong aria-current="page">Pedido recibido</strong>
    </nav>
  <?php endif; ?>

  <?php if (!is_order_received_page()) : ?>
    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="bgmg-co-back">
      ← Volver al carrito
    </a>
  <?php endif; ?>

  <?php if (is_order_received_page()) : ?>
    <h1 class="bgmg-co-title">Pedido recibido</h1>
  <?php else : ?>
    <h1 class="bgmg-co-title">Finalizar compra</h1>
  <?php endif; ?>

  <?php echo do_shortcode('[woocommerce_checkout]'); ?>

</div>

<?php /* Abrir/cerrar minicart + fragment refresh: ahora GLOBALES (bgmg-header-ui-js en bgmg-landing.php, BL-01c Fase 2). El checkout no tiene lupa (show_search=false). */ ?>

<?php wp_footer(); ?>
</body>
</html>
