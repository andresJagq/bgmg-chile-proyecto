# Auditoría de optimización — plugins BGMG (pre-migración)

> Iniciada 2026-05-29, en **staging**. Objetivo: dejar los 3 plugins sólidos (sin bugs,
> sin deuda técnica, con buena performance) ANTES de la migración V1→V2, para que esa
> migración sea un paso a paso limpio. Este documento es el entregable vivo de la auditoría.
>
> Severidades: 🔴 crítico · 🟠 alto · 🟡 medio · ⚪ bajo/cosmético.

---

## 1. `bgmg-chile` 1.18.0 — AUDITADO ✓

**Veredicto general: código de muy alta calidad.** Seguridad ejemplar en todos los handlers:
nonces verificados (con `wp_unslash`), `current_user_can` en cada escritura, sanitización
(`sanitize_key`/`sanitize_text_field`/`sanitize_textarea_field`), escapes de salida
(`esc_html`/`esc_attr`/`esc_url`/`wp_kses_post`), `$wpdb->prepare` + formatos en SQL, y
**validación server-side contra POST manipulado** (par región/comuna, listas blancas de
slugs y estados). No se encontró nada crítico ni alto. Los hallazgos son menores.

### Bugs funcionales

| ID | Sev | Archivo | Problema | Fix sugerido |
|----|-----|---------|----------|--------------|
| BC-01 | 🟡 | `inc/rut/account-fields.php` (`bgmg_chile_save_account_billing_rut`, ~L140) | El checkbox **"Necesito factura" no se puede DESMARCAR** desde *Mi cuenta → Editar dirección*: un checkbox desmarcado no viaja en `$_POST`, el `isset()` da false y el meta conserva el `'1'` anterior. | Escribir el meta siempre, explícito: `! empty($_POST['billing_bgmg_necesita_factura']) ? '1' : ''` — igual que ya hace `inc/perfil/admin-user-fields.php` (L294). |
| BC-02 | 🟡 | `inc/envio/admin-despachos-menu.php` (`bgmg_chile_despachos_get_stats`, L404) | El "Estado del sistema" lee `get_option('bgmg_chile_retiro_direccion')`, **opción que nunca se escribe** (los datos del retiro viven en las settings del método de envío) → **siempre** muestra "Retiro en tienda no configurado". | Detectar el retiro como ya se hace en otros lados: buscar el método `bgmg_chile_retiro` en las zonas y leer su `direccion`, o usar `bgmg_chile_obtener_datos_retiro_actual()`. |

### Performance

| ID | Sev | Archivo | Problema | Fix sugerido |
|----|-----|---------|----------|--------------|
| BC-03 | 🟡 | `admin-despachos-menu.php` (Resumen + Reportes) y los 3 wizards | 5 pantallas admin hacen `wc_get_orders(limit=-1)` **sin caché** en cada carga (a 90 días ≈ 1.500 pedidos como objetos). Ya anotado como pendiente 🟢 en el handoff. | `transient` de 5 min por pantalla/ventana, invalidado en `woocommerce_new_order`/`woocommerce_order_status_changed`. Implementar post-deploy con datos reales o ahora si se quiere adelantar. |
| BC-04 | ⚪ | `inc/wizard/wizard-checkout.php` | `rut_stats()` y `telefono_stats()` hacen **dos** `wc_get_orders(limit=-1, 30d)` idénticos en la misma pantalla. | Unificar en una sola pasada que acumule ambos sets de stats. |
| BC-05 | ⚪ | `inc/envio/admin-tarifas-rm.php` (`bgmg_chile_upsert_tarifa_rm`) | SELECT + UPDATE/INSERT por fila → hasta ~104 queries al guardar 52 comunas. | `INSERT … ON DUPLICATE KEY UPDATE` (hay `UNIQUE KEY` en `comuna_slug`). |

### Deuda técnica (DRY / CSS) y consistencia

