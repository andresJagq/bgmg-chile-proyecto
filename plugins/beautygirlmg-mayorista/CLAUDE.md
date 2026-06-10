# CLAUDE.md — beautygirlmg-mayorista

> Plugin WordPress/WooCommerce con lógica de precios mayoristas, modos de surtido (Sorpréndeme / Manual), swatches de variaciones y evaluación de surtido equilibrado. Sitio en producción: **https://new.beautygirlmg.cl**.

## Contexto del proyecto

Este es uno de **dos plugins hermanos** que cooperan:

- **beautygirlmg-mayorista** (este repo) — lógica de negocio mayorista
- **bgmg-landing** (`C:\Users\maria\Desktop\bgmg-landing\`) — templates custom del sitio (header, landing, /cart/, /checkout/, producto, categoría) servidos vía `template_include`

**API entre ellos** está documentada en `CONTRATO-PLUGIN-TEMA.md` (en este repo). Léelo antes de modificar funciones públicas, endpoints AJAX o selectores CSS compartidos.

## Versión actual

`2.7.6` — última en local. Bumpea con cada cambio de PHP/JS/CSS para forzar cache-busting (las versiones de scripts/styles se cuelgan de `BGM_VERSION`).

## Arquitectura

```
beautygirlmg-mayorista.php   ← bootstrap, define BGM_VERSION
includes/
  core/        helpers, settings, logger
  admin/       pestana-mayorista, editor-variaciones
  modos/       modo-auto, modo-manual
  ajax/        ajax-auto, ajax-manual, ajax-evaluar (regla centralizada)
  frontend/    producto-simple, producto-variable, carrito, swatches, avisos-carrito
assets/        admin.js+css, frontend-*.js, frontend.css
```

## Decisiones técnicas importantes

1. **Regla de surtido equilibrado** vive 100% en PHP (`bgm_evaluar_distribucion` en helpers.php). El JS la consulta via AJAX `bgm_evaluar_surtido` con debounce 300ms. **No duplicar en JS** — fue intencionalmente centralizado en v2.5.0.

2. **Regla "atrancadas no cuentan"**: si una variación tiene `qty === stock_máximo`, se excluye del cálculo de tolerancia. El cliente no se penaliza por restricciones de stock.

3. **Flag `bgm_origen`** (valor único `'surtido'` desde v2.7.6; antes 'auto'|'manual') en `cart_item_data`. NO afecta el precio (solo distingue items de surtido vs detalle normal para avisos visuales). **Nadie lee el valor — solo presencia (`!empty`)**; por eso se unificó: con el mismo valor, WC fusiona la misma variación venga de Sorpréndeme o de Manual (antes salían líneas duplicadas en la misma orden). Surtido vs DETALLE (sin flag) sigue SIN fusionarse a propósito (los avisos "no califica" aplican solo a líneas de surtido).

4. **Despliegue** vía SSH con Claude Code. Antes de asumir que un bug "raro" es del código local, verificar que el servidor tenga la versión actualizada (con `fetch(scriptSrc).then(r=>r.text())` en consola del navegador).

5. **Swatches activos por defecto**: `bgm_usar_swatches()` trata meta vacío como `true`. Productos sin configurar tienen pills automáticamente.

6. **Stock de variaciones en la ficha (v2.7.4):** las variaciones SIN stock se muestran **deshabilitadas ("Agotado"), no se ocultan**. El filtro `woocommerce_variation_is_active` (en `frontend/swatches.php`) devuelve `false` si `! is_in_stock()` → WC deshabilita la opción (swatch tachado, no seleccionable) pero la mantiene visible. La etiqueta "Agotado" (CSS `.bgm-swatches.is-single`) solo aplica en productos de **UN atributo** (ahí deshabilitado == sin stock; en multi-atributo un pill puede deshabilitarse por combinación, así que solo va tachado). **Dependencia crítica:** requiere el ajuste de WooCommerce *"ocultar artículos sin stock"* **APAGADO** — si se enciende, WC excluye la variación del JSON y no se puede marcar. El ocultado de PRODUCTOS agotados del catálogo lo hace **bgmg-landing** (independiente de ese ajuste), no este. **POR MAYOR:** si el producto tiene 0 variaciones con stock, muestra "no disponible" en vez del builder (`frontend/producto-variable.php`).

## Convenciones

- **Sin dependencias externas** (jQuery viene con WP, no agregar libs).
- **Sin frameworks JS** modernos (no React/Vue).
- **i18n**: textos hardcoded en español pero envueltos en `__( 'texto', 'beautygirlmg-mayorista' )`.
- **Hooks** con prefijo `bgm_`, JS handlers con `BGM_*` localizados.
- **`function_exists()` guards** en funciones públicas que el tema consume.
- **Memoria persistente** en `~/.claude/projects/C--Users-maria-Desktop-beautygirlmg-mayorista/memory/` (no versionada).

## Tareas frecuentes

- **Bumpear versión**: 2 lugares — header del plugin (`Version: X.Y.Z`) + constante `BGM_VERSION`.
- **Subir a producción**: comprimir carpeta entera, reemplazar en `wp-content/plugins/`.
- **Verificar archivo en producción**: `fetch('https://new.beautygirlmg.cl/wp-content/plugins/beautygirlmg-mayorista/assets/frontend-manual.js').then(r=>r.text()).then(t=>console.log(t.length))` desde la consola del navegador en el sitio.

## Pendientes y referencia

Roadmap actualizado y otros docs en la memoria persistente (cargada automáticamente). En el repo, ver también:
- `CONTRATO-PLUGIN-TEMA.md` — API entre plugin y tema
- `README.md` y `SPECS.md` — descripciones funcionales
