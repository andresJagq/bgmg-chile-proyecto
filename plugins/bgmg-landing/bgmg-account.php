<?php
defined('ABSPATH') || exit;
// Forzar que esta página nunca se cachee (evita servir sesión de otro usuario)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-LiteSpeed-Cache-Control: no-cache');
?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php woocommerce_page_title(); ?> — BeautyGirlMG</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style>
/* ── RESET + BASE ─────────────────────────────────────────── */
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
body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--dark); }

/* ── LAYOUT ───────────────────────────────────────────────── */
.bgmg-account-wrap {
  min-height: 100vh;
  padding-top: 80px;
  padding-bottom: 80px;
}
.bgmg-account-inner {
  max-width: 960px;
  margin: 0 auto;
  padding: 0 20px;
}
.bgmg-account-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 32px;
  font-weight: 600;
  color: var(--dark);
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}

/* Notices y botones WC — estilos base en bgmg-landing.php (global).
   Aquí solo override específico de la página de cuenta. */
.woocommerce-notices-wrapper { margin-bottom: 20px; }

/* ── FORMULARIO LOGIN / REGISTRO ─────────────────────────── */
/* WooCommerce ya maneja las columnas (float 48%/48%) — no tocamos el layout.
   Solo añadimos gap visual entre columnas en mobile */
