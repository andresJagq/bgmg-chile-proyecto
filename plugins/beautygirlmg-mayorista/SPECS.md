# Plugin BeautyGirlMG Mayorista — Especificaciones v2.0

> Documento de especificaciones para el rediseño del plugin.
> Versión actual del plugin: 1.0.0 — este documento define la versión 2.0.
> Este documento es **autocontenido**: un agente que lo lea de cero debe poder implementar el plugin sin más contexto.

---

## 0. Contexto del negocio

- **Tienda**: beautygirlmg.cl (WooCommerce)
- **Rubro**: maquillaje y cosmética, productos por mayor y detalle
- **Subdominio de pruebas**: new.beautygirlmg.cl
- **Razón del rediseño**: el plugin actual tiene inconsistencias funcionales serias que afectan UX y precisión de precios. Ver sección 14.

### Vocabulario del negocio
- **Detalle**: venta de 1-2 unidades, precio normal
- **Mayoreo / por mayor**: venta de 3+ unidades con descuento
- **Surtido**: para productos con variaciones (color, número, aroma), entrega de varias variaciones distintas en un mismo pedido mayorista para evitar romper cajas/empaques del proveedor
- **Caja**: empaque del proveedor con cantidad limitada por variación (ej: 4 unidades del color X). Solo se rompe si el cliente compra a precio detalle.

---

## 1. Resumen ejecutivo

Plugin WooCommerce que ofrece:

1. **Tiered pricing** de hasta 2 niveles para productos simples y variables
2. **Surtido automático** — el sistema reparte variaciones equilibradamente
3. **Surtido manual** — el cliente arma el surtido respetando un límite por variación
4. **Modo único o individual** para descuentos en variaciones
5. **Sistema de debug** integrado para diagnosticar comportamiento en producción

---

## 2. Arquitectura general

### Decisión arquitectónica: un plugin con interruptor de modo (Opción 2)

```
Ajustes WooCommerce → Mayorista → Modo de surtido:
  [ ] Solo automático ("Sorpréndeme")
  [ ] Solo manual (cliente arma)
  [x] Ambos (cliente decide)
```

**Razones:**
- Una sola base de código, sin duplicación
- Activación/desactivación de modos sin reinstalar
- Permite A/B testing por períodos
- Permite mostrar ambos modos al cliente si funciona bien

**Aislamiento de modos** (clave para debug):
- Cada modo vive en su propio archivo
- Hooks con prefijo único por modo
- Logger central marca cada operación con su modo origen
- Respuestas AJAX incluyen `_debug_modo`

---

## 3. Estructura de archivos

```
beautygirlmg-mayorista/
├── beautygirlmg-mayorista.php       ← bootstrap + carga condicional de módulos
├── SPECS.md                          ← este documento
├── README.md                         ← guía de instalación
├── includes/
│   ├── core/
│   │   ├── helpers.php               ← funciones compartidas (precios, descuentos)
│   │   ├── logger.php                ← bgm_log() y panel de debug
│   │   └── settings.php              ← pantalla de ajustes WC
│   ├── admin/
│   │   ├── pestaña-mayorista.php     ← pestaña en editar producto (simples + variables)
│   │   ├── editor-variaciones.php    ← campos en cada variación (modo individual)
│   │   └── preview-precio.js         ← preview en vivo del precio resultante
│   ├── frontend/
│   │   ├── producto-simple.php       ← bloques en página de producto simple
│   │   ├── producto-variable.php     ← bloques en página de producto variable
│   │   └── carrito.php               ← lógica de precios en carrito y pedidos
│   ├── modos/
│   │   ├── modo-auto.php             ← Modo A: Sorpréndeme con surtido
│   │   └── modo-manual.php           ← Modo B: cliente arma su surtido
│   └── ajax/
│       ├── ajax-auto.php             ← endpoint del modo automático
│       └── ajax-manual.php           ← endpoint del modo manual
└── assets/
    ├── frontend.css                  ← estilos cliente (sistema de diseño rosa/mauve)
    ├── frontend-auto.js              ← JS del modo automático
    ├── frontend-manual.js            ← JS del modo manual (grilla + contador)
    ├── admin.css                     ← estilos admin
    └── admin.js                      ← preview en vivo + switch modo único/individual
```

