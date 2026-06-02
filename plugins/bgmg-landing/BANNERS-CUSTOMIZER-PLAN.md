# Panel centralizado de imágenes/banners del tema

> Estado: **Fase 1 + Fase 2 + Fase 3 IMPLEMENTADAS** (plugin bgmg-landing v6.2).
> - Fase 1: Hero slider gestionable desde WP Customizer
> - Fase 2: Banner mid-page (promocional) gestionable desde WP Customizer
> - Fase 3: Banner header por categoría editable desde el editor de cada categoría
> Decisión: WP Customizer para piezas globales del landing, term meta para piezas específicas de categoría.
> Beneficio principal: el admin sube imágenes desde wp-admin sin tocar CSS/PHP, con preview en vivo.

## ✅ Cómo usar el Customizer (ya implementado)

1. Entrar a **wp-admin → Apariencia → Personalizar**
2. Click en **BGMG Tema** (panel)
3. Click en **Hero slider del landing** (sección)
4. Para cada slide (1, 2, 3):
   - **Mostrar este slide**: activar/desactivar
   - **Imagen desktop**: subir 1920×720 px (producto a la derecha)
   - **Imagen mobile** (opcional): subir 800×1000 px (producto centrado). Si vacío, se usa la desktop también en mobile.
   - **Posición focal**: dónde quieres anclar el centro de la imagen (center / left / right / top / bottom)
   - **Velo oscuro**: activar si la imagen es muy clara y el texto se pierde
   - **Badge, label, título, subtítulo, pills, CTA**: textos editables. Si los dejás vacíos, se ven los defaults (texto actual del sitio).
5. **Publicar** para guardar

**Defaults**: si no configurás nada, los slides se ven IGUAL que antes de la implementación. No rompe el sitio.

## ⚙️ Archivos relevantes

| Archivo | Función |
|---|---|
| `inc/customizer.php` | Registra panel, sección y settings; defaults; sanitización |
| `bgmg-template.php` (líneas del hero) | Loop de 3 slides leyendo `get_theme_mod()`. Inyecta CSS inline con `background-image` por slide + media query mobile |
| `bgmg-landing.php` | Carga `inc/customizer.php` al inicio + versión 6.0 |

## ✅ Banner promocional mid-page (Fase 2 implementada)

Sección **Apariencia → Personalizar → BGMG Tema → Banner promocional (mid-page)**:

- **Mostrar el banner** (checkbox)
- **Estilo de fondo**: "Dark" (gradiente oscuro, el por defecto) o "Imagen" (usa la imagen que subas)
- **Imagen desktop** (1920×400 px) — solo se usa con estilo "Imagen"
- **Imagen mobile** (800×800 px) — opcional, fallback a desktop
- **Posición focal**
- **Velo oscuro** (recomendado activado, mejora legibilidad)
- **Título** (acepta `<br>` y `<em>`)
- **Subtítulo**
- **CTA texto + URL**

Sin configurar nada se ve igual que antes (gradiente dark + textos hardcoded).

## ✅ Banner header por categoría (Fase 3 implementada)

Editable por categoría desde **wp-admin → Productos → Categorías → editar categoría**:

- **Banner header (BGMG)**: subir / cambiar / quitar la imagen (usa el Media Library nativo)
- **Posición focal** (Centro / Izquierda / Derecha / Arriba / Abajo)
- **Velo oscuro** (recomendado activado, para que el título se lea sobre la imagen)

Comportamiento:
- Si la categoría tiene imagen subida → se renderiza un banner header arriba de la página de categoría con el título, breadcrumb y descripción dentro del banner. El header sticky original NO se duplica.
- Si la categoría no tiene imagen → la página se ve como siempre (sin banner, header sticky con el título).

Recomendación de tamaño: **1920×300 px** para desktop. La versión mobile usa el tamaño 'large' que WP genera automáticamente.

Helper público: `bgmg_get_cat_banner( $term_id )` devuelve `['url_desktop', 'url_mobile', 'focus', 'overlay']` o `null`.

## 🔜 Pendientes opcionales (no implementados)

- **Logo dinámico**: el tema ya usa `get_theme_mod('custom_logo')` que se gestiona desde **Apariencia → Personalizar → Identidad del sitio** (nativo de WP). No requiere trabajo adicional.

