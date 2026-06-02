<?php
/* Template Name: BGMG Tienda */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="es" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tienda — BeautyGirlMG</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
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

/* ── MINI CART ── CSS estructural movido a assets/bgmg-global.css (BL-01c). ── */

/* ── CARD DE PRODUCTO ───────────────────────────────────────── */
.bgmg-card { display: flex; align-items: center; gap: 12px; padding: 12px; background: #fff; border: 1px solid var(--border); border-radius: 16px; position: relative; }
.bgmg-card-img { width: 80px; height: 80px; border-radius: 12px; flex-shrink: 0; object-fit: cover; }
.bgmg-card-link { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; text-decoration: none; color: inherit; }
.bgmg-card-link:hover .bgmg-card-name { color: var(--pink-dark); }
.bgmg-card-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.bgmg-badge { display: inline-block; font-size: 10px; font-weight: 500; color: var(--pink-dark); background: var(--pink-soft); padding: 2px 8px; border-radius: 20px; align-self: flex-start; }
.bgmg-badge-oferta { display: inline-block; font-size: 10px; font-weight: 500; color: #fff; background: var(--pink-dark); padding: 2px 8px; border-radius: 20px; align-self: flex-start; }
.bgmg-card-name { font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 400; color: var(--dark); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; }
.bgmg-card-price { font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; color: var(--dark); }
.bgmg-card-price del { font-size: 12px; color: var(--mid); margin-left: 4px; font-weight: 300; }
.bgmg-btn-add { width: 38px; height: 38px; border-radius: 50%; border: 1.5px solid var(--pink); background: #fff; color: var(--pink-dark); font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-decoration: none; line-height: 1; transition: background .2s, color .2s; }
.bgmg-btn-add:hover { background: var(--pink); color: #fff; }

/* ── STICKY CTA ─────────────────────────────────────────────── */
.bgmg-sticky { position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999; background: #fff; border-top: 1px solid var(--border); padding: 10px 16px 14px; display: flex; gap: 10px; box-shadow: 0 -4px 20px rgba(0,0,0,.06); }
.bgmg-sticky-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 13px 10px; border-radius: 30px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500; text-decoration: none; transition: opacity .2s; }
.bgmg-sticky-btn:hover { opacity: .85; }
.bgmg-sticky-wa   { background: #25D366; color: #fff; }
.bgmg-sticky-shop { background: var(--dark); color: #fff; }

/* ── SHOP ───────────────────────────────────────────────────── */
.bgmg-shop-wrap { padding-top: 64px; min-height: 100vh; }
.bgmg-shop-head {
  background: #fff; border-bottom: 1px solid var(--border);
  padding: 24px 20px 0; position: sticky; top: 64px; z-index: 100;
}
.bgmg-shop-title {
  font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 400;
  color: var(--dark); margin-bottom: 16px;
}
.bgmg-shop-cats {
  display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none;
  -webkit-overflow-scrolling: touch; padding-bottom: 12px; flex-wrap: nowrap;
}
.bgmg-shop-cats::-webkit-scrollbar { display: none; }
.bgmg-shop-cat {
  flex-shrink: 0; padding: 8px 18px; border-radius: 30px;
  border: 1.5px solid var(--border); background: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--mid);
  cursor: pointer; transition: all .2s; white-space: nowrap;
}
.bgmg-shop-cat:hover { background: var(--pink-soft); border-color: var(--pink); color: var(--dark); }
.bgmg-shop-cat.is-active { background: var(--dark); border-color: var(--dark); color: #fff; }
/* Subcategorías */
.bgmg-subcats { overflow: hidden; max-height: 0; transition: max-height .3s ease, padding .3s ease; padding: 0 20px; }
.bgmg-subcats.is-open { max-height: 80px; padding: 8px 20px 10px; }
.bgmg-subcats-scroll { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; padding-bottom: 2px; }
.bgmg-subcats-scroll::-webkit-scrollbar { display: none; }
.bgmg-subcat { flex-shrink: 0; padding: 6px 14px; border-radius: 30px; border: 1.5px solid var(--pink); background: var(--pink-soft); font-family: 'DM Sans', sans-serif; font-size: 12px; color: var(--mid); cursor: pointer; transition: all .2s; white-space: nowrap; }
.bgmg-subcat:hover { background: var(--pink); border-color: var(--pink); color: var(--dark); }
.bgmg-subcat.is-active { background: var(--pink-dark); border-color: var(--pink-dark); color: #fff; }
.bgmg-shop-toolbar {
  display: flex; align-items: center; gap: 8px; padding: 10px 0 14px;
}
.bgmg-price-wrap { position: relative; }
.bgmg-price-btn {
  display: flex; align-items: center; gap: 6px; padding: 9px 16px;
  border-radius: 30px; border: 1.5px solid var(--border); background: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--mid);
  cursor: pointer; white-space: nowrap; transition: all .2s;
}
.bgmg-price-btn:hover, .bgmg-price-btn.is-active { border-color: var(--pink-dark); color: var(--pink-dark); background: var(--pink-soft); }
.bgmg-price-dropdown {
  position: absolute; top: calc(100% + 8px); left: 0;
  background: #fff; border: 1px solid var(--border); border-radius: 16px;
  padding: 20px; width: 280px; box-shadow: 0 8px 32px rgba(0,0,0,.10);
  z-index: 200; display: none;
}
.bgmg-price-dropdown.is-open { display: block; }
.bgmg-price-dropdown-title { font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 1.5px; color: var(--mid); margin-bottom: 16px; }
.bgmg-price-range { position: relative; height: 32px; margin-bottom: 12px; }
.bgmg-price-track { position: absolute; top: 50%; transform: translateY(-50%); left: 0; right: 0; height: 4px; background: var(--border); border-radius: 2px; pointer-events: none; }
.bgmg-price-fill { position: absolute; height: 100%; background: var(--pink-dark); border-radius: 2px; }
.bgmg-price-range input[type="range"] {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 100%; height: 4px; background: transparent;
  -webkit-appearance: none; appearance: none; pointer-events: none; outline: none;
}
.bgmg-price-range input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none; width: 20px; height: 20px; border-radius: 50%;
  background: var(--pink-dark); cursor: pointer; pointer-events: all;
  border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.bgmg-price-range input[type="range"]::-moz-range-thumb {
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--pink-dark); cursor: pointer; pointer-events: all;
  border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.bgmg-price-labels { display: flex; justify-content: space-between; font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--mid); }
.bgmg-price-apply {
  display: block; width: 100%; margin-top: 14px; padding: 11px;
  border-radius: 30px; background: var(--dark); color: #fff; border: none;
  font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
  cursor: pointer; transition: opacity .2s;
}
.bgmg-price-apply:hover { opacity: .85; }
.bgmg-shop-sort {
  margin-left: auto; padding: 9px 16px; border-radius: 30px;
  border: 1.5px solid var(--border); background: #fff;
  font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--mid);
  cursor: pointer; -webkit-appearance: none; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237A5060' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center;
  padding-right: 32px; outline: none; transition: border-color .2s;
}
.bgmg-shop-sort:focus { border-color: var(--pink-dark); }

/* Products list */
.bgmg-shop-list { padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.bgmg-shop-empty { text-align: center; padding: 60px 20px; font-family: 'DM Sans', sans-serif; font-size: 15px; color: var(--mid); }

/* Load more */
.bgmg-load-more-wrap { text-align: center; padding: 8px 20px 100px; }
.bgmg-load-more-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 36px; border-radius: 30px; background: #fff;
  color: var(--dark); border: 1.5px solid var(--border);
  font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500;
  cursor: pointer; transition: all .2s;
}
.bgmg-load-more-btn:hover { background: var(--pink-soft); border-color: var(--pink); }
.bgmg-load-more-btn.is-loading { opacity: .6; pointer-events: none; }
.bgmg-load-more-btn.is-hidden { display: none; }

/* ── ANIMACIONES ────────────────────────────────────────────── */
@keyframes bgmg-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

/* ── RESPONSIVE ─────────────────────────────────────────────── */
@media (min-width: 768px) {
  .bgmg-header { height: 72px; }
  .bgmg-header-inner { padding: 0 40px; }
  .bgmg-dnav { display: flex; }
  .bgmg-hamburger { display: none; }
  .bgmg-search-overlay { top: 72px; padding: 18px 40px; }
  .bgmg-shop-wrap { padding-top: 72px; }
  .bgmg-shop-head { top: 72px; padding: 28px 40px 0; }
  .bgmg-shop-title { font-size: 36px; }
  .bgmg-shop-list { padding: 20px 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .bgmg-load-more-wrap { padding: 16px 40px 60px; }
  .bgmg-sticky { display: none; }
  .bgmg-subcats.is-open { padding: 8px 40px 10px; }
}
</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// Datos de cabecera
$logo_id     = get_theme_mod('custom_logo');
$parent_cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'exclude' => array(get_option('default_product_cat')), 'orderby' => 'name'));
$parent_cats = is_wp_error($parent_cats) ? array() : $parent_cats;

// Precio mínimo y máximo — cacheado 6h en transient
$price_row = get_transient('bgmg_price_range');
if ($price_row === false) {
    global $wpdb;
    $price_row = $wpdb->get_row("
        SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) as min_p,
               MAX(CAST(meta_value AS DECIMAL(10,2))) as max_p
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_price' AND CAST(meta_value AS DECIMAL(10,2)) > 0
        AND p.post_type = 'product' AND p.post_status = 'publish'
    ");
    set_transient('bgmg_price_range', $price_row, 6 * HOUR_IN_SECONDS);
}
$price_min = $price_row ? (int) floor($price_row->min_p) : 0;
$price_max = $price_row ? (int) ceil($price_row->max_p)  : 100000;

// Categorías: una sola query, árbol en PHP (elimina N+1)
$all_terms = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'exclude'    => array(get_option('default_product_cat')),
    'orderby'    => 'count',
    'order'      => 'DESC',
));
$all_terms  = is_wp_error($all_terms) ? array() : $all_terms;
$all_cats   = array();
$cats_index = array();
foreach ($all_terms as $t) { $cats_index[$t->term_id] = $t; }
foreach ($all_terms as $t) {
    if ((int)$t->parent === 0) $all_cats[] = $t;
}

// Búsqueda desde formulario
$search_q = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

// Productos iniciales (8)
$init_args = array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 8, 'paged' => 1, 'orderby' => 'date', 'order' => 'DESC');
if ($search_q !== '') { $init_args['s'] = $search_q; }
$init_query = new WP_Query($init_args);
$has_more_init = $init_query->max_num_pages > 1;
?>