| ID | Sev | Archivo | Problema |
|----|-----|---------|----------|
| BC-06 | ⚪ | `inc/envio/class-shipping-method.php` | Bloque de cálculo "envío gratis" duplicado casi idéntico en 2 ramas (tarifa fija y default RM). Extraer a helper privado. |
| BC-07 | ⚪ | `inc/envio/class-shipping-retiro.php` | Datos del local (dirección/horario/WhatsApp/instrucciones) triplicados (init / defaults del form / fallback). Riesgo de desincronización. Centralizar en una constante/función. |
| BC-08 | ⚪ | `admin-tarifas-rm.php`, `admin-despachos-menu.php`, los 3 wizards | CSS inline en `<style>` por pantalla. Admin-only (impacto frontend nulo). Coincide con el pendiente "extraer CSS inline" del handoff. |
| BC-09 | ⚪ | `inc/integracion/landing-helpers.php` (`bgmg_chile_render_order_summary`) | Default `mostrar_factura => true` contradice la decisión (2026-05-27) de ocultar "Datos para boleta/factura" al cliente. Hoy no se dispara porque bgmg-landing pasa `false`, pero el default es una trampa latente. Verificar en auditoría de bgmg-landing. |
| BC-10 | ⚪ | `admin-tarifas-rm.php` (`bgmg_chile_save_tarifas_rm_post`) | El contador de "cambios" cuenta todas las filas enviadas (52), no las modificadas → el aviso siempre dice "Se actualizaron 52 comunas". Cosmético. |
| BC-11 | ⚪ | `admin-despachos-menu.php` (placeholder retiro, L531) | El texto dice que los datos del retiro se setean en "Ajustes generales", cuando están en el método de envío. Mensaje incorrecto. |
| BC-12 | ⚪ | `inc/wizard/wizard-envios.php` (L209) | `wp_verify_nonce($_POST['_wpnonce'], …)` sin `wp_unslash` (el resto del plugin sí lo usa). No es bug funcional (nonces son alfanuméricos), solo consistencia. |
| BC-13 | ⚪ | `inc/tracking/class-email-tracking.php` (`trigger`, L159) | Muta `$this->settings['subject'/'heading']` en runtime; si se enviaran varios emails en un request el primero se "pegaría". Impacto nulo en el flujo actual (1 email/request). |
| BC-14 | ⚪ | `inc/rut/class-rut-validator.php` (`format`, L57) | Comentario dice "mínimo 7 caracteres" pero el código valida `< 2`. Solo documentación. |

---

## 2. `bgmg-landing` 6.4.6 — AUDITADO ✓

**Veredicto: seguridad buena; el gran tema es performance (CSS inline).** Es un plugin de
templates (~1.000-1.200 líneas/archivo, mayormente CSS/JS inline). Auditado a fondo:
`bgmg-landing.php` (main, 1214 L), `inc/account-renders.php`, `inc/category-meta.php`,
`inc/customizer.php`, `bgmg-template.php`. Los 5 templates de presentación (shop/category/
product/cart/checkout) se revisaron por búsqueda dirigida (no línea por línea del CSS).

Seguridad **correcta y consistente**: AJAX con `check_ajax_referer` + **rate-limiting** +
sanitización; entradas `$_GET/$_POST` todas saneadas; `current_user_can('view_order')` en el
detalle de pedido; `current_user_can` en el save de category-meta; Customizer con
`sanitize_callback` en cada setting; echos **pre-escapados** (`esc_html`/`esc_url`/`esc_attr`/
`wp_kses_post`); forms sensibles (editar dirección/cuenta) delegados a templates nativos de
WC. **No se encontró XSS ni entrada cruda.**

### Performance

