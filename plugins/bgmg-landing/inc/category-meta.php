<?php
/**
 * =========================================================
 * MÓDULO: META DE CATEGORÍAS (banner header)
 *
 * Agrega 3 campos custom al editor de cada categoría de
 * producto (wp-admin → Productos → Categorías):
 *
 *   - bgm_cat_banner_id        attachment ID de la imagen header
 *   - bgm_cat_banner_focus     posición focal CSS (default: center)
 *   - bgm_cat_banner_overlay   velo oscuro 'on'/'' (default: on)
 *
 * Estos campos los lee bgmg-category.php para renderizar un
 * banner de header en cada página de categoría. Si no se
 * configura ninguna imagen, no se muestra banner (la página
 * mantiene el layout actual).
 *
 * Hooks de admin: product_cat_add_form_fields y
 * product_cat_edit_form_fields para mostrar los campos;
 * created_product_cat y edited_product_cat para guardarlos.
 * =========================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Campos en el formulario "Añadir nueva categoría" ────────────────────────
add_action( 'product_cat_add_form_fields', 'bgmg_cat_meta_add_fields' );
function bgmg_cat_meta_add_fields() {
    ?>
    <div class="form-field">
        <label for="bgm_cat_banner_id"><?php esc_html_e( 'Banner header (BGMG)', 'bgmg' ); ?></label>
        <input type="hidden" id="bgm_cat_banner_id" name="bgm_cat_banner_id" value="" />
        <div class="bgmg-cat-banner-preview" style="margin-bottom:8px;"></div>
        <button type="button" class="button bgmg-cat-banner-upload"><?php esc_html_e( 'Subir / elegir imagen', 'bgmg' ); ?></button>
        <button type="button" class="button bgmg-cat-banner-remove" style="display:none;"><?php esc_html_e( 'Quitar', 'bgmg' ); ?></button>
        <p class="description"><?php esc_html_e( 'Imagen header de esta categoría. Recomendado: 1920×300 px (desktop). Si no se sube, no se muestra banner.', 'bgmg' ); ?></p>
    </div>
    <div class="form-field">
        <label for="bgm_cat_banner_focus"><?php esc_html_e( 'Posición focal', 'bgmg' ); ?></label>
        <select id="bgm_cat_banner_focus" name="bgm_cat_banner_focus">
            <option value="center"><?php esc_html_e( 'Centro', 'bgmg' ); ?></option>
            <option value="center top"><?php esc_html_e( 'Centro · Arriba', 'bgmg' ); ?></option>
            <option value="center bottom"><?php esc_html_e( 'Centro · Abajo', 'bgmg' ); ?></option>
            <option value="left"><?php esc_html_e( 'Izquierda', 'bgmg' ); ?></option>
            <option value="right"><?php esc_html_e( 'Derecha', 'bgmg' ); ?></option>
        </select>
    </div>
    <div class="form-field">
        <label>
            <input type="checkbox" id="bgm_cat_banner_overlay" name="bgm_cat_banner_overlay" value="1" checked="checked" />
            <?php esc_html_e( 'Aplicar velo oscuro sobre la imagen', 'bgmg' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Mejora la legibilidad del título sobre la imagen.', 'bgmg' ); ?></p>
    </div>
    <?php
}

// ─── Campos en el formulario "Editar categoría" ──────────────────────────────
add_action( 'product_cat_edit_form_fields', 'bgmg_cat_meta_edit_fields', 10, 2 );
function bgmg_cat_meta_edit_fields( $term, $taxonomy ) {
    $banner_id   = (int) get_term_meta( $term->term_id, 'bgm_cat_banner_id', true );
    $focus       = get_term_meta( $term->term_id, 'bgm_cat_banner_focus', true );
    $overlay     = get_term_meta( $term->term_id, 'bgm_cat_banner_overlay', true );
    $preview_url = $banner_id ? wp_get_attachment_image_url( $banner_id, 'medium' ) : '';

    if ( $focus === '' )   $focus   = 'center';
    if ( $overlay === '' ) $overlay = '1'; // por defecto activo

    $focus_choices = [
        'center'        => __( 'Centro', 'bgmg' ),
        'center top'    => __( 'Centro · Arriba', 'bgmg' ),
        'center bottom' => __( 'Centro · Abajo', 'bgmg' ),
        'left'          => __( 'Izquierda', 'bgmg' ),
        'right'         => __( 'Derecha', 'bgmg' ),
    ];
    ?>
    <tr class="form-field">
        <th scope="row"><label for="bgm_cat_banner_id"><?php esc_html_e( 'Banner header (BGMG)', 'bgmg' ); ?></label></th>
        <td>
            <input type="hidden" id="bgm_cat_banner_id" name="bgm_cat_banner_id" value="<?php echo esc_attr( $banner_id ); ?>" />
            <div class="bgmg-cat-banner-preview" style="margin-bottom:8px;">
                <?php if ( $preview_url ) : ?>
                    <img src="<?php echo esc_url( $preview_url ); ?>" style="max-width:300px;height:auto;border:1px solid #ddd;border-radius:6px;display:block;" />
                <?php endif; ?>
            </div>
            <button type="button" class="button bgmg-cat-banner-upload"><?php echo $banner_id ? esc_html__( 'Cambiar imagen', 'bgmg' ) : esc_html__( 'Subir / elegir imagen', 'bgmg' ); ?></button>
            <button type="button" class="button bgmg-cat-banner-remove" style="<?php echo $banner_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Quitar', 'bgmg' ); ?></button>
            <p class="description"><?php esc_html_e( 'Imagen header de esta categoría. Recomendado: 1920×300 px (desktop). Si no se sube, no se muestra banner.', 'bgmg' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="bgm_cat_banner_focus"><?php esc_html_e( 'Posición focal', 'bgmg' ); ?></label></th>
        <td>
            <select id="bgm_cat_banner_focus" name="bgm_cat_banner_focus">
                <?php foreach ( $focus_choices as $val => $label ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $focus, $val ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><?php esc_html_e( 'Velo oscuro', 'bgmg' ); ?></th>
        <td>
            <label>
                <input type="checkbox" id="bgm_cat_banner_overlay" name="bgm_cat_banner_overlay" value="1" <?php checked( $overlay, '1' ); ?> />
                <?php esc_html_e( 'Aplicar velo oscuro sobre la imagen', 'bgmg' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'Mejora la legibilidad del título sobre la imagen.', 'bgmg' ); ?></p>
        </td>
    </tr>
    <?php
}

// ─── Guardar los campos al crear / editar categoría ──────────────────────────
add_action( 'created_product_cat', 'bgmg_cat_meta_save' );
add_action( 'edited_product_cat',  'bgmg_cat_meta_save' );
function bgmg_cat_meta_save( $term_id ) {
    // Defensa en profundidad: solo usuarios que pueden gestionar términos
    // de productos (admin/shop_manager). WP ya hace su propio nonce-check
    // en el form admin de términos, pero el hook puede dispararse desde
    // otros contextos sin pasar por ese form.
    if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    // Imagen
    if ( isset( $_POST['bgm_cat_banner_id'] ) ) {
        $id = absint( wp_unslash( $_POST['bgm_cat_banner_id'] ) );
        if ( $id > 0 ) {
            update_term_meta( $term_id, 'bgm_cat_banner_id', $id );
        } else {
            delete_term_meta( $term_id, 'bgm_cat_banner_id' );
        }
    }
    // Posición focal (sanitize + whitelist)
    if ( isset( $_POST['bgm_cat_banner_focus'] ) ) {
        $valid = [ 'center', 'center top', 'center bottom', 'left', 'right' ];
        $raw   = sanitize_text_field( wp_unslash( $_POST['bgm_cat_banner_focus'] ) );
        $focus = in_array( $raw, $valid, true ) ? $raw : 'center';
        update_term_meta( $term_id, 'bgm_cat_banner_focus', $focus );
    }
    // Overlay (checkbox: si no se envía, está desmarcado)
    $overlay_on = ! empty( $_POST['bgm_cat_banner_overlay'] ) ? '1' : '';
    update_term_meta( $term_id, 'bgm_cat_banner_overlay', $overlay_on );
}

// ─── JS del Media Uploader para el botón "Subir imagen" ──────────────────────
add_action( 'admin_footer-edit-tags.php', 'bgmg_cat_meta_js' );
add_action( 'admin_footer-term.php',      'bgmg_cat_meta_js' );
function bgmg_cat_meta_js() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->taxonomy !== 'product_cat' ) return;

    wp_enqueue_media();
    ?>
    <script>
    (function($){
        var frame;
        $(document).on('click', '.bgmg-cat-banner-upload', function(e){
            e.preventDefault();
            var $btn     = $(this);
            var $field   = $btn.siblings('input[type=hidden]').first().length
                ? $btn.siblings('input[type=hidden]').first()
                : $('#bgm_cat_banner_id');
            var $preview = $btn.closest('.form-field, td').find('.bgmg-cat-banner-preview');
            var $remove  = $btn.siblings('.bgmg-cat-banner-remove');

            if (frame) { frame.open(); return; }
            frame = wp.media({
                title:  '<?php echo esc_js( __( 'Elige una imagen', 'bgmg' ) ); ?>',
                button: { text: '<?php echo esc_js( __( 'Usar esta imagen', 'bgmg' ) ); ?>' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $field.val(attachment.id);
                var url = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
                $preview.html('<img src="'+url+'" style="max-width:300px;height:auto;border:1px solid #ddd;border-radius:6px;display:block;">');
                $remove.show();
                $btn.text('<?php echo esc_js( __( 'Cambiar imagen', 'bgmg' ) ); ?>');
            });
            frame.open();
        });
        $(document).on('click', '.bgmg-cat-banner-remove', function(e){
            e.preventDefault();
            var $btn = $(this);
            var $field = $btn.siblings('input[type=hidden]').first().length
                ? $btn.siblings('input[type=hidden]').first()
                : $('#bgm_cat_banner_id');
            $field.val('');
            $btn.closest('.form-field, td').find('.bgmg-cat-banner-preview').empty();
            $btn.hide();
            $btn.siblings('.bgmg-cat-banner-upload').text('<?php echo esc_js( __( 'Subir / elegir imagen', 'bgmg' ) ); ?>');
        });
    })(jQuery);
    </script>
    <?php
}

// ─── Helper público: devuelve los datos del banner de un término ─────────────
/**
 * @param int|WP_Term $term  ID del término o objeto WP_Term
 * @return array|null  ['url_desktop','url_mobile','focus','overlay'] o null si no hay banner
 */
function bgmg_get_cat_banner( $term ) {
    $term_id = is_object( $term ) ? (int) $term->term_id : (int) $term;
    if ( ! $term_id ) return null;

    $att_id = (int) get_term_meta( $term_id, 'bgm_cat_banner_id', true );
    if ( ! $att_id ) return null;

    $url_full = wp_get_attachment_image_url( $att_id, 'full' );
    if ( ! $url_full ) return null;

    // Para mobile, intentamos el tamaño 'large' (suele ser ~1024px), si no
    // existe usamos la full. WP genera estos tamaños automáticamente.
    $url_mobile = wp_get_attachment_image_url( $att_id, 'large' ) ?: $url_full;

    $focus   = get_term_meta( $term_id, 'bgm_cat_banner_focus', true );
    $overlay = get_term_meta( $term_id, 'bgm_cat_banner_overlay', true );

    return [
        'url_desktop' => $url_full,
        'url_mobile'  => $url_mobile,
        'focus'       => $focus !== '' ? $focus : 'center',
        'overlay'     => $overlay === '1',
    ];
}
