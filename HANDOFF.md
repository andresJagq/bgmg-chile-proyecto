# HANDOFF BGMG Chile — estado vivo del proyecto

> **Doc ÚNICO y vivo.** Se **actualiza en sitio**, NO se crea uno nuevo por sesión.
> Arriba (§1–§4) está lo **volátil** (estado actual + qué sigue): reescribir al retomar.
> De §5 en adelante es **conocimiento durable**: referencia estable, solo se corrige.
> Snapshots viejos (27, 28, 30 may 2026) archivados en `historial/` — solo por si se
> necesita el detalle granular (changelogs fase a fase, datos crudos de Clarity).
>
> Última actualización: **2026-06-10**
>
> **Dónde quedamos (2026-06-10d):** **mayorista SOLO por surtido (mayorista 2.7.7)** — decisión de
> negocio del usuario: el mayorista exige surtir (evita cajas desarmadas); el DETALLE ya **no** cuenta
> para el grupo mayorista. En variables, el pricing separa las líneas por flag `bgm_origen`: con flag →
> grupo surtido (mínimo + equilibrio + mayorista, fallback promo); sin flag → subgrupo detalle (NUNCA
> mayorista, solo promo por su propio qty; función nueva `bgm_aplicar_promo_detalle_variaciones` en
> carrito.php). `bgm_estado_grupo_variaciones` (chips/cards) cuenta solo surtido (espejo del pricing) y
> el texto sugiere "agrega N **a tu surtido**". **SIMPLES intactos** (tier por cantidad, camino separado
> `bgm_aplicar_precio_simple` sin tocar). Plan + tabla de impacto en
> `~/.claude/plans/immutable-waddling-spark.md` (sesión); resumen en CLAUDE §3 del mayorista + CONTRATO.
> **Validar (usuario, carrito vacío):** (1) surtido OK + 2 sueltas del mismo producto → las sueltas a
> detalle, mayorista intacto; (2) 4×3 balanceado SOLO por detalle → todo detalle (antes daba mayorista);
> (3) simple con mayorista → tier igual que siempre; (4) promo aplica al detalle; (5) cards muestran
> solo qty del surtido.
>
> **Dónde quedamos (2026-06-10c):** **fix líneas duplicadas Sorpréndeme+Manual (mayorista 2.7.6)** —
> usar ambos modos en la misma orden generaba líneas duplicadas por variación (8 de 4: una 'auto' y
> una 'manual' por color), porque WC solo fusiona líneas con `cart_item_data` idéntico. El precio y la
> regla de equilibrio NO se veían afectados (suman por variación/padre) — era solo confusión visual y
> operativa en carrito/orden. Fix: **flag `bgm_origen` unificado a `'surtido'`** en ajax-auto y
> ajax-manual (verificado: NADIE lee el valor, solo presencia con `!empty` — plugin y landing).
> Surtido vs DETALLE sigue separado a propósito (avisos "no califica" por línea). Docs actualizados
> (CLAUDE §3 del mayorista + CONTRATO). **Validar (usuario):** agregar el mismo producto via
> Sorpréndeme y luego via Manual → las variaciones repetidas deben quedar en UNA línea sumada;
> avisos/chips de surtido intactos; DETALLE sigue en línea aparte.
>
> **Dónde quedamos (2026-06-10b):** **fix carrito en 0 en tienda móvil (bgmg-landing 6.8.3)** — el
> usuario reportó que en la tienda (móvil) el minicart aparecía vacío hasta agregar un producto.
> Verificado contra producción (beautygirlmg.cl/tienda): **`wc-cart-fragments` NO se estaba cargando**
> (WooCommerce moderno solo lo encola si existe el widget clásico de mini-carrito; el sitio no usa
> widgets) + **LiteSpeed Cache (LSCWP) activo** sirviendo HTML con el carrito horneado de otro momento
> → nada hidrataba el carrito al cargar. Fix: enqueue explícito de `wc-cart-fragments` en
> `wp_enqueue_scripts` (salvo cart/checkout). Nota: producción corre un bgmg-landing viejo (sin
> marcadores 6.7.3+) — subir el zip 6.8.3 lo trae todo. Ver CLAUDE.md de bgmg-landing §2-bis.
>
> **Dónde quedamos (2026-06-10):** **fix de sincronía del minicart (bgmg-landing 6.8.2)** — auditoría
> por reporte del usuario ("a veces no carga bien / caché raro según la página"). Causa: 3 verdades del
> carrito desincronizadas — los AJAX custom no actualizaban ni la cookie `woocommerce_cart_hash` (WC la
> setea en `shutdown`, tarde para AJAX) ni el sessionStorage de `wc-cart-fragments`, que re-pintaba un
> carrito VIEJO al navegar; un `wc_fragment_refresh` forzado lo "curaba" tarde (flash + 1 request/página).
> Fix: `bgmg_cart_ajax_payload()` (cookies antes del JSON + fragments + cart_hash en los 3 endpoints) +
> `window.bgmgSyncWcFragments()` en el JS + claves de fragment separadas para el badge del header y el de
> la tab bar (el genérico borraba la clase `bgmg-tab-count` → numerito desplazado en móvil) + se eliminó
> el refresh forzado. Ver CLAUDE.md de bgmg-landing §2-bis. **Validar (usuario):** cambiar qty/vaciar
> desde el minicart, navegar a otra página → el carrito debe verse correcto AL INSTANTE (sin flash de
> contenido viejo); badge de la tab bar móvil no se desplaza tras agregar producto.
>
> _Antes (2026-06-09):_ sesión de varios cambios encadenados (commiteados/pusheados `2692f7b`):
> - **Organizador de categorías (bgmg-landing 6.8.1)** — pantalla admin nueva **Productos → Organizar
>   categorías** (`inc/category-organizer.php` + `assets/category-organizer.js/.css`): árbol drag&drop
>   (jQuery UI Sortable, 2 niveles) para decidir padre/hija, orden y **visibilidad por dispositivo**.
>   **Canónicos:** helper `bgm_get_nav_cats($parent, $args)` con `$args['context']` = `'pc'` / `'mobile'`
>   / `'any'`; orden en term meta `order`/`menu_order`; visibilidad en `bgm_cat_hide_pc` y
>   `bgm_cat_hide_mobile` (checks **PC** / **Móvil** por categoría). Se refactorizaron los **9 consumidores**
>   (megamenú→`pc`, hoja móvil→`mobile`, pills/tienda/categoría/carrito→`any`). La hoja móvil ya **no**
>   tiene tope de 12. **Validar (usuario, requiere login):** arrastrar/anidar/promover, tildar/destildar
>   PC y Móvil por separado, Guardar; confirmar que el orden y la visibilidad por dispositivo se reflejan
>   en cada superficie (una con Móvil off NO sale en la hoja del celular pero sí en el megamenú de PC).
> - **Etiqueta de despacho (bgmg-chile 1.18.4)** — la etiqueta **térmica** ahora muestra el **correo**
>   (antes solo en A4/metabox). Commiteado/pusheado (`7bd1f63`).
> - **Limpieza de tipografía (mayorista 2.7.5)** — `frontend.css` usaba `'DM Sans'` (no se carga → caía
>   a font de sistema) en 4 bloques de la ficha; ahora **Poppins** (coherente con Alice+Poppins del
>   sitio). SPECS.md del mayorista actualizado. **Sin commitear aún** (junto con el organizador).
>
> **Dónde quedamos (2026-06-08):** **fix de error fatal en bgmg-chile 1.18.3** — la pantalla
> **Despachos BGMG → Reportes** (y las stats del Resumen) caía con `E_ERROR`
> (`Call to undefined method WC_Order_Refund::get_shipping_city()`) cuando `wc_get_orders()` devolvía
> un reembolso. Ambos loops de stats ahora saltan los objetos que no sean `WC_Order`. Se reportó en
> **beautygirlmg.cl** (ya corriendo el stack V2: tema BGMG Base 1.1.0). **Acción del usuario:** subir
> `zips\bgmg-chile.zip` (1.18.3) y reabrir Reportes para confirmar.
>
> En el mismo push entró el **trabajo hecho en casa** que estaba sin commitear (llegó por Drive):
> **mayorista 2.7.4** (variaciones sin stock se muestran *deshabilitadas "Agotado"*, no se ocultan —
> ver CLAUDE.md del plugin §6: `swatches.php`, `frontend.css`, `producto-variable.php`,
> `frontend-swatches.js`) y **bgmg-landing 6.7.5** (ajustes de templates). Todo lintea limpio.
>
> _Antes (2026-06-04):_ se construyó la **meta de regalo / free gift** (mayorista 2.7.0–2.7.2: Fase 1
> lógica + Fase 2 ventanita flotante en bgmg-landing 6.7.0) y el **fix del subtotal por nivel en
> Sorpréndeme** (`afcaf7a`). **Pendiente del usuario:** validar en staging esos zips. Pendiente opcional:
> estilizar la línea del regalo (🎁 + qty bloqueada) en el carrito/minicart custom. (Aparte: documento de
> cobro `detalle-valor-beautygirl.*` — privado, ignorado en git, solo sincroniza por Drive.)