Eso es todo lo que queda de "centralización de imágenes". El sitio está completamente gestionable desde wp-admin sin tocar código.

---

## Alcance (documentación original del plan)

### Hero slider del landing (prioridad alta)
3 slides, cada uno con:
- **Imagen de fondo DESKTOP** (upload via Media Library, 1920×720 px, producto a la derecha)
- **Imagen de fondo MOBILE** (upload via Media Library, 800×1000 px, producto centrado vertical, ratio 4:5). Opcional — si no se sube, se usa la desktop como fallback.
- **Posición focal**: `center` / `left` / `right` (CSS `background-position`)
- **Overlay activado**: boolean (velo oscuro 45% para legibilidad del texto)
- **Etiqueta superior** (texto corto, ej. "Nueva colección")
- **Título principal** (acepta `<em>` para resaltar en rosa)
- **Subtítulo / descripción**
- **Pills** (3 etiquetas cortas separadas por coma)
- **CTA**: texto del botón + URL de destino
- **Activar/desactivar** este slide

### Logos
- **Logo del header** — usar `custom_logo` nativo de WP (`add_theme_support('custom-logo')`). 400×120 px recomendado.
- **Logo del footer** — opción nueva en Customizer: subir uno o reusar el del header.

### Banners adicionales (prioridad baja — agregar cuando sea necesario)
- **Banner mid-page del landing** (entre productos destacados y la grilla)
- **Banner por categoría** (imagen header en `bgmg-category.php`)
- **Banner del carrito vacío** (en lugar del emoji 🛒)

---

## Implementación detallada

### Archivo nuevo: `inc/customizer.php`

Cargado desde `bgmg-landing.php` con `require_once`. Registra todo en `customize_register`.

Estructura:

```php
add_action( 'customize_register', 'bgmg_customizer_register' );
function bgmg_customizer_register( $wp_customize ) {
    // Panel "BGMG Tema"
    $wp_customize->add_panel( 'bgmg_tema', [
        'title'    => 'BGMG Tema',
        'priority' => 30,
    ] );

    // Sección "Hero slider"
    $wp_customize->add_section( 'bgmg_hero', [
        'title' => 'Hero del landing',
        'panel' => 'bgmg_tema',
    ] );

    // Por cada slide (1, 2, 3):
    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "bgmg_slide_{$i}_image", [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "bgmg_slide_{$i}_image", [
            'label'   => "Slide {$i} — Imagen de fondo",
            'section' => 'bgmg_hero',
            'description' => 'Recomendado: 1920×720 px. Punto focal a la derecha si el texto va a la izquierda.',
        ] ) );

        $wp_customize->add_setting( "bgmg_slide_{$i}_focus", [
            'default' => 'center',
            'sanitize_callback' => 'sanitize_key',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_focus", [
            'label'   => "Slide {$i} — Posición focal",
            'section' => 'bgmg_hero',
            'type'    => 'select',
            'choices' => [
                'center' => 'Centro',
                'left'   => 'Izquierda',
                'right'  => 'Derecha',
            ],
        ] );

        $wp_customize->add_setting( "bgmg_slide_{$i}_overlay", [
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_overlay", [
            'label'   => "Slide {$i} — Velo oscuro para mejorar legibilidad",
            'section' => 'bgmg_hero',
            'type'    => 'checkbox',
        ] );

        // Repetir para: label, title, subtitle, pills, cta_text, cta_url, enabled
    }
}
```

### Refactor en `bgmg-template.php`

El hero slider hoy renderiza HTML con clases hardcoded `.bgmg-slide-1`, `.bgmg-slide-2`, `.bgmg-slide-3`. El CSS asigna gradientes a cada una.

Cambio: loopear los 3 slides leyendo `get_theme_mod( "bgmg_slide_{$i}_image_desktop" )` y `_image_mobile`. Para servir la imagen correcta según viewport, emitir `<style>` inline con media query por slide.

```php
<style id="bgmg-hero-bg-<?php echo $i; ?>">
    .bgmg-slide-<?php echo $i; ?> {
        background-image: url('<?php echo esc_url($img_desktop); ?>');
        background-position: <?php echo $focus; ?>;
    }
    @media (max-width: 767px) {
        .bgmg-slide-<?php echo $i; ?> {
            background-image: url('<?php echo esc_url($img_mobile ?: $img_desktop); ?>');
        }
    }
</style>
```

