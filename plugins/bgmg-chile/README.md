# BGMG Chile — RUT y Comunas

Plugin propio de **BeautyGirlMG** que añade a WooCommerce todo lo específico de
Chile: validación de RUT, selector oficial de regiones y comunas, y un método
de envío único con tarifa fija para algunas comunas de la Región Metropolitana
y "Por pagar" (cliente paga al recibir) en el resto.

> No depende de ACF, no agrega jQuery extra, no llama APIs externas.
> Validación dual JS + PHP. Documentación inline en español.

---

## Estructura

```
bgmg-chile/
├── bgmg-chile.php              # Bootstrap, encolado, ciclo de vida, migraciones
├── uninstall.php               # Limpieza al eliminar el plugin
├── readme.txt                  # Para repositorios estilo WordPress.org
├── README.md                   # Este archivo
├── inc/
│   ├── helpers.php             # Utilidades comunes + definición canónica de campos
│   ├── data/
│   │   └── regiones-comunas.php  # 16 regiones + 346 comunas oficiales
│   ├── rut/
│   │   ├── class-rut-validator.php
│   │   ├── checkout-fields.php
│   │   ├── account-fields.php
│   │   ├── order-display.php
│   │   └── duplicates.php
│   ├── regiones/
│   │   ├── states-filter.php
│   │   ├── checkout-cascade.php
│   │   ├── validator.php
│   │   └── address-format.php
│   ├── envio/
│   │   ├── class-shipping-method.php   # Tarifa fija RM / Por pagar
│   │   ├── class-shipping-retiro.php   # Retiro en tienda (v1.4.0)
│   │   └── admin-tarifas-rm.php
│   ├── telefono/                       # v1.1.0
│   │   ├── class-telefono-validator.php
│   │   └── checkout-fields.php
│   ├── tracking/                       # v1.5.0
│   │   ├── class-email-tracking.php
│   │   └── order-tracking.php
│   └── perfil/                         # v1.5.0
│       └── admin-user-fields.php
├── assets/
│   ├── css/
│   │   ├── frontend.css
│   │   └── admin.css
│   └── js/
│       ├── rut-validator.js
│       ├── telefono-validator.js       # v1.1.0
│       ├── checkout.js
│       ├── regiones-comunas.js
│       └── admin-tarifas-rm.js
└── languages/
    └── bgmg-chile.pot
```

---

## Instalación

1. Copia la carpeta `bgmg-chile` a `/wp-content/plugins/`.
2. wp-admin → Plugins → **BGMG Chile — RUT y Comunas** → Activar.
3. wp-admin → **WooCommerce → Envíos Chile (RM)**: llena los precios fijos por
   comuna donde tengas tarifa pactada con Starken / Chilexpress / propio.
4. wp-admin → **WooCommerce → Ajustes → Envío → Zona Chile** → agrega el
   método **"Envío BeautyGirlMG (Chile)"**.

> Si WooCommerce no está activo el plugin no carga nada y muestra un aviso en
> admin pidiendo activar WC.

---

## Funcionalidades

### RUT

- Campo obligatorio en checkout, opcional en registro.
- Validación módulo 11 (espejo en PHP y JS).
- Formateo automático "12.345.678-9" al salir del campo.
- Detección de tipo: persona natural (1–49M) / empresa (≥50M).
- Toggle **"Necesito factura"**: oculto por defecto, despliega razón social,
  giro y dirección comercial cuando se marca.
- Aviso suave (no bloqueante) si el RUT parece empresa y el toggle está apagado.
- RUT visible en: admin de orden, emails (HTML y plano), Mi cuenta → orden, y
  como línea adicional en el formato de dirección de facturación de WC.
- Sincroniza al `user_meta` al comprar logueado → próxima compra autocompleta.
- Detección de RUT duplicado al registrar (no bloquea en checkout, solo en
  creación de cuenta).

### Regiones y comunas

- Reemplaza el dataset de WC para Chile: 16 regiones, 346 comunas oficiales.
- Selector `state` (Región) y `city` (Comuna) en cascada (sin AJAX, todo en
  cliente para responder sin latencia).
- Validación PHP de que la comuna pertenezca a la región seleccionada (defensa
  contra POST manipulado).
- Renderiza la dirección en formato chileno: Nombre / Empresa / Calle /
  Comuna / Región / Chile / RUT.

### Envío

- Método **`bgmg_chile_envio`** (Envío BeautyGirlMG Chile):
  - Si la comuna está en RM y tiene tarifa fija activa → cobra ese precio.
  - En cualquier otro caso → "Por pagar": $0 en el checkout + aviso visible
    explicando que el cliente paga el flete al courier al recibir.
