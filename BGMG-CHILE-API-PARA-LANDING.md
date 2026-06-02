# 📦 BGMG Chile — API para usar en bgmg-landing

> **PEGAME COMPLETO al chat ANTES de pedirle cualquier cosa que toque la página de gracias post-pago, dashboards de Mi cuenta, o mostrar datos chilenos en `bgmg-landing`.**
>
> Este documento es la "hoja de instrucciones" que `bgmg-chile` expone para
> que otro plugin/tema lo consuma sin reinventar la rueda.

---

## Contexto del sitio

`new.beautygirlmg.cl` (WordPress + WooCommerce) tiene **dos plugins propios** trabajando en conjunto:

| Plugin | Rol |
|--------|-----|
| `bgmg-landing` | Tema / templates / diseño visual del sitio. **(Esto es lo que estás editando.)** |
| `bgmg-chile` | Localización chilena: RUT, regiones/comunas, métodos de envío, retiro en tienda, tracking, estados del despacho, datos para factura. |

`bgmg-chile` ya inyecta su contenido **automáticamente** en los emails de WC, en "Mi cuenta → Detalle del pedido" y en la thank you page nativa, vía hooks estándar de WC. **Si vas a hacer un template custom**, no programes esa lógica desde cero — usá la API pública que se documenta abajo.

---

## Reglas de oro

1. ❌ **NO leas meta keys internos del plugin directamente** (`_bgmg_rut`, `_bgmg_tracking_codigo`, `_bgmg_estado_despacho`, etc.). Pueden cambiar entre versiones.
2. ✅ **SIEMPRE usá las funciones `bgmg_chile_render_*` o `bgmg_chile_get_*`** listadas abajo.
3. ✅ Todas son **seguras de llamar siempre**: si no aplican (ej. orden sin tracking, sin RUT, sin retiro), no imprimen nada. No hace falta `if` previos.
4. ❌ **NO dupliques contenido** en los hooks listados al final ("Hooks ya cubiertos") — quedaría el bloque por duplicado en pantalla.

---

## API de renderizado

### Resumen completo (lo más común)

```php
<?php bgmg_chile_render_order_summary( $order ); ?>
```

Renderiza, en este orden:
1. Badge del estado del despacho (si hay).
2. Bloque de tracking (método + código + botón "Copiar").
3. Bloque de retiro en tienda (solo si la orden fue por retiro).
4. Bloque de boleta/factura (RUT + razón social si pidió factura).

Con opciones para esconder bloques que ya muestres en otra parte del template:

```php
bgmg_chile_render_order_summary( $order, array(
    'mostrar_estado'   => true,
    'mostrar_tracking' => true,
    'mostrar_factura'  => false, // ya lo muestro arriba en mi diseño
    'mostrar_retiro'   => true,
) );
```

### Bloques individuales

```php
bgmg_chile_render_estado_box( $order, 'big' );      // badge del estado, opcional 'big'
bgmg_chile_render_tracking_block( $order );         // método + código + botón copiar
bgmg_chile_render_retiro_block_publico( $order );   // dirección, horario, WhatsApp del retiro
bgmg_chile_render_factura_block_publico( $order );  // RUT y datos de factura
```

### Mensaje "Gracias por tu compra" adaptado al flujo

```php
echo esc_html( bgmg_chile_get_thankyou_message( $order ) );
```

Devuelve texto distinto según el flujo:

| Flujo de la orden | Texto |
|-------------------|-------|
| Retiro en tienda | "¡Gracias por tu compra! …te avisaremos por email apenas esté listo para retirar." |
| Despacho con tarifa fija (RM) | "¡Gracias por tu compra! …te avisaremos cuando salga por courier con su código de seguimiento." |
| Despacho "Por pagar" (regiones) | "¡Gracias por tu compra! …el flete a tu comuna se paga al recibir el paquete." |

---

## Hooks WC donde `bgmg-chile` YA inyecta contenido

NO dupliques en estos hooks (te quedaría doble el bloque en pantalla):

| Hook WC | Qué inyecta `bgmg-chile` |
|---------|--------------------------|
| `woocommerce_email_after_order_table` | RUT/factura + tracking + retiro (en emails) |
| `woocommerce_order_details_after_order_table` | Mismos bloques en "Mi cuenta → Detalle" Y en thank you |
| `woocommerce_admin_order_data_after_billing_address` | Datos chilenos en admin de orden |
| `woocommerce_review_order_before_shipping` | Aviso "Te faltan $X para envío gratis" (checkout) |
| `woocommerce_review_order_after_shipping` | Aviso "Por pagar" / aviso retiro (checkout) |

