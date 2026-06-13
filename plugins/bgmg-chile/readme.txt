=== BGMG Chile — RUT y Comunas ===
Contributors: beautygirlmg
Tags: woocommerce, chile, rut, comunas, envio, checkout
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.20.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Localización chilena para BeautyGirlMG: validación de RUT, selector regiones/comunas y envío "Por pagar" con tarifas RM.

== Description ==

Plugin propio de BeautyGirlMG. Funcionalidades:

* Campo RUT en checkout (obligatorio) y registro (opcional).
* Validación módulo 11 en JS y PHP.
* Distinción persona natural / empresa, con toggle "Necesito factura" que despliega razón social, giro y dirección comercial.
* Reemplaza state/city nativos de WooCommerce con las 16 regiones y 346 comunas oficiales de Chile.
* Cascada Región → Comuna en checkout y "Mi cuenta".
* Método de envío "Envío BeautyGirlMG (Chile)" que aplica tarifa fija configurable por comuna RM, o "Por pagar al recibir" en el resto.
* Método de envío "Retiro en tienda" (gratis) para comunas RM habilitadas.
* Teléfono móvil chileno obligatorio en checkout (con validación y formato +56 9 XXXX XXXX).
* Tracking de envío en admin de orden + email custom "Pedido despachado".
* Sección "Datos Chile" en admin de usuarios (RUT, teléfono, facturación).
* Pantalla admin para gestionar tarifas RM y comunas con retiro disponible.
* Detección de RUT duplicado al registrar.

== Installation ==

1. Sube la carpeta `bgmg-chile` a `/wp-content/plugins/`.
2. Activa el plugin desde wp-admin → Plugins.
3. En WooCommerce → Envíos Chile (RM), llena los precios para las comunas RM con tarifa fija y marca las que permiten retiro.
4. En WooCommerce → Ajustes → Envío, agrega los métodos "Envío BeautyGirlMG (Chile)" y "Retiro en tienda" a tu zona de Chile.

== Changelog ==

= 1.20.0 =
* **PWA de despachos (Parte 2 — detalle + guardar):** tocar un pedido en la lista abre su **detalle** (`/despachos/?pedido=ID`) con todos los datos del cliente, dirección, productos y totales. Desde ahí se puede **guardar el estado de despacho, el courier y el número de seguimiento**, y marcar **"Avisar al cliente por email"** (con confirmación previa) — todo por AJAX, sin recargar. El guardado y el correo usan el **mismo núcleo** que el metabox de wp-admin (`bgmg_chile_persistir_tracking`), así que ambos dejan idénticas metas, notas de auditoría y disparan el mismo email "Tu pedido fue despachado": la app y el panel quedan siempre sincronizados. Couriers chilenos como accesos rápidos (Starken, Chilexpress, Bluexpress, Correos, Pullman, despacho propio) + WhatsApp directo al cliente.
* **Fix (lista):** la tarjeta del pedido ya no usa anchors anidados (el `<a>` del teléfono dentro del `<a>` de la tarjeta partía el HTML); ahora usa un enlace que cubre toda la tarjeta con el botón de llamar por encima.

= 1.19.0 =
* **NUEVO — PWA de despachos (Parte 1):** mini-app móvil en la URL `/despachos/` para gestionar los despachos desde el teléfono, instalable con "Agregar a pantalla de inicio" (manifest + standalone). Protegida con el login de WordPress (admins y gerentes de tienda; rol "Despachos" llegará en Fase 2). Esta primera parte trae la **lista de pedidos** con pestañas **Por despachar / Enviados / Retiro**, tarjetas con cliente, comuna, courier, total, tracking y llamada directa 📞. Página excluida de toda caché (privada por usuario). El detalle del pedido + guardar tracking + avisar al cliente llegan en la Parte 2.

= 1.18.4 =
* **Etiqueta de despacho:** la etiqueta térmica (60×80mm) ahora también muestra el **correo** del cliente. Antes solo aparecía en el formato A4 y en el metabox/"Copiar todo"; el térmico (el que se imprime por defecto) lo omitía.

= 1.18.3 =
* **Fix (error fatal):** la pantalla **Despachos BGMG → Reportes** (y las stats del Resumen) caía con un `E_ERROR` (`Call to undefined method WC_Order_Refund::get_shipping_city()`) cuando `wc_get_orders()` devolvía un reembolso. Ahora ambos bucles saltan los objetos que no sean `WC_Order` (los reembolsos no tienen dirección de envío).