---

## 4. Estructura de datos (meta keys)

### 4.1 Productos simples

| Meta key | Tipo | Default | Descripción |
|---|---|---|---|
| `_bgm_min_1` | int | global (3) | Cantidad mínima para precio mayor 1 |
| `_bgm_descuento_1` | float | (vacío) | Descuento $ aplicado en nivel 1 |
| `_bgm_min_2` | int | global (12) | Cantidad mínima para precio mayor 2 |
| `_bgm_descuento_2` | float | (vacío) | Descuento $ aplicado en nivel 2 |

### 4.2 Productos variables

#### Meta del producto padre

| Meta key | Tipo | Default | Descripción |
|---|---|---|---|
| `_bgm_modo_descuento` | string | `unico` | `unico` (mismo descuento para todas) o `individual` (por variación) |
| `_bgm_max_por_variacion` | int \| vacío | (vacío) | **Opcional**. Límite máx. de unidades por variación para acceder a precio mayor. Vacío = sin restricción de surtido (cliente arma como quiera) |

Si `_bgm_modo_descuento = unico`:
- Usa los mismos campos que simples (`_bgm_min_1`, `_bgm_descuento_1`, etc.) en el padre.

Si `_bgm_modo_descuento = individual`:
- Los campos `_bgm_min_1`, `_bgm_descuento_1`, `_bgm_min_2`, `_bgm_descuento_2` viven en cada **variación** (post de tipo `product_variation`).

### 4.3 Ajustes globales (en `wp_options`)

| Option key | Default | Descripción |
|---|---|---|
| `bgm_min_global_1` | 3 | Mínimo global para nivel 1 |
| `bgm_min_global_2` | 12 | Mínimo global para nivel 2 |
| `bgm_max_por_variacion_global` | (vacío) | **Opcional**. Máximo por variación default (vacío = sin restricción global) |
| `bgm_modo_surtido` | `ambos` | `auto`, `manual` o `ambos` |
| `bgm_debug_activo` | `0` | `0` (off) o `1` (on) — escribir logs |

### 4.4 Meta del ítem de carrito (cart item data)

Para items agregados como surtido (modo auto o manual):

| Key | Descripción |
|---|---|
| `bgm_modo` | `auto` o `manual` |
| `bgm_surtido_grupo` | UUID que agrupa varias variaciones del mismo surtido |
| `bgm_descuento_aplicado` | Monto $ descontado |
| `bgm_nivel_aplicado` | `1` o `2` (cuál tier se aplicó) |

---

## 5. Reglas de negocio

### 5.1 Productos simples — tiered pricing

**Lógica de precios en el carrito:**

```
qty = cantidad del ítem en carrito
si qty < min_1:                    → precio_regular
si qty >= min_1 y qty < min_2:    → precio_regular - descuento_1
si qty >= min_2:                   → precio_regular - descuento_2
```

**Tolerancia (importante):**
- Si solo configura nivel 1 → solo aplica nivel 1
- Si solo configura nivel 2 → aplica nivel 2 desde `min_2`
- Si no configura nada → producto sin precio mayorista (no se muestran bloques)

**Precio base siempre = `regular_price`** (ignora ofertas / `sale_price`).

### 5.2 Productos variables — modo único vs individual

**Modo `unico`** (default, mayoría de casos):
- Mismo descuento se aplica a todas las variaciones
- Configurado en la pestaña Mayorista del padre
- Una sola configuración para mantener

**Modo `individual`**:
- Cada variación tiene su propio descuento mayorista
- Configurado en el editor de cada variación
- Se usa cuando hay variaciones con precios muy distintos o promociones puntuales

El switch está en la pestaña Mayorista del padre y al cambiar entre modos NO se borran los datos del modo anterior (solo se ignoran).

### 5.3 Algoritmo: Surtido automático