**IMPORTANTE**: si reemplazás `templates/checkout/thankyou.php` con un template custom, asegurate de que dentro del template llames a:

```php
do_action( 'woocommerce_order_details_after_order_table', $order );
```

…para conservar la auto-inyección de los bloques. Si no querés la auto-inyección y preferís llamar los helpers manualmente, omitilo y usá `bgmg_chile_render_order_summary( $order )` donde quieras.

### Si tu template ya muestra "Gracias" con tu propio texto

`bgmg-chile` filtra el texto nativo de WC `woocommerce_thankyou_order_received_text` para adaptarlo al flujo. Si querés desactivarlo para no duplicar tu mensaje:

```php
remove_filter( 'woocommerce_thankyou_order_received_text', 'bgmg_chile_filter_thankyou_text', 10 );
```

---

## Snippet de referencia: thank you page custom

```php
<?php
/**
 * Template: templates/checkout/thankyou.php (override custom desde bgmg-landing)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! isset( $order ) || ! $order instanceof WC_Order ) return;
?>
<div class="bgmg-thankyou-wrap">

    <h1>¡Gracias por tu compra! ✨</h1>
    <p class="bgmg-thankyou-msg">
        <?php echo esc_html( bgmg_chile_get_thankyou_message( $order ) ); ?>
    </p>

    <!-- Toda la info chilena en un disparo, con tu paleta -->
    <?php bgmg_chile_render_order_summary( $order ); ?>

    <!-- Items y totales del pedido (WC nativo) -->
    <h2>Resumen de tu pedido</h2>
    <?php
    // Esto incluye lista de items + totales. Si lo llamás, NO uses
    // `do_action( 'woocommerce_order_details_after_order_table' )` debajo
    // porque ya lo dispara internamente WC al renderizar la order details.
    ?>
    <?php wc_get_template( 'order/order-details.php', array( 'order_id' => $order->get_id() ) ); ?>

</div>
```

---

## Datos persistidos (solo lectura excepcional)

Si por algún motivo necesitás los datos crudos (ej: para un export, un endpoint REST custom, un email externo):

| Meta key | Dónde | Tipo | Ejemplo |
|----------|-------|------|---------|
| `_bgmg_rut` | order, user | string | `"12.345.678-9"` |
| `_bgmg_rut_normalizado` | order, user | string | `"123456789"` |
| `_bgmg_rut_tipo` | order, user | string | `"natural"` o `"empresa"` |
| `_bgmg_necesita_factura` | order | string | `"si"` o `"no"` |
| `_bgmg_razon_social` | order | string | (solo si factura=si) |
| `_bgmg_giro` | order | string | (solo si factura=si) |
| `_bgmg_direccion_comercial` | order | string | (solo si factura=si) |
| `_bgmg_tracking_codigo` | order | string | código del courier |
| `_bgmg_tracking_metodo` | order | string | nombre del courier ("Starken", "Moto propia", etc.) |
| `_bgmg_estado_despacho` | order | string | `"preparando"` \| `"despachado"` \| `"listo_retiro"` \| `""` |
| `_bgmg_tracking_email_enviado` | order | int | timestamp Unix del último aviso enviado |

Pero **insisto**: lo correcto es usar los helpers. Estos meta keys pueden mutar en futuras versiones del plugin sin aviso.

---

## Ubicación del código fuente

```
plugins/bgmg-chile/
├── inc/integracion/landing-helpers.php   ← API pública (lo que llamás desde acá)
├── inc/tracking/                          ← tracking + estados + email
├── inc/envio/                             ← shipping methods + retiro en tienda
├── inc/etiqueta/                          ← bloque "Datos de despacho" imprimible (admin)
└── inc/rut/, inc/regiones/, inc/telefono/ ← validadores
```

---

## ✅ Checklist antes de programar

- [ ] ¿Estoy mostrando estado / tracking / retiro / RUT / factura? → **Usá los helpers**.
- [ ] ¿Estoy haciendo un template custom de thank you? → **Llamá `bgmg_chile_render_order_summary( $order )`** o los helpers individuales.
- [ ] ¿Voy a leer un meta `_bgmg_*` directamente? → **Frená**, releé "Reglas de oro". Usá los helpers.
- [ ] ¿Mi template custom todavía dispara `woocommerce_order_details_after_order_table`? → Si sí, los bloques aparecerán solos. Si no, llamá los helpers manualmente.
- [ ] ¿Mi mensaje "Gracias" se duplica con el de `bgmg-chile`? → Hacé `remove_filter()` como se muestra arriba, o usá su versión vía `bgmg_chile_get_thankyou_message()`.

---

**Versión del documento**: alineado con `bgmg-chile v1.10.0`
**Última actualización**: 2026-05-18
