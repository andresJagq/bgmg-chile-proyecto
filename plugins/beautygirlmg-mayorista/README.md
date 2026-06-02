# BeautyGirlMG Mayorista v2.0

Sistema de precios mayorista con tiered pricing (2 niveles) y surtido automático/manual para beautygirlmg.cl.

> Documento técnico completo en [SPECS.md](SPECS.md).

## Estado del desarrollo

| Fase | Estado |
|---|---|
| 1. Núcleo (bootstrap, helpers, logger, settings) | ✅ Completa |
| 2. Admin (pestaña Mayorista, preview, editor variaciones) | ✅ Completa |
| 3. Frontend simples (avisos, carrito tiered) | ✅ Completa |
| 4. Frontend variables base (avisos, surtido manual sin botón) | ✅ Completa |
| 5. Modo Auto (Sorpréndeme + algoritmo distribución + AJAX) | ✅ Completa |
| 6. Modo Manual (grilla + contador + validación + AJAX) | ✅ Completa |
| 7. CSS sistema de diseño rosa/mauve | ✅ Completa |
| 8. Checklist de validación post-instalación | ✅ Completa |

## Estructura completa

```
beautygirlmg-mayorista/
├── beautygirlmg-mayorista.php       ← bootstrap (carga modular según ajustes)
├── README.md                         ← este archivo
├── SPECS.md                          ← especificaciones técnicas completas
├── includes/
│   ├── core/
│   │   ├── helpers.php               ← cálculo de precios + lectura de config
│   │   ├── logger.php                ← sistema de debug con prefijo de modo
│   │   └── settings.php              ← WC → Ajustes → Mayorista
│   ├── admin/
│   │   ├── pestana-mayorista.php     ← pestaña en editar producto
│   │   └── editor-variaciones.php    ← campos en variaciones (modo individual)
│   ├── frontend/
│   │   ├── producto-simple.php       ← avisos en página de producto simple
│   │   ├── producto-variable.php     ← avisos + contenedor de modos en variables
│   │   └── carrito.php               ← lógica de precios en carrito
│   ├── modos/
│   │   ├── modo-auto.php             ← UI "Sorpréndeme" + algoritmo distribución
│   │   └── modo-manual.php           ← UI grilla + contador en vivo
│   └── ajax/
│       ├── ajax-auto.php             ← endpoint del modo automático
│       └── ajax-manual.php           ← endpoint del modo manual
└── assets/
    ├── admin.css                     ← estilos rosa/mauve para admin
    ├── admin.js                      ← preview en vivo del precio
    ├── frontend.css                  ← estilos rosa/mauve para tienda
    ├── frontend-auto.js              ← JS del modo automático
    └── frontend-manual.js            ← JS del modo manual (contador + validación)
```

## Instalación

1. Comprime la carpeta `beautygirlmg-mayorista/` en ZIP
2. **WordPress Admin → Plugins → Añadir nuevo → Subir plugin**
3. Sube el ZIP y **activa**

## Configuración inicial

Ir a **WooCommerce → Ajustes → Mayorista**:

1. **Defaults globales**: ajusta los mínimos de tier 1 y tier 2 si difieren de 3 y 12
2. **Máx por variación global**: deja vacío si no tienes restricción de empaques (recomendado por defecto)
3. **Modo de surtido**: elige `auto`, `manual`, o `ambos` según prefieras
4. **Debug**: déjalo apagado en producción, actívalo solo para diagnosticar

## Configurar precios mayoristas en productos

### Producto simple
1. Editar producto → pestaña **Mayorista**
2. Llenar nivel 1 (descuento $) y opcionalmente nivel 2
3. Ver el preview en vivo del precio resultante a la derecha
4. La tabla resumen abajo muestra la comparación detalle / mayor 1 / mayor 2

### Producto variable
1. Editar producto → pestaña **Mayorista**
2. Elegir **Modo de descuento**:
   - **Único**: configurar un solo descuento que aplica a todas las variaciones (lo más común)
   - **Individual**: configurar descuento por cada variación (cuando hay precios distintos)
3. Para "Surtido manual": configurar máximo unidades por variación (opcional)
4. En modo individual: ir a pestaña Variaciones → editar cada una → llenar campos mayoristas

## Cómo funciona en la tienda

### Producto simple
- Se muestran avisos: "Lleva 3 o más y paga $X c/u" / "Lleva 12 o más y paga $Y c/u"
- El cliente sube la cantidad con el botón nativo de WC
- En el carrito el descuento se aplica automáticamente según cantidad

### Producto variable
Aparece un bloque "Comprar por mayor" con uno o ambos modos según ajuste:

