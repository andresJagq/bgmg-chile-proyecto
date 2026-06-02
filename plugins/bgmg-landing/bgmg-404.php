<?php
/**
 * Template 404 — página no encontrada, con la marca del sitio.
 *
 * Servido por bgmg-landing.php (filtro template_include) cuando is_404().
 * Reusa el header global (bgmg_render_header) + footer (bgmg_footer) + tab bar y
 * el JS de lupa/minicart (wp_footer global, bgmg-header-ui-js) + el CSS global
 * (assets/bgmg-global.css). Solo agrega el bloque central del 404.
 * El status HTTP 404 ya lo fija WordPress en la query principal; este template
 * solo cambia lo que se RENDERIZA, no el código de estado (sigue siendo 404).
 *
 * El <title> lo genera wp_head() ("Página no encontrada — <nombre del sitio>");
 * quedará correcto en cuanto se ajuste el nombre del sitio en wp-admin.
 */
defined( 'ABSPATH' ) || exit;

$bgmg_shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/tienda/' );
?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style>
:root{--pink:#F2C4CE;--pink-soft:#FBF0F2;--pink-dark:#C4728A;--cream:#FDF7F4;--dark:#1A1015;--mid:#7A5060;--border:#f0e0e5;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--dark);}
.bgmg-404{min-height:62vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:104px 20px 120px;}
.bgmg-404-inner{max-width:540px;}
.bgmg-404-code{font-family:'Cormorant Garamond',serif;font-size:104px;font-weight:600;color:var(--pink-dark);line-height:1;letter-spacing:4px;}
.bgmg-404-title{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:600;color:var(--dark);margin:6px 0 12px;line-height:1.2;}
.bgmg-404-text{font-size:15px;color:var(--mid);line-height:1.6;margin-bottom:28px;}
.bgmg-404-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
.bgmg-404-btn{display:inline-block;padding:13px 30px;border-radius:30px;font-size:14px;font-weight:500;text-decoration:none;transition:opacity .2s,background .2s,border-color .2s;}
.bgmg-404-btn-primary{background:var(--dark);color:#fff;}
.bgmg-404-btn-primary:hover{opacity:.85;}
.bgmg-404-btn-secondary{background:#fff;color:var(--dark);border:1.5px solid var(--border);}
.bgmg-404-btn-secondary:hover{border-color:var(--pink);background:var(--pink-soft);}
@media(min-width:768px){.bgmg-404-code{font-size:128px;}.bgmg-404-title{font-size:40px;}}
</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php bgmg_render_header(); ?>

<main class="bgmg-404">
  <div class="bgmg-404-inner">
    <div class="bgmg-404-code">404</div>
    <h1 class="bgmg-404-title">Ups, no encontramos esta página</h1>
    <p class="bgmg-404-text">El enlace puede estar roto o la página se movió. Pero tenemos muchos productos esperándote 💕</p>
    <div class="bgmg-404-actions">
      <a href="<?php echo esc_url( $bgmg_shop_url ); ?>" class="bgmg-404-btn bgmg-404-btn-primary">Ir a la tienda</a>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="bgmg-404-btn bgmg-404-btn-secondary">Volver al inicio</a>
    </div>
  </div>
</main>

<?php bgmg_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