---

## 1. Estado actual

**Fase:** optimizar/pulir los 3 plugins en **staging** antes de la migración V1→V2 (que es un
paso posterior). Auditoría de los 3 plugins **COMPLETA**, sin hallazgos críticos/altos — plan y
estado vivo de correcciones en `AUDITORIA-OPTIMIZACION.md` §4.

| Pieza | Versión código |
|---|---|
| bgmg-chile | **1.18.4** |
| bgmg-landing | **6.8.3** |
| beautygirlmg-mayorista | **2.7.7** |
| bgmg-tema-base | 1.1.0 |

**Respaldo en GitHub (2026-06-02):** todo el proyecto está versionado en git y subido a un repo
**privado** (`andresJagq/bgmg-chile-proyecto`). Checkpoint pre-promo = commit `618f00a`. Flujo de
trabajo con 2 PCs + Drive en **§5.18**.

> ✅ **Commit/push de la sesión 2026-06-02 HECHO (2026-06-03).** Subido a `origin/main`: promo
> minorista Fase 1 (v2.5.5), las 8 planillas de migración + `generar-chunks.ps1`, y los docs. Repo
> y working tree en sync.

**Zip listo, PENDIENTE validar en staging:**
- **Subtotal por nivel en Sorpréndeme (mayorista 2.7.2):** la pestaña auto mostraba el "Subtotal aprox."
  con el precio unitario **fijo de nivel 1**, aunque la cantidad llegara al umbral de nivel 2. Ahora el
  subtotal en vivo usa el **nivel que corresponde a la cantidad** (igual que el carrito): desde `min_2`
  aplica el precio de nivel 2 si está configurado. `modo-auto.php` pasa `data-min-2` y `data-precio-2`
  al bloque; `frontend-auto.js` calcula `precioUnitario(qty)`. **Validar:** en un producto variable con
  nivel 2 configurado, subir la cantidad en Sorpréndeme y ver que al cruzar `min_2` el subtotal baja al
  precio de nivel 2 (ej. 3×1100 vs 12×precio-nivel-2).
