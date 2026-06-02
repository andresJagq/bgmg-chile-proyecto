<?php
/* Template Name: BGMG Landing */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BeautyGirlMG — Tu rutina de belleza natural</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
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
body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--dark); }

/* ── SECCIÓN ────────────────────────────────────────────────── */
.bgmg-sec       { padding: 48px 20px; }
.bgmg-sec-inner { max-width: 1100px; margin: 0 auto; }
.bgmg-sec-label {
  display: inline-block;
  font-family: 'DM Sans', sans-serif;
  font-size: 11px; font-weight: 500;
  text-transform: uppercase; letter-spacing: 2px;
  color: var(--pink-dark); background: var(--pink-soft);
  padding: 5px 14px; border-radius: 30px; margin-bottom: 10px;
}
.bgmg-sec-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 32px; font-weight: 400;
  color: var(--dark); line-height: 1.2; margin: 4px 0 24px;
}

/* ── HERO SLIDER ─────────────────────────────────────────────── */
.bgmg-hero-slider { margin-top: 64px; position: relative; }
.bgmg-hero-slider .swiper-slide {
  min-height: 520px; display: flex; align-items: center;
  position: relative; background-size: cover; background-position: center; overflow: hidden;
}
/* ── Fondos de cada slide
   Para usar imagen real: cambia el background-image de cada clase
   Ejemplo: .bgmg-slide-1 { background-image: url('https://tu-sitio.cl/wp-content/uploads/tu-imagen.jpg'); }
   ────────────────────────────────────────────────────────────── */
