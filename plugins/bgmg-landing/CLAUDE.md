# CLAUDE.md — bgmg-landing

> Plugin WordPress (NO es un tema, aunque actúa como tal) que provee templates custom para WooCommerce sirviéndolos vía `template_include`. Sitio en producción: **https://new.beautygirlmg.cl**.

## Contexto del proyecto

Este es uno de **dos plugins hermanos** que cooperan:

- **bgmg-landing** (este repo) — templates + header/footer + minicart + Customizer
- **beautygirlmg-mayorista** (`../beautygirlmg-mayorista/`) — lógica de precios mayoristas

**API entre ellos** está documentada en `../beautygirlmg-mayorista/CONTRATO-PLUGIN-TEMA.md`. Léelo antes de modificar funciones públicas, endpoints AJAX o selectores CSS compartidos con el otro plugin.

## Versión actual

`6.8.1` — última en local. Versiona en **2 sitios**: header del plugin + constante `BGMG_LANDING_VERSION` (cache-buster de los `wp_enqueue_*`). Bumpea **ambos** al cambiar PHP/JS/CSS.

## Arquitectura

```
bgmg-landing.php       ← plugin principal: hooks globales (wp_head, wp_footer),
                         endpoints AJAX (bgmg_update_cart, bgmg_clear_cart,
                         bgmg_search), header/footer, función minicart, filtros
                         template_include que sirven los demás archivos
bgmg-template.php      ← template de la landing page (asignado a una Page)
bgmg-shop.php          ← reemplaza el template de tienda
bgmg-category.php      ← reemplaza el template de categoría
bgmg-product.php       ← reemplaza el template de producto
bgmg-cart.php          ← reemplaza el template de /cart/
bgmg-checkout.php      ← reemplaza el template de /checkout/
bgmg-account.php       ← reemplaza el template de cuenta
inc/
  customizer.php       ← registra panel Customizer (hero + midbanner)
  category-meta.php    ← campos custom en editor de categoría (banner header)
  category-organizer.php ← pantalla admin "Organizar categorías" (árbol drag&drop)
                           + helper bgm_get_nav_cats() (orden/visibilidad canónicos)
BANNERS-CUSTOMIZER-PLAN.md ← roadmap del Customizer, ya completado
```

## Decisiones técnicas importantes

1. **Es un plugin, no un tema.** Por eso los hooks `wp_head` (líneas ~251+ de `bgmg-landing.php`) aplican GLOBALMENTE. Si necesitas CSS que aplique en TODAS las páginas (no solo en una plantilla), va ahí, no en archivos de plantilla individuales.

2. **Estilos + markup del minicart: GLOBALES (BL-01c Fase 1, v6.5.0).** El CSS estructural del panel vive en `assets/bgmg-global.css` (junto a los enhancements); el markup lo rinde `bgmg_render_minicart_panel()` desde `bgmg_render_header()` (cuando `show_cart`). Ya **no** hay copias inline en los templates — el panel aparece en TODAS las páginas desde una sola fuente. **Pendiente Fase 2:** el JS de abrir/cerrar (botón `#bgmg-cart-btn` del header + `added_to_cart`) sigue duplicado per-template; globalizarlo cierra BL-01c (ver `../../HANDOFF.md` §3). *(Histórico: el bloque `#bgmg-mc-enhancements` que esta doc mencionaba nunca existió.)*

3. **Customizer**: panel "BGMG Tema" con secciones para hero slider y banner mid-page. Defaults reproducen el contenido hardcoded anterior — sin configurar nada, el sitio se ve igual. Ver `BANNERS-CUSTOMIZER-PLAN.md`.

4. **Banner por categoría**: term meta `bgm_cat_banner_id` + `_focus` + `_overlay`. Editable desde el editor de la categoría (no del Customizer, porque es per-término).

   **4-bis. Orden y visibilidad de categorías — CANÓNICOS (v6.8.0 / v6.8.1).** Hay **una sola fuente
   de verdad**: el orden manual se guarda en term meta **`order`** (la clave que WC traduce desde
   `orderby => 'menu_order'`) y la visibilidad por **dispositivo** en dos metas opt-in (`'1'` = oculta):
   **`bgm_cat_hide_pc`** (megamenú de escritorio) y **`bgm_cat_hide_mobile`** (hoja de categorías de
   móvil). Se editan con la pantalla **Productos → Organizar categorías** (`inc/category-organizer.php`,
   árbol drag&drop, 2 niveles, con checks **PC** / **Móvil** por categoría). **TODAS** las superficies
   que listan categorías deben usar el helper **`bgm_get_nav_cats($parent = 0, $args = [])`** — NO llamar
   `get_terms` con `orderby` propio. Args útiles: `$parent = null` = todos los niveles; `$args['context']`
   = **`'pc'`** (megamenú), **`'mobile'`** (hoja móvil) o **`'any'`** (default: vitrinas compartidas —
   pills, tienda, categoría, carrito; muestra salvo que esté oculta en AMBOS dispositivos).