- **Meta de regalo — FASE 2 ventanita flotante (bgmg-landing 6.7.0):** módulo nuevo
  `inc/meta-regalo-widget.php`. Ventanita fija (abajo-derecha en desktop, sobre la tab bar en móvil) que
  aparece cuando el carrito está **cerca** del próximo nivel ("¡Te faltan $X para tu regalo!" + barra de
  progreso + nombre del regalo) o al **desbloquear** ("¡Desbloqueaste tu regalo!"). Consume
  `bgm_meta_regalo_estado()` (con function_exists). Se refresca sin recargar: fragment WC
  `#bgm-meta-widget-root` (add desde tarjetas) + los 3 endpoints AJAX del side-cart (add/update/clear)
  devuelven `meta_widget_html` y el JS llama `window.bgmMetaSwap()`. Descartable por sesión
  (sessionStorage, firma del estado → reaparece al cambiar de nivel). CSS en `bgmg-global.css`
  (`.bgm-meta-card`). El "cerca" se configura en Ajustes → Mayorista (`bgm_meta_cerca_monto`, default
  5.000). **Validar:** acercarse al umbral (ver ventanita + barra), cruzarlo (mensaje de desbloqueo),
  cerrar (× la oculta), y que se actualice al agregar/quitar desde tarjeta, página de producto y
  side-cart, sin recargar. **Pendiente opcional:** estilizar la línea del regalo (🎁 / qty bloqueada) en
  el carrito/minicart custom de bgmg-landing (hoy usa el baseline de los filtros WC del mayorista).