**Input**: producto variable + cantidad solicitada (qty)

**Pasos:**

1. Obtener todas las variaciones del producto
2. Filtrar: solo las que tienen stock > 0 (o stock no gestionado)
3. Si no hay ninguna disponible → error: "Sin stock disponible"
4. Calcular distribución base:
   ```
   n = cantidad de variaciones disponibles
   por_variacion = qty ÷ n  (división entera)
   sobrantes     = qty mod n
   ```
5. Asignar `por_variacion` a cada variación
6. Asignar +1 a `sobrantes` variaciones elegidas al azar
7. **Validar stock por variación:**
   - Si una variación tiene `stock < asignado` → reasignar el excedente a otras con stock disponible
   - Repetir hasta que todas las asignaciones sean válidas
   - Si el stock total de todas las variaciones < qty solicitada → error con cantidad disponible
8. Crear N items en el carrito (uno por cada variación con asignación > 0), agrupados por `bgm_surtido_grupo`
9. Aplicar precio mayorista (nivel correspondiente según qty total)

**Ejemplos verificados:**

| Variaciones disponibles | Pide | Resultado |
|---|---|---|
| 4 colores | 8 | 2+2+2+2 |
| 10 colores | 3 | 1+1+1 (3 al azar) |
| 4 colores | 5 | 2+1+1+1 (el +1 al azar) |
| 4 colores | 6 | 2+2+1+1 |
| 4 colores | 10 | 3+3+2+2 |
| 4 colores (1 sin stock) | 6 | reparte entre 3: 2+2+2 |

### 5.4 Algoritmo: Surtido manual

**Input**: producto variable + objeto `{variacion_id: cantidad}` (lo que el cliente armó)

**Validaciones:**

1. Verificar stock por variación
2. **Si `_bgm_max_por_variacion` está configurado**: verificar que ninguna variación lo supere. **Si está vacío**: no aplicar restricción de surtido (cliente puede armar como quiera, incluso 100 de un solo color)
3. Sumar cantidad total
4. Determinar qué tier aplica según total:
   - `total >= min_2` → nivel 2
   - `total >= min_1` → nivel 1
   - `total < min_1` → no aplica mayorista (precio detalle)

**Si `_bgm_max_por_variacion` está configurado y una variación lo supera:**
- Mostrar mensaje: "Máximo X unidades por variación para precio mayorista"
- Mostrar precio detalle automáticamente para esa cantidad
- Permitir agregar al carrito a precio detalle (no bloquear venta)

**Sin topes superiores**: el cliente puede comprar cualquier cantidad. El sistema solo determina el precio que paga, nunca bloquea una venta por exceso.

**Si todo ok:**
- Crear N items en el carrito agrupados por `bgm_surtido_grupo`
- Aplicar precio mayorista del tier correspondiente

### 5.5 Cliente arma surtido manual SIN usar el bloque mayorista

Caso: cliente agrega variaciones al carrito una por una con el botón nativo de WooCommerce, sumando 3+ unidades en total del mismo producto padre.

**Decisión**: el plugin **detecta** este caso y aplica precio mayorista si:
- Suma total de unidades del mismo producto padre ≥ `min_1`
- Si `_bgm_max_por_variacion` está configurado: ninguna variación lo supera. Si está vacío: no se aplica restricción
- El producto padre tiene descuento mayorista configurado

Si `_bgm_max_por_variacion` está configurado y una variación lo supera → no aplica mayorista para ese conjunto, queda a precio detalle.

Esto resuelve la inconsistencia actual donde el cliente que arma manualmente queda sin descuento.

---

## 6. UX cliente

### 6.1 Página de producto simple

```
[Galería + título + precio normal]

┌──────────────────────────────────────────┐
│ ★ Lleva 3 o más y paga $5.790 c/u       │   ← aviso nivel 1
│   Ahorras $200 por unidad               │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│ ★★ Lleva 12 o más y paga $5.490 c/u     │   ← aviso nivel 2 (si configurado)
│   Ahorras $500 por unidad               │
└──────────────────────────────────────────┘

[ - 1 + ]  [ Agregar al carrito ]   ← botones nativos de WC
```