= 1.18.2 =
* **Pulido (auditoría, sin cambios de comportamiento):**
  * Texto del placeholder "Retiro en tienda": ahora indica que los datos se configuran en el método de envío (antes decía "Ajustes generales", incorrecto).
  * `wp_unslash` en el nonce del Asistente de Envíos (consistencia con el resto del plugin).
  * `bgmg_chile_render_order_summary()`: el default de `mostrar_factura` pasa a `false` (coherente con ocultar el bloque de factura al cliente; el único caller ya lo pasaba explícito).
  * Comentario corregido en el validador de RUT.

= 1.18.1 =
* **Fix:** el checkbox "Necesito factura" ahora se puede DESMARCAR desde Mi cuenta → Editar dirección. Antes, al desmarcarlo no se guardaba el cambio (un checkbox desmarcado no viaja en el POST) y conservaba el valor anterior. Ahora el valor se escribe siempre ('1' o vacío), coherente con el checkout.
* **Fix:** el panel Despachos BGMG → Resumen → "Estado del sistema" detectaba mal el retiro en tienda: leía una opción que nunca se escribe y siempre mostraba "no configurado". Ahora detecta si el método "Retiro en tienda" está agregado a una zona de envío.

= 1.18.0 =
* **Reportes de despachos (Fase 11)**: reemplaza el placeholder "En construcción" del submenú Despachos BGMG → Reportes con una pantalla real. Tres secciones:
  * Resumen del período: total pedidos despachados, ingresos por envío, promedio por pedido.
  * Ranking de comunas (top 10): muestra qué comunas piden más y si tienen tarifa fija configurada (✓) o no (⚠). Permite detectar huecos de configuración visualmente.
  * Métodos de envío: ratio de uso de Tarifa fija RM vs Por pagar Starken vs Por pagar Bluexpress vs Retiro en tienda, con ingresos por método.
* Selector de ventana: Últimos 7 / 30 / 90 días (default 30). Pasa por query arg `?dias=X`.
* Sólo cuenta pedidos pagados (`processing` + `completed`). Excluye pedidos BACS aún en `on-hold`.
* Sin caché todavía — a 90 días con muchos pedidos puede tardar un poco. Se agregará caché transient post-deploy si hace falta (ver tech debt).

= 1.17.0 =
* Hardening preventivo del sistema de wizards (Fase 10 del HANDOFF, sin la caché de stats — esa se aplicará post-deploy con datos reales).
* **D1 — Fallback de menú padre:** los 3 wizards (Envíos, Checkout, Operativa) ahora se registran con bgmg_chile_wizard_register_submenu(). Si en algún punto el menú padre "bgmg-despachos" no está registrado (porque admin-despachos-menu.php no se cargó, o porque otro plugin lo eliminó), los wizards caen a top-level menu en lugar de desaparecer silenciosamente del sidebar.
* **D2 — Validación de WhatsApp en editor inline (wizard de envíos):** el campo WhatsApp del paso "Retiro en tienda" ahora valida que sea un móvil chileno válido (módulo BGMG_Chile_Telefono_Validator) y normaliza al formato canónico "+56 9 XXXX XXXX" antes de guardar. Si la dueña pega un número mal formateado, se rechaza con mensaje claro en lugar de guardar basura que rompa el link wa.me del checkout.
* **D3 — Pre-flight check WC en los 3 wizards:** antes de renderizar, cada wizard verifica que WooCommerce y WC_Shipping_Zones existan. Si falta algo, muestra un mensaje admin con el problema concreto y un link a Plugins, en lugar de tirar fatal PHP / pantalla blanca.

= 1.16.0 =
* UX checkout: el campo "Código postal / ZIP" queda oculto vía CSS en checkout y formularios de dirección. El campo sigue existiendo en el DOM por si alguna pasarela de pago futura lo requiere.
* UX cliente: el bloque "Datos para boleta/factura" deja de renderizarse en los emails al cliente y en la página "Mi cuenta → Detalle del pedido" (y por extensión en la página de Pedido recibido). El RUT sigue mostrándose dentro del bloque de dirección de facturación (filtro woocommerce_order_formatted_billing_address). El panel de admin → Pedidos mantiene la sección "Datos Chile (RUT)" intacta para que la dueña pueda emitir boleta.

= 1.15.2 =
* Fix HPOS: los 9 links a "Ver pedidos" desde los wizards y la vista de etiquetas estaban hardcoded con la URL legacy de WordPress (edit.php?post_type=shop_order). Con HPOS activo (que el plugin declara como compatible) esos links podían redirigir mal o romper el filtro de estado. Ahora todos pasan por un helper nuevo bgmg_chile_admin_orders_url() que detecta HPOS y devuelve la URL correcta para cada caso (incluido el filtro &status=processing vs &post_status=wc-processing).