<?php bgmg_render_header(); ?>

<?php /* Mini-cart panel: ahora lo rinde bgmg_render_header() (BL-01c). */ ?>

<!-- SHOP -->
<div class="bgmg-shop-wrap">
  <div class="bgmg-shop-head">
    <h1 class="bgmg-shop-title">Tienda</h1>

    <!-- Pills de categorías top-level -->
    <div class="bgmg-shop-cats">
      <button class="bgmg-shop-cat is-active" data-cat="0" data-children="">Todas</button>
      <?php foreach ($all_cats as $cat) :
        // Hijos desde el índice en memoria — 0 queries adicionales
        $kids = array_filter($all_terms, function($t) use ($cat){ return (int)$t->parent === (int)$cat->term_id; });
        $kids_json = json_encode(array_values(array_map(function($k){ return array('id' => $k->term_id, 'name' => $k->name); }, $kids)));
      ?>
      <button class="bgmg-shop-cat" data-cat="<?php echo esc_attr($cat->term_id); ?>" data-children="<?php echo esc_attr($kids_json); ?>"><?php echo esc_html($cat->name); ?></button>
      <?php endforeach; ?>
    </div>

    <!-- Subcategorías (aparece al seleccionar una categoría con hijos) -->
    <div class="bgmg-subcats" id="bgmg-subcats">
      <div class="bgmg-subcats-scroll" id="bgmg-subcats-inner"></div>
    </div>

    <!-- Toolbar: precio + ordenar -->
    <div class="bgmg-shop-toolbar">
      <div class="bgmg-price-wrap">
        <button class="bgmg-price-btn" id="bgmg-price-btn">💰 Precio ▾</button>
        <div class="bgmg-price-dropdown" id="bgmg-price-dropdown">
          <div class="bgmg-price-dropdown-title">Rango de precio</div>
          <div class="bgmg-price-range">
            <div class="bgmg-price-track"><div class="bgmg-price-fill" id="bgmg-price-fill"></div></div>
            <input type="range" id="bgmg-range-min" min="<?php echo $price_min; ?>" max="<?php echo $price_max; ?>" value="<?php echo $price_min; ?>" step="1000">
            <input type="range" id="bgmg-range-max" min="<?php echo $price_min; ?>" max="<?php echo $price_max; ?>" value="<?php echo $price_max; ?>" step="1000">
          </div>
          <div class="bgmg-price-labels">
            <span id="bgmg-label-min">$<?php echo number_format($price_min, 0, ',', '.'); ?></span>
            <span id="bgmg-label-max">$<?php echo number_format($price_max, 0, ',', '.'); ?></span>
          </div>
          <button class="bgmg-price-apply" id="bgmg-price-apply">Aplicar</button>
        </div>
      </div>
      <select class="bgmg-shop-sort" id="bgmg-shop-sort">
        <option value="date">Más recientes</option>
        <option value="popularity">Más vendidos</option>
        <option value="price">Menor precio</option>
        <option value="price-desc">Mayor precio</option>
      </select>
    </div>
  </div>

  <!-- Lista de productos -->
  <div class="bgmg-shop-list" id="bgmg-shop-list">
    <?php
    if ($init_query->have_posts()) :
      while ($init_query->have_posts()) : $init_query->the_post();
        echo bgmg_product_card_html(get_the_ID());
      endwhile;
      wp_reset_postdata();
    else :
      echo '<p class="bgmg-shop-empty">No hay productos disponibles.</p>';
    endif;
    ?>
  </div>

  <!-- Ver más -->
  <div class="bgmg-load-more-wrap">
    <button class="bgmg-load-more-btn<?php echo $has_more_init ? '' : ' is-hidden'; ?>" id="bgmg-load-more" data-page="1">Ver más productos</button>
  </div>