El descuento se aplica automáticamente cuando suben la cantidad en el botón nativo.

### 6.2 Página de producto variable

```
[Galería + título + rango de precios + selectores de variación]

┌──────────────────────────────────────────┐
│ ★ Mayorista desde 3 ud:  $5.790 c/u    │   ← badge nivel 1
│ ★★ Mayorista desde 12 ud: $5.490 c/u   │   ← badge nivel 2
└──────────────────────────────────────────┘

[ Selección de variación + Agregar normal ]   ← botón WC para detalle

═══════ COMPRAR POR MAYOR ═══════

  Modo del cliente (según ajuste 'bgm_modo_surtido'):

  ┌──────────────────┬──────────────────┐
  │  Sorpréndeme     │  Armar mi surtido │   ← tabs si modo='ambos'
  └──────────────────┴──────────────────┘

  [contenido del modo activo]
```

#### Modo A — Sorpréndeme con surtido

```
┌──────────────────────────────────────────┐
│ "Te enviamos variedad equilibrada       │
│  según el stock disponible"             │
│                                          │
│ Cantidad: [ - 6 + ]                     │
│ Subtotal: $34.740 (a $5.790 c/u)        │
│                                          │
│ [ Agregar surtido al carrito ]          │
└──────────────────────────────────────────┘
```

#### Modo B — Armar mi surtido

```
┌──────────────────────────────────────────┐
│ Elige cuántas unidades por color:       │
│ Máximo 4 por color para precio mayor.   │
│                                          │
│ ● Rosa palo    [ - 2 + ]                │
│ ● Coral        [ - 2 + ]                │
│ ● Nude         [ - 2 + ]                │
│ ● Vino         [ - 0 + ]                │
│                                          │
│ ─────────────────────────────────       │
│ Llevas 6 unidades · Precio mayor 1 ✓    │   ← contador en vivo
│ Subtotal: $34.740                       │
│                                          │
│ [ Agregar surtido al carrito ]          │
└──────────────────────────────────────────┘
```

**Estados del contador:**
- `Llevas 1-2 ud · Precio detalle` (gris, sin descuento aún)
- `Llevas 3-11 ud · Precio mayor 1 ✓` (rosa, descuento 1 activo)
- `Llevas 12+ ud · Precio mayor 2 ✓✓` (rosa fuerte, descuento 2 activo)
- `Excediste 4 ud en Coral · Aplica precio detalle` (rojo suave, advertencia)

### 6.3 Carrito

Los items de surtido (mismo `bgm_surtido_grupo`) se muestran agrupados visualmente:

```
┌──────────────────────────────────────────┐
│ Producto X · Surtido mayorista          │
│ ├─ Rosa palo × 2 ··············· $11.580 │
│ ├─ Coral × 2 ··················· $11.580 │
│ └─ Nude × 2 ···················· $11.580 │
│ Total grupo: 6 ud × $5.790 = $34.740    │
│ [ Quitar surtido completo ]             │
└──────────────────────────────────────────┘
```

Para productos simples con descuento aplicado:
```
Subtotal: $17.370
✓ Precio mayorista nivel 1 aplicado
```

Si está cerca del siguiente tier:
```
Subtotal: $34.740
ℹ Agrega 4 más y paga $5.490 c/u (ahorras $1.200 más)
```

---

## 7. UX admin

### 7.1 Pestaña "Mayorista" en editar producto

