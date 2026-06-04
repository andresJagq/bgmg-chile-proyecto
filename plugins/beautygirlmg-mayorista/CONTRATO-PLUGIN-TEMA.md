# Contrato plugin ↔ tema

> **Plugins involucrados**
> - **beautygirlmg-mayorista** (este repo) — lógica de precios mayoristas, modos auto/manual, swatches, evaluación de surtido
> - **bgmg-landing** — templates custom del sitio (header, footer, landing, producto, /cart/, minicart) servidos vía `template_include`
>
> Ambos son **plugins de WordPress** que cooperan. Conviven y se llaman entre sí. Este documento es la API pública entre ellos: qué expone cada uno, qué eventos comparten, qué metas y selectores son contrato.

## 1. Resumen visual

```
                      bgmg-landing (plantillas)
                              ▲
                              │
         llama funciones      │ renderiza con datos
         del plugin           │ del plugin
                              │
                              ▼
                  beautygirlmg-mayorista (lógica)

                              ▲
                              │ plugin lee del tema en
                              │ respuestas AJAX (minicart_html)
                              │
                              ▼
                       bgmg_minicart_inner()
```

El plugin de mayorista **no requiere** el tema para funcionar; cae a comportamiento por defecto si las funciones del tema no existen (todas las llamadas van guarded con `function_exists()`).

---

## 2. Funciones PHP que el plugin expone al tema

Funciones globales del plugin que el tema (o cualquier otro plugin) puede llamar:

| Función | Sirve para | Dónde la usa hoy |
|---|---|---|
| `bgm_tiene_precio_mayorista( $product_id, $variation_id = 0 )` | Detectar si un producto tiene mayorista configurado | `bgmg-product.php` (label "Precio detalle") |
| `bgm_variable_tiene_mayorista( $product )` | Lo mismo pero para producto variable (chequea modo único e individual) | `bgmg-product.php` (`$bgm_is_variable_mayorista`) |
| `bgm_resumen_mayorista_variable( $product )` | Devuelve `[min_1, min_2, desc_1_max, desc_2_max]` | `bgmg-product.php` (badge del tab "Por mayor", cálculo de % máximo) |
| `bgm_get_precio_base( $product )` | Precio regular ignorando ofertas WC | `bgmg-product.php` |
| `bgm_render_mayorista_bloque_publico( $product )` | Renderiza el bloque mayorista completo del producto variable | `bgmg-product.php` dentro del tab "Por mayor" |
| `bgm_render_chip_minicart( $cart_item )` | Devuelve HTML de un chip "⚠ Sin mayorista" si el item es de surtido y el grupo no califica. `''` si no aplica | `bgmg-landing.php` → `bgmg_minicart_inner()` |
| `bgm_render_avisos_grupos_cart()` | Devuelve HTML de bloque "Estado de tus surtidos mayoristas" para `/cart/` | `bgmg-cart.php` antes del form de items |
| `bgm_estado_grupo_variaciones( $padre_id )` | Devuelve `['califica', 'razon', 'tier', 'qty_total', 'mensaje', ...]` del grupo de variaciones del padre en el carrito actual | Usado internamente por las dos funciones de arriba. Cacheada por request. |
| `bgm_capacidades_variaciones( $product )` | Devuelve `[vid => stock_max]` por variación | Usado en evaluación; disponible para el tema si lo necesita |
| `bgm_evaluar_distribucion( $product_id, $cantidades, $n_disponibles = null, $stocks = null )` | Evalúa si una distribución cumple la regla de surtido. Devuelve `true` o `WP_Error`. **Centralizado**: única fuente de verdad de la regla | Plugin solo, pero expuesto si el tema lo necesita |
| `bgm_get_min_1` / `min_2` / `descuento_1` / `descuento_2` ( $product_id, $variation_id = 0 ) | Getters de config del producto | Tema podría usarlos para badges custom |
| `bgm_get_promo_info( $product )` | Devuelve `['precio_base','precio_promo','ahorro','pct','qty_min']` o `null` si la promo no está activa/elegible. Fuente del % para el badge unificado | `woocommerce_get_price_html` (precio tachado) + badge unificado del tema (`bgmg_oferta_badge_html`) |
| `bgm_get_oferta_etiqueta()` | Texto configurable del badge de descuento. Default `'Oferta'`. Config en WC → Ajustes → Mayorista | Badge unificado (`bgmg_oferta_badge_html`) + overlay de la galería |
| `bgm_get_oferta_descuento_pct( $product )` | `int` con el % de la **oferta nativa de WC** (ej. `17`) o `0`. Soporta simple/variación/variable (mayor % entre variaciones). Solo se usa como fallback si NO hay promo | Badge unificado + overlay |
| `bgm_promo_ids_afectados()` | `int[]` de IDs de productos en promo (categorías ∪ personalizados − excluidos). Cacheado 5 min. NO filtra por fecha/toggle | Sección "Precios irresistibles" de la home (`bgmg-template.php`) |
| `bgm_promo_badge_html( $product )` | HTML del badge "−X% / Promo". **OBSOLETO** desde la unificación del badge: el tema ya no lo llama (lo reemplaza el badge unificado `bgmg_oferta_badge_html`). Se mantiene por compatibilidad | — (sin uso en el tema) |