| ID | Sev | Archivo | Problema | Fix sugerido |
|----|-----|---------|----------|--------------|
| BL-01 | 🟡 (el más relevante) | `bgmg-landing.php` (wp_head) + todos los templates | **CSS 100% inline y en parte DUPLICADO.** El CSS de header/minicart/search/footer vive en bloques `wp_head` globales y **además** se repite en `bgmg-template.php`; cada template trae su propio `<style>` grande. Nada es cacheable como archivo externo → se reenvía en cada request. Coincide con el pendiente 🟢 "extraer CSS inline" del handoff. | Extraer a `assets/*.css` con `wp_enqueue_style` (versionado). Cacheable por navegador/CDN, baja el peso de cada página y mejora TTFB/FCP. Beneficio real a 50K visits/mes. |
| BL-02 | 🟡 | `bgmg-landing.php` L29 | Filtro `gettext` **global** para 4 strings de rebrand: corre en cada string traducido del frontend (miles/request). Handler liviano, pero el hook es muy caliente. | Acotar con filtros WC específicos (`woocommerce_checkout_fields`, labels) o `gettext_woocommerce`. |
| BL-03 | ⚪ | `bgmg-landing.php` (`minicart_inner`, `update_cart`) + `bgmg-cart.php` | Algoritmo de descuento/ahorro por ítem (fee_share/saving/unit_actual) **duplicado en ~4 lugares**. Riesgo de divergencia. | Extraer a un helper compartido `bgmg_calc_item_saving($item, …)`. |
| BL-04 | ⚪ | `bgmg-shop.php`, `bgmg-category.php` | `$price_min`/`$price_max` emitidos en JS/atributos sin cast `(int)` explícito. No es XSS (los precios los fija el admin), pero conviene castear. | `echo (int) $price_min;` al emitir. |
| BL-07 | ✅ | `bgmg-landing/CLAUDE.md` | Doc desactualizada: dice "Versión actual 6.2.1" (código en 6.4.6) y referencia rutas `C:\Users\maria\Desktop\…` de otra PC. | **Hecho 2026-05-31:** versión→6.4.12, rutas→relativas, y la decisión #2 (fantasma `#bgmg-mc-enhancements`) reescrita a la realidad (CSS inline en 7 templates, pendiente BL-01c). |

> BL-05/06: el contrato con `beautygirlmg-mayorista` (`window.bgmAfterAddToCart`,
> `bgmg_minicart_inner()`) se verifica en la auditoría del mayorista (sección 3).

## 3. `beautygirlmg-mayorista` 2.5.2 — AUDITADO ✓

**Veredicto: muy alta calidad, seguridad sólida.** Modular y limpio (como bgmg-chile).
Auditado a fondo: bootstrap, `CONTRATO-PLUGIN-TEMA.md`, core (helpers/settings/ajax-helpers),
los 3 endpoints AJAX, `frontend/carrito.php`, `modos/modo-auto.php`, y los 2 guardados de meta
admin (`pestana-mayorista.php`, `editor-variaciones.php`). Cobertura de seguridad verificada
por grep en **todo** el plugin.

Seguridad **completa** (grep sin excepciones):
- 3 endpoints AJAX (`bgm_agregar_auto/manual`, `bgm_evaluar_surtido`) con `check_ajax_referer`
  + `absint` en todos los inputs.
- 2 guardados de meta admin con nonce + `current_user_can('edit_product')` + `wc_clean` +
  `is_numeric`.
- Acción de logs con `manage_woocommerce` + nonce.
- **Todos** los `$_GET/$_POST` saneados. Sin input crudo.
- **Precio mayorista 100% server-side** en `woocommerce_before_calculate_totals` (reevaluado en
  cada cálculo; nunca confía en el cliente). Regla de surtido centralizada en un solo sitio PHP.
  Distribución auto robusta (cap por stock + loop anti-infinito).
- El checkbox swatches usa el patrón hidden-0 + checkbox-1 (correcto) — justo el patrón que le
  FALTA a BC-01 en bgmg-chile.
- Código muerto (módulo migración v1→v2) ya eliminado en 2.5.2 — confirmado ausente del bootstrap.

| ID | Sev | Archivo | Hallazgo | Fix sugerido |
|----|-----|---------|----------|--------------|
| BM-01 | ✅ | `ajax/ajax-*.php` | Los 3 endpoints AJAX son `nopriv` (públicos) y estaban **sin rate-limiting**, a diferencia de los del tema. `bgm_evaluar_surtido` se llama en vivo (debounce 300ms). | **Hecho 2026-05-31 (v2.5.4):** helper `bgm_rate_limit_exceeded` en `core/ajax-helpers.php`; auto/manual 30/min, evaluar 120/min (generoso para no romper el surtido en vivo). |
| BM-02 | ⚪ | `ajax/ajax-evaluar.php` (L104-112) | La selección de tier (tier2 si qty≥min_2, si no tier1) se **replica** del cálculo de `bgm_calcular_precio` (helpers.php). | Unificar la selección de tier en un helper. |
| BM-03 | ⚪ | `ajax/ajax-manual.php` L25, `ajax-evaluar.php` L43 | `(array) $_POST['cantidades']` sin verificar que los valores sean escalares antes de `absint`. Si un value es array → warning PHP con `WP_DEBUG` (no explotable). | Castear/validar escalar antes de `absint`, o `array_map`. |
| BM-04 | ✅ | `CLAUDE.md`, `CONTRATO-PLUGIN-TEMA.md` §9 | Docs desactualizadas: versión "2.5.1"/"2.5.0"/"6.2" (código real 2.5.2 / 6.4.12) y rutas `C:\Users\maria\Desktop\…`. | **Hecho 2026-05-31:** `CLAUDE.md` mayorista (2.5.2 + ruta relativa + memoria nativa eliminada) y `CONTRATO-PLUGIN-TEMA.md` §9 (2.5.2 / 6.4.12). |