```
┌──────────────────────────────────────────────────────┐
│ MAYORISTA                                            │
├──────────────────────────────────────────────────────┤
│                                                      │
│ ┌─ Solo para variables ─────────────────────────┐   │
│ │ Modo de descuento:                            │   │
│ │  ◉ Único para todas las variaciones           │   │
│ │  ○ Individual por variación                   │   │
│ └────────────────────────────────────────────────┘   │
│                                                      │
│ ─────────────────────────────────────────            │
│                                                      │
│ Nivel 1 — mayoreo                                    │
│  Cantidad mínima:  [   3   ]   (default global: 3) │
│  Descuento $:      [  200  ]   → Precio: $5.790    │
│                                                      │
│ Nivel 2 — mayoreo grande (opcional)                  │
│  Cantidad mínima:  [  12   ]   (default global: 12)│
│  Descuento $:      [  500  ]   → Precio: $5.490    │
│                                                      │
│ ─────────────────────────────────────────            │
│                                                      │
│ ┌─ Solo para variables ─────────────────────────┐   │
│ │ Surtido manual                                 │   │
│ │  Máx. unidades por variación: [  4  ]         │   │
│ │  (default global: 2)                          │   │
│ └────────────────────────────────────────────────┘   │
│                                                      │
│ ─────────────────────────────────────────            │
│                                                      │
│ ┌──────────────────────────────────┐                │
│ │ Resumen                          │                │
│ │  Detalle (1-2 ud)       $5.990   │                │
│ │  Mayorista 1 (3-11 ud)  $5.790   │                │
│ │  Mayorista 2 (12+ ud)   $5.490   │                │
│ └──────────────────────────────────┘                │
└──────────────────────────────────────────────────────┘
```

**Comportamiento del campo "Descuento $":**
- Mientras el admin escribe → preview en vivo "→ Precio: $X" se actualiza
- Si dejas vacío → el nivel queda desactivado
- No se rompe si solo configuras nivel 1 o solo nivel 2

### 7.2 Editor de variación (modo individual)

Cuando `_bgm_modo_descuento = individual`, en cada variación aparece:

```
[Campos nativos: Imagen, SKU, Precio, Stock, ...]
─────────────────────────────────────────
Mayorista (esta variación)
  Cantidad mínima nivel 1: [  3  ]
  Descuento $ nivel 1:     [ 250 ] → $5.740
  Cantidad mínima nivel 2: [ 12  ]
  Descuento $ nivel 2:     [ 600 ] → $5.390
```

### 7.3 Pantalla de ajustes globales

`WooCommerce → Ajustes → Mayorista`

```
┌──────────────────────────────────────────────────────┐
│ AJUSTES MAYORISTA (BeautyGirlMG)                     │
├──────────────────────────────────────────────────────┤
│                                                      │
│ Defaults globales                                    │
│  Mínimo nivel 1 (global):     [  3  ]                │
│  Mínimo nivel 2 (global):     [ 12  ]                │
│  Máx. por variación (global): [  2  ]                │
│                                                      │
│ ─────────────────────────────────────────            │
│                                                      │
│ Modo de surtido para variables                       │
│  ○ Solo automático (Sorpréndeme)                    │
│  ○ Solo manual (cliente arma)                       │
│  ◉ Ambos (cliente decide)                           │
│                                                      │
│ ─────────────────────────────────────────            │
│                                                      │
│ Debug                                                │
│  ☐ Activar registro de logs                         │
│  Ruta: /wp-content/uploads/bgm-logs/bgm.log         │
│  [ Ver logs ] [ Vaciar logs ]                       │
└──────────────────────────────────────────────────────┘
```

---

## 8. Sistema de debug

### 8.1 Logger central

```php
bgm_log( $modo, $mensaje, $contexto = [] );

// Ejemplos:
bgm_log( 'auto',   'Variación elegida', [ 'id' => 42, 'stock' => 12 ] );
bgm_log( 'manual', 'Excedió límite', [ 'variacion' => 'Rojo', 'qty' => 5, 'max' => 4 ] );
bgm_log( 'core',   'Aplicado nivel 2', [ 'qty' => 14, 'descuento' => 500 ] );
```

**Salida** (en `wp-content/uploads/bgm-logs/bgm.log`):
```
[2026-05-15 14:32:01] [auto]   Variación elegida {"id":42,"stock":12}
[2026-05-15 14:32:01] [core]   Aplicado nivel 1 {"qty":6,"descuento":200}
[2026-05-15 14:33:15] [manual] Excedió límite {"variacion":"Rojo","qty":5,"max":4}
```