@media (max-width: 767px) {
  .u-columns.col2-set .col-1 { margin-bottom: 24px; }
}
.woocommerce-form-login,
.woocommerce-form-register {
  background: #fff !important;
  border: 1px solid var(--border) !important;
  border-radius: 20px !important;
  padding: 32px 28px !important;
  box-shadow: 0 4px 24px rgba(26,16,21,.06) !important;
}
.woocommerce-form-login h2,
.woocommerce-form-register h2 {
  font-family: 'Cormorant Garamond', serif !important;
  font-size: 26px !important;
  font-weight: 600 !important;
  color: var(--dark) !important;
  margin-bottom: 20px !important;
}
.woocommerce-form-row { margin-bottom: 16px !important; }
.woocommerce-form-row label {
  display: block !important;
  font-family: 'DM Sans', sans-serif !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  color: var(--mid) !important;
  margin-bottom: 6px !important;
}
.woocommerce-Input--text,
.woocommerce-Input--password,
.woocommerce-Input--email,
input[type="text"].input-text,
input[type="password"].input-text,
input[type="email"].input-text,
input[type="tel"].input-text,
.bgmg-acc-main .woocommerce-address-fields select,
.bgmg-acc-main .woocommerce-EditAccountForm select {
  width: 100% !important;
  border: 1.5px solid var(--border) !important;
  border-radius: 10px !important;
  padding: 12px 14px !important;
  font-family: 'DM Sans', sans-serif !important;
  font-size: 15px !important;
  color: var(--dark) !important;
  background: var(--pink-soft) !important;
  outline: none !important;
  transition: border-color .2s !important;
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  appearance: none !important;
}
/* Chevron de los selects (porque desactivamos appearance) */
.bgmg-acc-main .woocommerce-address-fields select,
.bgmg-acc-main .woocommerce-EditAccountForm select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%237A5060' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
  background-repeat: no-repeat !important;
  background-position: right 16px center !important;
  background-size: 12px 8px !important;
  padding-right: 40px !important;
}
.woocommerce-Input--text:focus,
input[type="text"].input-text:focus,
input[type="password"].input-text:focus,
input[type="email"].input-text:focus,
input[type="tel"].input-text:focus,
.bgmg-acc-main .woocommerce-address-fields select:focus,
.bgmg-acc-main .woocommerce-EditAccountForm select:focus { border-color: var(--pink-dark) !important; background-color: #fff !important; }

.woocommerce-form__label-for-checkbox {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  font-size: 13px !important;
  color: var(--mid) !important;
  cursor: pointer !important;
}
/* Botones de formulario — full width */
button[name="login"],
button[name="register"] {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 100% !important;
  padding: 14px 24px !important;
  border-radius: 30px !important;
  border: none !important;
  background: var(--dark) !important;
  color: #fff !important;
  font-family: 'DM Sans', sans-serif !important;
  font-size: 15px !important;
  font-weight: 500 !important;
  cursor: pointer !important;
  transition: opacity .2s !important;
  margin-top: 8px !important;
}
/* Botones inline — base en bgmg-landing.php. Solo override cuenta: sin margin-top */
.woocommerce-EditAccountForm .woocommerce-Button,
.woocommerce-address-fields .woocommerce-Button { margin-top: 16px !important; }

.woocommerce-LostPassword { margin-top: 14px !important; text-align: center !important; }
.woocommerce-LostPassword a {
  font-family: 'DM Sans', sans-serif !important;
  font-size: 13px !important;
  color: var(--pink-dark) !important;
  text-decoration: none !important;
}
.woocommerce-LostPassword a:hover { text-decoration: underline !important; }

/* ── LAYOUT LOGGED-IN: sidebar + contenido ───────────────── */
.woocommerce-MyAccount-navigation {
  margin-bottom: 20px;
}
.woocommerce-MyAccount-navigation ul {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  list-style: none;
  padding: 0;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.woocommerce-MyAccount-navigation ul::-webkit-scrollbar { display: none; }
.woocommerce-MyAccount-navigation ul li a {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 30px;
  border: 1.5px solid var(--border);
  background: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  color: var(--mid);
  text-decoration: none;
  white-space: nowrap;
  transition: background .15s, border-color .15s, color .15s;
}
.woocommerce-MyAccount-navigation ul li a:hover {
  background: var(--pink-soft);
  border-color: var(--pink);
  color: var(--dark);
}
.woocommerce-MyAccount-navigation ul li.is-active a,
.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--active a {
  background: var(--dark);
  border-color: var(--dark);
  color: #fff;
}
.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--customer-logout a {
  border-color: var(--border);
  color: #b91c1c;
}
.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--customer-logout a:hover {
  background: #fff0f0;
  border-color: #fca5a5;
}

/* ── CONTENIDO CUENTA ────────────────────────────────────── */
.woocommerce-MyAccount-content {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 28px 24px;
}
.woocommerce-MyAccount-content p {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--mid);
  line-height: 1.6;
  margin-bottom: 12px;
}
.woocommerce-MyAccount-content h2,
.woocommerce-MyAccount-content h3 {
  font-family: 'Cormorant Garamond', serif;
  font-size: 22px;
  font-weight: 600;
  color: var(--dark);
  margin-bottom: 16px;
}
.woocommerce-MyAccount-content a { color: var(--pink-dark); text-decoration: none; }
.woocommerce-MyAccount-content a:hover { text-decoration: underline; }

/* ── TABLA DE ÓRDENES ─────────────────────────────────────── */
.woocommerce-orders-table {
  width: 100%;
  border-collapse: collapse;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  overflow: hidden;
}
.woocommerce-orders-table thead th {
  padding: 10px 14px;
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  color: var(--mid);
  text-transform: uppercase;
  letter-spacing: .05em;
  border-bottom: 1px solid var(--border);
  background: var(--pink-soft);
}
.woocommerce-orders-table tbody tr {
  border-bottom: 1px solid var(--border);
  transition: background .15s;
}
.woocommerce-orders-table tbody tr:last-child { border-bottom: none; }
.woocommerce-orders-table tbody tr:hover { background: var(--pink-soft); }
.woocommerce-orders-table td { padding: 14px; color: var(--dark); }
.woocommerce-orders-table .woocommerce-orders-table__cell-order-actions a {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 20px;
  background: var(--pink-soft);
  border: 1px solid var(--pink);
  font-size: 12px;
  font-weight: 500;
  color: var(--pink-dark);
  text-decoration: none;
  transition: background .15s;
}
.woocommerce-orders-table .woocommerce-orders-table__cell-order-actions a:hover { background: var(--pink); color: var(--dark); }
.wc-order-item-name { font-weight: 500; color: var(--dark); }

/* Mark: estado del pedido */
.woocommerce-order-status {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
mark.order-status { background: none !important; }
mark.order-status.status-completed   { color: #166534; background: #dcfce7 !important; border-radius: 20px; padding: 3px 10px; }
mark.order-status.status-processing  { color: #1e40af; background: #dbeafe !important; border-radius: 20px; padding: 3px 10px; }
mark.order-status.status-pending     { color: var(--mid); background: var(--pink-soft) !important; border-radius: 20px; padding: 3px 10px; }
mark.order-status.status-cancelled   { color: #b91c1c; background: #fee2e2 !important; border-radius: 20px; padding: 3px 10px; }
mark.order-status.status-on-hold     { color: #92400e; background: #fef3c7 !important; border-radius: 20px; padding: 3px 10px; }

/* ── FORMULARIO EDITAR CUENTA / DIRECCIÓN ────────────────── */
.woocommerce-EditAccountForm fieldset,
.woocommerce-address-fields fieldset {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 20px;
}
.woocommerce-EditAccountForm fieldset legend,
.woocommerce-address-fields fieldset legend {
  font-family: 'Cormorant Garamond', serif;
  font-size: 18px;
  font-weight: 600;
  color: var(--dark);
  padding: 0 8px;
}
.woocommerce-EditAccountForm .form-row,
.woocommerce-address-fields .form-row,
.woocommerce-account .form-row { margin-bottom: 14px !important; }
.woocommerce-EditAccountForm label,
.woocommerce-address-fields label {
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 500;
  color: var(--mid);
  display: block;
  margin-bottom: 5px;
}
.woocommerce-EditAccountForm .woocommerce-Button,
.woocommerce-address-fields .woocommerce-Button,
.bgmg-acc-main button[name="save_address"],
.bgmg-acc-main button[name="save_account_details"] {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: auto !important;
  padding: 12px 32px !important;
  border-radius: 30px !important;
  border: none !important;
  background: var(--dark) !important;
  color: #fff !important;
  font-family: 'DM Sans', sans-serif !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  cursor: pointer !important;
  transition: background .2s, transform .15s !important;
}
.woocommerce-EditAccountForm .woocommerce-Button:hover,
.woocommerce-address-fields .woocommerce-Button:hover,
.bgmg-acc-main button[name="save_address"]:hover,
.bgmg-acc-main button[name="save_account_details"]:hover {
  background: var(--pink-dark) !important;
  transform: translateY(-1px) !important;
}

/* ── DETALLES DE ORDEN ────────────────────────────────────── */
.woocommerce-order-details h2,
.woocommerce-customer-details h2 {
  font-family: 'Cormorant Garamond', serif !important;
  font-size: 20px !important;
  font-weight: 600 !important;
  color: var(--dark) !important;
  margin: 20px 0 12px !important;
}
.woocommerce-table--order-details {
  width: 100%;
  border-collapse: collapse;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
}
.woocommerce-table--order-details th,
.woocommerce-table--order-details td {
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
}
.woocommerce-table--order-details tfoot tr:last-child td,
.woocommerce-table--order-details tfoot tr:last-child th {
  font-weight: 600;
  color: var(--dark);
  border-bottom: none;
}
.woocommerce-order-overview {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  list-style: none;
  padding: 0;
  margin-bottom: 20px !important;
}
.woocommerce-order-overview li {
  flex: 1;
  min-width: 120px;
  background: var(--pink-soft);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px 16px;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  color: var(--mid);
}
.woocommerce-order-overview li strong {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--dark);
  margin-top: 4px;
}
.woocommerce-column--billing-address address,
.woocommerce-column--shipping-address address {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--mid);
  line-height: 1.7;
  font-style: normal;
}

/* ── DESKTOP: nav lateral ────────────────────────────────── */
@media (min-width: 768px) {
  .bgmg-account-wrap {
    padding-top: 96px;
    padding-bottom: 96px;
  }
  .bgmg-account-title { font-size: 40px; margin-bottom: 32px; }
  /* WooCommerce ya posiciona nav (30%) y content (68%) con float — no interferir */
  .woocommerce-MyAccount-navigation { margin-bottom: 0; }
  .woocommerce-MyAccount-navigation ul {
    flex-direction: column;
    gap: 4px;
    flex-wrap: nowrap;
    overflow: visible;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 8px;
  }
  .woocommerce-MyAccount-navigation ul li a {
    display: block;
    border-radius: 10px;
    border: none;
    padding: 10px 14px;
    font-size: 14px;
  }
  .woocommerce-MyAccount-navigation ul li.is-active a,
  .woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--active a {
    background: var(--pink-soft);
    color: var(--pink-dark);
    border: none;
  }
}

/* ═══════════════════════════════════════════════════════════════════
   DISEÑO CUSTOM "MI CUENTA" — mobile-first
   Reemplaza el HTML del shortcode con cards + nav responsive.
   ═══════════════════════════════════════════════════════════════════ */

/* ── Layout ───────────────────────────────────────────────────── */
.bgmg-acc-layout { display: flex; flex-direction: column; gap: 16px; }
.bgmg-acc-main { min-width: 0; }

/* ── NAVEGACIÓN (mobile = tira horizontal, desktop = sidebar) ─── */
.bgmg-acc-nav {
  margin: 0 -20px;
  padding: 0 20px;
  background: #fff;
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 64px;
  z-index: 50;
}
.bgmg-acc-nav-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  gap: 4px;
  overflow-x: auto;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}
.bgmg-acc-nav-list::-webkit-scrollbar { display: none; }
.bgmg-acc-nav-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 14px 12px;
  white-space: nowrap;
  color: var(--mid);
  text-decoration: none;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  border-bottom: 2px solid transparent;
  transition: color .2s, border-color .2s;
}
.bgmg-acc-nav-link:hover { color: var(--dark); }
.bgmg-acc-nav-link.is-active {
  color: var(--pink-dark);
  border-bottom-color: var(--pink-dark);
  font-weight: 600;
}
.bgmg-acc-icon {
  display: inline-flex;
  width: 18px;
  height: 18px;
}
.bgmg-acc-icon svg { width: 100%; height: 100%; }
.bgmg-acc-nav-logout { margin-left: auto; }
.bgmg-acc-nav-logout .bgmg-acc-nav-link { color: var(--mid); }
.bgmg-acc-nav-logout .bgmg-acc-nav-link:hover { color: #c0392b; }

/* ── Hero saludo (dashboard) ─────────────────────────────────── */
.bgmg-acc-hero { margin-bottom: 20px; padding: 8px 0 4px; }
.bgmg-acc-hello {
  font-family: 'Cormorant Garamond', serif;
  font-size: 30px;
  font-weight: 400;
  color: var(--dark);
  margin: 0 0 4px;
  line-height: 1.1;
}
.bgmg-acc-hello-sub {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--mid);
  margin: 0;
}

/* ── Headers de página secundaria ────────────────────────────── */
.bgmg-acc-page-head { margin-bottom: 20px; }
.bgmg-acc-page-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 28px;
  font-weight: 400;
  color: var(--dark);
  margin: 0 0 4px;
}
.bgmg-acc-page-sub {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--mid);
  margin: 0;
}
.bgmg-acc-back {
  display: inline-block;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--mid);
  text-decoration: none;
  margin-bottom: 10px;
  transition: color .2s;
}
.bgmg-acc-back:hover { color: var(--pink-dark); }