- Método **`bgmg_chile_retiro`** (Retiro en tienda) — desde v1.4.0:
  - Solo se ofrece a comunas RM marcadas como "Permite retiro" en la pantalla
    de tarifas.
  - Costo $0. Aviso con dirección, horario, WhatsApp e instrucciones.
- Pantalla admin lista las 52 comunas de la RM con campos para precio, activo
  y disponibilidad de retiro.

### Teléfono móvil chileno (v1.1.0)

- Campo `billing_phone` obligatorio en checkout cuando el país es Chile.
- Validación móvil: 9 dígitos comenzando con 9 (no acepta fijos).
- Acepta `+56`, `56`, `0056` o sin prefijo. Normaliza a `+56 9 XXXX XXXX`.
- Espejo PHP + JS (`BGMG_Chile_Telefono_Validator` / `window.BgmgChileTelefono`).

### Tracking de envío (v1.5.0)

- Metabox en admin de orden con método/courier (texto libre) y código de
  seguimiento (puede quedar vacío para despachos en moto propia).
- Checkbox "Avisar al cliente por email" → dispara email custom
  (registrado como `WC_Email`, editable desde WC → Ajustes → Emails).
- Render en "Mi cuenta → Detalle del pedido" cuando hay tracking.
- Nota privada automática en la orden con el cambio.

### Datos Chile en admin de usuarios (v1.5.0)

- Sección "Datos Chile" en wp-admin → Usuarios → Editar usuario.
- Permite a la dueña ver/editar RUT, teléfono móvil y datos de facturación
  de cualquier cliente sin abrir una orden.
- Validación con los mismos algoritmos del checkout (módulo 11 + móvil CL).
- Columna "RUT" extra en wp-admin → Usuarios.

---

## API pública para `bgmg-landing` (templates custom)

`bgmg-chile` expone helpers pensados para que `bgmg-landing` (u otro tema/plugin)
pueda mostrar los datos chilenos en cualquier template custom — típicamente la
thank you page (order-received) o un dashboard de cuenta — sin tener que
conocer la estructura interna de meta keys.

### Resumen completo en un solo llamado

```php
<?php
// $order es WC_Order — ya disponible en la thank you page.
bgmg_chile_render_order_summary( $order );
```

Esto renderiza, en este orden, los bloques que apliquen a la orden:
1. Badge del estado del despacho (si hay).
2. Tracking (método + código + botón copiar).
3. Datos de retiro en tienda (solo si la orden fue por retiro).
4. Datos de boleta/factura (RUT, razón social si pidió factura).

Con opciones para esconder bloques que ya estén en otra parte del template:

```php
bgmg_chile_render_order_summary( $order, array(
    'mostrar_estado'   => true,
    'mostrar_tracking' => true,
    'mostrar_factura'  => false, // ya lo muestro arriba
    'mostrar_retiro'   => true,
) );
```

### Bloques individuales

```php
bgmg_chile_render_estado_box( $order, 'big' );     // solo el badge, opcional 'big'
bgmg_chile_render_tracking_block( $order );        // método + código + botón copiar
bgmg_chile_render_retiro_block_publico( $order );  // datos de retiro (si aplica)
bgmg_chile_render_factura_block_publico( $order ); // datos de boleta/factura
```

Todos chequean condiciones internamente: si no aplican (ej. orden sin tracking,
sin RUT, sin retiro), simplemente no imprimen nada. Llamarlos siempre es seguro.

### Mensaje "Gracias por tu pedido"

`bgmg-chile` reemplaza automáticamente el texto genérico de WC en la thank you
page según el flujo de la orden:

| Flujo | Texto que aparece |
|-------|-------------------|
| Retiro en tienda | "¡Gracias! Te avisaremos cuando esté listo para retirar." |
| Despacho con tarifa fija (RM) | "¡Gracias! Te avisaremos cuando salga con su código." |
| Despacho "Por pagar" (regiones) | "¡Gracias! El flete a tu comuna se paga al recibir." |

Si querés usar el mismo texto desde un template custom de `bgmg-landing`:

```php
echo esc_html( bgmg_chile_get_thankyou_message( $order ) );
```

Si querés desactivar el filtro automático (porque tu template ya muestra otro
texto y no querés duplicado):

```php
remove_filter( 'woocommerce_thankyou_order_received_text', 'bgmg_chile_filter_thankyou_text', 10 );
```

### Hooks WC donde `bgmg-chile` ya inyecta contenido (no duplicar)