---

# 4. Resumen consolidado y plan priorizado

**Conclusión global:** los 3 plugins están **bien construidos**. Seguridad sólida en los tres
(nonces, capabilities, sanitización, escapes, SQL con `prepare`, precios server-side,
validación contra POST manipulado). **No hay hallazgos críticos ni altos. Nada bloquea la
migración.** Lo encontrado es pulido: 2 bugs funcionales menores, performance conocida (caché +
CSS inline) y deuda técnica (DRY, docs).

### 4.1. Bugs reales — ✅ RESUELTOS en bgmg-chile 1.18.1 (2026-05-29)
| ID | Qué se rompía | Estado |
|----|---------------|--------|
| **BC-01** | El checkbox "Necesito factura" no se podía DESMARCAR desde Mi cuenta → Editar dirección. | ✅ Corregido en `inc/rut/account-fields.php`: el checkbox se escribe SIEMPRE (`'1'`/`''`), coherente con el checkout. |
| **BC-02** | El panel "Estado del sistema" siempre decía "Retiro no configurado" (leía opción que nunca se escribe). | ✅ Corregido en `inc/envio/admin-despachos-menu.php`: ahora detecta el método `bgmg_chile_retiro` en las zonas de envío. |

> **✅ Desplegado y verificado en vivo (2026-05-29):** BC-01 y BC-02 OK en staging.
>
> **Bonus — bugs del Customizer encontrados en testeo, corregidos en `bgmg-landing` 6.4.7
> (verificados en vivo 2026-05-29):**
> - **Hero slider:** subir SOLO la imagen de celular dejaba el slide sin fondo. Causa: el CSS de
>   fondo dependía de la imagen desktop. Fix en `bgmg-template.php` (imagen base con fallback a
>   mobile + `$has_image` mira ambas).
> - **Banner mid-page:** mismo patrón, mismo fix.

### 4.2. Optimización de performance (el objetivo declarado del staging)
| ID | Plugin | Acción | Impacto |
|----|--------|--------|---------|
| **BL-01** | bgmg-landing | Extraer CSS inline → `assets/*.css` con `wp_enqueue_style`. Quitar la duplicación header/minicart/search. | **Alto** a 50K visits/mes (cacheable, baja peso/TTFB). El más valioso. |
| **BC-03** | bgmg-chile | Caché `transient` (5 min) en stats de Resumen/Reportes/wizards. | Medio (post-deploy con datos reales). |
| BC-05 / BL-03 / BM-02 | varios | Micro-optimizaciones y DRY (upsert SQL, helpers de descuento/tier). | Bajo. |

#### Progreso de BL-01 (al 2026-05-31) — `bgmg-landing` en **6.5.0**
Se hace POR PARTES; cada tanda se valida en vivo antes de seguir. La versión sube en cada tanda
(header del plugin + constante `BGMG_LANDING_VERSION`).