**Modo Sorpréndeme**: el cliente elige solo cantidad. El sistema reparte equitativamente entre las variaciones disponibles (si pide 8 y hay 4 colores → 2+2+2+2).

**Modo Armar mi surtido**: grilla con cada variación. El cliente arma manualmente. Contador en vivo muestra: cantidad total · tier aplicado · subtotal. Si excede el máximo por variación, aplica precio detalle.

**Variación libre sin botón**: si el cliente agrega variaciones con el botón nativo de WC y suma ≥ mínimo, el descuento también se aplica (siempre que no exceda max_por_variacion configurado).

## Checklist de validación post-instalación

Recomendado correr en orden:

- [ ] Plugin activo sin errores PHP en log
- [ ] Pestaña "WooCommerce → Ajustes → Mayorista" visible
- [ ] Defaults globales se guardan correctamente
- [ ] Modo de surtido se cambia entre auto/manual/ambos
- [ ] Debug: activar → guardar → "Ver logs" muestra al menos 1 línea

### Producto simple
- [ ] Pestaña "Mayorista" en el editor de producto simple
- [ ] Preview de precio en vivo al escribir descuento
- [ ] Tabla resumen actualiza en tiempo real
- [ ] Guarda y persiste valores
- [ ] Aviso "Lleva 3 o más y paga $X" en página de producto
- [ ] Si tier 2 configurado: aviso "Lleva 12 o más y paga $Y"
- [ ] Carrito: agregar 2 → precio detalle
- [ ] Carrito: agregar 3 → precio mayor 1 + aviso "Mayorista 1 ✓"
- [ ] Carrito: agregar 12 → precio mayor 2 + aviso "Mayorista 2 ✓"
- [ ] Sugerencia "Agrega N más y paga $Z c/u" cuando está cerca del siguiente tier

### Producto variable — modo único
- [ ] Switch "Único / Individual" funciona y muestra/oculta aviso correspondiente
- [ ] En modo único: campos editables en pestaña Mayorista del padre
- [ ] Avisos de mayoreo en página de producto variable
- [ ] Modo Sorpréndeme: cliente pide 8 → 4 variaciones → 2+2+2+2 en carrito
- [ ] Modo Sorpréndeme: una variación sin stock → reasigna correctamente
- [ ] Modo Manual: grilla muestra todas las variaciones disponibles
- [ ] Modo Manual: contador en vivo cambia color según tier
- [ ] Modo Manual: si max_por_variacion=2 y pones 3 en una variación → fila se marca rojo + mensaje "Excediste"
- [ ] Modo Manual: subtotal en vivo refleja el tier aplicado
- [ ] Items en carrito tienen tag "Surtido mayorista" / "Surtido manual"

### Producto variable — modo individual
- [ ] En modo individual: campos del padre quedan deshabilitados con aviso
- [ ] En cada variación: aparece bloque "Mayorista (esta variación)" con sus campos
- [ ] Preview de precio en vivo en cada variación
- [ ] Cada variación puede tener su propio descuento

### Cliente arma sin botón mayorista
- [ ] Cliente agrega 4 variaciones distintas con botón nativo de WC (1 cada una) → recibe descuento mayorista
- [ ] Si una variación supera max_por_variacion → todo el conjunto queda a precio detalle

### Logs
- [ ] Con debug activo: cada operación importante queda registrada
- [ ] Líneas marcadas con prefijo `[auto]`, `[manual]`, `[core]`, `[admin]`, `[cart]`
- [ ] Botón "Vaciar logs" funciona

## Sistema de debug

Si algo se comporta raro:

1. Ir a **WC → Ajustes → Mayorista**
2. Activar checkbox "Activar registro de logs"
3. Guardar
4. Reproducir el problema en frontend
5. Volver a Ajustes → click "Ver logs"
6. Ver qué módulo procesó la operación (`auto`, `manual`, `cart`, etc.)

Cada respuesta AJAX trae también `_debug.modo` en la consola del navegador (Network → click en la request → Response).

## Notas técnicas

- **Meta keys del plugin**: todos con prefijo `_bgm_*`
- **Opciones globales**: todas con prefijo `bgm_*`
- **Funciones**: todas con prefijo `bgm_*`
- **Hooks AJAX**: `bgm_agregar_auto`, `bgm_agregar_manual`
- **Action hooks de extensión**: `bgm_render_modo_auto`, `bgm_render_modo_manual`

## Próximos pasos sugeridos (post-validación)

1. Probar checklist completo en subdominio `new.beautygirlmg.cl`
2. Verificar UX en mobile (área táctil 44px ya considerada)
3. Si todo OK: replicar en producción `beautygirlmg.cl`
4. Importar CSV de descuentos restantes con importador nativo de WC mapeando columna a `_bgm_descuento_1`
