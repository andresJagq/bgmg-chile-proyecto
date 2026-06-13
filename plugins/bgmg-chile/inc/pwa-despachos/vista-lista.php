<?php
/**
 * PWA Despachos — vista LISTA (pantalla 1).
 * Variables que llegan del controlador (bgmg_chile_pwa_render):
 *   @var string $tab     pendientes | enviados | retiro
 *   @var array  $listas  ['pendientes'=>WC_Order[], 'retiro'=>..., 'enviados'=>...]
 *
 * Página standalone: NO carga el tema (el controlador hace exit). CSS propio
 * con la paleta BGMG (mismos tonos que bgmg-landing).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bgmg_pwa_base = home_url( '/despachos/' );
$bgmg_pwa_tabs = array(
	'pendientes' => array( 'label' => __( 'Por despachar', 'bgmg-chile' ), 'icon' => '📦' ),
	'enviados'   => array( 'label' => __( 'Enviados', 'bgmg-chile' ), 'icon' => '🚚' ),
	'retiro'     => array( 'label' => __( 'Retiro', 'bgmg-chile' ), 'icon' => '📍' ),
);
$bgmg_pwa_lista_activa = isset( $listas[ $tab ] ) ? $listas[ $tab ] : array();
$bgmg_pwa_icon_180     = get_site_icon_url( 180 );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Despachos — BeautyGirlMG</title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#C4728A">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Despachos">
<?php if ( $bgmg_pwa_icon_180 ) : ?>
<link rel="apple-touch-icon" href="<?php echo esc_url( $bgmg_pwa_icon_180 ); ?>">
<?php endif; ?>
<link rel="manifest" href="<?php echo esc_url( $bgmg_pwa_base . '?bgmg_manifest=1' ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
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
body { font-family: 'Poppins', sans-serif; background: var(--cream); color: var(--dark); padding-bottom: 40px; }

.pwa-head {
  position: sticky; top: 0; z-index: 50; background: #fff;
  border-bottom: 1px solid var(--border);
  padding: calc(12px + env(safe-area-inset-top)) 16px 12px;
  display: flex; align-items: center; gap: 10px;
}
.pwa-title { font-size: 17px; font-weight: 600; }
.pwa-title span { color: var(--pink-dark); }
.pwa-head-btns { margin-left: auto; display: flex; gap: 6px; }
.pwa-hbtn {
  width: 36px; height: 36px; border-radius: 50%; border: 1.5px solid var(--border);
  background: #fff; font-size: 16px; display: flex; align-items: center;
  justify-content: center; text-decoration: none; color: var(--mid); cursor: pointer;
}
.pwa-hbtn:active { background: var(--pink-soft); }

.pwa-tabs {
  display: flex; gap: 8px; padding: 12px 16px 4px; overflow-x: auto;
  scrollbar-width: none; -webkit-overflow-scrolling: touch;
}
.pwa-tabs::-webkit-scrollbar { display: none; }
.pwa-tab {
  flex-shrink: 0; padding: 8px 16px; border-radius: 30px; text-decoration: none;
  border: 1.5px solid var(--border); background: #fff; font-size: 13px;
  color: var(--mid); white-space: nowrap;
}
.pwa-tab.is-active { background: var(--pink-dark); border-color: var(--pink-dark); color: #fff; }
.pwa-tab .n {
  display: inline-block; min-width: 20px; text-align: center; margin-left: 4px;
  background: rgba(0,0,0,.08); border-radius: 12px; padding: 1px 6px; font-size: 11px;
}
.pwa-tab.is-active .n { background: rgba(255,255,255,.25); }

.pwa-list { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; max-width: 560px; margin: 0 auto; }
.pwa-card {
  background: #fff; border: 1px solid var(--border); border-radius: 16px;
  padding: 12px 14px; display: block; color: inherit; text-decoration: none;
}
.pwa-card:active { background: var(--pink-soft); }
.pwa-card-top { display: flex; align-items: center; gap: 8px; }
.pwa-card-num { font-size: 14px; font-weight: 600; }
.pwa-card-name { font-size: 14px; font-weight: 400; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pwa-badge { flex-shrink: 0; font-size: 10px; font-weight: 500; padding: 3px 9px; border-radius: 12px; white-space: nowrap; }
.b-pend    { background: var(--pink-soft); color: var(--pink-dark); }
.b-prep    { background: #FFF3E0; color: #A0561B; }
.b-desp    { background: #E3F2FD; color: #1565C0; }
.b-retiro  { background: #E8F5E9; color: #2E7D32; }
.b-comp    { background: #f0f0f0; color: #555; }
.pwa-card-line { margin-top: 5px; font-size: 12px; color: var(--mid); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pwa-card-line .sep { opacity: .5; }
.pwa-track { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; background: var(--pink-soft); border-radius: 8px; padding: 1px 8px; color: var(--dark); }
.pwa-tel { margin-left: auto; text-decoration: none; font-size: 16px; }

.pwa-empty { text-align: center; padding: 60px 20px; color: var(--mid); font-size: 14px; }
.pwa-empty .big { font-size: 40px; display: block; margin-bottom: 10px; }
.pwa-foot { text-align: center; font-size: 11px; color: var(--mid); opacity: .7; padding: 18px 0 8px; }

.pwa-toast {
  position: sticky; bottom: 16px; margin: 0 auto; width: max-content; max-width: 90%;
  background: var(--dark); color: #fff; font-size: 13px; padding: 10px 18px;
  border-radius: 30px; opacity: 0; transition: opacity .25s; pointer-events: none;
}
.pwa-toast.show { opacity: 1; }
</style>
</head>
<body>

<header class="pwa-head">
  <div class="pwa-title">Despachos <span>BGMG</span></div>
  <div class="pwa-head-btns">
    <button class="pwa-hbtn" type="button" onclick="location.reload()" aria-label="<?php esc_attr_e( 'Actualizar', 'bgmg-chile' ); ?>">↻</button>
    <a class="pwa-hbtn" href="<?php echo esc_url( wp_logout_url( $bgmg_pwa_base ) ); ?>" aria-label="<?php esc_attr_e( 'Cerrar sesión', 'bgmg-chile' ); ?>">⏻</a>
  </div>
</header>

<nav class="pwa-tabs">
  <?php foreach ( $bgmg_pwa_tabs as $bgmg_pwa_slug => $bgmg_pwa_t ) : ?>
  <a class="pwa-tab<?php echo $tab === $bgmg_pwa_slug ? ' is-active' : ''; ?>"
     href="<?php echo esc_url( add_query_arg( 'tab', $bgmg_pwa_slug, $bgmg_pwa_base ) ); ?>">
    <?php echo esc_html( $bgmg_pwa_t['icon'] . ' ' . $bgmg_pwa_t['label'] ); ?><span class="n"><?php echo (int) count( $listas[ $bgmg_pwa_slug ] ); ?></span>
  </a>
  <?php endforeach; ?>
</nav>

<main class="pwa-list">
<?php if ( empty( $bgmg_pwa_lista_activa ) ) : ?>
  <div class="pwa-empty">
    <span class="big"><?php echo 'pendientes' === $tab ? '🎉' : '🌸'; ?></span>
    <?php
    if ( 'pendientes' === $tab ) {
        esc_html_e( 'Nada pendiente por despachar. ¡Al día!', 'bgmg-chile' );
    } else {
        esc_html_e( 'No hay pedidos en esta lista.', 'bgmg-chile' );
    }
    ?>
  </div>
<?php else : ?>
  <?php
  foreach ( $bgmg_pwa_lista_activa as $bgmg_pwa_order ) :
      $c = bgmg_chile_pwa_card_data( $bgmg_pwa_order );

      // Badge: completado > estado de despacho > por despachar.
      if ( 'completed' === $c['wc_status'] ) {
          $badge_clase = 'b-comp';
          $badge_texto = __( 'Completado', 'bgmg-chile' );
      } elseif ( 'despachado' === $c['estado'] ) {
          $badge_clase = 'b-desp';
          $badge_texto = $c['estado_label'];
      } elseif ( 'listo_retiro' === $c['estado'] ) {
          $badge_clase = 'b-retiro';
          $badge_texto = $c['estado_label'];
      } elseif ( 'preparando' === $c['estado'] ) {
          $badge_clase = 'b-prep';
          $badge_texto = $c['estado_label'];
      } else {
          $badge_clase = 'b-pend';
          $badge_texto = __( 'Pagado', 'bgmg-chile' );
      }
  ?>
  <a class="pwa-card" href="#" data-order="<?php echo (int) $c['id']; ?>">
    <div class="pwa-card-top">
      <span class="pwa-card-num"><?php echo esc_html( $c['numero'] ); ?></span>
      <span class="pwa-card-name"><?php echo esc_html( $c['nombre'] ); ?></span>
      <span class="pwa-badge <?php echo esc_attr( $badge_clase ); ?>"><?php echo esc_html( $badge_texto ); ?></span>
    </div>
    <div class="pwa-card-line">
      <span>📍 <?php echo esc_html( $c['comuna'] ); ?></span>
      <?php if ( $c['metodo'] ) : ?><span class="sep">·</span><span><?php echo esc_html( $c['metodo'] ); ?></span><?php endif; ?>
      <?php if ( $c['telefono'] ) : ?>
      <a class="pwa-tel" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $c['telefono'] ) ); ?>" onclick="event.stopPropagation()" aria-label="<?php esc_attr_e( 'Llamar', 'bgmg-chile' ); ?>">📞</a>
      <?php endif; ?>
    </div>
    <div class="pwa-card-line">
      <span><?php echo (int) $c['items']; ?> <?php echo esc_html( _n( 'producto', 'productos', $c['items'], 'bgmg-chile' ) ); ?></span>
      <span class="sep">·</span><span><?php echo esc_html( $c['total'] ); ?></span>
      <?php if ( $c['fecha'] ) : ?><span class="sep">·</span><span><?php echo esc_html( $c['fecha'] ); ?></span><?php endif; ?>
      <?php if ( $c['tracking'] ) : ?><span class="pwa-track"><?php echo esc_html( $c['tracking'] ); ?></span><?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
<?php endif; ?>
</main>

<div class="pwa-toast" id="pwa-toast"></div>
<div class="pwa-foot">Despachos BGMG · v<?php echo esc_html( BGMG_CHILE_VERSION ); ?></div>

<script>
(function(){
  var toast = document.getElementById('pwa-toast');
  var timer;
  function showToast(msg){
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(function(){ toast.classList.remove('show'); }, 2200);
  }
  document.querySelectorAll('.pwa-card').forEach(function(card){
    card.addEventListener('click', function(e){
      e.preventDefault();
      showToast('El detalle del pedido llega en la Parte 2 😉');
    });
  });
})();
</script>
</body>
</html>
