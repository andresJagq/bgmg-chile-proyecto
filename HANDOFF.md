# HANDOFF BGMG Chile — estado vivo del proyecto

> **Doc ÚNICO y vivo.** Se **actualiza en sitio**, NO se crea uno nuevo por sesión.
> Arriba (§1–§4) está lo **volátil** (estado actual + qué sigue): reescribir al retomar.
> De §5 en adelante es **conocimiento durable**: referencia estable, solo se corrige.
> Snapshots viejos (27, 28, 30 may 2026) archivados en `historial/` — solo por si se
> necesita el detalle granular (changelogs fase a fase, datos crudos de Clarity).
>
> Última actualización: **2026-05-31**

---

## 1. Estado actual

**Fase:** optimizar/pulir los 3 plugins en **staging** antes de la migración V1→V2 (que es un
paso posterior). Auditoría de los 3 plugins **COMPLETA**, sin hallazgos críticos/altos — plan y
estado vivo de correcciones en `AUDITORIA-OPTIMIZACION.md` §4.

| Pieza | Versión código |
|---|---|
| bgmg-chile | **1.18.2** |
| bgmg-landing | **6.5.5** |
| beautygirlmg-mayorista | **2.5.4** |
| bgmg-tema-base | 1.1.0 |

**Hecho y VERIFICADO en vivo:**
- **bgmg-chile 1.18.1** — BC-01 (checkbox "Necesito factura" se desmarca) + BC-02 (estado retiro
  en el panel Resumen).
- **bgmg-landing hasta 6.4.9** — fix hero/mid-banner (imagen solo-mobile no se veía) + **BL-01a**
  (footer CSS → `assets/bgmg-footer.css`) + **BL-01b** (CSS global header/minicart/buscador/tab bar
  → `assets/bgmg-global.css`). OK en páginas normales.
- **bgmg-landing 6.4.11** — lupa en Mi cuenta + checkout PC a 1 columna. OK confirmado por el usuario.

- **bgmg-landing 6.4.12** — fix del CSS estructural del panel minicart en Mi cuenta (faltaba el CSS
  en `bgmg-account.php`; el carrito salía suelto). **Validado en vivo.**
- **bgmg-landing 6.5.0** — **BL-01c Fase 1:** minicart consolidado a global. CSS estructural →
  `assets/bgmg-global.css` (una sola vez, fusionado con los enhancements); markup → helper
  `bgmg_render_minicart_panel()` llamado desde `bgmg_render_header()`. **Eliminadas las 7 copias
  inline** (CSS + markup). **Validado en vivo en las 7 páginas.** El JS de abrir/cerrar quedó
  per-template (Fase 2 — ver §3).
- **Quick wins de auditoría (riesgo nulo, zips listos para subir):** **bgmg-chile 1.18.2** (BC-09
  default `mostrar_factura`→false, BC-11 texto del placeholder de retiro, BC-12 `wp_unslash` en nonce
  del wizard, BC-14 comentario del validador RUT) + **mayorista 2.5.3** (BM-03 guarda escalar antes
  de `absint` en los AJAX). BL-04 ya estaba cubierto (precios casteados a int en origen).
- **mayorista 2.5.4 (zip listo):** **BM-01** — rate-limit en los 3 AJAX públicos del mayorista
  (`bgm_rate_limit_exceeded` en `core/ajax-helpers.php`; auto/manual 30/min, evaluar 120/min).
- **bgmg-landing 6.5.3 (zip listo, PENDIENTE validar):** template **404 branded** (`bgmg-404.php`).
  Reusa el header global + footer + tab bar + `bgmg-global.css`; muestra "404 / Ups, no encontramos
  esta página" + CTA a Tienda/Inicio; mantiene el **status HTTP 404** (verificado: el sitio ya
  devuelve 404, no soft-404). Enganchado en `template_include` con `is_404()`. Reemplaza el fallback
  del tema (`bgmg-tema-base/index.php`), que no tenía header, era inconsistente y filtraba
  "BeautyNew3" en el `<title>`. **Validar:** subir el zip y visitar una URL inexistente (la **lupa,
  el carrito y el footer** deben funcionar). Desde **6.5.3** el JS de lupa/carrito es global
  (BL-01c Fase 2), así que el 404 lo hereda sin copia local.