> **Badge UNIFICADO** (decisión 2026-06-03): el tema muestra UN solo badge de descuento `🔥 {etiqueta} -X%` vía `bgmg_oferta_badge_html()`, que cubre **promo** (precede) y **oferta nativa de WC** (fallback). El nombre sale de `bgm_get_oferta_etiqueta()`; el % de `bgm_get_promo_info()` o, si no hay promo, de `bgm_get_oferta_descuento_pct()`. El plugin expone solo DATOS; el markup/CSS del badge (pill `.bgmg-badge-oferta` + overlay `.bgmg-oferta-overlay`) lo arma el tema.

**Buena práctica**: el tema siempre debe envolver con `function_exists( 'bgm_xxx' )` para que el sitio no se rompa si el plugin se desactiva.

---

## 3. Funciones PHP que el tema expone al plugin

| Función | Sirve para | Dónde la usa el plugin |
|---|---|---|
| `bgmg_minicart_inner()` | Renderiza el HTML del side-cart desde `<div id="bgmg-minicart-inner">` | Plugin: helper `bgm_ajax_responder_exito()` (en `includes/ajax/ajax-auto.php`) lo llama vía `ob_start()` para incluirlo en la respuesta AJAX como `minicart_html` |
| `bgmg_get_cat_banner( $term )` | Devuelve datos del banner header de la categoría (`url_desktop`, `url_mobile`, `focus`, `overlay`) | Usado por `bgmg-category.php` (mismo tema) — podría usarlo el plugin si necesita |

**Buena práctica**: el plugin debe envolver con `function_exists( 'bgmg_minicart_inner' )` (ya lo hace) para que sus AJAX funcionen aunque el tema no esté activo.

---

## 4. Eventos JavaScript

### `window.bgmAfterAddToCart( data )` — interfaz crítica

**Definida por**: el tema (`bgmg-landing.php` → `bgmg-product.php` JS inline)
**Llamada por**: `frontend-auto.js` y `frontend-manual.js` del plugin tras un add-to-cart AJAX exitoso

El plugin tiene una guard explícita para NO sobrescribir esta función:
```js
// frontend-common.js
if ( typeof window.bgmAfterAddToCart !== 'function' ) {
    window.bgmAfterAddToCart = function( respData ) { /* fallback */ };
}
```

**Contrato del parámetro `data`** que el plugin envía:
```js
{
    message:       string,    // 'Surtido agregado al carrito.'
    cart_count:    int,       // qty total del carrito
    cart_url:      string,    // wc_get_cart_url()
    minicart_html: string,    // HTML completo de #bgmg-minicart-inner (si bgmg_minicart_inner existe)
    distribucion:  object,    // [vid => qty] solo en modo auto
    qty_total:     int        // solo en modo manual
}
```