/* ── GRID de cards (dashboard) ───────────────────────────────── */
.bgmg-acc-cards-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

/* ── Card base ────────────────────────────────────────────────── */
.bgmg-acc-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 18px 18px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: border-color .2s, box-shadow .2s, transform .2s;
}
.bgmg-acc-card:hover {
  border-color: var(--pink);
  box-shadow: 0 4px 16px rgba(196, 114, 138, .08);
}
.bgmg-acc-card-head {
  display: flex;
  align-items: center;
  gap: 10px;
}
.bgmg-acc-card-icon {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: var(--pink-soft);
  color: var(--pink-dark);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.bgmg-acc-card-icon svg { width: 16px; height: 16px; }
.bgmg-acc-card-title {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: var(--dark);
  margin: 0;
}
.bgmg-acc-card-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.bgmg-acc-card-text {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--dark);
  line-height: 1.5;
  margin: 0;
}
.bgmg-acc-mid { color: var(--mid); font-size: 13px; }
.bgmg-acc-card-link {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--pink-dark);
  text-decoration: none;
  font-weight: 500;
  transition: opacity .2s;
}
.bgmg-acc-card-link:hover { opacity: .8; text-decoration: underline; }

/* Card destacada (último pedido) — fondo soft */
.bgmg-acc-card-feat {
  background: linear-gradient(135deg, var(--pink-soft) 0%, #fff 100%);
  border-color: var(--pink);
}

/* Card de stat (número grande) */
.bgmg-acc-card-stat {
  align-items: flex-start;
  background: var(--dark);
  color: #fff;
  border-color: var(--dark);
}
.bgmg-acc-card-stat:hover { box-shadow: 0 6px 20px rgba(26, 16, 21, .25); }
.bgmg-acc-stat-number {
  font-family: 'Cormorant Garamond', serif;
  font-size: 48px;
  font-weight: 600;
  line-height: 1;
  color: var(--pink);
}
.bgmg-acc-stat-label {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: rgba(255, 255, 255, .75);
  margin-top: 4px;
}
.bgmg-acc-card-stat .bgmg-acc-card-link {
  color: var(--pink);
  margin-top: auto;
  padding-top: 6px;
}
.bgmg-acc-card-stat .bgmg-acc-card-link:hover { color: #fff; }

/* ── Botones ─────────────────────────────────────────────────── */
.bgmg-acc-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 18px;
  border-radius: 30px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: background .2s, color .2s, border-color .2s, transform .15s;
  border: 1.5px solid transparent;
  align-self: flex-start;
}
.bgmg-acc-btn-primary {
  background: var(--dark);
  color: #fff;
}
.bgmg-acc-btn-primary:hover {
  background: var(--pink-dark);
  color: #fff;
  transform: translateY(-1px);
}
.bgmg-acc-btn-ghost {
  background: transparent;
  color: var(--dark);
  border-color: var(--border);
}
.bgmg-acc-btn-ghost:hover {
  border-color: var(--pink);
  background: var(--pink-soft);
  color: var(--pink-dark);
}

/* ── Pedido mini (dashboard) ─────────────────────────────────── */
.bgmg-acc-order-mini {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.bgmg-acc-order-num {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: var(--dark);
}
.bgmg-acc-order-meta {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--mid);
}
.bgmg-acc-order-total {
  font-size: 16px;
  font-weight: 600;
  color: var(--pink-dark);
}

/* ── Badges de estado ─────────────────────────────────────────── */
.bgmg-acc-order-status {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 20px;
  font-family: 'DM Sans', sans-serif;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .4px;
  white-space: nowrap;
}
.bgmg-status-pending    { background: #fff8e1; color: #b87800; }
.bgmg-status-processing { background: #fff3e0; color: #d84315; }
.bgmg-status-on-hold    { background: #ede7f6; color: #5e35b1; }
.bgmg-status-completed  { background: #e8f5e9; color: #2e7d32; }
.bgmg-status-cancelled  { background: #fbe9e7; color: #c62828; }
.bgmg-status-refunded   { background: #f5f5f5; color: #616161; }
.bgmg-status-failed     { background: #fbe9e7; color: #c62828; }

/* ── Lista de pedidos ────────────────────────────────────────── */
.bgmg-acc-orders-list { display: flex; flex-direction: column; gap: 12px; }
.bgmg-acc-order-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 16px;
  transition: border-color .2s, box-shadow .2s;
}
.bgmg-acc-order-card:hover {
  border-color: var(--pink);
  box-shadow: 0 4px 16px rgba(196, 114, 138, .08);
}
.bgmg-acc-order-card-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}
.bgmg-acc-order-card-head > div { display: flex; flex-direction: column; gap: 4px; }
.bgmg-acc-order-date {
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  color: var(--mid);
}
.bgmg-acc-order-card-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  padding-top: 12px;
  border-top: 1px solid var(--pink-soft);
}
.bgmg-acc-order-summary {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--mid);
}
.bgmg-acc-order-card-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

/* ── Paginación ───────────────────────────────────────────────── */
.bgmg-acc-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 20px;
  flex-wrap: wrap;
}
.bgmg-acc-pagination-info {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--mid);
}

/* ── Detalle de pedido ───────────────────────────────────────── */
.bgmg-acc-order-status-banner {
  padding: 12px 16px;
  border-radius: 12px;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  margin-bottom: 24px;
  border: 1px solid currentColor;
}
.bgmg-acc-order-status-banner.bgmg-status-pending    { background: #fff8e1; color: #b87800; border-color: #ffd180; }
.bgmg-acc-order-status-banner.bgmg-status-processing { background: #fff3e0; color: #d84315; border-color: #ffccbc; }
.bgmg-acc-order-status-banner.bgmg-status-on-hold    { background: #ede7f6; color: #5e35b1; border-color: #d1c4e9; }
.bgmg-acc-order-status-banner.bgmg-status-completed  { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
.bgmg-acc-order-status-banner.bgmg-status-cancelled,
.bgmg-acc-order-status-banner.bgmg-status-failed     { background: #fbe9e7; color: #c62828; border-color: #ffcdd2; }

.bgmg-acc-section {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
}
.bgmg-acc-section-title {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .8px;
  color: var(--mid);
  margin: 0 0 16px;
}

/* Items del pedido */
.bgmg-acc-items-list { display: flex; flex-direction: column; gap: 12px; }
.bgmg-acc-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  background: var(--pink-soft);
  border-radius: 12px;
}
.bgmg-acc-item-img-link { flex-shrink: 0; }
.bgmg-acc-item-img {
  width: 56px;
  height: 56px;
  border-radius: 10px;
  object-fit: cover;
  display: block;
}
.bgmg-acc-item-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.bgmg-acc-item-name {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  color: var(--dark);
  text-decoration: none;
  line-height: 1.3;
}
.bgmg-acc-item-name:hover { color: var(--pink-dark); }
.bgmg-acc-item-qty { font-size: 12px; color: var(--mid); }
.bgmg-acc-item-price {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: var(--dark);
  white-space: nowrap;
}

/* Totales */
.bgmg-acc-totals { display: flex; flex-direction: column; gap: 6px; }
.bgmg-acc-total-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--dark);
  padding: 4px 0;
}
.bgmg-acc-total-row.is-grand {
  font-size: 17px;
  font-weight: 600;
  border-top: 1px solid var(--border);
  padding-top: 12px;
  margin-top: 6px;
  color: var(--pink-dark);
}

/* Direcciones (cards y address text) */
.bgmg-acc-addresses-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}
.bgmg-acc-address-card {
  padding: 14px;
  background: var(--pink-soft);
  border-radius: 12px;
}
.bgmg-acc-address-label {
  font-family: 'DM Sans', sans-serif;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--pink-dark);
  margin-bottom: 8px;
}
.bgmg-acc-address-card address,
.bgmg-acc-address-text {
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--dark);
  font-style: normal;
  line-height: 1.6;
}

/* Empty states */
.bgmg-acc-empty {
  background: #fff;
  border: 1px dashed var(--border);
  border-radius: 16px;
  padding: 40px 20px;
  text-align: center;
}
.bgmg-acc-empty-icon { font-size: 48px; margin-bottom: 12px; }
.bgmg-acc-empty-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 22px;
  color: var(--dark);
  margin: 0 0 8px;
}
.bgmg-acc-empty-text {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--mid);
  margin: 0 0 18px;
  line-height: 1.5;
}

