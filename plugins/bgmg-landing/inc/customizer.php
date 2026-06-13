<?php
/**
 * =========================================================
 * MÓDULO: WP CUSTOMIZER (panel "BGMG Tema")
 *
 * Registra una sección visible en Apariencia → Personalizar
 * para que el admin gestione las imágenes y textos del hero
 * slider del landing SIN tocar código.
 *
 * Por slide (1, 2, 3) se configuran:
 *   - enabled (activar/desactivar)
 *   - image_desktop (1920×720 px, producto a la derecha)
 *   - image_mobile  (800×1000 px, producto centrado vertical)
 *   - focus         (center / left / right)
 *   - overlay       (velo oscuro para legibilidad)
 *   - badge         (etiqueta flotante superior derecha)
 *   - label         (etiqueta pequeña sobre el título)
 *   - title         (título principal, acepta <br> y <em>)
 *   - subtitle      (texto descriptivo)
 *   - pill_1/2/3    (chips de feature)
 *   - cta_text      (texto del botón)
 *   - cta_url       (link del botón)
 *
 * Los valores se leen desde bgmg-template.php con
 * get_theme_mod( "bgmg_slide_{$i}_xxx", $default ). Si el
 * admin no configura nada, los defaults reproducen el
 * contenido actual del hero (sin cambios visibles).
 * =========================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'customize_register', 'bgmg_customizer_register' );
function bgmg_customizer_register( $wp_customize ) {

    // ─── Panel principal ────────────────────────────────────────
    $wp_customize->add_panel( 'bgmg_tema', [
        'title'       => __( 'BGMG Tema', 'bgmg' ),
        'description' => __( 'Configura imágenes, textos y CTAs del landing sin tocar código.', 'bgmg' ),
        'priority'    => 30,
    ] );

    // ─── Sección Hero ───────────────────────────────────────────
    $wp_customize->add_section( 'bgmg_hero', [
        'title'       => __( 'Hero slider del landing', 'bgmg' ),
        'description' => __( '3 slides rotativos en la parte superior del landing. Sube imágenes desktop (1920×720) y mobile (800×1000) para cada uno. Si dejas vacío, se usa el gradiente / contenido de fallback.', 'bgmg' ),
        'panel'       => 'bgmg_tema',
        'priority'    => 10,
    ] );

    // Defaults por slide (replican el contenido actual del hero
    // para que sin configurar nada el sitio se vea igual que hoy)
    $defaults = bgmg_customizer_hero_defaults();

    for ( $i = 1; $i <= 3; $i++ ) {

        // ─── Encabezado tipo "Slide N" (texto informativo) ──
        $wp_customize->add_setting( "bgmg_slide_{$i}_heading", [
            'default'           => '',
            'sanitize_callback' => '__return_empty_string',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( new BGMG_Customize_Heading_Control(
            $wp_customize,
            "bgmg_slide_{$i}_heading",
            [
                'section' => 'bgmg_hero',
                'label'   => sprintf( __( '▸ Slide %d', 'bgmg' ), $i ),
            ]
        ) );

        // ─── Activar este slide ─────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_enabled", [
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_enabled", [
            'label'   => __( 'Mostrar este slide', 'bgmg' ),
            'section' => 'bgmg_hero',
            'type'    => 'checkbox',
        ] );

        // ─── Imagen DESKTOP ─────────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_image_desktop", [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "bgmg_slide_{$i}_image_desktop", [
            'label'       => __( 'Imagen desktop', 'bgmg' ),
            'description' => __( '1920×720 px. Producto / foco visual a la DERECHA (el texto va a la izquierda).', 'bgmg' ),
            'section'     => 'bgmg_hero',
        ] ) );

        // ─── Imagen MOBILE ──────────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_image_mobile", [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, "bgmg_slide_{$i}_image_mobile", [
            'label'       => __( 'Imagen mobile (opcional)', 'bgmg' ),
            'description' => __( '800×1000 px (vertical 4:5). Foco visual CENTRADO. Si lo dejás vacío, se usa la desktop también en mobile.', 'bgmg' ),
            'section'     => 'bgmg_hero',
        ] ) );

        // ─── Posición focal ─────────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_focus", [
            'default'           => 'center',
            'sanitize_callback' => 'bgmg_sanitize_focus',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_focus", [
            'label'   => __( 'Posición focal de la imagen', 'bgmg' ),
            'section' => 'bgmg_hero',
            'type'    => 'select',
            'choices' => [
                'center top'    => __( 'Centro · Arriba', 'bgmg' ),
                'center'        => __( 'Centro', 'bgmg' ),
                'center bottom' => __( 'Centro · Abajo', 'bgmg' ),
                'left'          => __( 'Izquierda', 'bgmg' ),
                'right'         => __( 'Derecha', 'bgmg' ),
            ],
        ] );

        // ─── Overlay oscuro ─────────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_overlay", [
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_overlay", [
            'label'       => __( 'Aplicar velo oscuro sobre la imagen', 'bgmg' ),
            'description' => __( 'Mejora la legibilidad del texto si la imagen es muy clara o con mucho detalle en el lado del texto.', 'bgmg' ),
            'section'     => 'bgmg_hero',
            'type'        => 'checkbox',
        ] );

        // ─── Badge (etiqueta flotante arriba-derecha) ───────
        $wp_customize->add_setting( "bgmg_slide_{$i}_badge", [
            'default'           => $defaults[ $i ]['badge'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_badge", [
            'label'       => __( 'Badge flotante (opcional)', 'bgmg' ),
            'description' => __( 'Etiqueta pequeña arriba-derecha. Ej: "+500 clientas felices ✨". Vacío = no se muestra.', 'bgmg' ),
            'section'     => 'bgmg_hero',
            'type'        => 'text',
        ] );

        // ─── Label (sobre el título) ────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_label", [
            'default'           => $defaults[ $i ]['label'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_label", [
            'label'       => __( 'Etiqueta (sobre el título)', 'bgmg' ),
            'description' => __( 'Texto corto en mayúsculas, ej: "NUEVA COLECCIÓN".', 'bgmg' ),
            'section'     => 'bgmg_hero',
            'type'        => 'text',
        ] );

        // ─── Título ─────────────────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_title", [
            'default'           => $defaults[ $i ]['title'],
            'sanitize_callback' => 'wp_kses_post',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_title", [
            'label'       => __( 'Título principal', 'bgmg' ),
            'description' => __( 'Acepta <br> para saltos de línea y <em>texto</em> para resaltar en rosa.', 'bgmg' ),
            'section'     => 'bgmg_hero',
            'type'        => 'textarea',
        ] );

        // ─── Subtítulo ──────────────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_subtitle", [
            'default'           => $defaults[ $i ]['subtitle'],
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_subtitle", [
            'label'   => __( 'Subtítulo / descripción', 'bgmg' ),
            'section' => 'bgmg_hero',
            'type'    => 'textarea',
        ] );

        // ─── Pills (3 chips de feature) ─────────────────────
        for ( $p = 1; $p <= 3; $p++ ) {
            $wp_customize->add_setting( "bgmg_slide_{$i}_pill_{$p}", [
                'default'           => $defaults[ $i ][ "pill_{$p}" ],
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'refresh',
            ] );
            $wp_customize->add_control( "bgmg_slide_{$i}_pill_{$p}", [
                'label'       => sprintf( __( 'Pill %d', 'bgmg' ), $p ),
                'description' => $p === 1 ? __( 'Etiquetas pequeñas debajo del subtítulo. Vacío = no se muestra.', 'bgmg' ) : '',
                'section'     => 'bgmg_hero',
                'type'        => 'text',
            ] );
        }

        // ─── CTA texto + URL ────────────────────────────────
        $wp_customize->add_setting( "bgmg_slide_{$i}_cta_text", [
            'default'           => $defaults[ $i ]['cta_text'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_cta_text", [
            'label'   => __( 'Texto del botón CTA', 'bgmg' ),
            'section' => 'bgmg_hero',
            'type'    => 'text',
        ] );

        $wp_customize->add_setting( "bgmg_slide_{$i}_cta_url", [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( "bgmg_slide_{$i}_cta_url", [
            'label'       => __( 'URL del botón CTA', 'bgmg' ),
            'description' => __( 'Vacío = enlaza a la página de tienda.', 'bgmg' ),
            'section'     => 'bgmg_hero',
            'type'        => 'url',
        ] );
    }
}

// ─── Sección Banner Mid-Page (promocional entre secciones) ────────────────
add_action( 'customize_register', 'bgmg_customizer_register_midbanner', 20 );
function bgmg_customizer_register_midbanner( $wp_customize ) {

    $wp_customize->add_section( 'bgmg_midbanner', [
        'title'       => __( 'Banner promocional (mid-page)', 'bgmg' ),
        'description' => __( 'Banner que aparece entre las secciones del landing (entre Novedades y Ofertas). Útil para promos, descuentos, fechas especiales.', 'bgmg' ),
        'panel'       => 'bgmg_tema',
        'priority'    => 20,
    ] );

    $defaults = bgmg_customizer_midbanner_defaults();

    // Mostrar banner
    $wp_customize->add_setting( 'bgmg_midbanner_enabled', [
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_enabled', [
        'label'   => __( 'Mostrar el banner promocional', 'bgmg' ),
        'section' => 'bgmg_midbanner',
        'type'    => 'checkbox',
    ] );

    // Estilo: dark (gradiente) o image
    $wp_customize->add_setting( 'bgmg_midbanner_style', [
        'default'           => 'dark',
        'sanitize_callback' => 'bgmg_sanitize_midbanner_style',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_style', [
        'label'       => __( 'Estilo de fondo', 'bgmg' ),
        'description' => __( 'Dark = gradiente oscuro elegante (por defecto). Imagen = usa la imagen que subas abajo.', 'bgmg' ),
        'section'     => 'bgmg_midbanner',
        'type'        => 'select',
        'choices'     => [
            'dark'  => __( 'Fondo dark (gradiente)', 'bgmg' ),
            'image' => __( 'Fondo con imagen', 'bgmg' ),
        ],
    ] );

    // Imagen desktop
    $wp_customize->add_setting( 'bgmg_midbanner_image_desktop', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'bgmg_midbanner_image_desktop', [
        'label'       => __( 'Imagen desktop', 'bgmg' ),
        'description' => __( '1920×400 px. Solo se usa si seleccionás "Fondo con imagen" arriba.', 'bgmg' ),
        'section'     => 'bgmg_midbanner',
    ] ) );

    // Imagen mobile
    $wp_customize->add_setting( 'bgmg_midbanner_image_mobile', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'bgmg_midbanner_image_mobile', [
        'label'       => __( 'Imagen mobile (opcional)', 'bgmg' ),
        'description' => __( '800×800 px (cuadrada). Si vacía, se usa la desktop también en mobile.', 'bgmg' ),
        'section'     => 'bgmg_midbanner',
    ] ) );

    // Posición focal
    $wp_customize->add_setting( 'bgmg_midbanner_focus', [
        'default'           => 'center',
        'sanitize_callback' => 'bgmg_sanitize_focus',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_focus', [
        'label'   => __( 'Posición focal', 'bgmg' ),
        'section' => 'bgmg_midbanner',
        'type'    => 'select',
        'choices' => [
            'center top'    => __( 'Centro · Arriba', 'bgmg' ),
            'center'        => __( 'Centro', 'bgmg' ),
            'center bottom' => __( 'Centro · Abajo', 'bgmg' ),
            'left'          => __( 'Izquierda', 'bgmg' ),
            'right'         => __( 'Derecha', 'bgmg' ),
        ],
    ] );

    // Overlay
    $wp_customize->add_setting( 'bgmg_midbanner_overlay', [
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_overlay', [
        'label'       => __( 'Velo oscuro sobre la imagen', 'bgmg' ),
        'description' => __( 'Recomendado dejarlo activado para que el texto se lea sobre cualquier imagen.', 'bgmg' ),
        'section'     => 'bgmg_midbanner',
        'type'        => 'checkbox',
    ] );

    // Título
    $wp_customize->add_setting( 'bgmg_midbanner_title', [
        'default'           => $defaults['title'],
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_title', [
        'label'       => __( 'Título', 'bgmg' ),
        'description' => __( 'Acepta <br> y <em>texto</em> para resaltar.', 'bgmg' ),
        'section'     => 'bgmg_midbanner',
        'type'        => 'textarea',
    ] );

    // Subtítulo
    $wp_customize->add_setting( 'bgmg_midbanner_subtitle', [
        'default'           => $defaults['subtitle'],
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_subtitle', [
        'label'   => __( 'Subtítulo', 'bgmg' ),
        'section' => 'bgmg_midbanner',
        'type'    => 'text',
    ] );

    // CTA texto
    $wp_customize->add_setting( 'bgmg_midbanner_cta_text', [
        'default'           => $defaults['cta_text'],
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_cta_text', [
        'label'   => __( 'Texto del botón', 'bgmg' ),
        'section' => 'bgmg_midbanner',
        'type'    => 'text',
    ] );

    // CTA url
    $wp_customize->add_setting( 'bgmg_midbanner_cta_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'bgmg_midbanner_cta_url', [
        'label'       => __( 'URL del botón', 'bgmg' ),
        'description' => __( 'Vacío = enlaza a la página de tienda.', 'bgmg' ),
        'section'     => 'bgmg_midbanner',
        'type'        => 'url',
    ] );
}

/**
 * Defaults del mid-banner (replican el contenido hardcoded actual).
 */