= 1.15.1 =
* Hardening previo al primer despliegue completo:
  * current_time('timestamp') (deprecated en WP 5.3) reemplazado por time() en el cálculo de stats del wizard operativa.
  * Constantes de slug de los wizards (BGMG_CHILE_WIZARD_*_SLUG) migradas de const a define con guarda if (! defined()) para alinear con la convención del plugin y evitar fatales ante doble carga.

= 1.15.0 =
* Nuevo: Asistente de Operativa diaria (wp-admin → Despachos BGMG → 📦 Operativa diaria). Pantalla con foco operacional para el día a día:
  * Stats de tracking (códigos cargados, despachados sin código, preparando atrasados, avisos enviados).
  * Stats de etiquetas (pedidos pagados sin estado de despacho, en preparación).
  * Alertas automáticas cuando algo necesita atención: "X pedidos marcados despachado sin código", "X pedidos llevan más de 3 días en preparando", "X pedidos pagados sin estado".
  * Links directos a la lista filtrada de pedidos y a la config del email de tracking.
* Recordatorios visibles del flujo: cómo cargar código, cómo imprimir etiqueta, cómo usar la acción masiva "Imprimir etiquetas BGMG".

= 1.14.0 =
* Nuevo: Asistente Checkout (wp-admin → Despachos BGMG → 🛒 Asistente Checkout). Pantalla informativa con estado y estadísticas de los módulos RUT/facturación y teléfono móvil:
  * Clientes con RUT guardado (total).
  * Órdenes con RUT y órdenes que pidieron factura (últimos 30 días).
  * Órdenes con móvil válido (últimos 30 días) + cuántas tienen formato sospechoso (anteriores al plugin).
  * Info contextual sobre cómo se procesa la facturación (manual) y por qué el móvil es obligatorio.
  * Links a checkout y a wp-admin → Usuarios.
* Por ahora la pantalla es solo informativa (no agrega toggles). Si más adelante surgen necesidades de configuración (ej. permitir compra sin RUT, aceptar fijos), se agregan acá.

= 1.13.1 =
* Mensaje thank-you ahora menciona el courier específico: "Despachamos tu pedido por Starken" (o Bluexpress) en lugar de "por courier" genérico. Aplica tanto al mensaje principal como al mensaje de espera de transferencia.
* Email "Pedido despachado" también menciona el courier en el intro: "Ya despachamos tu pedido por Starken. Acá están los datos…". Si el método es texto libre (ej. "Moto propia"), también se inserta.
* Polish visual: los bloques de bgmg-chile que aparecen en la thank-you y Mi cuenta (RUT/factura, tracking, retiro) ahora tienen separación clara entre sí y sus headings se renderizan con la misma tipografía Cormorant Garamond que los headings nativos de WC. Antes podían verse pegados o con tipografías inconsistentes.
* Nuevos helpers públicos para consumir desde templates custom: bgmg_chile_orden_courier() y bgmg_chile_orden_courier_nombre().

= 1.13.0 =
* Nuevo: Asistente de configuración de envíos (wp-admin → Despachos BGMG → 🪄 Asistente). Guía paso a paso para dejar listo el sistema:
  1. Crear la zona de envío Chile (botón "Crear" si no existe).
  2. Agregar el método "Envío BeautyGirlMG (Chile)" a la zona.
  3. Agregar el método "Retiro en tienda" + editar dirección, horario, WhatsApp e instrucciones desde la misma pantalla.
  4. Tarifas RM por comuna (resumen + link al admin existente).
  5. Comunas con retiro disponible (resumen + link al admin existente).
* Cada paso detecta su estado en vivo (sin flags persistidos), así puedes entrar y salir sin perder progreso. Lo que ya tienes hecho aparece con ✓.
* Al activar el plugin la primera vez, redirige automáticamente al asistente. Si lo activas en bulk con otros plugins, no interrumpe.
* Barra de progreso "X de 5 pasos completados".

= 1.12.0 =
* "Por pagar" en checkout ahora se ofrece como DOS opciones separadas para que el cliente elija courier:
  * Por pagar — Starken
  * Por pagar — Bluexpress
  (Ambas $0 en el checkout; el cliente paga el flete al recibir.)