| Tanda | Estado | Detalle |
|---|---|---|
| Fix hero / mid-banner (bug del Customizer, no era BL-01) | ✅ verificado | Imagen base con fallback: subir SOLO la imagen de celular dejaba el slide/banner sin fondo. v6.4.7-6.4.8 |
| **BL-01a** footer → `assets/bgmg-footer.css` | ✅ verificado en vivo | ~3.5 KB. v6.4.8 |
| **BL-01b** global (header/minicart/buscador/tab bar) → `assets/bgmg-global.css` | ✅ extraído, OK en páginas normales | ~17.5 KB. v6.4.9. Las variables `:root` las aporta el `<style>` de cada template (consolidar en BL-01c). |
| Fix **Mi cuenta** lupa | ✅ OK en vivo | `bgmg-account.php` era el ÚNICO template sin `#bgmg-mc-panel` ni el JS de lupa/carrito; se le agregaron en v6.4.10. La lupa quedó OK. |
| Fix **Mi cuenta** minicart (CSS) | ✅ OK en vivo | v6.4.10 agregó markup + JS pero faltó el CSS del panel → el carrito salía suelto. **v6.4.12** le pegó el bloque del minicart. Validado. (La globalización real fue BL-01c Fase 1, abajo.) |
| Fix **checkout PC** (1 columna) | ✅ OK en vivo | `.col2-set` se ponía en 2 columnas desde 540px → achicaba facturación y mandaba "Notas del pedido" a una columna derecha casi vacía. Forzado a 1 columna en `bgmg-checkout.php`. v6.4.11 |
| **BL-01c Fase 1** — minicart CSS + markup → global | ✅ OK en vivo (v6.5.0) | CSS estructural → `assets/bgmg-global.css` (fusionado con enhancements); markup → `bgmg_render_minicart_panel()` en `bgmg_render_header()`. Quitadas las 7 copias inline. Validado en las 7 páginas. |
| **BL-01c Fase 2** — minicart + lupa JS → global | ✅ OK (v6.5.3) | Bloque único `bgmg-header-ui-js` (wp_footer, corre en checkout); lupa + abrir/cerrar minicart con delegación en `document`. Quitadas las **8 copias inline** (7 templates + 404) → de 8 a 1. **BL-01c CERRADO.** |
| Consolidar `:root` (variables de color) a un base.css global | ⬜ pendiente | Hoy cada template aporta su `:root`. Independiente del minicart. |

> **Pendiente al retomar:** BL-01c Fase 1 (CSS+markup) **validada en 6.5.0**. Decidir si se hace la
> **Fase 2** (JS abrir/cerrar a global) o se pasa al backlog de mayor valor (config wp-admin +
> migración V1→V2). Ver `HANDOFF.md` §2–§3.

### 4.3. Deuda técnica / limpieza (bajo riesgo)
**✅ Aplicados 2026-05-31 (quick wins, riesgo nulo):** BC-09 (default `mostrar_factura`→false),
BC-11 (texto retiro), BC-12 (`wp_unslash` nonce), BC-14 (comentario RUT), BM-03 (guarda escalar
antes de `absint`); BL-04 ya estaba cubierto (precios casteados a int en origen); BL-07 y BM-04
(docs) hechos antes. → bumps **bgmg-chile 1.18.2**, **mayorista 2.5.3**.

**✅ BM-01 aplicado 2026-05-31 (mayorista 2.5.4):** rate-limiting en los 3 AJAX públicos del
mayorista (helper `bgm_rate_limit_exceeded` en `core/ajax-helpers.php`; auto/manual 30/min,
evaluar 120/min). Era el único pendiente con valor de seguridad.

**⬜ Pendientes (más involucrados — no son "quick"):** DRY (BC-06, BC-07, BL-03, BM-02), perf
(BC-04 dos queries idénticas, BC-05 upsert SQL, BL-02 `gettext` global), cosméticos (BC-10 contador
de comunas, BC-13 settings mutadas en runtime). Ver detalle en cada sección.

### 4.4. Orden de trabajo sugerido (un ciclo por plugin: editar → bump versión → zip)
1. **bgmg-chile** → BC-01, BC-02 (bugs) + BC-05/BC-10 (quick wins). Bump 1.18.0 → 1.18.1.
2. **bgmg-landing** → BL-01 (CSS a archivos) como tarea principal + BL-03/BL-04. Bump 6.4.6 → 6.5.0.
3. **beautygirlmg-mayorista** → BM-01/BM-02/BM-04. Bump 2.5.2 → 2.5.3.
4. **BC-03** (caché de stats): dejar para post-deploy con tráfico real, o adelantar si se quiere.
5. Regenerar los 4 ZIPs (script PowerShell en `CLAUDE.md` § Cómo regenerar zips) y subir.

> Decisiones de producto a confirmar antes de tocar: BC-09 (default `mostrar_factura` — hoy
> mitigado) y si BC-03 se adelanta o se difiere a post-deploy.

*Auditoría completada 2026-05-29 en staging. Sin hallazgos críticos/altos en los 3 plugins.*