- **Meta de regalo / free gift — FASE 1 lógica (mayorista 2.7.0):** módulo nuevo
  `includes/core/meta-regalo.php`. Si el carrito alcanza un umbral, se agrega AUTO un producto a $0;
  soporta **hasta 3 niveles** (escalera) y entrega el del **nivel más alto alcanzado** (uno solo, NO
  acumulativo). Aplica a minoristas y mayoristas (subtotal real, ya descontado). Config en **WC →
  Ajustes → Mayorista → "Meta de regalo"** (toggle maestro + monto "cerca" + por nivel: umbral/producto
  simple/activo, con buscador de productos WC). Lógica en `woocommerce_before_calculate_totals` prio
  **100** (corre tras mayorista 99) con guard estático anti-recursión; la línea lleva flag `bgm_regalo`,
  precio forzado a 0, qty fija 1, y los hooks mayorista/promo la **ignoran** (`continue` en carrito.php).
  Filtros baseline de WC para nombre "🎁 Regalo", qty fija y sin "quitar". Expone
  `bgm_meta_regalo_estado()` para la Fase 2. **2.7.1:** el selector de producto-regalo pasó de
  `wc-product-search` (no persistía: WC valida el valor AJAX contra el `options` del campo y lo
  descartaba) a **campo de ID numérico** que además muestra el nombre del producto y avisa si el ID no
  sirve (no existe / no simple / sin stock) vía `bgm_meta_desc_producto()`. **Pendiente Fase 2:** ventanita flotante en bgmg-landing +
  estilizar la línea del regalo en el carrito/minicart custom. **Validar Fase 1 (cart, manual):** activar
  + configurar nivel(es), cruzar/bajar el umbral (regalo entra/sale a $0), cambiar de nivel (regalo se
  cambia), y que mayorista/surtido/promo y el checkout sigan OK.
- **Imágenes de cards +10px (bgmg-landing 6.6.3):** `.bgmg-card-img` pasó de **80×80 a 90×90**. La
  regla está duplicada inline en **4 templates** (`bgmg-template.php`, `bgmg-product.php` —relacionados—,
  `bgmg-category.php`, `bgmg-shop.php`); se actualizaron las 4 para mantener las cards parejas en toda la
  web (NO hay copia en global.css). Las cards son flex con `align-items:center`, así que solo crecen un
  poco de alto, sin romper layout. **Validar:** home, tienda, categoría y relacionados (producto).
- **Hero sin flechas (bgmg-landing 6.6.2):** se quitaron las flechas prev/next del slider Swiper de la
  home en sus 3 puntos (markup `.swiper-button-prev/next`, su CSS, y el bloque `navigation:` del init).
  Se conservan los **puntitos de paginación** y el autoplay. Solo en `bgmg-template.php`. JS validado
  con `node --check`. **Validar:** la home ya no muestra flechas y el slider sigue rotando/paginando.
- **Pills de categorías centradas (bgmg-landing 6.6.1):** la sección "Encuentra lo que buscas" de la
  home distribuía las pills con `flex` alineadas a la izquierda (borde derecho irregular / pills
  "solas" al envolver). Se agregó `justify-content:center` a `.bgmg-cats` **en los 2 sitios** donde
  estaba definida (inline en `bgmg-template.php` —la que ganaba— y `assets/bgmg-global.css`, que tenía
  una versión `grid` muerta ahora alineada a `flex` centrado). El markup `bgmg-cats` solo existe en la
  home. **Validar:** abrir la home y ver las pills centradas y balanceadas (desktop y móvil).