.bgmg-slide-1 { background: linear-gradient(160deg, #fce8ee 0%, #f9d5e0 55%, #f2c4ce 100%); }
.bgmg-slide-2 { background: linear-gradient(160deg, #f0e8fc 0%, #e2d5f9 55%, #d4c4f2 100%); }
.bgmg-slide-3 { background: linear-gradient(160deg, #e8fce8 0%, #d5f9d5 55%, #c4f2c4 100%); }
/* Overlay oscuro — activar cuando uses imagen real cambiando display:none a block */
.bgmg-slide-overlay {
  display: none; position: absolute; inset: 0;
  background: rgba(26,16,21,.45); z-index: 0;
}
.bgmg-slide-badge {
  position: absolute; top: 22px; right: 20px; z-index: 2;
  background: rgba(255,255,255,.92); color: var(--dark);
  font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 500;
  padding: 8px 16px; border-radius: 30px; border: 1px solid var(--border);
  animation: bgmg-float 3s ease-in-out infinite; white-space: nowrap;
}
.bgmg-slide-inner {
  max-width: 1100px; margin: 0 auto; padding: 64px 20px 72px;
  display: flex; align-items: center; gap: 48px; width: 100%; position: relative; z-index: 1;
}
.bgmg-slide-text { flex: 1; }
.bgmg-slide-label {
  display: inline-block; font-family: 'DM Sans', sans-serif;
  font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 2px;
  color: var(--pink-dark); background: rgba(255,255,255,.75);
  padding: 5px 14px; border-radius: 30px; margin-bottom: 14px;
}
.bgmg-slide-title {
  font-family: 'Cormorant Garamond', serif; font-size: 44px; font-weight: 400;
  color: var(--dark); line-height: 1.15; margin-bottom: 16px;
}
.bgmg-slide-title em { font-style: italic; color: var(--pink-dark); }
.bgmg-slide-sub {
  font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 300;
  color: var(--mid); line-height: 1.65; margin-bottom: 28px; max-width: 480px;
}
.bgmg-slide-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 28px; }
.bgmg-slide-pills span {
  background: rgba(255,255,255,.72); border: 1px solid var(--border);
  border-radius: 20px; padding: 6px 14px;
  font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--mid);
}
.bgmg-slide-cta {
  display: inline-block; background: var(--dark); color: #fff;
  padding: 16px 36px; border-radius: 30px; text-decoration: none;
  font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 500;
  transition: opacity .2s, transform .2s;
}
.bgmg-slide-cta:hover { opacity: .85; transform: translateY(-1px); }
.bgmg-slide-visual { display: none; }
.bgmg-slide-emoji { font-size: 140px; display: block; line-height: 1; }
/* Swiper controls */
.bgmg-hero-slider .swiper-pagination { bottom: 20px; }
.bgmg-hero-slider .swiper-pagination-bullet {
  width: 8px; height: 8px; background: var(--pink-dark); opacity: .35; transition: all .3s;
}
.bgmg-hero-slider .swiper-pagination-bullet-active {
  opacity: 1; width: 24px; border-radius: 4px;
}
.bgmg-hero-slider .swiper-button-prev,
.bgmg-hero-slider .swiper-button-next {
  width: 40px; height: 40px; border-radius: 50%;
  background: rgba(255,255,255,.85); color: var(--dark);
  --swiper-navigation-size: 15px; transition: background .2s;
}
.bgmg-hero-slider .swiper-button-prev:hover,
.bgmg-hero-slider .swiper-button-next:hover { background: #fff; }

/* ── CARD DE PRODUCTO ───────────────────────────────────────── */
.bgmg-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
.bgmg-card {
  display: flex; align-items: center; gap: 12px; padding: 12px;
  background: #fff; border: 1px solid var(--border); border-radius: 16px;
  position: relative; overflow: hidden;
}
.bgmg-card-img  { width: 80px; height: 80px; border-radius: 12px; flex-shrink: 0; object-fit: cover; }
.bgmg-card-link {
  display: flex; align-items: center; gap: 12px;
  flex: 1; min-width: 0; text-decoration: none; color: inherit;
}
.bgmg-card-link:hover .bgmg-card-name { color: var(--pink-dark); }
.bgmg-card-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.bgmg-badge {
  display: inline-block; font-size: 10px; font-weight: 500;
  color: var(--pink-dark); background: var(--pink-soft);
  padding: 2px 8px; border-radius: 20px; align-self: flex-start;
}
.bgmg-badge-oferta {
  display: inline-block; font-size: 10px; font-weight: 500;
  color: #fff; background: var(--pink-dark);
  padding: 2px 8px; border-radius: 20px; align-self: flex-start;
}
.bgmg-card-name {
  font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 400; color: var(--dark);
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden; line-height: 1.3;
}
.bgmg-card-price { font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; color: var(--dark); }
.bgmg-card-price del { font-size: 12px; color: var(--mid); margin-left: 4px; font-weight: 300; }
.bgmg-btn-add {
  width: 38px; height: 38px; border-radius: 50%;
  border: 1.5px solid var(--pink); background: #fff; color: var(--pink-dark);
  font-size: 22px; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; text-decoration: none; line-height: 1; transition: background .2s, color .2s;
}
.bgmg-btn-add:hover { background: var(--pink); color: #fff; }

/* ── CATEGORÍAS ─────────────────────────────────────────────── */
.bgmg-cats { display: flex; flex-wrap: wrap; gap: 10px; }
.bgmg-cat {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 18px; background: #fff; border: 1px solid var(--border);
  border-radius: 30px; text-decoration: none; color: var(--dark);
  font-family: 'DM Sans', sans-serif; font-size: 14px; transition: background .2s, border-color .2s;
}
.bgmg-cat:hover { background: var(--pink-soft); border-color: var(--pink); }
.bgmg-cat-icon  { font-size: 18px; }

/* ── TIPS ───────────────────────────────────────────────────── */
.bgmg-tips-scroll {
  display: flex; gap: 14px; overflow-x: auto; padding-bottom: 10px;
  scroll-snap-type: x mandatory; scrollbar-width: none; -webkit-overflow-scrolling: touch;
}
.bgmg-tips-scroll::-webkit-scrollbar { display: none; }
.bgmg-tips-card {
  flex: 0 0 264px; border-radius: 20px; padding: 28px 22px;
  display: flex; flex-direction: column; gap: 8px; scroll-snap-align: start;
  transition: transform .2s;
}
.bgmg-tips-card:hover { transform: translateY(-3px); }
.bgmg-tips-pink  { background: #fce8ee; }
.bgmg-tips-aqua  { background: #e0f5f0; }
.bgmg-tips-peach { background: #fdeede; }
.bgmg-tips-emoji { font-size: 38px; margin-bottom: 2px; }
.bgmg-tips-tag   { font-family: 'DM Sans', sans-serif; font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: 1.5px; color: var(--mid); }
.bgmg-tips-title { font-family: 'Cormorant Garamond', serif; font-size: 22px; font-weight: 600; color: var(--dark); line-height: 1.2; }
.bgmg-tips-desc  { font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--mid); line-height: 1.55; flex: 1; font-weight: 300; }
.bgmg-tips-link  { font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500; color: var(--pink-dark); text-decoration: none; margin-top: 4px; }

/* ── BANNER ─────────────────────────────────────────────────── */
.bgmg-banner {
  background: linear-gradient(135deg, var(--dark) 0%, #2d1a22 100%);
  border-radius: 20px; padding: 40px 28px; text-align: center; position: relative; overflow: hidden;
}
.bgmg-banner::before { content: ''; position: absolute; top: -40px; right: -40px; width: 160px; height: 160px; background: rgba(242,196,206,.12); border-radius: 50%; }
.bgmg-banner::after  { content: ''; position: absolute; bottom: -50px; left: -30px; width: 120px; height: 120px; background: rgba(242,196,206,.07); border-radius: 50%; }
.bgmg-banner-title { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 600; color: #fff; line-height: 1.2; margin-bottom: 8px; position: relative; }
.bgmg-banner-sub   { color: var(--pink); font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 300; margin-bottom: 24px; position: relative; }
.bgmg-banner-btn   { display: inline-block; background: var(--pink); color: var(--dark); padding: 14px 32px; border-radius: 30px; text-decoration: none; font-family: 'DM Sans', sans-serif; font-weight: 500; font-size: 15px; position: relative; transition: opacity .2s; }
.bgmg-banner-btn:hover { opacity: .88; }
/* Variante "is-image": el fondo se setea inline desde el Customizer */
.bgmg-banner.is-image { background: #1a1015; }
.bgmg-banner.is-image::before,
.bgmg-banner.is-image::after { display: none; }
.bgmg-banner-overlay {
  position: absolute; inset: 0;
  background: rgba(26, 16, 21, .55);
  z-index: 0;
}
.bgmg-banner-content { position: relative; z-index: 1; }
.bgmg-banner.is-image .bgmg-banner-title,
.bgmg-banner.is-image .bgmg-banner-sub { text-shadow: 0 2px 8px rgba(0, 0, 0, .35); }

/* ── TRUST STRIP ────────────────────────────────────────────── */
.bgmg-trust { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 32px 20px; background: #fff; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.bgmg-trust-item { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; }
.bgmg-trust-icon { font-size: 24px; }
.bgmg-trust-text { font-family: 'DM Sans', sans-serif; font-size: 12px; color: var(--mid); line-height: 1.4; }

/* ── TESTIMONIOS ────────────────────────────────────────────── */
.bgmg-testi-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
.bgmg-testi-card { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 24px; }
.bgmg-testi-stars  { color: var(--pink-dark); font-size: 16px; margin-bottom: 12px; letter-spacing: 2px; }
.bgmg-testi-text   { font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 18px; color: var(--dark); line-height: 1.55; margin-bottom: 18px; }
.bgmg-testi-author { display: flex; align-items: center; gap: 10px; }
.bgmg-testi-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--pink); color: var(--dark); font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 15px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.bgmg-testi-name   { font-family: 'DM Sans', sans-serif; font-weight: 500; font-size: 14px; color: var(--dark); }
.bgmg-testi-date   { font-family: 'DM Sans', sans-serif; font-size: 12px; color: var(--mid); font-weight: 300; }

/* ── STICKY CTA ─────────────────────────────────────────────── */
.bgmg-sticky { position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999; background: #fff; border-top: 1px solid var(--border); padding: 10px 16px 14px; display: flex; gap: 10px; box-shadow: 0 -4px 20px rgba(0,0,0,.06); }
.bgmg-sticky-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 13px 10px; border-radius: 30px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; text-decoration: none; transition: opacity .2s; }
.bgmg-sticky-btn:hover { opacity: .85; }
.bgmg-sticky-wa   { background: #25D366; color: #fff; }
.bgmg-sticky-shop { background: var(--dark); color: #fff; }

/* ── HEADER ─────────────────────────────────────────────────── */
.bgmg-header {
  position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
  background: #fff; border-bottom: 1px solid var(--border);
  height: 64px; box-shadow: 0 1px 12px rgba(0,0,0,.04);
}
.bgmg-header-inner {
  max-width: 1200px; margin: 0 auto; height: 100%; padding: 0 20px;
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
}
/* Logo */
.bgmg-logo-link { display: flex; align-items: center; text-decoration: none; flex-shrink: 0; }
.bgmg-logo-img  { height: 36px; width: auto; display: block; object-fit: contain; }
.bgmg-logo-text { font-family: 'Cormorant Garamond', serif; font-size: 21px; font-weight: 600; color: var(--dark); white-space: nowrap; }
.bgmg-logo-text em { font-style: italic; color: var(--pink-dark); }
/* Desktop nav */
.bgmg-dnav { display: none; align-items: center; gap: 2px; list-style: none; }
.bgmg-dnav > li { position: relative; }
.bgmg-dnav > li > a {
  display: flex; align-items: center; gap: 4px; padding: 8px 12px;
  font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--mid);
  text-decoration: none; border-radius: 8px; white-space: nowrap;
  transition: color .2s, background .2s;
}
.bgmg-dnav > li > a:hover,
.bgmg-dnav > li:hover > a { color: var(--dark); background: var(--pink-soft); }
/* Mega dropdown */
.bgmg-drop {
  display: none; position: absolute; top: 100%; left: 50%;
  transform: translateX(-50%);
  background: #fff; border: 1px solid var(--border); border-radius: 16px;
  padding: 24px 20px 20px; gap: 16px;
  box-shadow: 0 12px 40px rgba(0,0,0,.10);
  min-width: 480px; max-width: 700px; z-index: 200;
}
/* Puente invisible que cubre el hueco entre el link y el dropdown */
.bgmg-drop::before {
  content: ''; position: absolute;
  top: -12px; left: 0; right: 0; height: 12px;
}
.bgmg-dnav > li:hover .bgmg-drop { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
.bgmg-drop-col h4 {
  font-family: 'Cormorant Garamond', serif; font-size: 14px; font-weight: 600;
  color: var(--dark); margin-bottom: 6px; padding-bottom: 5px; border-bottom: 1px solid var(--border);
}
.bgmg-drop-col h4 a { text-decoration: none; color: inherit; }
.bgmg-drop-col h4 a:hover { color: var(--pink-dark); }
.bgmg-drop-col ul { list-style: none; display: flex; flex-direction: column; gap: 2px; }
.bgmg-drop-col ul li a {
  font-family: 'DM Sans', sans-serif; font-size: 12px; color: var(--mid);
  text-decoration: none; padding: 2px 0; display: block; transition: color .2s;
}
.bgmg-drop-col ul li a:hover { color: var(--pink-dark); }
/* Cart + hamburger */
.bgmg-header-right { display: flex; align-items: center; gap: 8px; }
.bgmg-cart-btn {
  position: relative; display: flex; align-items: center; justify-content: center;
  width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
  background: var(--pink-soft); border: none; cursor: pointer; font-size: 18px; transition: background .2s;
  touch-action: manipulation;
}
.bgmg-cart-btn:hover { background: var(--pink); }
.bgmg-cart-count {
  position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 4px;
  border-radius: 9px; background: var(--pink-dark); color: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 10px; font-weight: 600;
  display: flex; align-items: center; justify-content: center;
}
.bgmg-cart-count:empty { display: none; }
.bgmg-hamburger {
  display: flex; flex-direction: column; gap: 5px; flex-shrink: 0;
  cursor: pointer; padding: 6px; background: none; border: none;
}
.bgmg-hamburger span { display: block; width: 22px; height: 2px; background: var(--dark); border-radius: 2px; transition: all .3s; }
.bgmg-hamburger.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.bgmg-hamburger.is-open span:nth-child(2) { opacity: 0; }
.bgmg-hamburger.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
/* Mobile drawer */
.bgmg-mdrawer {
  display: none; position: fixed; top: 64px; left: 0; right: 0; bottom: 0;
  background: #fff; z-index: 999; overflow-y: auto; border-top: 1px solid var(--border);
}
.bgmg-mdrawer.is-open { display: block; }
.bgmg-mdrawer-link {
  display: block; padding: 14px 20px;
  font-family: 'DM Sans', sans-serif; font-size: 15px; color: var(--dark);
  text-decoration: none; border-bottom: 1px solid var(--pink-soft); transition: background .2s;
}
.bgmg-mdrawer-link:hover { background: var(--pink-soft); }
.bgmg-mob-toggle {
  display: flex; align-items: center; justify-content: space-between; width: 100%;
  padding: 14px 20px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500;
  color: var(--dark); background: none; border: none; border-bottom: 1px solid var(--pink-soft);
  cursor: pointer; text-align: left; transition: background .2s;
}
.bgmg-mob-toggle:hover { background: var(--pink-soft); }
.bgmg-mob-toggle .arr { transition: transform .3s; font-size: 11px; }
.bgmg-mob-toggle.is-open .arr { transform: rotate(180deg); }
.bgmg-mob-children { display: none; background: var(--pink-soft); }
.bgmg-mob-children.is-open { display: block; }
.bgmg-mob-children a {
  display: block; padding: 10px 20px 10px 36px;
  font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--mid);
  text-decoration: none; border-bottom: 1px solid rgba(242,196,206,.4); transition: color .2s;
}
.bgmg-mob-children a:hover { color: var(--dark); }
.bgmg-mob-children .bgmg-view-all { font-weight: 500; color: var(--pink-dark); }

/* ── BUSCADOR ────────────────────────────────────────────────── */
.bgmg-search-btn {
  width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
  background: var(--pink-soft); border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: var(--dark); transition: background .2s;
}
.bgmg-search-btn:hover { background: var(--pink); }
.bgmg-search-btn svg { stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; }
.bgmg-search-overlay {
  position: fixed; top: 64px; left: 0; right: 0; z-index: 998;
  background: #fff; border-bottom: 1px solid var(--border);
  padding: 14px 20px;
  transform: translateY(-110%); opacity: 0;
  transition: transform .3s cubic-bezier(.4,0,.2,1), opacity .3s;
  pointer-events: none; box-shadow: 0 8px 32px rgba(0,0,0,.08);
}
.bgmg-search-overlay.is-open { transform: translateY(0); opacity: 1; pointer-events: all; }
.bgmg-search-inner { max-width: 700px; margin: 0 auto; display: flex; align-items: center; gap: 12px; }
.bgmg-search-form {
  flex: 1; display: flex; align-items: center; gap: 8px;
  background: var(--pink-soft); border-radius: 30px; padding: 0 16px;
  border: 1.5px solid var(--border); transition: border-color .2s;
}
.bgmg-search-form:focus-within { border-color: var(--pink-dark); }
.bgmg-search-form svg { width: 16px; height: 16px; flex-shrink: 0; opacity: .5; stroke: var(--dark); fill: none; stroke-width: 2; stroke-linecap: round; }
.bgmg-search-form input[type="search"] {
  flex: 1; border: none; background: none; padding: 13px 0;
  font-family: 'DM Sans', sans-serif; font-size: 15px; color: var(--dark); outline: none;
  -webkit-appearance: none;
}
.bgmg-search-form input::placeholder { color: var(--mid); }
.bgmg-search-form input::-webkit-search-cancel-button { display: none; }
.bgmg-search-submit {
  background: var(--pink-dark); color: #fff; border: none;
  width: 34px; height: 34px; border-radius: 50%; cursor: pointer; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; transition: opacity .2s;
}
.bgmg-search-submit:hover { opacity: .85; }
.bgmg-search-submit svg { width: 15px; height: 15px; stroke: #fff; fill: none; stroke-width: 2.5; stroke-linecap: round; }
.bgmg-search-close {
  background: none; border: none; cursor: pointer; flex-shrink: 0;
  width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  color: var(--mid); transition: background .2s, color .2s;
}
.bgmg-search-close:hover { background: var(--pink-soft); color: var(--dark); }
.bgmg-search-close svg { width: 26px; height: 26px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; }
.bgmg-search-backdrop {
  display: none; position: fixed; inset: 0; z-index: 997; background: rgba(26,16,21,.2);
}
.bgmg-search-backdrop.is-open { display: block; }
/* Preview de resultados */
.bgmg-search-results-wrap { position: relative; flex: 1; display: flex; flex-direction: column; }
.bgmg-search-results {
  position: absolute; top: calc(100% + 8px); left: 0; right: 0;
  background: #fff; border: 1px solid var(--border); border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,.10); overflow: hidden; display: none; z-index: 10;
}
.bgmg-search-results.is-visible { display: block; }
.bgmg-search-result-item {
  display: flex; align-items: center; gap: 12px; padding: 10px 14px;
  text-decoration: none; color: var(--dark);
  border-bottom: 1px solid var(--pink-soft); transition: background .15s;
}
.bgmg-search-result-item:hover { background: var(--pink-soft); }
.bgmg-search-result-img { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
.bgmg-search-result-name { font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--dark); line-height: 1.3; }
.bgmg-search-result-price { font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--pink-dark); font-weight: 500; margin-top: 2px; }
.bgmg-search-view-all {
  display: block; text-align: center; padding: 12px 16px;
  font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
  color: var(--pink-dark); text-decoration: none; background: var(--pink-soft);
  transition: background .15s, color .15s;
}
.bgmg-search-view-all:hover { background: var(--pink-dark); color: #fff; }
.bgmg-search-msg {
  padding: 16px; text-align: center; color: var(--mid);
  font-family: 'DM Sans', sans-serif; font-size: 13px;
}

/* ── MINI CART ── El CSS estructural del panel (y los enhancements) ahora viven
   en assets/bgmg-global.css (BL-01c: antes duplicado inline en los 7 templates).
   Aplica en TODAS las páginas. Las variables (--pink, etc.) las sigue dando el :root. ── */

/* ── ANIMACIONES ────────────────────────────────────────────── */
@keyframes bgmg-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

/* ── RESPONSIVE ─────────────────────────────────────────────── */
@media (min-width: 768px) {
  .bgmg-header { height: 72px; }
  .bgmg-header-inner { padding: 0 40px; }
  .bgmg-dnav { display: flex; }
  .bgmg-hamburger { display: none; }
  .bgmg-search-overlay { top: 72px; padding: 18px 40px; }
  .bgmg-sec           { padding: 64px 40px; }
  .bgmg-sec-title     { font-size: 40px; }
  .bgmg-grid          { grid-template-columns: 1fr 1fr; }
  .bgmg-hero-slider   { margin-top: 72px; }
  .bgmg-hero-slider .swiper-slide { min-height: 580px; }
  .bgmg-slide-inner   { padding: 88px 40px 80px; }
  .bgmg-slide-title   { font-size: 58px; }
  .bgmg-slide-badge   { top: 32px; right: 40px; font-size: 13px; }
  .bgmg-slide-visual  { display: flex; align-items: center; justify-content: center; flex: 0 0 300px; }
  .bgmg-slide-emoji   { font-size: 160px; animation: bgmg-float 4s ease-in-out infinite; filter: drop-shadow(0 24px 48px rgba(196,114,138,.25)); }
  .bgmg-trust       { padding: 40px; }
  .bgmg-trust-text  { font-size: 14px; }
  .bgmg-testi-grid  { grid-template-columns: 1fr 1fr; }
  .bgmg-tips-scroll { display: grid; grid-template-columns: repeat(3, 1fr); overflow-x: visible; padding-bottom: 0; scroll-snap-type: none; }
  .bgmg-tips-card   { flex: unset; }
  .bgmg-banner-title { font-size: 40px; }
  .bgmg-sticky      { display: none; }
}
</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php bgmg_render_header(); ?>

<?php /* Mini-cart panel: ahora lo rinde bgmg_render_header() (BL-01c). */ ?>

<!-- ══ 1. HERO SLIDER ════════════════════════════════════════════ -->
<?php
// Los textos, imágenes, overlays y CTAs del hero se configuran desde
// Apariencia → Personalizar → BGMG Tema → Hero slider del landing.
// Si no se configura nada, se usan los defaults (gradiente + textos
// hardcoded en bgmg_customizer_hero_defaults() de inc/customizer.php).
$bgmg_hero_defaults = function_exists( 'bgmg_customizer_hero_defaults' )
    ? bgmg_customizer_hero_defaults()
    : [];
$bgmg_hero_default_url = function_exists( 'bgmg_customizer_default_cta_url' )
    ? bgmg_customizer_default_cta_url()
    : esc_url( home_url( '/' ) );

// Generar CSS inline (background-image + overlay color) por slide
$bgmg_hero_css = '';
for ( $bgmg_i = 1; $bgmg_i <= 3; $bgmg_i++ ) {
    $img_desktop = get_theme_mod( "bgmg_slide_{$bgmg_i}_image_desktop", '' );
    $img_mobile  = get_theme_mod( "bgmg_slide_{$bgmg_i}_image_mobile", '' );
    $focus       = get_theme_mod( "bgmg_slide_{$bgmg_i}_focus", 'center' );

    // Imagen base (la que se ve en desktop). Si el admin NO subió la desktop pero
    // SÍ la mobile, usamos la mobile como base — así un slide con solo imagen de
    // celular igual se muestra. Antes todo el bloque dependía de $img_desktop, por
    // lo que subir solo la mobile dejaba el slide sin fondo (en blanco/gradiente).
    $img_base = $img_desktop ?: $img_mobile;
    if ( $img_base ) {
        $bgmg_hero_css .= ".bgmg-slide-{$bgmg_i}{background-image:url('" . esc_url( $img_base ) . "');background-size:cover;background-position:" . esc_attr( $focus ) . ";background-repeat:no-repeat;}";
        // Mobile: si hay imagen mobile la usamos; si no, dejamos la base (desktop).
        $bgmg_img_mobile = $img_mobile ?: $img_base;
        $bgmg_hero_css .= "@media(max-width:767px){.bgmg-slide-{$bgmg_i}{background-image:url('" . esc_url( $bgmg_img_mobile ) . "');background-position:center;}}";
    }
}
if ( $bgmg_hero_css ) {
    echo '<style id="bgmg-hero-bg">' . $bgmg_hero_css . '</style>';
}
?>
<div class="swiper bgmg-hero-slider">
  <div class="swiper-wrapper">

<?php for ( $bgmg_i = 1; $bgmg_i <= 3; $bgmg_i++ ) :
    $enabled = (bool) get_theme_mod( "bgmg_slide_{$bgmg_i}_enabled", true );
    if ( ! $enabled ) continue;

    $defaults    = isset( $bgmg_hero_defaults[ $bgmg_i ] ) ? $bgmg_hero_defaults[ $bgmg_i ] : [];
    $has_image   = (bool) ( get_theme_mod( "bgmg_slide_{$bgmg_i}_image_desktop", '' ) || get_theme_mod( "bgmg_slide_{$bgmg_i}_image_mobile", '' ) );
    $overlay_on  = (bool) get_theme_mod( "bgmg_slide_{$bgmg_i}_overlay", false );
    $badge       = get_theme_mod( "bgmg_slide_{$bgmg_i}_badge",    $defaults['badge']    ?? '' );
    $label       = get_theme_mod( "bgmg_slide_{$bgmg_i}_label",    $defaults['label']    ?? '' );
    $title       = get_theme_mod( "bgmg_slide_{$bgmg_i}_title",    $defaults['title']    ?? '' );
    $subtitle    = get_theme_mod( "bgmg_slide_{$bgmg_i}_subtitle", $defaults['subtitle'] ?? '' );
    $cta_text    = get_theme_mod( "bgmg_slide_{$bgmg_i}_cta_text", $defaults['cta_text'] ?? 'Ver más →' );
    $cta_url     = get_theme_mod( "bgmg_slide_{$bgmg_i}_cta_url",  '' );
    $cta_url     = $cta_url !== '' ? $cta_url : $bgmg_hero_default_url;

    $pills = [];
    for ( $bgmg_p = 1; $bgmg_p <= 3; $bgmg_p++ ) {
        $pill = get_theme_mod( "bgmg_slide_{$bgmg_i}_pill_{$bgmg_p}", $defaults[ "pill_{$bgmg_p}" ] ?? '' );
        if ( $pill !== '' ) $pills[] = $pill;
    }
?>
    <div class="swiper-slide bgmg-slide-<?php echo (int) $bgmg_i; ?>">
      <?php if ( $overlay_on && $has_image ) : ?>
        <div class="bgmg-slide-overlay" style="display:block;"></div>
      <?php else : ?>
        <div class="bgmg-slide-overlay"></div>
      <?php endif; ?>

      <?php if ( $badge !== '' ) : ?>
        <div class="bgmg-slide-badge"><?php echo esc_html( $badge ); ?></div>
      <?php endif; ?>

      <div class="bgmg-slide-inner">
        <div class="bgmg-slide-text">
          <?php if ( $label !== '' ) : ?>
            <span class="bgmg-slide-label"><?php echo esc_html( $label ); ?></span>
          <?php endif; ?>

          <?php
          // Tag distinto para slide 1 (H1 por SEO) vs los demás (H2)
          $title_tag = $bgmg_i === 1 ? 'h1' : 'h2';
          if ( $title !== '' ) :
              printf(
                  '<%1$s class="bgmg-slide-title">%2$s</%1$s>',
                  $title_tag,
                  wp_kses( $title, [ 'br' => [], 'em' => [], 'strong' => [], 'span' => [] ] )
              );
          endif;
          ?>

          <?php if ( $subtitle !== '' ) : ?>
            <p class="bgmg-slide-sub"><?php echo esc_html( $subtitle ); ?></p>
          <?php endif; ?>

          <?php if ( ! empty( $pills ) ) : ?>
            <div class="bgmg-slide-pills">
              <?php foreach ( $pills as $pill ) : ?>
                <span><?php echo esc_html( $pill ); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ( $cta_text !== '' ) : ?>
            <a href="<?php echo esc_url( $cta_url ); ?>" class="bgmg-slide-cta"><?php echo esc_html( $cta_text ); ?></a>
          <?php endif; ?>
        </div>
        <?php
        // Visual decorativo (emoji) solo en desktop si NO hay imagen subida
        if ( ! $has_image ) :
            $emojis = [ 1 => '🌸', 2 => '🧴', 3 => '🚚' ];
            $emoji  = $emojis[ $bgmg_i ] ?? '✨';
        ?>
          <div class="bgmg-slide-visual"><span class="bgmg-slide-emoji"><?php echo esc_html( $emoji ); ?></span></div>
        <?php endif; ?>
      </div>
    </div>

<?php endfor; ?>

  </div>
  <div class="swiper-pagination"></div>
  <div class="swiper-button-prev"></div>
  <div class="swiper-button-next"></div>
</div>

<!-- ══ 2. CATEGORÍAS ════════════════════════════════════════════ -->
<?php
$cat_emojis = array(
    'maquillaje'  => '💄',
    'skin-care'   => '🧴',
    'skincare'    => '🧴',
    'corporal'    => '🛁',
    'cabello'     => '💆',
    'unas'        => '💅',
    'kits'        => '🎁',
    'accesorios'  => '👜',
    'perfumes'    => '🌹',
    'cuidado'     => '✨',
);
$cat_args = array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'exclude'    => array(get_option('default_product_cat')),
    'number'     => 8,
    'orderby'    => 'count',
    'order'      => 'DESC',
);
$cat_terms = get_terms($cat_args);
if (!is_wp_error($cat_terms) && !empty($cat_terms)) :
?>
<section class="bgmg-sec" style="background:#fff;">
  <div class="bgmg-sec-inner">
    <span class="bgmg-sec-label">Categorías</span>
    <h2 class="bgmg-sec-title">Encuentra lo que buscas</h2>
    <div class="bgmg-cats">
      <?php foreach ($cat_terms as $term) : $emoji = isset($cat_emojis[$term->slug]) ? $cat_emojis[$term->slug] : '🌸'; ?>
        <a href="<?php echo esc_url(get_term_link($term)); ?>" class="bgmg-cat">
          <span class="bgmg-cat-icon"><?php echo esc_html( $emoji ); ?></span>
          <?php echo esc_html($term->name); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 3. PRODUCTOS DESTACADOS ══════════════════════════════════ -->
<?php
$dest_args = array('post_type' => 'product', 'posts_per_page' => 6);
$dest_args['tax_query'] = array(array('taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured'));
$dest_query = new WP_Query($dest_args);
if ($dest_query->have_posts()) :
?>
<section class="bgmg-sec">
  <div class="bgmg-sec-inner">
    <span class="bgmg-sec-label">Selección especial</span>
    <h2 class="bgmg-sec-title">Productos Destacados</h2>
    <div class="bgmg-grid">
      <?php while ($dest_query->have_posts()) : $dest_query->the_post();
        $p_id  = get_the_ID();
        $prod  = wc_get_product($p_id);
        if (!$prod) continue;
        $p_name  = esc_html(get_the_title());
        $p_img   = get_the_post_thumbnail_url($p_id, 'thumbnail') ?: wc_placeholder_img_src();
        $p_terms = get_the_terms($p_id, 'product_cat');
        $p_cat   = ($p_terms && !is_wp_error($p_terms)) ? esc_html($p_terms[0]->name) : '';
      ?>
        <div class="bgmg-card">
          <a href="<?php echo esc_url(get_permalink($p_id)); ?>" class="bgmg-card-link">
            <img class="bgmg-card-img" src="<?php echo esc_url($p_img); ?>" alt="<?php echo esc_attr($p_name); ?>" loading="lazy">
            <div class="bgmg-card-body">
              <?php if ($p_cat) : ?><span class="bgmg-badge"><?php echo $p_cat; ?></span><?php endif; ?>
              <div class="bgmg-card-name"><?php echo $p_name; ?></div>
              <div class="bgmg-card-price"><?php echo $prod->get_price_html(); ?></div>
            </div>
          </a>
          <a href="<?php echo esc_url($prod->add_to_cart_url()); ?>"
             class="bgmg-btn-add add_to_cart_button ajax_add_to_cart"
             data-product_id="<?php echo esc_attr($p_id); ?>"
             data-product_type="<?php echo esc_attr($prod->get_type()); ?>"
             data-quantity="1" rel="nofollow">+</a>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 4. TIPS EDITORIALES ══════════════════════════════════════ -->
<section class="bgmg-sec" style="background:var(--cream);">
  <div class="bgmg-sec-inner">
    <span class="bgmg-sec-label">Contenido editorial</span>
    <h2 class="bgmg-sec-title">Tips de belleza</h2>
    <div class="bgmg-tips-scroll">
      <article class="bgmg-tips-card bgmg-tips-pink">
        <div class="bgmg-tips-emoji">🧴</div>
        <span class="bgmg-tips-tag">Skincare</span>
        <h3 class="bgmg-tips-title">Rutina de noche en 3 pasos</h3>
        <p class="bgmg-tips-desc">Descubre cómo cuidar tu piel mientras duermes con ingredientes naturales que regeneran de verdad.</p>
        <a href="#" class="bgmg-tips-link">Leer más →</a>
      </article>
      <article class="bgmg-tips-card bgmg-tips-aqua">
        <div class="bgmg-tips-emoji">💆</div>
        <span class="bgmg-tips-tag">Cabello</span>
        <h3 class="bgmg-tips-title">Cómo usar el exfoliante capilar</h3>
        <p class="bgmg-tips-desc">Dale un reset a tu cuero cabelludo con esta técnica de 5 minutos que transforma tu melena.</p>
        <a href="#" class="bgmg-tips-link">Leer más →</a>
      </article>
      <article class="bgmg-tips-card bgmg-tips-peach">
        <div class="bgmg-tips-emoji">🌿</div>
        <span class="bgmg-tips-tag">Natural</span>
        <h3 class="bgmg-tips-title">Ingredientes que transforman tu piel</h3>
        <p class="bgmg-tips-desc">Los activos botánicos que deberías incluir en tu rutina ahora mismo y cómo usarlos bien.</p>
        <a href="#" class="bgmg-tips-link">Leer más →</a>
      </article>
    </div>
  </div>
</section>

<!-- ══ 5. NOVEDADES ═════════════════════════════════════════════ -->
<?php
$nov_args = array('post_type' => 'product', 'posts_per_page' => 4, 'orderby' => 'date', 'order' => 'DESC');
$nov_query = new WP_Query($nov_args);
if ($nov_query->have_posts()) :
?>
<section class="bgmg-sec" style="background:var(--pink-soft);">
  <div class="bgmg-sec-inner">
    <span class="bgmg-sec-label">Recién llegados</span>
    <h2 class="bgmg-sec-title">Novedades</h2>
    <div class="bgmg-grid">
      <?php while ($nov_query->have_posts()) : $nov_query->the_post();
        $p_id  = get_the_ID();
        $prod  = wc_get_product($p_id);
        if (!$prod) continue;
        $p_name  = esc_html(get_the_title());
        $p_img   = get_the_post_thumbnail_url($p_id, 'thumbnail') ?: wc_placeholder_img_src();
        $p_terms = get_the_terms($p_id, 'product_cat');
        $p_cat   = ($p_terms && !is_wp_error($p_terms)) ? esc_html($p_terms[0]->name) : '';
      ?>
        <div class="bgmg-card">
          <a href="<?php echo esc_url(get_permalink($p_id)); ?>" class="bgmg-card-link">
            <img class="bgmg-card-img" src="<?php echo esc_url($p_img); ?>" alt="<?php echo esc_attr($p_name); ?>" loading="lazy">
            <div class="bgmg-card-body">
              <?php if ($p_cat) : ?><span class="bgmg-badge"><?php echo $p_cat; ?></span><?php endif; ?>
              <div class="bgmg-card-name"><?php echo $p_name; ?></div>
              <div class="bgmg-card-price"><?php echo $prod->get_price_html(); ?></div>
            </div>
          </a>
          <a href="<?php echo esc_url($prod->add_to_cart_url()); ?>"
             class="bgmg-btn-add add_to_cart_button ajax_add_to_cart"
             data-product_id="<?php echo esc_attr($p_id); ?>"
             data-product_type="<?php echo esc_attr($prod->get_type()); ?>"
             data-quantity="1" rel="nofollow">+</a>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 6. BANNER OFERTA (configurable desde Customizer) ═════════ -->
<?php
$bgmg_mb_enabled = (bool) get_theme_mod( 'bgmg_midbanner_enabled', true );
if ( $bgmg_mb_enabled ) :
    $bgmg_mb_defaults = function_exists( 'bgmg_customizer_midbanner_defaults' )
        ? bgmg_customizer_midbanner_defaults()
        : [];
    $bgmg_mb_style   = get_theme_mod( 'bgmg_midbanner_style', 'dark' );
    $bgmg_mb_img_d   = get_theme_mod( 'bgmg_midbanner_image_desktop', '' );
    $bgmg_mb_img_m   = get_theme_mod( 'bgmg_midbanner_image_mobile', '' );
    $bgmg_mb_focus   = get_theme_mod( 'bgmg_midbanner_focus', 'center' );
    $bgmg_mb_overlay = (bool) get_theme_mod( 'bgmg_midbanner_overlay', true );
    $bgmg_mb_title   = get_theme_mod( 'bgmg_midbanner_title', $bgmg_mb_defaults['title'] ?? '' );
    $bgmg_mb_sub     = get_theme_mod( 'bgmg_midbanner_subtitle', $bgmg_mb_defaults['subtitle'] ?? '' );
    $bgmg_mb_cta_txt = get_theme_mod( 'bgmg_midbanner_cta_text', $bgmg_mb_defaults['cta_text'] ?? 'Ver más' );
    $bgmg_mb_cta_url = get_theme_mod( 'bgmg_midbanner_cta_url', '' );
    if ( $bgmg_mb_cta_url === '' ) {
        $bgmg_mb_cta_url = function_exists( 'wc_get_page_id' )
            ? get_permalink( wc_get_page_id( 'shop' ) )
            : home_url( '/' );
    }

    // Acepta imagen de PC O de celular: si solo subes una, se usa como base. Antes
    // exigía la de PC ($bgmg_mb_img_d), igual que el bug del hero slider.
    $bgmg_mb_img_base  = $bgmg_mb_img_d ?: $bgmg_mb_img_m;
    $bgmg_mb_use_image = ( $bgmg_mb_style === 'image' ) && $bgmg_mb_img_base;

    // CSS inline para variante con imagen
    if ( $bgmg_mb_use_image ) {
        $bgmg_mb_img_m_use = $bgmg_mb_img_m ?: $bgmg_mb_img_base;
        echo '<style id="bgmg-midbanner-bg">';
        echo ".bgmg-banner.is-image{background-image:url('" . esc_url( $bgmg_mb_img_base ) . "');background-size:cover;background-position:" . esc_attr( $bgmg_mb_focus ) . ";background-repeat:no-repeat;}";
        echo "@media(max-width:767px){.bgmg-banner.is-image{background-image:url('" . esc_url( $bgmg_mb_img_m_use ) . "');background-position:center;}}";
        echo '</style>';
    }
?>
<section class="bgmg-sec" style="background:var(--cream);padding-top:8px;padding-bottom:8px;">
  <div class="bgmg-sec-inner">
    <div class="bgmg-banner<?php echo $bgmg_mb_use_image ? ' is-image' : ''; ?>">
      <?php if ( $bgmg_mb_use_image && $bgmg_mb_overlay ) : ?>
        <div class="bgmg-banner-overlay"></div>
      <?php endif; ?>
      <div class="bgmg-banner-content">
        <?php if ( $bgmg_mb_title !== '' ) : ?>
          <div class="bgmg-banner-title"><?php echo wp_kses( $bgmg_mb_title, [ 'br' => [], 'em' => [], 'strong' => [], 'span' => [] ] ); ?></div>
        <?php endif; ?>
        <?php if ( $bgmg_mb_sub !== '' ) : ?>
          <p class="bgmg-banner-sub"><?php echo esc_html( $bgmg_mb_sub ); ?></p>
        <?php endif; ?>
        <?php if ( $bgmg_mb_cta_txt !== '' ) : ?>
          <a href="<?php echo esc_url( $bgmg_mb_cta_url ); ?>" class="bgmg-banner-btn"><?php echo esc_html( $bgmg_mb_cta_txt ); ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ 7. OFERTAS ═══════════════════════════════════════════════ -->
<?php
$sale_ids = wc_get_product_ids_on_sale();
if (!empty($sale_ids)) :
  $sale_args = array('post_type' => 'product', 'posts_per_page' => 4, 'post__in' => $sale_ids);
  $sale_query = new WP_Query($sale_args);
  if ($sale_query->have_posts()) :
?>
<section class="bgmg-sec">
  <div class="bgmg-sec-inner">
    <span class="bgmg-sec-label">🔥 Ofertas especiales</span>
    <h2 class="bgmg-sec-title">Precios irresistibles</h2>
    <div class="bgmg-grid">
      <?php while ($sale_query->have_posts()) : $sale_query->the_post();
        $p_id = get_the_ID();
        $prod = wc_get_product($p_id);
        if (!$prod) continue;
        $p_name = esc_html(get_the_title());
        $p_img  = get_the_post_thumbnail_url($p_id, 'thumbnail') ?: wc_placeholder_img_src();
      ?>
        <div class="bgmg-card">
          <a href="<?php echo esc_url(get_permalink($p_id)); ?>" class="bgmg-card-link">
            <img class="bgmg-card-img" src="<?php echo esc_url($p_img); ?>" alt="<?php echo esc_attr($p_name); ?>" loading="lazy">
            <div class="bgmg-card-body">
              <span class="bgmg-badge-oferta">🔥 Oferta</span>
              <div class="bgmg-card-name"><?php echo $p_name; ?></div>
              <div class="bgmg-card-price"><?php echo $prod->get_price_html(); ?></div>
            </div>
          </a>
          <a href="<?php echo esc_url($prod->add_to_cart_url()); ?>"
             class="bgmg-btn-add add_to_cart_button ajax_add_to_cart"
             data-product_id="<?php echo esc_attr($p_id); ?>"
             data-product_type="<?php echo esc_attr($prod->get_type()); ?>"
             data-quantity="1" rel="nofollow">+</a>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; endif; ?>

<!-- ══ 8. TRUST STRIP ══════════════════════════════════════════ -->
<div class="bgmg-trust">
  <div class="bgmg-trust-item">
    <span class="bgmg-trust-icon">🚚</span>
    <span class="bgmg-trust-text">Envío gratis<br>sobre $25.000</span>
  </div>
  <div class="bgmg-trust-item">
    <span class="bgmg-trust-icon">✅</span>
    <span class="bgmg-trust-text">100% ingredientes<br>naturales</span>
  </div>
  <div class="bgmg-trust-item">
    <span class="bgmg-trust-icon">💬</span>
    <span class="bgmg-trust-text">Atención por<br>WhatsApp</span>
  </div>
</div>

<!-- ══ 9. TESTIMONIOS ══════════════════════════════════════════ -->
<section class="bgmg-sec" style="background:var(--pink-soft);">
  <div class="bgmg-sec-inner">
    <span class="bgmg-sec-label">Reseñas reales</span>
    <h2 class="bgmg-sec-title">Lo que dicen nuestras clientas</h2>
    <div class="bgmg-testi-grid">
      <div class="bgmg-testi-card">
        <div class="bgmg-testi-stars">★★★★★</div>
        <p class="bgmg-testi-text">"Los productos son increíbles, mi piel nunca había estado tan hidratada. El envío fue súper rápido y el empaque muy cuidado."</p>
        <div class="bgmg-testi-author">
          <div class="bgmg-testi-avatar">V</div>
          <div>
            <div class="bgmg-testi-name">Valentina R.</div>
            <div class="bgmg-testi-date">Marzo 2025 · Santiago</div>
          </div>
        </div>
      </div>
      <div class="bgmg-testi-card">
        <div class="bgmg-testi-stars">★★★★★</div>
        <p class="bgmg-testi-text">"Compré la rutina de noche y en dos semanas noté la diferencia. Cien por ciento recomendado. Ya hice mi segunda compra."</p>
        <div class="bgmg-testi-author">
          <div class="bgmg-testi-avatar">C</div>
          <div>
            <div class="bgmg-testi-name">Camila S.</div>
            <div class="bgmg-testi-date">Abril 2025 · Viña del Mar</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ 10. STICKY CTA (solo mobile) ════════════════════════════ -->

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
// Hero slider
new Swiper('.bgmg-hero-slider', {
  loop: true,
  speed: 800,
  autoplay: { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true },
  effect: 'fade',
  fadeEffect: { crossFade: true },
  pagination: { el: '.bgmg-hero-slider .swiper-pagination', clickable: true },
  navigation: {
    nextEl: '.bgmg-hero-slider .swiper-button-next',
    prevEl: '.bgmg-hero-slider .swiper-button-prev',
  },
});
</script>
<script>
(function(){
  // Hamburger + mobile drawer
  var hamburger = document.getElementById('bgmg-hamburger');
  var drawer    = document.getElementById('bgmg-mdrawer');
  if (hamburger && drawer) {
    hamburger.addEventListener('click', function() {
      hamburger.classList.toggle('is-open');
      drawer.classList.toggle('is-open');
    });
  }
  
  // Lupa + abrir/cerrar minicart + refresco de fragmentos: ahora GLOBALES
  // (bloque bgmg-header-ui-js en bgmg-landing.php, BL-01c Fase 2). Aquí solo
  // queda el hamburger, que es propio de este template.
})();
</script>
<?php bgmg_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