- **bgmg-landing 6.5.3 (zip listo, PENDIENTE validar):** **BL-01c Fase 2** — JS de **lupa + abrir/cerrar
  minicart** globalizado en UN bloque (`bgmg-header-ui-js`, wp_footer, corre en checkout también).
  **Eliminadas las 8 copias inline** (7 templates + el 404) → de 8 a 1. Botón del header y de la tab
  bar enganchados por delegación en `document`. **Validar página por página** (lupa + carrito
  abren/cierran): landing, tienda, categoría, producto, carrito, checkout, Mi cuenta, **404**.
- **bgmg-landing 6.5.4-6.5.5 (zip listo, PENDIENTE validar):**
  - **6.5.4 cosmética** (producto): quitado "Cambio garantizado" de los badges + variación
    ("Tono"/swatches) centrada (CSS de `.variations` a bloque centrado en `bgmg-product.php`).
  - **6.5.5 — add-to-cart del producto por AJAX:** endpoint `bgmg_add_to_cart` (bgmg-landing.php,
    hermano de update/clear) + intercepción del `form.cart` en el bloque `bgmg-header-ui-js`. El
    "Añadir al carrito" del producto ya **no recarga**: agrega por AJAX y abre el side-cart (como las
    tarjetas). Soporta variables (`variation_id` + atributos). Ante cualquier falla cae al **submit
    normal de WC**. NO toca el surtido del mayorista (otra ruta). **Validar a fondo (flujo de compra).**

---

## 2. Pendiente inmediato

**Validación en vivo (usuario):** subir los zips pendientes. Para **bgmg-landing 6.5.3** (BL-01c
Fase 2): que la **lupa** y el **carrito** abran/cierren en las **7 páginas + el 404** (es lo que se
globalizó). Después, lo de mayor valor para *lanzar*: los 2 ajustes 🔴 de wp-admin (§4) y el
**script de migración V1→V2**.

> **BL-01c quedó CERRADO** (Fase 1 CSS+markup + Fase 2 JS). El minicart/lupa están 100% globales,
> de 8 copias a 1. Detalle en §3.

---

## 3. BL-01c — consolidar el minicart a global

**Fase 1 (CSS + markup): HECHA y validada en vivo (6.5.0).** CSS estructural en
`assets/bgmg-global.css` (fusionado con los enhancements, `border:none !important` de `.bgmg-mc-rm`
preservado); markup vía `bgmg_render_minicart_panel()` desde `bgmg_render_header()` (se rinde cuando
`show_cart`). Eliminadas las 7 copias inline (CSS + markup). La doc fantasma `#bgmg-mc-enhancements`
ya fue corregida en los `CLAUDE.md` y comentarios.

**Fase 2 (JS de lupa + abrir/cerrar minicart): HECHA (6.5.3).** Se creó UN bloque global
`bgmg-header-ui-js` (wp_footer, prioridad 20, SIN excluir checkout) con la lupa (buscador + live
search) + abrir/cerrar del minicart; el botón del header (`#bgmg-cart-btn`) y el de la tab bar
(`#bgmg-tab-cart`) se enganchan por **delegación en `document`**. Se le quitó el open/close al bloque
`bgmg-tabbar-js` (quedó solo el bottom-sheet) y se borraron las **8 copias inline** (7 templates +
el 404). Resultado: de 8 copias a 1. Estado del JS ANTES del refactor (referencia histórica):
- **Ya global:** cantidades/eliminar/vaciar (`bgmg-mc-qty-js`, wp_footer, corre en todas las
  páginas) + abrir/cerrar de la **tab bar móvil** (`bgmg-tabbar-js`, wp_footer) — pero ese bloque
  hace `if (is_checkout()) return;`, así que **NO corre en checkout**.
- **Duplicado per-template (7):** wiring del **botón de carrito del header** (`#bgmg-cart-btn` →
  abrir) + `added_to_cart` → abrir. En checkout, `bgmg-checkout.php` (~L532-541) es lo único que
  abre el carrito ahí.