### 8.2 Niveles
- `info` — operaciones normales
- `warning` — comportamiento inesperado pero recuperable
- `error` — falla que afecta al cliente

### 8.3 Marca en respuestas AJAX

Toda respuesta AJAX trae:
```json
{
  "success": true,
  "data": { ... },
  "_debug": {
    "modo": "auto",
    "version": "2.0.0",
    "tier_aplicado": 1
  }
}
```

### 8.4 Mensajes de error visibles

Cuando el debug está activo, los mensajes de error muestran el origen:
```
"Error al agregar al carrito (modo: surtido automático)"
```

Cuando está inactivo:
```
"Error al agregar al carrito. Intenta de nuevo."
```

---

## 9. Sistema de diseño visual

> Aplicar estos estilos en `assets/frontend.css`. Sistema definido para new.beautygirlmg.cl.

### Variables CSS

```css
:root {
  --pink:      #F2C4CE;   /* rosa suave — fondos, bordes */
  --pink-soft: #FBF0F2;   /* rosa muy suave — fondos hover, badges */
  --pink-dark: #C4728A;   /* rosa fuerte — acentos, botones primarios */
  --cream:     #FDF7F4;   /* crema — fondo general */
  --dark:      #1A1015;   /* casi negro — textos principales */
  --mid:       #7A5060;   /* mauve — textos secundarios, iconos */
  --border:    #f0e0e5;   /* rosa muy claro — bordes */
}
```

### Tipografía
> Migrada en bgmg-landing 6.5.11: el sitio carga **Alice + Poppins**. El plugin las hereda del tema
> (los `<link>` de Google Fonts viven en los templates de bgmg-landing). Mantener este par.
- **Títulos**: `'Alice', serif` (un solo peso, 400; los `font-weight:600` salen en negrita sintética)
- **UI / texto**: `'Poppins', sans-serif`

### Componentes

**Botones**
- Primario: bg `--pink-dark` + texto blanco, `border-radius: 30px`
- Secundario: outline `1.5px solid --border`, hover `--pink-soft`
- Circular (+/−): 38–40px, borde `--pink`, texto `--pink-dark`, hover relleno rosa

**Cards**
- Fondo blanco, `border-radius: 14–20px`
- Sombra: `0 4px 32px rgba(196, 114, 138, .10)`

**Badges**
- Categoría: `--pink-soft` fondo + `--pink-dark` texto, `border-radius: 20px`
- Oferta/destacado: `--pink-dark` fondo + texto blanco

**Inputs**
- Borde `--border`, focus `--pink-dark`, `border-radius: 6–8px`

**Pills/filtros**
- Activo: `--pink-dark` fondo + blanco
- Inactivo: `--pink-soft` + `--mid`

### Patrones generales
- Border-radius: 6px inputs · 8px botones · 14–16px cards · 20px modales
- Transiciones: 0.15s–0.2s ease en hover
- Área táctil mínima mobile: 44px

---

## 10. Decisiones tomadas (consolidadas)

| # | Decisión |
|---|---|
| 1 | 2 niveles de tiered pricing, ambos opcionales, no se rompe si falta uno |
| 2 | Campo es **descuento $** (no precio), con preview en vivo del precio resultante |
| 3 | Umbrales (mín_1, mín_2, máx_por_variación) configurables por producto, con default global |
| 4 | Variables: switch **único / individual** para descuento por variación |
| 5 | Surtido automático = distribución equitativa, reasigna si una variación no tiene stock |
| 6 | Surtido manual = límite máximo configurable por variación (Opción A) |
| 7 | Precio base = `regular_price` siempre (ignora ofertas / `sale_price`) |
| 8 | UX cliente: dos modos (Sorpréndeme / Armar) seleccionables por ajuste global |
| 9 | Límite máximo por variación = **valor fijo configurable** por producto |
| 10 | Arquitectura: un plugin con interruptor de modo, módulos aislados, logger central |
| 11 | Cliente que arma manual sin botón mayorista también recibe descuento si cumple reglas |
| 12 | Tiered pricing aplica a simples y variables por igual |
| 13 | **Sin diferenciación de compradores**: todos los visitantes ven precio mayorista (no hay roles B2B) |
| 14 | **Impuestos**: el precio configurado ya incluye IVA. No se aplica lógica de impuestos especial |
| 15 | **Sin topes superiores**: el cliente puede comprar cualquier cantidad. Las reglas solo determinan el precio, nunca bloquean la venta |
| 16 | `_bgm_max_por_variacion` es **opcional**: vacío = sin restricción de surtido; con valor = filtro anti-rompe-cajas |