- **Páginas legales/informativas (bgmg-landing 6.6.0):** se crean las **5 páginas de contenido** que
  el footer ya enlazaba (FAQ, Envíos, Devoluciones, Términos) **+ Política de privacidad** (nueva, con
  su link añadido al footer). Implementación:
  - **Template branded reutilizable** `bgmg-page.php` (registrado como *"BGMG Página"* en
    `theme_page_templates` + enrutado en `template_include` por `get_page_template_slug()`): reúsa
    header/footer/tab bar + CSS/JS global, pinta `the_title()` + `the_content()` con tipografía BGMG.
    La **FAQ usa acordeón `<details>/<summary>` nativo** (sin JS).
  - **Contenido + seeder** en `inc/content-pages.php`: el texto se tomó de **beautygirlmg.cl** (footer
    + FAQ verbatim; Privacidad y Términos los redacté para Chile — Ley 19.496 / 19.628 / SERNAC). El
    seeder es **idempotente** (crea solo las páginas que faltan, NO pisa ediciones; a las existentes
    solo les asigna el template) y corre **1 vez en `admin_init`** gated por la opción
    `bgmg_content_pages_seeded='v1'` (el activation hook NO se redispara al actualizar por zip).
  - **OJO / pendientes del dueño:** (a) Términos y Privacidad tienen `[RAZÓN SOCIAL]` y `[RUT]` como
    placeholders → rellenar desde **Páginas → editar**. (b) La exclusión del *derecho a retracto* viene
    textual de la FAQ V1; conviene revisión legal (art. 3 bis Ley 19.496 en venta a distancia).
  - **Validar:** subir el zip, entrar a **wp-admin** (dispara el seeder), ir a **Ajustes → Enlaces
    permanentes → Guardar** (flush) y abrir las 5 URLs (`/preguntas-frecuentes/`, `/politica-de-envios/`,
    `/politica-de-devoluciones/`, `/terminos-y-condiciones/`, `/politica-de-privacidad/`): deben verse
    con header/footer branded y la FAQ debe abrir/cerrar cada pregunta. Revisar los 5 links del footer.
- **Cambio de tipografía (bgmg-landing 6.5.11):** por pedido del cliente, **títulos → Alice** (serif)
  y **textos → Poppins** (sans). Reemplazo de `Cormorant Garamond → Alice` y `DM Sans → Poppins`
  en las 8 plantillas + `bgmg-global.css` + `bgmg-footer.css` (cubrió comillas simples y dobles), y
  el `<link>` de Google Fonts en cada `<head>` (`family=Alice&family=Poppins:wght@300;400;500;600;700`).
  **OJO:** Alice tiene **un solo peso (400)**; los títulos con `font-weight:600` se ven en
  **negrita sintética** (la pinta el navegador). Si no convence, opciones: dejar esos títulos en 400,
  o elegir otro serif con bold real. **Validar** que se vean bien títulos y textos en todo el sitio.
- **Badge de descuento UNIFICADO (mayorista 2.6.2 + bgmg-landing 6.5.10) — decisión: la promo es el
  único sistema de descuento.** El cliente quiere dejar de usar la oferta nativa de WC (NO se suprime
  en código, solo se deja de usar) y que TODO descuento muestre el MISMO badge personalizado. Ahora
  hay **un solo badge** `🔥 {etiqueta} -X%` vía `bgmg_oferta_badge_html()`, que cubre **promo**
  (precede) y **oferta nativa** (fallback): el nombre sale de `bgm_get_oferta_etiqueta()`, el % de
  `bgm_get_promo_info()` o, si no hay promo, de `bgm_get_oferta_descuento_pct()`. Se **eliminó el
  badge chico** `bgm_promo_badge_html` de junto al precio en tarjetas, relacionados y producto
  (queda obsoleto pero definido por compat). La sección **"Precios irresistibles"** ahora lista
  productos en **promo ∪ oferta nativa** (nuevo helper `bgm_promo_ids_afectados()`). El overlay de
  la imagen del producto también cubre promo. **Pendientes conocidos:** (a) el tope de unidades de la
  promo está en 2 — si se usa como descuento general conviene `bgm_promo_qty_max=0`; (b) el precio
  tachado del filtro de promo solo aplica a **simples** (en variables se ve el badge pero no el
  tachado). **Validar** tienda/categoría/novedades/destacados/producto/relacionados con productos en
  promo (simples y variables) y que la sección Ofertas liste los de promo.
- **Fix 6.5.9 (bgmg-landing):** las 3 secciones inline de la home (Destacados, Novedades, Ofertas)
  no llamaban a `bgm_promo_badge_html()` → los productos descontados por la **promo Cyber** (no
  oferta nativa) mostraban precio tachado pero **sin badge**. Causa: esas secciones solo evaluaban
  el badge de oferta nativa (`is_on_sale()`), que es `false` en productos de promo; el tachado lo
  pinta el filtro `bgm_promo_price_html` vía `get_price_html()`. Fix: se agregó el badge de promo
  antes del precio en las 3 secciones (igual que la función canónica `bgmg_product_card_html` y la
  página de producto). OJO: en **variables** en promo el tachado no aparece (el filtro solo actúa en
  simples); la promo se comunica por el badge. **Validar** Novedades/Destacados con productos en promo.