5. **`window.bgmAfterAddToCart`**: función definida POR ESTE PLUGIN en bgmg-product.php (~línea 592). El plugin de mayorista la LLAMA tras add-to-cart. NO sobrescribir. El plugin de mayorista tiene una guard `if ( typeof !== 'function' )` que la respeta.

6. **`bgmg_minicart_inner()`**: helper que renderiza el HTML del side-cart. El plugin de mayorista la LLAMA (vía `function_exists()`) para incluir HTML fresco del minicart en sus respuestas AJAX. Es interfaz contrato.

7. **Especificidad CSS contra WooCommerce**: WC inyecta estilos con `.woocommerce .button` que ganan a selectores simples. Para botones críticos (`#place_order`, etc.) usar selectores múltiples + `!important`. Ver el bloque `#place_order` en bgmg-checkout.php.

8. **CSS crítico de estados `.is-open`: INLINE a propósito (v6.7.3). NO mover a global.css.**
   Las reglas que hacen VISIBLE un panel al abrirse (`.bgmg-search-overlay.is-open`,
   `.bgmg-search-backdrop.is-open`, `.bgmg-mc-panel.is-open`, `.bgmg-mc-backdrop.is-open`,
   `.bgmg-catsheet.is-open`, `.bgmg-catsheet-back.is-open`) están **inline en un `<style
   id="bgmg-critical-open">` emitido en `wp_head`** (bgmg-landing.php, junto al enqueue de
   global.css). Motivo: esas clases las añade el JS al hacer clic, así que **no existen en el
   HTML estático** y los optimizadores de *"Quitar CSS sin usar"* (LiteSpeed **UCSS**,
   Autoptimize, etc.) las borran del CSS externo → el panel recibe la clase pero queda
   invisible (buscador/minicart/tab-bar "no funcionan"). Inline son inmunes a esa poda.
   *(Bug real diagnosticado: con UCSS ON, la tienda no abría buscador/carrito en PC; el landing
   sí, porque tenía esas reglas inline en su propia plantilla. Síntoma clásico: el JS añade
   `.is-open` pero `getComputedStyle` muestra el panel oculto.)* **Si añades un panel nuevo que
   se abre por JS, mete su regla `.is-open` en ese bloque inline, no solo en global.css.**

9. **Stock en tarjetas de listado: "Agotado" (v6.7.4).** Las tarjetas de producto NO usaban
   stock (todo se veía comprable). Ahora dos helpers en `bgmg-landing.php` lo resuelven, y se
   usan en TODAS las superficies de listado (tienda, categoría, home destacados/novedades/ofertas,
   relacionados de la ficha, y el AJAX `bgmg_load_products`):
   - `bgmg_card_in_stock($product)`: disponibilidad real. Para **variables** NO se fía de
     `is_in_stock()` del padre (puede quedar "instock" si no se resincronizó al agotarse las
     variaciones); cuenta variaciones comprables vía `bgm_contar_variaciones_disponibles()` del
     plugin **mayorista** (con `function_exists()` guard — soft-dependency).
   - `bgmg_card_action_html($product)`: comprable → botón "+" (ajax); sin stock → `<span>` inerte
     (sin clases ajax, WooCommerce no lo engancha) → no se puede comprar desde el listado.
   - Sin stock se muestra badge **"Agotado"** (reemplaza el de categoría/oferta) y la card se
     atenúa (`.bgmg-card-agotado`). Política elegida: **mostrar marcado, NO ocultar** (conserva
     SEO/descubrimiento). Estilos en global.css con selectores compuestos (ganan a la base
     `.bgmg-btn-add`/`.bgmg-badge` que vive inline en cada template, sin `!important`).
   - La **ficha individual** ya respetaba stock (usa el `variations_form` nativo de WC) — no se tocó.

## Convenciones

- **Sin dependencias externas** salvo Swiper (CDN) en producto + landing hero.
- **Mobile-first**: media queries `@media (min-width: 768px)` para desktop.
- **i18n**: prefijo `bgmg-` (no `bgm-`, ese es del plugin de mayorista).
- **Despliegue**: SSH desde Claude Code → `wp-content/plugins/bgmg-landing/`.

## Tareas frecuentes

- **Bumpear versión**: header del plugin (`Version: X.Y.Z`) **y** la constante `BGMG_LANDING_VERSION` (ambos, o el cache-buster de CSS/JS queda viejo).
- **Editar contenido del hero/midbanner**: wp-admin → Apariencia → Personalizar → BGMG Tema (no tocar código).
- **Editar banner de categoría**: wp-admin → Productos → Categorías → editar.
- **Subir a producción**: comprimir carpeta entera, reemplazar en `wp-content/plugins/`. Incluir `inc/`.

## Pendientes

**BL-01c**: consolidar CSS + markup + JS del minicart a global (hoy duplicado en 7 templates — ver decisión #2 y `../../HANDOFF.md` §3). Lo demás no crítico: ver `BANNERS-CUSTOMIZER-PLAN.md` (Customizer, ya implementado).