- `woocommerce_email_after_order_table` → bloque RUT/factura + bloque tracking + bloque retiro (en emails).
- `woocommerce_order_details_after_order_table` → mismos bloques en "Mi cuenta → Detalle del pedido" **y** en la thank you page (WC dispara el mismo hook en ambos).
- `woocommerce_admin_order_data_after_billing_address` → datos chilenos en admin de orden.
- `woocommerce_review_order_before_shipping` → aviso "Te faltan $X para envío gratis" en checkout.
- `woocommerce_review_order_after_shipping` → aviso "Por pagar" / aviso "Retiro en tienda" en checkout.

Si `bgmg-landing` reemplaza el template `templates/checkout/thankyou.php`, los
bloques inyectados via `woocommerce_order_details_after_order_table` **siguen
funcionando** mientras el template custom haga `do_action( 'woocommerce_order_details_after_order_table', $order )`.

---

## Hooks útiles para integrar con otros plugins

```php
// Lectura de meta de orden
$rut          = $order->get_meta( '_bgmg_rut' );
$rut_norm     = $order->get_meta( '_bgmg_rut_normalizado' );
$tipo         = $order->get_meta( '_bgmg_rut_tipo' );           // 'natural' | 'empresa'
$factura      = $order->get_meta( '_bgmg_necesita_factura' );    // 'si' | 'no'
$razon_social = $order->get_meta( '_bgmg_razon_social' );
$giro         = $order->get_meta( '_bgmg_giro' );
$direccion    = $order->get_meta( '_bgmg_direccion_comercial' );

// Validar / formatear RUT desde otro plugin
BGMG_Chile_RUT_Validator::is_valid( '12.345.678-9' );   // bool
BGMG_Chile_RUT_Validator::format( '123456789' );        // '12.345.678-9'
BGMG_Chile_RUT_Validator::normalize( '12.345.678-9' );  // '123456789'
BGMG_Chile_RUT_Validator::tipo( '76.555.555-5' );       // 'empresa'

// Comunas
bgmg_chile_get_regiones();                              // array código → nombre
bgmg_chile_get_comunas_por_region();                    // mapa por región
bgmg_chile_get_comuna_nombre( 'providencia' );          // 'Providencia'
bgmg_chile_get_region_de_comuna( 'providencia' );       // 'RM'

// Tarifas RM
bgmg_chile_get_tarifa_fija( 'providencia', 'RM' );      // float|null
```

---

## Datos persistidos

### `order_meta`
| Clave | Descripción |
|------|-------------|
| `_bgmg_rut` | RUT formateado "12.345.678-9" |
| `_bgmg_rut_normalizado` | RUT solo dígitos+DV "123456789" |
| `_bgmg_rut_tipo` | `natural` \| `empresa` |
| `_bgmg_necesita_factura` | `si` \| `no` |
| `_bgmg_razon_social` | Solo si factura = si |
| `_bgmg_giro` | Solo si factura = si |
| `_bgmg_direccion_comercial` | Solo si factura = si |

### `user_meta`
Los mismos `_bgmg_rut*` se sincronizan al cliente logueado al comprar para
autocompletar futuras compras.

### Tabla `wp_bgmg_chile_tarifas_rm`
| Campo | Tipo |
|------|------|
| `id` | BIGINT PK |
| `comuna_slug` | VARCHAR(64) UNIQUE |
| `comuna_nombre` | VARCHAR(120) |
| `precio` | DECIMAL(10,2) |
| `activo` | TINYINT(1) |
| `creado` / `actualizado` | DATETIME |

---

## Compatibilidad

- WordPress ≥ 6.0
- WooCommerce ≥ 7.0 (testeado hasta 9.5)
- PHP ≥ 7.4
- **HPOS-compatible** (declarado vía `FeaturesUtil::declare_compatibility`).
- Checkout clásico (shortcode `[woocommerce_checkout]`). El checkout en bloques
  requiere adaptación adicional (registrar campos con
  `woocommerce_blocks_register_checkout_field`). Pendiente para futura versión.

---

## Plugins relacionados (no se tocan)

- `bgmg-landing` v6.2 — tema/templates custom del sitio.
- `beautygirlmg-mayorista` v2.5.0 — lógica B2B mayorista.

Este plugin es **autónomo** y no depende de ninguno de los anteriores. Si la
dueña los activa o desactiva, BGMG Chile sigue funcionando normalmente.

---

## Roadmap

- Soporte completo para el checkout de bloques.
- Adaptador opcional para OpenFactura / Bsale (cuando la dueña decida emitir
  factura electrónica automatizada — hoy es boleta manual).
- Importador masivo de tarifas RM vía CSV.
- Tests automatizados con PHPUnit.