- **Badge de oferta configurable (mayorista 2.6.1 + bgmg-landing 6.5.8).** El badge "🔥 Oferta"
  (oferta nativa de WC, `is_on_sale()`) dejó de ser texto fijo: el **nombre es configurable** en
  WC → Ajustes → Mayorista → "Etiqueta de oferta" (`bgm_oferta_etiqueta`, default "Oferta").
  Nuevo módulo `includes/core/oferta.php` con `bgm_get_oferta_etiqueta()` y
  `bgm_get_oferta_descuento_pct($product)` (% de oferta; en variables el mayor % entre variaciones).
  El tema arma el badge vía `bgmg_oferta_badge_html()` (con `function_exists`, fallback "Oferta").
  Cambios: (a) el nombre reemplaza "Oferta" en **todas las vistas** (home Ofertas, tienda,
  categoría, relacionados, `bgmg_product_card_html`); (b) **Novedades y Destacados** ahora muestran
  **categoría + tag de oferta** lado a lado (wrapper `.bgmg-card-badges`); (c) en la **página de
  producto** hay un **overlay sobre la imagen** con el tag + **% de descuento**; (d) el % también
  sale en las tarjetas ("🔥 Oferta -17%"). Contrato actualizado (§2 + §7). Lint 0 errores, sin BOM.
  **Validar:** poner un nombre en Ajustes → Mayorista y revisar tarjetas (home/tienda/categoría),
  Novedades/Destacados (2 badges) y la imagen del producto (overlay + %), con productos simples y
  variables en oferta.

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

**Migración V1→V2 — TRABAJO ACTIVO (2026-06-02).** Pipeline construido y **piloto validada en vivo**.
- Workspace `poblacion-bd-v2/` con scripts reusables: `generar-import.ps1` (export WC + columnas
  `Meta: _bgm_*` cruzadas por SKU, recorte limpio) y `generar-chunks.ps1` (trocea por producto).
- Datos 2-jun: **1237 productos con escalones mayoristas** mapeados · 663 reglas huérfanas
  descartadas · 0 ambiguos. Salida: **8 planillas de 200 productos** en `poblacion-bd-v2/planillas/`
  + `_MANIFIESTO.txt` (checklist).
- **planilla-01 subida — se ve bien.** Falta **subir 02–08** marcando "Actualizar productos
  existentes"; al terminar: Enlaces permanentes → Guardar + spot-check de un escalón en el carrito.
- Fundamentos durables del mapeo en §5 (items 19–23).

**Nuevo feature pedido (2026-06-02): descuento promocional minorista** en el plugin mayorista.
Diseño YA acordado con el usuario; falta implementar. Empezar por **Fase 1 (productos simples)**.
Spec completa y decisiones en §4 (bloque "Descuento promocional minorista").

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
- **Migración manual V1 → V2: pipeline CONSTRUIDO y piloto validada** (`poblacion-bd-v2/`, ver §2 +
  §5 items 19–23). Pendiente: subir planillas 02–08. **Clientes (opción B, acordado):** migrar solo
  los que ya compraron (email + RUT + ≥1 pedido), conservando el hash de contraseña (WP→WP); mapear
  la meta-key del RUT de V1 → `_bgmg_rut` cuando toque.
- **LiteSpeed Cache presets para WC**: qué cachear, qué excluir (carrito, checkout, mi cuenta, ajax),
  cómo invalidar tras deploy.

**🟡 Descuento promocional minorista (plugin mayorista) — FASE 1 (simples) HECHA en 2.5.5 (zip listo, PENDIENTE validar en staging). Fase 2 (variables) pendiente:**

*Reglas de negocio:*
- Aplica solo a **productos o categorías seleccionables en admin**.
- Activo solo en **fechas/ocasiones especiales** (ej. Cyber): fecha inicio/fin **+** interruptor
  on/off (zona horaria del sitio; inicio 00:00, fin 23:59:59 inclusive).
- Tipo de descuento **configurable en admin: % o monto fijo** (siempre sobre el precio **regular**,
  `bgm_get_precio_base`).