**Lo que hace la función del tema con esos datos**:
1. Reemplaza el contenido de `#bgmg-minicart-inner` con `data.minicart_html`
2. Actualiza todos los `.bgmg-cart-count` con `data.cart_count`
3. Abre el `#bgmg-mc-panel` con la clase `.is-open`

### Eventos estándar de WC

| Evento | Quién dispara | Quién escucha |
|---|---|---|
| `wc_fragment_refresh` | Plugin (`frontend-common.js`) tras add-to-cart | WC core + tema (via filter `woocommerce_add_to_cart_fragments` que devuelve `#bgmg-minicart-inner`) |
| `added_to_cart` | Plugin (`frontend-common.js`) tras add-to-cart | Cualquier escucha estándar de WC. Algunos themes lo usan para abrir side-cart. El tema bgmg-landing no lo usa porque tiene `bgmAfterAddToCart` |

---

## 5. Metas / claves de datos compartidas

### Cart item data

Set por plugin, leído por plugin + tema:

| Key | Valor | Set por | Leído por |
|---|---|---|---|
| `bgm_origen` | `'auto'` o `'manual'` | `add_to_cart` en `ajax-auto.php` y `ajax-manual.php` | Plugin (`bgm_estado_grupo_variaciones`) + tema (`bgmg_minicart_inner` para clase `bgm-item-surtido`) |

**Importante**: WC NO fusiona items con `cart_item_data` distinto. Si el cliente agrega vía Sorpréndeme y luego vía detalle normal, verá 2 entradas separadas del mismo producto/variación.

### Post meta (productos)

Set por el plugin desde el editor de producto:

| Key | Valor |
|---|---|
| `_bgm_descuento_1` / `_bgm_descuento_2` | float — descuento en $ por tier |
| `_bgm_min_1` / `_bgm_min_2` | int — cantidad mínima por tier |
| `_bgm_modo_descuento` | `'unico'` o `'individual'` (solo variables) |
| `_bgm_tolerancia_porcentaje` | int — override por producto (default global) |
| `_bgm_usar_swatches` | `'0'` o `'1'` (default: `'1'` cuando no hay valor) |

### Term meta (categorías de producto)

Set por el tema desde el editor de categoría:

| Key | Valor |
|---|---|
| `bgm_cat_banner_id` | int — attachment ID de la imagen |
| `bgm_cat_banner_focus` | `'center'` / `'left'` / `'right'` / etc |
| `bgm_cat_banner_overlay` | `'1'` o `''` |

### Theme mods (WP Customizer)

Set por el tema:

| Key pattern | Valor |
|---|---|
| `bgmg_slide_{1,2,3}_image_desktop` | URL imagen desktop hero |
| `bgmg_slide_{1,2,3}_image_mobile` | URL imagen mobile hero |
| `bgmg_slide_{1,2,3}_enabled/focus/overlay/badge/label/title/subtitle/pill_{1,2,3}/cta_text/cta_url` | Config por slide |
| `bgmg_midbanner_{enabled/style/image_desktop/image_mobile/focus/overlay/title/subtitle/cta_text/cta_url}` | Config banner mid-page |
| `custom_logo` | int — attachment ID (nativo de WP) |

---

## 6. Endpoints AJAX

### Plugin (acción WP)
| Action | Descripción | Nonce |
|---|---|---|
| `bgm_agregar_auto` | Agrega surtido Sorpréndeme | `bgm_auto` |
| `bgm_agregar_manual` | Agrega surtido manual | `bgm_manual` |
| `bgm_evaluar_surtido` | Evalúa distribución del surtido manual (centralizado v2.5.0+) | `bgm_manual` |

### Tema (acción WP)
| Action | Descripción | Nonce |
|---|---|---|
| `bgmg_update_cart` | Actualiza qty o elimina item del carrito | `bgmg_cart` |
| `bgmg_clear_cart` | Vacía el carrito completo | `bgmg_cart` |
| `bgmg_search` | Búsqueda live de productos/categorías | `bgmg_search` |

---

## 7. Selectores CSS compartidos (contrato)

Selectores que ambos lados conocen. Cambiar uno requiere cambiar el otro.