Si no hay `$img_mobile`, fallback al desktop. Si no hay ninguno, fallback al gradiente CSS (no se setea ningún `background-image`).

```php
<?php for ( $i = 1; $i <= 3; $i++ ) :
    $enabled = get_theme_mod( "bgmg_slide_{$i}_enabled", true );
    if ( ! $enabled ) continue;
    $image   = get_theme_mod( "bgmg_slide_{$i}_image", '' );
    $focus   = get_theme_mod( "bgmg_slide_{$i}_focus", 'center' );
    $overlay = get_theme_mod( "bgmg_slide_{$i}_overlay", false );
    $bg_style = $image
        ? "background-image:url('" . esc_url( $image ) . "');background-position:" . esc_attr( $focus ) . ";"
        : "";
    ?>
    <div class="swiper-slide bgmg-slide-<?php echo $i; ?>" style="<?php echo $bg_style; ?>">
        <?php if ( $overlay && $image ) : ?>
            <div class="bgmg-slide-overlay" style="display:block;"></div>
        <?php endif; ?>
        <div class="bgmg-slide-inner">
            <!-- contenido del slide ... -->
        </div>
    </div>
<?php endfor; ?>
```

Con esto, el gradiente queda como fallback en CSS para cuando no hay imagen.

### Logo (usar nativo de WP)

En `bgmg-landing.php`, agregar:

```php
add_action( 'after_setup_theme', function() {
    add_theme_support( 'custom-logo', [
        'height'      => 120,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ] );
} );
```

Luego en `bgmg_render_header()`, reemplazar el código actual del logo por:

```php
if ( has_custom_logo() ) {
    the_custom_logo();
} else {
    echo '<a class="bgmg-logo-link" href="' . esc_url( home_url('/') ) . '">';
    echo '<span class="bgmg-logo-text">BeautyGirl<em>MG</em></span>';
    echo '</a>';
}
```

El admin podrá cambiar el logo desde **Apariencia → Personalizar → Identidad del sitio**.

---

## Roadmap por fases

| Fase | Tarea | Tiempo estimado |
|---|---|---|
| 1 | Crear `inc/customizer.php`, registrar settings del hero (3 slides × ~8 settings c/u) | 45 min |
| 2 | Refactorizar `bgmg-template.php`: hero loopea slides y lee del Customizer | 30 min |
| 3 | Activar `custom-logo` nativo + integrar en header y footer | 20 min |
| 4 | Probar: subir imagen al slide 1, verificar preview en vivo, guardar, verificar en producción | 15 min |
| 5 | Documentación inline (tooltips con medidas recomendadas) | 10 min |
| **Opcional** | Banner mid-page del landing (sección nueva del Customizer) | 30 min |
| **Opcional** | Banner header de categoría (modificar `bgmg-category.php`) | 20 min |

**Total Fase 1-5**: ~2 horas.

---

## Notas técnicas

- **Cache busting**: cuando el admin cambie una imagen, WP refresca el `theme_mod` pero el browser puede tener cacheada la URL anterior. WP genera URLs con IDs únicos por upload, así que esto se resuelve solo.
- **Sanitización**:
  - URLs de imágenes: `esc_url_raw` (save) + `esc_url` (output)
  - Booleans: `rest_sanitize_boolean`
  - Selects: `sanitize_key`
  - Textos cortos: `sanitize_text_field`
  - URLs CTA: `esc_url_raw`
- **Performance**: cada slide hace 1-2 llamadas a `get_theme_mod()`. Trivial.
- **Backwards compat**: el `for $i = 1; $i <= 3` reemplaza el HTML hardcoded actual del hero. Si el admin no sube nada, los gradientes se mantienen por el fallback CSS.

---

## Cuándo retomar

Cuando el cliente:
1. Tenga las imágenes del hero listas (3 archivos, 1920×720 px o similar)
2. Quiera empezar a editar el contenido del hero sin tocar código
3. Quiera agregar banners adicionales (categorías, mid-page, etc.)

En ese momento, retomar este plan empezando por la Fase 1.