- Es para **MINORISTAS**: aplica solo **bajo el umbral mayorista**. El mayorista entra con
  `qty >= min_1` (default `min_1 = 3`, ver `beautygirlmg-mayorista.php`). **Caso límite resuelto:**
  en `qty = 3` **gana el mayorista**; el promo cubre `qty < min_1`. **No se pisan ni se suman.**

*Arquitectura (clave: NO tocar la lógica mayorista):*
- `bgm_calcular_precio()` (`includes/core/helpers.php`) queda **intacta** (sigue pura mayorista). El
  promo va en un helper **aislado** `bgm_calcular_precio_promo($product, $qty)` → devuelve precio o `null`.
- **Precedencia en `includes/frontend/carrito.php`**: primero mayorista; **solo si el mayorista NO
  aplicó (nivel 0)** se intenta el promo → mutuamente excluyentes por construcción (cero stacking).
- Config en `wp_options`: `bgm_promo_activa`, `bgm_promo_fecha_inicio`/`_fin`, `bgm_promo_tipo`
  (% | monto), `bgm_promo_valor` (default global), `bgm_promo_qty_min`/`_qty_max`,
  `bgm_promo_categorias` (term IDs). Por producto (meta): `_bgm_promo_modo` + `_bgm_promo_valor`.
- Helpers soporte: `bgm_promo_activa_ahora()` (toggle + fechas) y `bgm_producto_en_promo($product_id)`
  (modo del producto **o** categoría coincide; para variaciones resuelve al **padre**). Con **cache
  estático por request** (el carrito recalcula muchas veces).
- Ajustes en `carrito.php`: ampliar el gate de `bgm_aplicar_precio_simple` (~L110) para dejar pasar
  productos **solo-promo** (sin mayorista); para variables, ampliar la agrupación (~L84) e intentar
  promo cuando el surtido falla o `qty_total < umbral` (**el promo ignora la regla de surtido**).

*Entrega poco a poco (el usuario quiere ambos tipos, bien hechos):*
- **Fase 1 — productos simples: HECHA y VALIDADA (2.5.5).** Módulo aislado `includes/core/promo.php`
  + config en **WC → Ajustes → Mayorista** + fallback en `bgm_aplicar_precio_simple` (mayorista primero;
  promo solo si nivel 0). `bgm_calcular_precio()` **intacta**.
- **Per-producto: HECHA (2.5.6, lint 0 errores).** Control en la pestaña Mayorista del producto:
  meta `_bgm_promo_modo` (heredar / `custom` / `excluir`) + `_bgm_promo_valor`. **Precedencia:** el
  producto manda sobre lo global (`excluir` > `custom` > categoría). Getters `bgm_get_promo_modo()` /
  `bgm_get_promo_valor()` (fallback global). **Quitado** el textarea global de IDs (`bgm_promo_productos`);
  la inclusión es por **categorías globales** (ocasiones) **o** modo `custom`. En el panel global:
  **contador de afectados** (`bgm_promo_contar_afectados()`, transient 5 min, invalidado en
  `save_post_product` + guardar ajustes) + **alerta de solapamiento**. Badge de estado en el producto.
  **Tipo (%/monto) sigue global.** Preview en vivo en `admin.js`. **VALIDADO por el usuario (2026-06-03).**
- **Mostrar la promo al CLIENTE (frontend) — Parte A HECHA (2.5.9).** Filtro `woocommerce_get_price_html`
  en el mayorista: muestra «~precio normal~ → precio promo» en **productos simples**, automático en
  tarjetas (`bgmg_product_card_html`), página de producto y relacionados (ya usan `get_price_html()`),
  **SIN tocar bgmg-landing**. Helper `bgm_get_promo_info($product)` (fuente única: `bgm_calcular_precio_promo`).
  Solo simples (en variables el precio es rango). **Quirk menor:** en "relacionados" (`bgmg-product.php`
  L476) el HTML pasa por `wp_strip_all_tags` → ahí el tachado se ve como 2 números; lo arregla la Parte B.
