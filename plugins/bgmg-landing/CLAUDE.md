# CLAUDE.md — bgmg-landing

> Plugin WordPress (NO es un tema, aunque actúa como tal) que provee templates custom para WooCommerce sirviéndolos vía `template_include`. Sitio en producción: **https://new.beautygirlmg.cl**.

## Contexto del proyecto

Este es uno de **dos plugins hermanos** que cooperan:

- **bgmg-landing** (este repo) — templates + header/footer + minicart + Customizer
- **beautygirlmg-mayorista** (`../beautygirlmg-mayorista/`) — lógica de precios mayoristas

**API entre ellos** está documentada en `../beautygirlmg-mayorista/CONTRATO-PLUGIN-TEMA.md`. Léelo antes de modificar funciones públicas, endpoints AJAX o selectores CSS compartidos con el otro plugin.

## Versión actual

`6.5.0` — última en local. Versiona en **2 sitios**: header del plugin + constante `BGMG_LANDING_VERSION` (cache-buster de los `wp_enqueue_*`). Bumpea **ambos** al cambiar PHP/JS/CSS.

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
BANNERS-CUSTOMIZER-PLAN.md ← roadmap del Customizer, ya completado
```

## Decisiones técnicas importantes

1. **Es un plugin, no un tema.** Por eso los hooks `wp_head` (líneas ~251+ de `bgmg-landing.php`) aplican GLOBALMENTE. Si necesitas CSS que aplique en TODAS las páginas (no solo en una plantilla), va ahí, no en archivos de plantilla individuales.

2. **Estilos + markup del minicart: GLOBALES (BL-01c Fase 1, v6.5.0).** El CSS estructural del panel vive en `assets/bgmg-global.css` (junto a los enhancements); el markup lo rinde `bgmg_render_minicart_panel()` desde `bgmg_render_header()` (cuando `show_cart`). Ya **no** hay copias inline en los templates — el panel aparece en TODAS las páginas desde una sola fuente. **Pendiente Fase 2:** el JS de abrir/cerrar (botón `#bgmg-cart-btn` del header + `added_to_cart`) sigue duplicado per-template; globalizarlo cierra BL-01c (ver `../../HANDOFF.md` §3). *(Histórico: el bloque `#bgmg-mc-enhancements` que esta doc mencionaba nunca existió.)*

3. **Customizer**: panel "BGMG Tema" con secciones para hero slider y banner mid-page. Defaults reproducen el contenido hardcoded anterior — sin configurar nada, el sitio se ve igual. Ver `BANNERS-CUSTOMIZER-PLAN.md`.

4. **Banner por categoría**: term meta `bgm_cat_banner_id` + `_focus` + `_overlay`. Editable desde el editor de la categoría (no del Customizer, porque es per-término).

5. **`window.bgmAfterAddToCart`**: función definida POR ESTE PLUGIN en bgmg-product.php (~línea 592). El plugin de mayorista la LLAMA tras add-to-cart. NO sobrescribir. El plugin de mayorista tiene una guard `if ( typeof !== 'function' )` que la respeta.

6. **`bgmg_minicart_inner()`**: helper que renderiza el HTML del side-cart. El plugin de mayorista la LLAMA (vía `function_exists()`) para incluir HTML fresco del minicart en sus respuestas AJAX. Es interfaz contrato.

7. **Especificidad CSS contra WooCommerce**: WC inyecta estilos con `.woocommerce .button` que ganan a selectores simples. Para botones críticos (`#place_order`, etc.) usar selectores múltiples + `!important`. Ver el bloque `#place_order` en bgmg-checkout.php.

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