* El courier elegido se guarda en la orden y se pre-carga automáticamente en el campo "Método / Courier" del metabox de tracking — así no hay que tipearlo a mano.
* El aviso explicativo del checkout ahora menciona el courier específico que eligió el cliente ("Sobre tu despacho por Starken: …").
* Labels editables desde WooCommerce → Ajustes → Envío → Editar método "Envío BeautyGirlMG (Chile)".
* Placeholder del campo "Método" en el metabox de tracking actualizado: "Starken, Bluexpress, Moto propia…" (antes mencionaba Chilexpress).

= 1.11.4 =
* Fix: el aviso "Despacho por pagar" en checkout ya no se muestra cuando el cliente aún no completó su comuna (antes aparecía hablando de "tu comuna" sin que el cliente hubiera elegido ninguna).
* Fix: la preferencia "Necesito factura" + razón social + giro + dirección comercial ahora se guarda al user_meta del cliente logueado, así la próxima compra autocompleta los datos (antes el cliente empresa tenía que reingresar todo en cada checkout).

= 1.11.3 =
* Email "Pedido en espera" (transferencia bancaria) ahora tiene asunto y heading propios: "Pedido #X — pendiente de transferencia" y "Tu pedido está pendiente de transferencia". Antes llegaba con el texto genérico de WC que no aclaraba qué hacer.
* Aviso destacado al inicio del cuerpo del email (cliente) alineado con el mensaje de la thank-you, reutilizando la misma función para que web y email siempre digan lo mismo:
  * En "on-hold" (transferencia pendiente) → cuadro rosa con la instrucción de transferir.
  * En "processing" (Transbank confirmado) → cuadro verde con la promesa de courier según el flujo de envío.
* Solo aplica a órdenes de Chile y con método "Transferencia bancaria directa" (bacs); el resto conserva los emails WC nativos.

= 1.11.2 =
* Mensaje de "Pedido recibido" ahora depende del estado de pago, no solo del envío:
  * Transferencia bancaria pendiente (on-hold) → "Realiza la transferencia para confirmar".
  * Transbank rechazado (failed) → "Tu pago no se pudo completar, puedes reintentar".
  * Cancelada / pendiente → mensaje específico de cada caso.
  * Pagada (processing / completed) → mensaje según el flujo de envío (igual que antes).
* Antes, todas las órdenes (incluso las no pagadas) veían "¡Gracias, vamos a preparar tu pedido!" con cuadro verde, lo que generaba falsa expectativa de pago confirmado.

= 1.10.0 =
* API pública para bgmg-landing: helpers para mostrar estado, tracking, retiro y factura desde templates custom.
* Mensaje "Gracias por tu pedido" adaptado al flujo de la orden (retiro, tarifa fija, por pagar).
* Documentación de hooks expuestos en README para no duplicar contenido.

= 1.9.0 =
* Botón "📋 Copiar" al lado del código de tracking en Mi cuenta y en la página de gracias post-pago.
* En emails (donde no se puede ejecutar JS), el código de tracking ahora va en monospace con fondo destacado + pista para copiarlo manualmente.

= 1.8.0 =
* Nuevo bloque "🏷️ Datos de despacho" en admin de orden con los 8 campos en orden estándar (Nombre, RUT, Dirección, Comuna, Región, Correo, Método, ID).
* Botón "Copiar todo" envía los datos al portapapeles listos para pegar en Starken/Chilexpress.
* Botón "Imprimir etiqueta" abre vista limpia print-friendly en pestaña nueva.

= 1.7.0 =
* Sub-estado "Estado del despacho" (Preparando / Despachado / Listo para retiro) en el metabox de tracking, independiente del estado WC de la orden.
* "Listo para retiro" solo se muestra cuando la orden fue por retiro en tienda.
* Badge visible del estado en admin de orden y en Mi cuenta → Detalle del pedido.
* Email custom adapta subject, heading y cuerpo al estado actual del despacho.
* Nota privada automática en la orden cuando cambia el estado.

= 1.6.0 =
* Envío gratis configurable por monto del carrito (solo a comunas con tarifa fija).
* Aviso visible "Te faltan $X para envío gratis" en cart y checkout cuando la comuna califica y el subtotal está cerca del umbral.

= 1.5.0 =
* Sección "Datos Chile" en admin de usuarios.
* Tracking de envío con email custom.
* Múltiples correcciones de bugs y mejoras de consistencia.

= 1.4.0 =
* Método de envío "Retiro en tienda".

= 1.1.0 =
* Teléfono móvil chileno obligatorio en checkout.

= 1.0.0 =
* Lanzamiento inicial.
