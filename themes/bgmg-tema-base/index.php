<?php
/**
 * Fallback template — casi nunca se renderiza.
 *
 * El plugin bgmg-landing intercepta `template_include` con prioridad 99 y
 * sirve sus propios templates para home, shop, category, product, cart,
 * checkout, account. Este index.php solo se ejecutaría si:
 *
 *   1. El plugin bgmg-landing está desactivado.
 *   2. Estamos en una vista que el plugin no cubre (ej. search, 404).
 *
 * En esos casos servimos un HTML mínimo, válido y semántico — sin estilos
 * propios — para que la página al menos NO sea inutilizable.
 *
 * @package BGMG_Base
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<main style="max-width:720px;margin:40px auto;padding:0 20px;font-family:system-ui,-apple-system,sans-serif;color:#1A1015;">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article style="margin-bottom:40px;">
				<header>
					<h1 style="font-size:28px;margin:0 0 8px;"><?php the_title(); ?></h1>
				</header>
				<div>
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<h1 style="font-size:28px;margin:0 0 16px;">Página no encontrada</h1>
		<p>No se encontró contenido para mostrar.</p>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#C4728A;">Volver al inicio →</a></p>
	<?php endif; ?>
</main>

<?php wp_footer(); ?>
</body>
</html>