function bgmg_customizer_midbanner_defaults() {
    return [
        'title'    => '¿Compras al por mayor?<br>Aprovecha nuestros <em>precios mayoristas</em>',
        'subtitle' => 'Mientras más llevas, mejor precio · Arma tu surtido',
        'cta_text' => 'Ver la tienda 🛍️',
    ];
}

/**
 * Sanitiza el select de estilo del mid-banner.
 */
function bgmg_sanitize_midbanner_style( $value ) {
    return in_array( $value, [ 'dark', 'image' ], true ) ? $value : 'dark';
}

/**
 * Defaults que replican el contenido hardcoded actual del hero.
 * Si el admin no toca el Customizer, el sitio se ve igual que hoy.
 */
function bgmg_customizer_hero_defaults() {
    return [
        1 => [
            'badge'    => '+500 clientas felices ✨',
            'label'    => 'Belleza natural · Chile',
            'title'    => 'Tu rutina de<br><em>belleza natural</em><br>empieza aquí',
            'subtitle' => 'Productos 100% naturales seleccionados para ti. Envío gratis sobre $25.000 en todo Chile.',
            'pill_1'   => '🌿 Natural',
            'pill_2'   => '🚚 Envío rápido',
            'pill_3'   => '💳 Pago seguro',
            'cta_text' => 'Ver productos →',
        ],
        2 => [
            'badge'    => '',
            'label'    => 'Nueva colección',
            'title'    => 'Skincare que<br><em>transforma</em><br>tu piel',
            'subtitle' => 'Activos botánicos cuidadosamente seleccionados para una rutina que realmente funciona.',
            'pill_1'   => '🧴 Activos botánicos',
            'pill_2'   => '✨ Resultados visibles',
            'pill_3'   => '',
            'cta_text' => 'Ver colección →',
        ],
        3 => [
            'badge'    => '',
            'label'    => 'Envío a todo Chile',
            'title'    => 'Belleza en<br><em>tu puerta,</em><br>sin costo',
            'subtitle' => 'Envío gratis en compras sobre $25.000. Recíbelo en 2 a 5 días hábiles donde estés.',
            'pill_1'   => '🚚 Despacho gratis',
            'pill_2'   => '📦 Embalaje cuidado',
            'pill_3'   => '',
            'cta_text' => 'Comprar ahora →',
        ],
    ];
}