</div>

<script>
(function(){


  // Lupa + abrir/cerrar minicart: ahora GLOBALES (bgmg-header-ui-js en bgmg-landing.php, BL-01c Fase 2).

  // ── SHOP FILTERS & LOAD MORE ────────────────────────────────
  var currentPage   = 1;
  var currentCat    = 0;
  var currentSearch = <?php echo json_encode($search_q); ?>;
  var currentMin    = <?php echo $price_min; ?>;
  var currentMax    = <?php echo $price_max; ?>;
  var priceMin     = <?php echo $price_min; ?>;
  var priceMax     = <?php echo $price_max; ?>;
  var currentOrder = 'date';
  var isLoading    = false;

  // Precio range
  var rangeMin  = document.getElementById('bgmg-range-min');
  var rangeMax  = document.getElementById('bgmg-range-max');
  var labelMin  = document.getElementById('bgmg-label-min');
  var labelMax  = document.getElementById('bgmg-label-max');
  var fill      = document.getElementById('bgmg-price-fill');

  function updatePriceFill(){
    var mn = parseInt(rangeMin.value);
    var mx = parseInt(rangeMax.value);
    var total = priceMax - priceMin;
    var leftPct  = ((mn - priceMin) / total) * 100;
    var rightPct = ((mx - priceMin) / total) * 100;
    fill.style.left  = leftPct + '%';
    fill.style.width = (rightPct - leftPct) + '%';
    labelMin.textContent = '$' + mn.toLocaleString('es-CL');
    labelMax.textContent = '$' + mx.toLocaleString('es-CL');
  }
  if(rangeMin) rangeMin.addEventListener('input', function(){
    if(parseInt(rangeMin.value) > parseInt(rangeMax.value)) rangeMin.value = rangeMax.value;
    updatePriceFill();
  });
  if(rangeMax) rangeMax.addEventListener('input', function(){
    if(parseInt(rangeMax.value) < parseInt(rangeMin.value)) rangeMax.value = rangeMin.value;
    updatePriceFill();
  });
  updatePriceFill();

  // Toggle precio dropdown
  var priceBtn = document.getElementById('bgmg-price-btn');
  var priceDrop = document.getElementById('bgmg-price-dropdown');
  if(priceBtn) priceBtn.addEventListener('click', function(e){
    e.stopPropagation();
    priceDrop.classList.toggle('is-open');
    priceBtn.classList.toggle('is-active');
  });
  document.addEventListener('click', function(e){
    if(priceDrop && !priceDrop.contains(e.target) && e.target !== priceBtn){
      priceDrop.classList.remove('is-open'); priceBtn.classList.remove('is-active');
    }
  });

  // Aplicar precio
  var applyBtn = document.getElementById('bgmg-price-apply');
  if(applyBtn) applyBtn.addEventListener('click', function(){
    currentMin = parseInt(rangeMin.value);
    currentMax = parseInt(rangeMax.value);
    priceDrop.classList.remove('is-open'); priceBtn.classList.remove('is-active');
    // Marcar botón si filtro activo
    if(currentMin > priceMin || currentMax < priceMax) priceBtn.classList.add('is-active');
    loadProducts(true);
  });

  // Categorías + subcategorías
  var subcatsBar   = document.getElementById('bgmg-subcats');
  var subcatsInner = document.getElementById('bgmg-subcats-inner');

  function wireSubcats() {
    if (!subcatsInner) return;
    subcatsInner.querySelectorAll('.bgmg-subcat').forEach(function(sb){
      sb.addEventListener('click', function(){
        subcatsInner.querySelectorAll('.bgmg-subcat').forEach(function(b){ b.classList.remove('is-active'); });
        this.classList.add('is-active');
        currentCat = parseInt(this.dataset.cat);
        loadProducts(true);
      });
    });
  }

  document.querySelectorAll('.bgmg-shop-cat').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.bgmg-shop-cat').forEach(function(b){ b.classList.remove('is-active'); });
      this.classList.add('is-active');
      currentCat = parseInt(this.dataset.cat);

      // Mostrar / ocultar subcategorías
      var kids = [];
      try { kids = JSON.parse(this.dataset.children || '[]'); } catch(e) {}

      if (kids.length > 0 && currentCat !== 0) {
        var html = '<button class="bgmg-subcat is-active" data-cat="' + currentCat + '">Todas</button>';
        kids.forEach(function(k){ html += '<button class="bgmg-subcat" data-cat="' + k.id + '">' + k.name + '</button>'; });
        subcatsInner.innerHTML = html;
        subcatsBar.classList.add('is-open');
        wireSubcats();
      } else {
        subcatsBar.classList.remove('is-open');
        subcatsInner.innerHTML = '';
      }

      loadProducts(true);
    });
  });

  // Ordenar
  var sortSel = document.getElementById('bgmg-shop-sort');
  if(sortSel) sortSel.addEventListener('change', function(){
    currentOrder = this.value;
    loadProducts(true);
  });

  // Load more
  var loadMoreBtn = document.getElementById('bgmg-load-more');
  if(loadMoreBtn) loadMoreBtn.addEventListener('click', function(){
    loadProducts(false);
  });

  function loadProducts(reset){
    if(isLoading) return;
    isLoading = true;
    if(reset){ currentPage = 1; }
    else { currentPage++; }

    var list = document.getElementById('bgmg-shop-list');
    if(reset){
      list.innerHTML = '<div class="bgmg-shop-empty">Cargando...</div>';
      if(loadMoreBtn) loadMoreBtn.classList.add('is-hidden');
    } else {
      if(loadMoreBtn) loadMoreBtn.classList.add('is-loading');
    }

    var data = new FormData();
    data.append('action',    'bgmg_load_products');
    data.append('nonce',     window.bgmgAjax ? window.bgmgAjax.shopNonce : '');
    data.append('page',      currentPage);
    data.append('cat',       currentCat);
    data.append('search',    currentSearch);
    data.append('min_price', currentMin);
    data.append('max_price', currentMax);
    data.append('orderby',   currentOrder);

    fetch(window.bgmgAjax ? window.bgmgAjax.url : '/wp-admin/admin-ajax.php', { method: 'POST', body: data })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if(reset){ list.innerHTML = res.html || '<p class="bgmg-shop-empty">No hay productos para estos filtros.</p>'; }
        else { list.insertAdjacentHTML('beforeend', res.html); }
        if(loadMoreBtn){
          if(res.has_more){ loadMoreBtn.classList.remove('is-hidden'); loadMoreBtn.classList.remove('is-loading'); }
          else { loadMoreBtn.classList.add('is-hidden'); }
        }
        isLoading = false;
      })
      .catch(function(){ isLoading = false; if(loadMoreBtn) loadMoreBtn.classList.remove('is-loading'); });
  }

  // Drag-to-scroll en pills (desktop)
  var catsEl = document.querySelector('.bgmg-shop-cats');
  if (catsEl) {
    var isDragging = false, startX, scrollLeft;
    catsEl.addEventListener('mousedown', function(e) {
      isDragging = true; startX = e.pageX - catsEl.offsetLeft; scrollLeft = catsEl.scrollLeft;
      catsEl.style.cursor = 'grabbing'; catsEl.style.userSelect = 'none';
    });
    document.addEventListener('mouseup', function() {
      isDragging = false; catsEl.style.cursor = 'grab'; catsEl.style.userSelect = '';
    });
    document.addEventListener('mousemove', function(e) {
      if (!isDragging) return;
      e.preventDefault();
      var x = e.pageX - catsEl.offsetLeft;
      catsEl.scrollLeft = scrollLeft - (x - startX) * 1.5;
    });
    // Scroll con rueda del mouse también
    catsEl.addEventListener('wheel', function(e) {
      if (e.deltaY !== 0) { e.preventDefault(); catsEl.scrollLeft += e.deltaY; }
    }, { passive: false });
    catsEl.style.cursor = 'grab';
  }

  // Fragmentos WooCommerce
  if(typeof jQuery !== 'undefined'){
    jQuery(function($){ $(document.body).trigger('wc_fragment_refresh'); });
  }
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