| Selector | Definido por | Usado por |
|---|---|---|
| `.bgmg-cart-count` | Tema (header, footer, tab bar) | Plugin (`frontend-common.js` `actualizarContadorHeader`) |
| `#bgmg-mc-panel` | Tema (panel del side-cart) | Plugin no toca directamente; lo abre la función `bgmAfterAddToCart` del tema |
| `#bgmg-minicart-inner` | Tema (wrapper del HTML interno) | Plugin reemplaza este nodo con `minicart_html` |
| `.bgmg-mc-item` con `[data-key]` | Tema (cada item del minicart) | Plugin no toca directamente |
| `.bgmg-mc-item.bgm-item-surtido` | Tema agrega esta clase | Plugin: indicador de que el item vino de Sorpréndeme/Manual |
| `.bgm-bloque-mayor`, `.bgm-bloque-auto`, `.bgm-bloque-manual`, `.bgm-subtabs`, `.bgm-subtab`, `.bgm-subpanel` | Plugin (render del bloque mayorista) | Plugin (frontend-auto/manual.js) |
| `.bgm-variacion-row[data-vid][data-stock]` | Plugin (render manual) | Plugin (frontend-manual.js) |
| `form.cart input[name="quantity"]` | WC core | Plugin (`frontend-simple.js` para preview en vivo) |
| `form.variations_form table.variations select` | WC core | Plugin (`frontend-swatches.js` los convierte en pills) |
| `.bgm-promo-badge` | Plugin (HTML vía `bgm_promo_badge_html`) | Tema (estilo en `assets/bgmg-global.css`, cargado en todas las páginas) |
| `.bgmg-badge-oferta` | Tema (HTML vía `bgmg_oferta_badge_html`, texto+% del plugin) | Tema (estilo per-template + `bgmg-global.css`). Badge de oferta nativa de WC en tarjetas |
| `.bgmg-oferta-overlay`, `.bgmg-card-badges` | Tema | Tema. Overlay del badge sobre la imagen del producto y wrapper categoría+oferta lado a lado |

---

## 8. Cómo extender la integración

### Si el plugin se desactiva
- El tema sigue funcionando, sin avisos ni bloques mayoristas.
- Las llamadas a funciones del plugin están envueltas en `function_exists()`.

### Si el tema se desactiva (vuelve a un tema normal)
- El plugin sigue funcionando con su `bgmAfterAddToCart` de fallback (toast flotante).
- El bloque mayorista del producto se renderiza por hook `woocommerce_after_single_product_summary` (fallback genérico).
- El contador del header NO se actualiza si el otro tema no usa `.bgmg-cart-count` (el plugin tiene heurística con otros selectores comunes: `.cart-count`, `[data-cart-count]`, etc.).

### Agregar nueva integración
Patrón recomendado:

1. **Plugin** expone una función pública `bgm_xxx()` documentada acá.
2. **Tema** la llama con guard:
   ```php
   if ( function_exists( 'bgm_xxx' ) ) {
       echo bgm_xxx( $args );
   }
   ```
3. **Versionar**: si la firma cambia (parámetros o retorno), bump major del plugin y agregar nota en este documento.

### Agregar nuevo selector compartido
Documentarlo en la tabla de la sección 7 ANTES de usarlo en ambos lados, así nadie lo cambia accidentalmente.

---

## 9. Versionado y cambios breaking

Cuando una de estas APIs cambia de forma incompatible:

- Bump **MAJOR** del plugin que cambió (ej: `2.x.x` → `3.0.0`)
- Actualizar este documento describiendo el cambio
- Si el otro plugin depende de la versión, agregar check de versión:
  ```php
  if ( defined( 'BGM_VERSION' ) && version_compare( BGM_VERSION, '3.0', '<' ) ) {
      // comportamiento legacy
  }
  ```

**Versiones actuales** (2026-05-31; tabla viva en `../../CLAUDE.md`):
- `beautygirlmg-mayorista`: 2.5.2 (con `bgm_evaluar_surtido` AJAX endpoint)
- `bgmg-landing`: 6.4.12 (con Customizer + banner mid-page + banner por categoría)