- **Plan:** mover openMC/closeMC + todo el wiring (header btn, X, backdrop, Esc, added_to_cart,
  tab-cart) a un bloque que corra en TODAS las páginas (p.ej. dentro de `bgmg-mc-qty-js`), usando
  **delegación en `document`** para no depender del orden de render. Quitar el abrir/cerrar inline
  de los 7. **Riesgo:** que el carrito deje de abrir en alguna página (sobre todo checkout). Validar
  las 7 otra vez.

> El mapa detallado de abajo es del relevamiento original; CSS y markup ya están hechos, así que
> aplica solo la parte de JS.

### ⚠️ La doc miente: NO existe `#bgmg-mc-enhancements`
`bgmg-landing/CLAUDE.md` (decisión #2) y comentarios en `bgmg-template.php` (~L542) y
`bgmg-account.php` (~L1123) dicen que el CSS del minicart vive en un bloque global
`#bgmg-mc-enhancements` del wp_head. **Ese bloque no existe** (`grep` = 0). Corregir esa doc como
parte de BL-01c.

### Qué hay realmente (3 piezas, duplicadas en 7 templates)
Templates afectados: `bgmg-template, shop, category, product, cart, checkout, account`.
1. **CSS — estructura del panel** (~20 selectores por template): `.bgmg-mc-panel
   {position:fixed; transform:translateX(100%)}` + `.bgmg-mc-backdrop` + header/body/list/item/
   img/info/name/meta/rm/footer/subtotal/actions/btn-primary/btn-secondary/empty/close/title.
   `bgmg-template.php` (432–540) y `bgmg-account.php` (1125–1232) tienen el bloque completo (21);
   los otros 5 tienen 20 minificados en una línea (ej. `bgmg-shop.php:31-39`).
2. **Markup**: `<div class="bgmg-mc-panel" id="bgmg-mc-panel">…<?php bgmg_minicart_inner(); ?>…</div>`
   + `<div id="bgmg-mc-backdrop">`. 7 copias.
3. **JS abrir/cerrar**: `cartBtn.addEventListener('click', openCart)` etc. 7 copias.

### Lo que YA es global (NO volver a meterlo — daría doble)
`assets/bgmg-global.css` (enqueue en `bgmg-landing.php` L290) ya tiene los "enhancements" (13
selectores): `price-row/-orig/-now/-pct`, `controls`, `qty-btn`, `qty-val`, `savings`,
`body.is-loading`, `item` (solo fade-out), `rm` (solo hover/disabled), `.bgmg-mc-panel
button.bgmg-mc-clear`. **El JS de cantidades/eliminar/vaciar YA es global** vía `wp_footer`
(`bgmg-landing.php` 294–416, id `bgmg-mc-qty-js`; engancha por delegación al `#bgmg-mc-panel`).
> OJO solapamiento: `bgmg-global.css` ya define PARCIALMENTE `.bgmg-mc-item`, `.bgmg-mc-rm` y
> `.bgmg-mc-body`. Al mover el bloque estructural, **fusionar** esos 3 (no duplicar el selector).

### Plan de ejecución (con entorno estable)
1. `bgmg-global.css`: pegar la estructura del panel **una sola vez** (bloque de `bgmg-template.php`
   432–540), fusionando los 3 selectores que ya existían.
2. Markup → helper PHP `bgmg_render_minicart_panel()` en `bgmg-landing.php`, disparado en
   `wp_footer` global (o un único include). Quitar el markup inline de los 7.
3. JS abrir/cerrar → globalizar junto al `bgmg-mc-qty-js` existente. Quitar el inline de los 7.
4. Borrar el CSS `.bgmg-mc-*` inline de los 7 (del minicart no hay nada exclusivo de cada página).
5. Corregir la doc del fantasma `#bgmg-mc-enhancements` (CLAUDE.md de landing + 2 comentarios).
6. Bump versión + zip + validar **página por página** (el panel aparece en todas): landing,
   tienda, categoría, producto, carrito, checkout, Mi cuenta.

> **Riesgo:** si borras el CSS de un template y olvidas globalizarlo, el minicart se rompe en ESA
> página (lo que pasó con Mi cuenta). Por eso: **primero global, verificar, después borrar.**

---

## 4. Backlog de fondo (orden aproximado)

**🔴 Config wp-admin (sin código, lo hace el usuario):**
- [ ] **Crear cuenta en checkout**: WC → Ajustes → Cuentas y privacidad → ☑ permitir crear cuenta
      + ☑ permitir login durante el checkout.
- [ ] **Título del sitio**: WP → Ajustes → General → "BeautyNew3" → "BeautyGirlMG" (hoy los emails
      salen con el nombre técnico de staging).

**🟡 Optimización / próximas tareas:**
- Resto de quick wins de la auditoría + **BC-03 caché de stats** (transient 5 min; difería a
  post-deploy, adelantar si se quiere). Ver `AUDITORIA-OPTIMIZACION.md` §4.4.
- **Script de migración manual V1 → V2** (el usuario lo pidió). A definir en conjunto: cómo exporta
  el V1, qué datos trasladar (precios, stock, mayoristas, imágenes, categorías, atributos), formato
  de salida (CSV para WP All Import / JSON / SQL).
- **LiteSpeed Cache presets para WC**: qué cachear, qué excluir (carrito, checkout, mi cuenta, ajax),
  cómo invalidar tras deploy.

**🟢 Post-deploy / opcional:** auditoría línea a línea de `wizard-checkout.php` y `wizard-operativa.php`
(ya validados en vivo); BANNERS-CUSTOMIZER-PLAN.md L68 tiene info vieja del logo.

---

## 5. Decisiones de arquitectura / producto (durable)

**Localización y envíos (bgmg-chile):**
1. Couriers en "Por pagar": **solo Starken y Bluexpress**. Chilexpress **fuera** (2026-05-22).
2. Estados de despacho: solo **Preparando, Despachado, Listo para retiro**. No "Entregado"/"En reparto".
3. Plugin asume **Chile only**: validaciones RUT/teléfono/región solo si `billing_country === 'CL'`.
4. **HPOS** declarado y código compatible (incl. URLs de admin vía `bgmg_chile_admin_orders_url()`).
5. Checkout con **shortcode clásico**, no Block Checkout. bgmg-landing sirve template vía
   `template_include` y dentro hace `do_shortcode('[woocommerce_checkout]')`.
6. **Wizards** bajo menú "Despachos BGMG" como submenús; **idempotentes** (cada paso detecta su
   estado consultando WC en vivo, sin flags persistidos); con fallback a top-level si falta el menú padre.

**Pago y mensajes:**
7. **Mensaje thank-you depende del estado de PAGO** (no solo del envío) y menciona el courier.
8. Email "en espera" custom **solo cuando método = `bacs`** (transferencia). Otros casos: WC nativo.

**Datos del cliente / facturación:**
9. Bloque "Datos para boleta/factura" **removido de la vista del cliente** (email, thank-you,
   Mi cuenta → detalle). Se mantiene en admin → Pedidos. El RUT sigue saliendo dentro del bloque
   de dirección de facturación.
10. **Mi cuenta → Direcciones**: UN solo cuadro "Mi dirección / Envío" que edita billing (donde vive
    el RUT). No se muestran billing y shipping separados.
11. **Rebrand frontend**: el cliente ve "Dirección de envío" en todos lados (filtro `gettext`, solo
    frontend; admin mantiene términos nativos de WC).
12. Toggle **"Necesito factura (empresa)"** en checkout mantiene la palabra "factura" — refiere al
    documento tributario, no a la dirección. La factura persiste en `user_meta` `_billing_bgmg_*`.
13. **Código postal**: oculto vía CSS, no eliminado del DOM (reversible si una pasarela lo requiere).
14. **Breadcrumb** removido del checkout (solo botón "← Volver al carrito"); se mantiene en thank-you.

**Operación:**
15. **Migración V1 → V2: manual y limpia.** Solo se trasladan precios, productos y métodos de pago.
    **NO se copian plugins ni temas del V1** → neutraliza el bug `wp is not defined` (43% sesiones V1).
16. **Caché de stats wizards/reportes**: no implementada aún; se configura post-deploy con LiteSpeed
    + datos reales si llega a ser problema (>50ms en `wc_get_orders limit=-1`).
17. Tests con estado (carrito, login, compra) los hace **el usuario manualmente**; Claude solo
    verifica URLs públicas sin sesión.

---

## 6. Riesgos técnicos latentes (no son bugs hoy)

1. **Submenú wizards depende del orden de prioridades `admin_menu`**: el padre `bgmg-despachos`
   (prio 70) vs wizards (80–82). Mitigado con el fallback a top-level (D1), pero ojo si se tocan
   esas prioridades.
2. **Transbank puede sobrescribir `woocommerce_thankyou_order_received_text`** con prioridad más
   alta. Si el mensaje custom no aparece tras pagar con Transbank, subir la prioridad del filtro de
   bgmg-chile a 99.
3. **Zona "Worldwide" sin location CL explícita**: el wizard de envíos no la detecta y ofrece crear
   una duplicada. Improbable.
4. **`get_users` con `number=9999`** en wizard checkout: si hay >9999 clientes con RUT no los cuenta
   todos. Para la escala actual (<500) OK.

---

## 7. Hechos del sitio V2 verificados en vivo (no son bugs)

- **Slug del checkout = `/finalizar-compra/`** (español), NO `/checkout/`. La URL `/checkout/` da
  404 — esperado, no requiere acción.
- **HPOS activado.** Menú "Despachos BGMG" con 7 submenús: Resumen, Tarifas RM, Retiro en tienda,
  Reportes, Asistente Envíos, Asistente Checkout, Operativa diaria.
- Cuando una comuna RM tiene **tarifa fija** (ej. Santiago), esa tarifa **reemplaza** las opciones
  "Por pagar" — esperado.
- Compra de prueba BACS end-to-end OK (pedido #1132): RUT validado/guardado en `_bgmg_rut`, email
  "en espera" con asunto custom, mensaje thank-you correcto, notas al email.

---

## 8. Datos del negocio (Clarity, último mes ~abr–may 2026, sitio **V1**)

> V2 aún sin tráfico medible. Snapshot de mayo 2026 — si pasó tiempo, pedir CSV actualizado.
> Detalle granular (top páginas, funnel completo, CWV) en `historial/HANDOFF-2026-05-27.md` §4–5.

- Escala: **~33.000 sesiones/mes**, ~16.000 usuarios únicos, **~202 compras/mes** (~2.400/año).
- **Conversión ~0,61%** (0,69% sin bots). De los que inician finalización, **32% completa**.
- **95% mobile** (Instagram in-app 36%, Facebook in-app 21%, Chrome Mobile 20%). Desktop ~5%.
- Meta declarada por el usuario: **50K visits/mes**.
- **Bug externo de V1 (NO de nuestros plugins):** `wp is not defined` en **43% de sesiones** (algún
  plugin/tema usa `wp.hooks` sin declarar dependencia) + `awdr_params is not defined` (Advanced
  Dynamic Pricing). **No viajarán a V2** por la migración limpia (decisión §5.15). CWV V1: LCP 3,15s 🟡.

---

## 9. Checklist post-deploy (cuando suba la última tanda)

- [ ] Subir los 4 zips (tema + 3 plugins) desde wp-admin → Subir → "Reemplazar".
- [ ] **Ajustes → Enlaces permanentes → Guardar** (flushea rewrite rules; necesario tras cada
      update de bgmg-landing).
- [ ] Despachos BGMG → **Reportes**: las 3 secciones se ven sin error.
- [ ] Página de producto: el short description aparece **una** sola vez.
- [ ] Mi cuenta → Direcciones → Editar: el form abre con estilos completos.
- [ ] Pedido de prueba: NO aparece "Datos para boleta/factura" en thank-you ni email.
- [ ] Wizard Envíos → editor de retiro: WhatsApp inválido (`asdf`) debe rechazarse con mensaje claro.