/**
 * Sanitización para el select de posición focal.
 */
function bgmg_sanitize_focus( $value ) {
    $valid = [ 'center', 'center top', 'center bottom', 'left', 'right' ];
    return in_array( $value, $valid, true ) ? $value : 'center';
}

/**
 * Devuelve la URL pública del shop (fallback CTA cuando no hay URL custom).
 */
function bgmg_customizer_default_cta_url() {
    return function_exists( 'wc_get_page_id' )
        ? esc_url( get_permalink( wc_get_page_id( 'shop' ) ) )
        : esc_url( home_url( '/' ) );
}

/**
 * Control custom: muestra un encabezado decorativo "▸ Slide N" sin input.
 * Sirve para separar visualmente la sección de cada slide en el panel.
 */
add_action( 'customize_register', function() {
    if ( class_exists( 'BGMG_Customize_Heading_Control' ) ) return;

    class BGMG_Customize_Heading_Control extends WP_Customize_Control {
        public $type = 'bgmg_heading';

        public function render_content() {
            ?>
            <h3 style="margin:24px 0 8px;padding:8px 12px;background:#FBF0F2;border-left:3px solid #C4728A;font-size:13px;font-weight:600;color:#1A1015;border-radius:3px;">
                <?php echo esc_html( $this->label ); ?>
            </h3>
            <?php
        }
    }
}, 0 );
