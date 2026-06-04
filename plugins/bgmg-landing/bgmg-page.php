<?php
/**
 * Template branded para páginas de CONTENIDO (FAQ, Términos, Privacidad,
 * Envíos, Devoluciones, etc.).
 *
 * Servido por bgmg-landing.php (filtro template_include) cuando una página WP
 * tiene asignado el template 'bgmg-page.php'. Reusa el header global
 * (bgmg_render_header) + footer (bgmg_footer) + tab bar + JS/CSS global. Solo
 * renderiza el título de la página + the_content() con tipografía branded.
 *
 * Las FAQ usan <details>/<summary> nativos (acordeón sin JS): cada pregunta se
 * abre/cierra al tocarla.
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alice&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style>
:root{--pink:#F2C4CE;--pink-soft:#FBF0F2;--pink-dark:#C4728A;--cream:#FDF7F4;--dark:#1A1015;--mid:#7A5060;--border:#f0e0e5;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:var(--cream);color:var(--dark);}

/* ── Hero de la página ── */
.bgmg-page-hero{padding:96px 20px 28px;text-align:center;background:#fff;border-bottom:1px solid var(--border);}
.bgmg-page-hero-label{display:inline-block;font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:2px;color:var(--pink-dark);background:var(--pink-soft);padding:5px 14px;border-radius:30px;margin-bottom:10px;}
.bgmg-page-title{font-family:'Alice',serif;font-size:32px;font-weight:600;color:var(--dark);line-height:1.2;}

/* ── Cuerpo ── */
.bgmg-page{padding:36px 20px 80px;}
.bgmg-page-inner{max-width:760px;margin:0 auto;}
.bgmg-page-content{font-size:15px;line-height:1.7;color:var(--dark);}
.bgmg-page-content > h2:first-child,
.bgmg-page-content > h3:first-child,
.bgmg-page-content > p:first-child{margin-top:0;}
.bgmg-page-content h2{font-family:'Alice',serif;font-size:23px;font-weight:600;color:var(--dark);margin:34px 0 12px;line-height:1.25;}
.bgmg-page-content h3{font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;color:var(--dark);margin:22px 0 8px;}
.bgmg-page-content p{margin:0 0 14px;color:var(--mid);}
.bgmg-page-content strong{color:var(--dark);font-weight:600;}
.bgmg-page-content a{color:var(--pink-dark);text-decoration:underline;text-underline-offset:2px;}
.bgmg-page-content a:hover{opacity:.8;}
.bgmg-page-content ul,.bgmg-page-content ol{margin:0 0 16px;padding-left:22px;color:var(--mid);}
.bgmg-page-content li{margin-bottom:7px;}
.bgmg-page-content hr{border:none;border-top:1px solid var(--border);margin:28px 0;}

/* ── Acordeón FAQ (<details>/<summary>) ── */
.bgmg-page-content details{background:#fff;border:1px solid var(--border);border-radius:14px;margin-bottom:10px;overflow:hidden;}
.bgmg-page-content summary{list-style:none;cursor:pointer;padding:16px 46px 16px 18px;position:relative;font-family:'Poppins',sans-serif;font-size:15px;font-weight:500;color:var(--dark);}
.bgmg-page-content summary::-webkit-details-marker{display:none;}
.bgmg-page-content summary::after{content:'+';position:absolute;right:18px;top:50%;transform:translateY(-50%);font-size:24px;font-weight:300;color:var(--pink-dark);line-height:1;}
.bgmg-page-content details[open] summary::after{content:'\2013';}
.bgmg-page-content details[open] summary{color:var(--pink-dark);}
.bgmg-page-content summary:hover{background:var(--pink-soft);}
.bgmg-page-content details > p,
.bgmg-page-content details > ul,
.bgmg-page-content details > ol{font-size:14px;color:var(--mid);line-height:1.65;padding-left:18px;padding-right:18px;}
.bgmg-page-content details > p{margin:0 0 10px;}
.bgmg-page-content details > ul,
.bgmg-page-content details > ol{margin:0 0 12px;padding-left:38px;}
.bgmg-page-content details > p:last-child,
.bgmg-page-content details > ul:last-child,
.bgmg-page-content details > ol:last-child{margin-bottom:16px;}

/* ── Nota / aviso destacado ── */
.bgmg-page-content .bgmg-note{background:var(--pink-soft);border:1px solid var(--border);border-radius:14px;padding:16px 18px;margin:0 0 22px;font-size:14px;color:var(--mid);}
.bgmg-page-content .bgmg-note strong{color:var(--pink-dark);}

.bgmg-page-updated{margin-top:30px;padding-top:16px;border-top:1px solid var(--border);font-size:13px;color:var(--mid);font-style:italic;}

@media(min-width:768px){
  .bgmg-page-hero{padding:120px 40px 32px;}
  .bgmg-page-title{font-size:42px;}
  .bgmg-page{padding:44px 40px 96px;}
  .bgmg-page-content{font-size:16px;}
  .bgmg-page-content h2{font-size:27px;}
}
</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php bgmg_render_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
<header class="bgmg-page-hero">
  <span class="bgmg-page-hero-label">BeautyGirl MG</span>
  <h1 class="bgmg-page-title"><?php the_title(); ?></h1>
</header>

<main class="bgmg-page">
  <div class="bgmg-page-inner">
    <div class="bgmg-page-content">
      <?php the_content(); ?>
    </div>
  </div>
</main>
<?php endwhile; ?>

<?php bgmg_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