---

## 11. Validación post-implementación

Checklist para verificar que la v2.0 funciona:

- [ ] Plugin activo sin errores PHP en log
- [ ] Producto simple con descuento nivel 1 → aviso visible en página
- [ ] Agregar 3 simples al carrito → aplica precio mayor 1 automático
- [ ] Agregar 12 simples → aplica precio mayor 2
- [ ] Producto variable modo único → mismo descuento en todas las variaciones
- [ ] Producto variable modo individual → cada variación con su descuento
- [ ] Modo Auto: pedir 8 con 4 variaciones → reparte 2+2+2+2
- [ ] Modo Auto: una variación sin stock → reasigna correctamente
- [ ] Modo Manual: armar 6 distribuido → aplica mayor 1
- [ ] Modo Manual: armar 6 con 5 en una variación (límite 4) → muestra precio detalle
- [ ] Cliente arma 3 manualmente sin usar bloque → recibe descuento
- [ ] Items de surtido se agrupan visualmente en carrito
- [ ] Pedido completo refleja descuento correcto
- [ ] Logs se escriben cuando debug está activo
- [ ] Logs NO se escriben cuando debug está inactivo

---

## 12. Pendientes / decisiones futuras

> Las decisiones sobre roles B2B, impuestos y límites máximos ya están cerradas — ver decisiones #13, #14, #15 en sección 10.

**Pendientes reales para versiones futuras:**

- **Reportes**: panel admin con métricas (cuántos pedidos mayoristas, ahorro total entregado, productos más vendidos por mayor)
- **Notificaciones**: avisar al admin cuando un cliente reciba un descuento muy alto o haga un pedido grande
- **Impuestos avanzados** (futuro): si en algún momento se decide manejar precios sin IVA y aplicar impuestos por separado, definir lógica de descuento sobre base imponible
- **Roles B2B** (futuro): si se quiere ofrecer precios diferenciados para clientes registrados vs públicos, agregar capa de visibilidad por rol

---

## 14. Problemas del plugin v1.0 que esta versión resuelve

1. **Surtido falso**: v1 elegía 1 sola variación con más stock (no era surtido). v2 reparte real.
2. **Inconsistencia simples vs variables**: v1 daba descuento a simples automáticamente pero no a variables sin botón. v2 detecta cualquier configuración válida.
3. **Mezcla regular/sale**: v1 mezclaba `get_price()` y `get_regular_price()`. v2 usa siempre `regular_price`.
4. **Variables con precios distintos**: v1 calculaba sobre el mínimo. v2 soporta modo individual.
5. **Campo confuso**: v1 llamaba "precio mayorista" pero almacenaba descuento. v2 explícitamente "descuento $".
6. **Botón sin validar stock**: v1 mostraba botón aunque no hubiera stock. v2 valida y oculta/avisa.
7. **Supresión agresiva de popup**: v1 ocultaba popup nativo de WC para todos los items. v2 solo para items de surtido.
8. **Sin logs**: v1 no registraba nada. v2 tiene logger central con on/off.
9. **Mínimo hardcodeado**: v1 tenía `BGM_MIN_MAYORISTA = 3` fijo. v2 configurable por producto.
10. **Sin tiered pricing**: v1 solo un nivel. v2 dos niveles.

---

**Versión del documento**: 1.0  
**Fecha**: 2026-05-15  
**Estado**: aprobado para implementación