- **Parte B — HECHA (mayorista 2.6.0 + bgmg-landing 6.5.7): badge "Promo −X%"** en tarjetas,
  producto **y relacionados** (simples **y variables**). Helper `bgm_promo_badge_html()` (mayorista)
  llamado con `function_exists()` en `bgmg_product_card_html`, `bgmg-product.php` (precio + relacionados).
  Estilo `.bgm-promo-badge` en `bgmg-global.css` (cargado en todas las páginas). Contrato actualizado
  (§2 funciones + §7 selector). En relacionados se cambió `wp_strip_all_tags`→`wp_kses_post` para que
  el precio tachado se vea bien (resuelve el quirk de la Parte A). **Validar en staging** (tarjetas
  tienda/categoría + producto + relacionados, simples y variables).
- **Fase 2 — productos variables: HECHA (2.5.7, lint 0 errores).** Cuenta por **TOTAL del producto**
  (`qty_total`, consistente con el mayorista). En `carrito.php`: (a) la agrupación incluye padres en
  promo aunque NO tengan mayorista (`bgm_promo_activa_ahora() && bgm_producto_en_promo($padre)`);
  (b) `bgm_aplicar_precio_conjunto_variaciones` aplica **mayorista primero** (surtido OK + algún
  nivel>0) y, si NO aplicó (surtido falla o `qty_total` bajo umbral), intenta **promo ignorando el
  surtido** → mutuamente excluyentes. `bgm_calcular_precio_promo($variacion, $qty_total)`: elegibilidad
  por el **padre**, precio base por **variación**. **Validar en staging.**
- **Tope de la promo (2.5.8):** `bgm_promo_qty_max` default = **2** (la promo es al detalle: 1–2 uds,
  **sumando variaciones**). Es un tope **plano y global**, NO atado al umbral mayorista. Migración
  una vez (0→2, flag `bgm_promo_qty_max_migrado`) para instalaciones que ya habían guardado 0.
- Pendiente menor (fuera de alcance inicial): **aviso visual propio del promo** en
  `includes/frontend/avisos-carrito.php` (hoy esos avisos son solo de mayorista).
- Cierre: **bump `BGM_VERSION` en 2 sitios**, escribir sin BOM, `php -l` antes de subir.

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
18. **Respaldo en GitHub + flujo 2 PCs.** Repo **privado** `andresJagq/bgmg-chile-proyecto` (creado
    2026-06-02). El proyecto entero (raíz `bgmg-chile-proyecto/`) es un repo git; `.gitignore` excluye
    `zips/`, `**/.claude/settings.local.json` y cruft de SO. Identidad git `BeautyGirlMG
    <jose1011961@gmail.com>`. Como `.git` vive **dentro de Drive**: (a) dejar que Drive sincronice
    **100% ANTES de apagar** y **ANTES de correr git** en la otra PC; (b) **nunca** trabajar en las 2
    PCs a la vez; (c) la credencial de GitHub se guarda **por PC** (Windows Cred Manager, no viaja por
    Drive) → el primer `push` en cada PC abre el navegador una vez. **GitHub es la fuente de verdad:**
    si Drive corrompe `.git`, re-clonar con `git clone https://github.com/andresJagq/bgmg-chile-proyecto.git`.
    Flujo normal: `commit` → `push`.

**Migración V1→V2 — pipeline (`poblacion-bd-v2/`):**
19. Fuente: export nativo de WC + export de **Advanced Woo Discount Rules**, cruzados por **SKU**.
20. Precio mayorista V1 = reglas `wdr_bulk_discount` tipo **`flat` (pesos/unidad)** → mapean directo a
    `_bgm_descuento_*` (sin conversión de %). Corte de escalón: `from<12`→Nivel I, `≥12`→Nivel II.
21. CSV de importación = export de WC **recortado limpio** (se botan `ID`, `Swatches Attributes` y las
    ~107 `Meta:` de plugins de V1: Elementor/Facebook/Google/Yoast/woosea/Neve…) + 5 columnas
    `Meta: _bgm_*`, con `_bgm_modo_descuento=unico` (config en el padre; las variaciones heredan).
22. **663 reglas huérfanas** (SKU sin producto en el catálogo) se **descartan** (no son productos, no
    se pierde nada); si faltan productos, se re-exporta y re-corre. **340 productos sin regla** = solo-detalle.
23. Subida por **planillas de 200 productos** (Ruta C): variaciones siempre con su padre, header con
    BOM en cada una, importar con "Actualizar productos existentes". Scripts reusables
    `generar-import.ps1` + `generar-chunks.ps1` regeneran todo si cambia el export.

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