/* Login / registro (cuando no está logueado) */
.bgmg-acc-login-wrap {
  padding: 20px 0;
  max-width: 960px;
  margin: 0 auto;
}
.bgmg-acc-login-card {
  background: transparent;
  padding: 0;
}
.bgmg-acc-login-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 32px;
  color: var(--dark);
  margin: 0 0 4px;
  text-align: center;
}
.bgmg-acc-login-sub {
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--mid);
  margin: 0 0 28px;
  text-align: center;
}
/* En mobile, dejar las 2 columnas apiladas con buen espaciado */
@media (max-width: 767px) {
  .bgmg-acc-login-title { font-size: 26px; }
  .bgmg-acc-login-wrap .u-columns .col-1,
  .bgmg-acc-login-wrap .u-columns .col-2 { width: 100% !important; float: none !important; margin-bottom: 16px; }
}

/* === Tabs Login / Crear cuenta === */
.bgmg-acc-auth-tabs {
  display: flex;
  gap: 4px;
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 4px;
  max-width: 440px;
  margin: 0 auto 28px;
  box-shadow: 0 2px 12px rgba(26,16,21,.05);
}
.bgmg-acc-auth-tab {
  flex: 1;
  background: transparent;
  border: 0;
  padding: 12px 16px;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  font-weight: 500;
  color: var(--mid);
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
}
.bgmg-acc-auth-tab:hover { color: var(--dark); background: var(--pink-soft); }
.bgmg-acc-auth-tab.is-active {
  background: var(--pink-dark);
  color: #fff;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(196,114,138,.3);
}
.bgmg-acc-auth-tab.is-active:hover { color: #fff; background: var(--pink-dark); }

/* Mostrar solo la columna correspondiente al tab activo */
.bgmg-acc-auth-body[data-active-tab="login"] .u-columns .col-2,
.bgmg-acc-auth-body[data-active-tab="register"] .u-columns .col-1 {
  display: none !important;
}
.bgmg-acc-auth-body[data-active-tab="login"] .u-columns .col-1,
.bgmg-acc-auth-body[data-active-tab="register"] .u-columns .col-2 {
  width: 100% !important;
  float: none !important;
  max-width: 480px;
  margin: 0 auto !important;
}

/* Ocultar los h2 nativos ("Acceder" / "Registrarse") porque ya tenemos los tabs */
.bgmg-acc-auth-body .u-columns .col-1 > h2,
.bgmg-acc-auth-body .u-columns .col-2 > h2 {
  display: none;
}

/* Link "¿No tienes cuenta? Crea una" debajo de cada form */
.bgmg-acc-auth-switch {
  text-align: center;
  margin-top: 16px;
  font-size: 14px;
  color: var(--mid);
}
.bgmg-acc-auth-switch button {
  background: none;
  border: 0;
  padding: 0;
  color: var(--pink-dark);
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  font-size: inherit;
  font-family: inherit;
}
.bgmg-acc-auth-switch button:hover { color: var(--dark); }

/* Form wrapper para edit-account */
.bgmg-acc-form-wrap {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 24px;
}

/* ── DESKTOP ≥ 768px: sidebar + grid 2 columnas ───────────────── */
@media (min-width: 768px) {
  .bgmg-acc-layout {
    flex-direction: row;
    gap: 32px;
    align-items: flex-start;
  }
  .bgmg-acc-nav {
    width: 220px;
    flex-shrink: 0;
    background: transparent;
    border-bottom: none;
    border-right: 1px solid var(--border);
    padding: 8px 20px 8px 0;
    margin: 0;
    position: sticky;
    top: 96px;
  }
  .bgmg-acc-nav-list {
    flex-direction: column;
    gap: 2px;
    overflow: visible;
  }
  .bgmg-acc-nav-link {
    padding: 10px 14px;
    border-bottom: none;
    border-left: 3px solid transparent;
    border-radius: 0 10px 10px 0;
    width: 100%;
  }
  .bgmg-acc-nav-link.is-active {
    background: var(--pink-soft);
    border-bottom: none;
    border-left-color: var(--pink-dark);
  }
  .bgmg-acc-nav-logout { margin-top: auto; margin-left: 0; padding-top: 16px; }
  .bgmg-acc-main { flex: 1; min-width: 0; }
  .bgmg-acc-hello { font-size: 36px; }
  .bgmg-acc-page-title { font-size: 32px; }

  .bgmg-acc-cards-grid {
    grid-template-columns: 2fr 1fr;
    gap: 16px;
  }
  /* Card destacada del dashboard ocupa toda la 1ra columna */
  .bgmg-acc-card-feat { grid-row: span 2; }

  .bgmg-acc-addresses-grid {
    grid-template-columns: 1fr 1fr;
  }
}

/* ── MINI CART ── CSS estructural movido a assets/bgmg-global.css (BL-01c:
   antes duplicado inline en los 7 templates, incluido este). ── */
</style>
</head>
<body <?php body_class('bgmg-account-page'); ?>>
<?php wp_body_open(); ?>

<?php bgmg_render_header(); ?>

<?php /* Mini-cart panel: ahora lo rinde bgmg_render_header() (BL-01c). */ ?>

<div class="bgmg-account-wrap">
  <div class="bgmg-account-inner">
    <?php
    // Render completamente custom (inc/account-renders.php).
    // Reemplaza el shortcode [woocommerce_my_account] con cards visuales,
    // sidebar / tabs responsive y vistas dedicadas por endpoint.
    if ( function_exists( 'bgmg_account_render' ) ) {
        bgmg_account_render();
    } else {
        // Fallback al shortcode si el módulo no está cargado
        echo do_shortcode( '[woocommerce_my_account]' );
    }
    ?>
  </div>
</div>

<?php /* Lupa + abrir/cerrar minicart: ahora GLOBALES (bgmg-header-ui-js en bgmg-landing.php, BL-01c Fase 2). */ ?>
<?php bgmg_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
