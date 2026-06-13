<?php
/**
 * PWA Despachos — vista DETALLE (pantalla 2).
 * Variables del controlador (bgmg_chile_pwa_render):
 *   @var WC_Order $order
 *   @var array    $detalle  (de bgmg_chile_pwa_detalle_data)
 *
 * Standalone (no carga el tema). Guarda por AJAX a bgmg_pwa_guardar; como
 * la página /despachos/ NO se cachea, el nonce siempre va fresco.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bgmg_pwa_base  = home_url( '/despachos/' );
$bgmg_pwa_nonce = wp_create_nonce( 'bgmg_pwa_guardar' );
$bgmg_pwa_ajax  = admin_url( 'admin-ajax.php' );
$bgmg_pwa_tel   = preg_replace( '/[^0-9+]/', '', $detalle['telefono'] );
$bgmg_pwa_icon  = get_site_icon_url( 180 );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo esc_html( $detalle['numero'] ); ?> — Despachos BGMG</title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#C4728A">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Despachos">
<?php if ( $bgmg_pwa_icon ) : ?>
<link rel="apple-touch-icon" href="<?php echo esc_url( $bgmg_pwa_icon ); ?>">
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
body { font-family: 'Poppins', sans-serif; background: var(--cream); color: var(--dark); padding-bottom: 120px; }

.pwa-head {
  position: sticky; top: 0; z-index: 50; background: #fff;
  border-bottom: 1px solid var(--border);
  padding: calc(12px + env(safe-area-inset-top)) 14px 12px;
  display: flex; align-items: center; gap: 10px;
}
.pwa-back {
  width: 38px; height: 38px; border-radius: 50%; border: 1.5px solid var(--border);
  background: #fff; font-size: 18px; display: flex; align-items: center;
  justify-content: center; text-decoration: none; color: var(--mid); flex-shrink: 0;
}
.pwa-back:active { background: var(--pink-soft); }
.pwa-head-title { font-size: 16px; font-weight: 600; }
.pwa-head-sub { font-size: 11px; color: var(--mid); }

.pwa-wrap { max-width: 560px; margin: 0 auto; padding: 14px; display: flex; flex-direction: column; gap: 12px; }
.pwa-sec { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 14px; }
.pwa-sec h2 { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--mid); margin-bottom: 10px; }

.pwa-row { display: flex; align-items: center; gap: 8px; font-size: 14px; padding: 4px 0; }
.pwa-row .ic { width: 20px; text-align: center; flex-shrink: 0; }
.pwa-row .val { flex: 1; min-width: 0; }
.pwa-row a.link { color: var(--pink-dark); text-decoration: none; }
.pwa-name { font-size: 16px; font-weight: 500; margin-bottom: 4px; }

.pwa-retiro-banner { background: #E8F5E9; color: #2E7D32; border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 500; margin-bottom: 10px; }

.pwa-items { list-style: none; display: flex; flex-direction: column; gap: 6px; }
.pwa-items li { display: flex; gap: 8px; font-size: 13px; }
.pwa-items .q { font-weight: 600; color: var(--pink-dark); flex-shrink: 0; min-width: 26px; }
.pwa-totes { border-top: 1px dashed var(--border); margin-top: 10px; padding-top: 10px; font-size: 13px; color: var(--mid); display: flex; flex-direction: column; gap: 3px; }
.pwa-totes .big { font-size: 16px; font-weight: 600; color: var(--dark); }
.pwa-totes .ln { display: flex; justify-content: space-between; }

label.fld { display: block; font-size: 12px; font-weight: 500; color: var(--mid); margin: 12px 0 6px; }
label.fld:first-child { margin-top: 0; }
.pwa-input {
  width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 12px;
  font-family: 'Poppins', sans-serif; font-size: 15px; color: var(--dark); background: #fff; outline: none;
}
.pwa-input:focus { border-color: var(--pink-dark); }

.pwa-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.pwa-pill {
  padding: 8px 14px; border-radius: 30px; border: 1.5px solid var(--border);
  background: #fff; font-family: 'Poppins', sans-serif; font-size: 13px; color: var(--mid);
  cursor: pointer; white-space: nowrap;
}
.pwa-pill:active { background: var(--pink-soft); }
.pwa-pill.is-active { background: var(--pink-dark); border-color: var(--pink-dark); color: #fff; }

.pwa-check { display: flex; align-items: flex-start; gap: 10px; margin-top: 16px; padding: 12px; background: var(--pink-soft); border-radius: 12px; cursor: pointer; }
.pwa-check input { width: 20px; height: 20px; margin-top: 1px; flex-shrink: 0; accent-color: var(--pink-dark); }
.pwa-check .t { font-size: 13px; }
.pwa-check .t strong { font-weight: 600; }
.pwa-check .t small { display: block; color: var(--mid); margin-top: 2px; }

.pwa-savebar {
  position: fixed; left: 0; right: 0; bottom: 0; z-index: 60; background: #fff;
  border-top: 1px solid var(--border);
  padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
}
.pwa-savebar .inner { max-width: 560px; margin: 0 auto; }
.pwa-save-btn {
  width: 100%; padding: 15px; border-radius: 30px; border: none; background: var(--pink-dark);
  color: #fff; font-family: 'Poppins', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer;
}
.pwa-save-btn:active { opacity: .85; }
.pwa-save-btn.loading { opacity: .6; pointer-events: none; }

.pwa-sent { font-size: 11px; color: var(--mid); text-align: center; margin-top: 8px; }
.pwa-foot { text-align: center; font-size: 11px; color: var(--mid); opacity: .7; padding: 4px 0 8px; }
.pwa-admin-link { color: var(--mid); }

.pwa-toast {
  position: fixed; bottom: 86px; left: 50%; transform: translateX(-50%);
  background: var(--dark); color: #fff; font-size: 13px; padding: 11px 18px;
  border-radius: 30px; opacity: 0; transition: opacity .25s; pointer-events: none;
  max-width: 90%; text-align: center; z-index: 70;
}
.pwa-toast.show { opacity: 1; }
.pwa-toast.err { background: #B3261E; }
</style>
</head>
<body>

<header class="pwa-head">
  <a class="pwa-back" href="<?php echo esc_url( $bgmg_pwa_base ); ?>" aria-label="<?php esc_attr_e( 'Volver', 'bgmg-chile' ); ?>">←</a>
  <div>
    <div class="pwa-head-title"><?php esc_html_e( 'Pedido', 'bgmg-chile' ); ?> <?php echo esc_html( $detalle['numero'] ); ?></div>
    <div class="pwa-head-sub"><?php echo esc_html( $detalle['fecha'] ); ?> · <?php echo esc_html( $detalle['wc_status_lbl'] ); ?></div>
  </div>
</header>

<div class="pwa-wrap">

  <!-- Cliente -->
  <section class="pwa-sec">
    <div class="pwa-name"><?php echo esc_html( $detalle['nombre'] ); ?></div>
    <?php if ( $detalle['telefono'] ) : ?>
    <div class="pwa-row"><span class="ic">📞</span><span class="val"><a class="link" href="tel:<?php echo esc_attr( $bgmg_pwa_tel ); ?>"><?php echo esc_html( $detalle['telefono'] ); ?></a></span><a class="link" href="https://wa.me/<?php echo esc_attr( ltrim( $bgmg_pwa_tel, '+' ) ); ?>" target="_blank" rel="noopener">WhatsApp</a></div>
    <?php endif; ?>
    <?php if ( $detalle['correo'] ) : ?>
    <div class="pwa-row"><span class="ic">✉️</span><span class="val"><a class="link" href="mailto:<?php echo esc_attr( $detalle['correo'] ); ?>"><?php echo esc_html( $detalle['correo'] ); ?></a></span></div>
    <?php endif; ?>
    <?php if ( $detalle['rut'] ) : ?>
    <div class="pwa-row"><span class="ic">🆔</span><span class="val"><?php echo esc_html( $detalle['rut'] ); ?></span></div>
    <?php endif; ?>
  </section>

  <!-- Envío -->
  <section class="pwa-sec">
    <h2><?php esc_html_e( 'Envío', 'bgmg-chile' ); ?></h2>
    <?php if ( $detalle['es_retiro'] ) : ?>
      <div class="pwa-retiro-banner">📍 <?php esc_html_e( 'Retiro en tienda', 'bgmg-chile' ); ?></div>
    <?php endif; ?>
    <?php if ( $detalle['calle'] ) : ?>
    <div class="pwa-row"><span class="ic">🏠</span><span class="val"><?php echo esc_html( $detalle['calle'] ); ?></span></div>
    <?php endif; ?>
    <div class="pwa-row"><span class="ic">📍</span><span class="val"><?php echo esc_html( trim( $detalle['comuna'] . ( $detalle['region'] ? ', ' . $detalle['region'] : '' ) ) ); ?></span></div>
    <?php if ( $detalle['metodo_envio'] ) : ?>
    <div class="pwa-row"><span class="ic">🚚</span><span class="val"><?php echo esc_html( $detalle['metodo_envio'] ); ?></span></div>
    <?php endif; ?>
  </section>

  <!-- Productos -->
  <section class="pwa-sec">
    <h2><?php echo esc_html( sprintf( _n( '%d producto', '%d productos', $detalle['item_count'], 'bgmg-chile' ), $detalle['item_count'] ) ); ?></h2>
    <ul class="pwa-items">
      <?php foreach ( $detalle['items'] as $it ) : ?>
      <li><span class="q"><?php echo (int) $it['qty']; ?>×</span><span><?php echo esc_html( $it['nombre'] ); ?></span></li>
      <?php endforeach; ?>
    </ul>
    <div class="pwa-totes">
      <?php if ( $detalle['envio'] ) : ?><div class="ln"><span><?php esc_html_e( 'Envío', 'bgmg-chile' ); ?></span><span><?php echo esc_html( $detalle['envio'] ); ?></span></div><?php endif; ?>
      <div class="ln"><span class="big"><?php esc_html_e( 'Total', 'bgmg-chile' ); ?></span><span class="big"><?php echo esc_html( $detalle['total'] ); ?></span></div>
      <?php if ( $detalle['pago'] ) : ?><div class="ln"><span><?php esc_html_e( 'Pago', 'bgmg-chile' ); ?></span><span><?php echo esc_html( $detalle['pago'] ); ?></span></div><?php endif; ?>
    </div>
    <?php if ( $detalle['nota'] ) : ?>
    <div style="margin-top:10px;padding:10px;background:var(--pink-soft);border-radius:10px;font-size:13px;">📝 <?php echo esc_html( $detalle['nota'] ); ?></div>
    <?php endif; ?>
  </section>

  <!-- Despacho (editable) -->
  <section class="pwa-sec">
    <h2><?php esc_html_e( 'Despacho', 'bgmg-chile' ); ?></h2>

    <label class="fld"><?php esc_html_e( 'Estado', 'bgmg-chile' ); ?></label>
    <div class="pwa-pills" id="pwa-estado-pills">
      <button type="button" class="pwa-pill<?php echo '' === $detalle['estado'] ? ' is-active' : ''; ?>" data-val=""><?php esc_html_e( 'Sin estado', 'bgmg-chile' ); ?></button>
      <?php foreach ( $detalle['estados'] as $slug => $label ) : ?>
      <button type="button" class="pwa-pill<?php echo $detalle['estado'] === $slug ? ' is-active' : ''; ?>" data-val="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></button>
      <?php endforeach; ?>
    </div>
    <input type="hidden" id="pwa-estado" value="<?php echo esc_attr( $detalle['estado'] ); ?>">

    <label class="fld" for="pwa-metodo"><?php esc_html_e( 'Courier / método', 'bgmg-chile' ); ?></label>
    <div class="pwa-pills" id="pwa-courier-pills" style="margin-bottom:8px;">
      <?php foreach ( bgmg_chile_pwa_couriers() as $cur ) : ?>
      <button type="button" class="pwa-pill" data-courier="<?php echo esc_attr( $cur ); ?>"><?php echo esc_html( $cur ); ?></button>
      <?php endforeach; ?>
    </div>
    <input type="text" id="pwa-metodo" class="pwa-input" value="<?php echo esc_attr( $detalle['metodo'] ); ?>" placeholder="<?php esc_attr_e( 'Starken, Bluexpress, moto propia…', 'bgmg-chile' ); ?>" autocomplete="off">

    <label class="fld" for="pwa-codigo"><?php esc_html_e( 'Número de seguimiento', 'bgmg-chile' ); ?></label>
    <input type="text" id="pwa-codigo" class="pwa-input" value="<?php echo esc_attr( $detalle['codigo'] ); ?>" placeholder="<?php esc_attr_e( 'Déjalo vacío si no aplica', 'bgmg-chile' ); ?>" autocomplete="off" inputmode="text">

    <label class="pwa-check">
      <input type="checkbox" id="pwa-avisar">
      <span class="t">
        <strong><?php esc_html_e( 'Avisar al cliente por email', 'bgmg-chile' ); ?></strong>
        <small><?php echo $detalle['correo'] ? esc_html( $detalle['correo'] ) : esc_html__( 'el pedido no tiene correo', 'bgmg-chile' ); ?></small>
      </span>
    </label>

    <?php if ( $detalle['email_fecha'] ) : ?>
    <p class="pwa-sent">✓ <?php printf( esc_html__( 'Último aviso: %s', 'bgmg-chile' ), esc_html( $detalle['email_fecha'] ) ); ?></p>
    <?php endif; ?>
  </section>

  <div class="pwa-foot">
    <a class="pwa-admin-link" href="<?php echo esc_url( $detalle['edit_url'] ); ?>"><?php esc_html_e( 'Abrir en el panel ↗', 'bgmg-chile' ); ?></a>
  </div>

</div>

<div class="pwa-savebar">
  <div class="inner">
    <button type="button" class="pwa-save-btn" id="pwa-save"><?php esc_html_e( 'Guardar', 'bgmg-chile' ); ?></button>
  </div>
</div>

<div class="pwa-toast" id="pwa-toast"></div>

<script>
(function(){
  var AJAX  = <?php echo wp_json_encode( $bgmg_pwa_ajax ); ?>;
  var NONCE = <?php echo wp_json_encode( $bgmg_pwa_nonce ); ?>;
  var OID   = <?php echo (int) $detalle['id']; ?>;
  var CORREO = <?php echo wp_json_encode( $detalle['correo'] ); ?>;

  var toast = document.getElementById('pwa-toast');
  var tTimer;
  function showToast(msg, isErr){
    toast.textContent = msg;
    toast.classList.toggle('err', !!isErr);
    toast.classList.add('show');
    clearTimeout(tTimer);
    tTimer = setTimeout(function(){ toast.classList.remove('show'); }, 3000);
  }

  // Estado: pills → input oculto
  var estadoInput = document.getElementById('pwa-estado');
  document.getElementById('pwa-estado-pills').addEventListener('click', function(e){
    var btn = e.target.closest('.pwa-pill');
    if (!btn) return;
    this.querySelectorAll('.pwa-pill').forEach(function(b){ b.classList.remove('is-active'); });
    btn.classList.add('is-active');
    estadoInput.value = btn.dataset.val || '';
  });

  // Courier: pills → llenan el input (el texto manda)
  var metodoInput = document.getElementById('pwa-metodo');
  document.getElementById('pwa-courier-pills').addEventListener('click', function(e){
    var btn = e.target.closest('.pwa-pill');
    if (!btn) return;
    metodoInput.value = btn.dataset.courier || '';
    metodoInput.focus();
  });

  // Guardar
  var codigoInput = document.getElementById('pwa-codigo');
  var avisarCb    = document.getElementById('pwa-avisar');
  var saveBtn     = document.getElementById('pwa-save');

  saveBtn.addEventListener('click', function(){
    if (saveBtn.classList.contains('loading')) return;

    // Confirmación antes de enviarle un correo al cliente (acción visible).
    if (avisarCb.checked) {
      if (!metodoInput.value.trim() && !codigoInput.value.trim()) {
        showToast('<?php echo esc_js( __( 'Para avisar, escribe el courier o el código.', 'bgmg-chile' ) ); ?>', true);
        return;
      }
      if (!window.confirm('<?php echo esc_js( __( '¿Enviar el aviso de despacho al cliente?', 'bgmg-chile' ) ); ?>' + (CORREO ? '\n' + CORREO : ''))) {
        return;
      }
    }

    saveBtn.classList.add('loading');
    saveBtn.textContent = '<?php echo esc_js( __( 'Guardando…', 'bgmg-chile' ) ); ?>';

    var fd = new FormData();
    fd.append('action', 'bgmg_pwa_guardar');
    fd.append('nonce', NONCE);
    fd.append('order_id', OID);
    fd.append('metodo', metodoInput.value);
    fd.append('codigo', codigoInput.value);
    fd.append('estado', estadoInput.value);
    fd.append('avisar', avisarCb.checked ? '1' : '0');

    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(res){
        saveBtn.classList.remove('loading');
        saveBtn.textContent = '<?php echo esc_js( __( 'Guardar', 'bgmg-chile' ) ); ?>';
        if (!res || !res.success) {
          showToast((res && res.data && res.data.message) || '<?php echo esc_js( __( 'No se pudo guardar', 'bgmg-chile' ) ); ?>', true);
          return;
        }
        showToast(res.data.message || '<?php echo esc_js( __( 'Guardado', 'bgmg-chile' ) ); ?>');
        if (res.data.emailed) { avisarCb.checked = false; }
      })
      .catch(function(){
        saveBtn.classList.remove('loading');
        saveBtn.textContent = '<?php echo esc_js( __( 'Guardar', 'bgmg-chile' ) ); ?>';
        showToast('<?php echo esc_js( __( 'Error de conexión', 'bgmg-chile' ) ); ?>', true);
      });
  });
})();
</script>
</body>
</html>
