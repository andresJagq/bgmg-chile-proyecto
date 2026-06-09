<?php
/**
 * =========================================================
 * MÓDULO: ORGANIZADOR DE CATEGORÍAS (árbol arrastrable)
 *
 * Una pantalla de admin (Productos → Organizar categorías) con un
 * árbol drag & drop para decidir, en un solo lugar:
 *   - quién es categoría PADRE y quién es HIJA (jerarquía),
 *   - en qué ORDEN aparecen, y
 *   - si se MUESTRA u OCULTA en la navegación del sitio.
 *
 * Modelo de datos (todo nativo de WP/WC, sin tablas nuevas):
 *   - Orden:       term meta 'order'  (la clave que WooCommerce
 *                  traduce desde orderby => 'menu_order').
 *   - Jerarquía:   campo nativo 'parent' del término (wp_update_term).
 *   - Visibilidad: por dispositivo, dos metas opt-in ('1' = oculta):
 *                  'bgm_cat_hide_pc' (megamenú) y 'bgm_cat_hide_mobile' (hoja móvil).
 *
 * El helper bgm_get_nav_cats() centraliza la lectura para que TODAS las
 * superficies del sitio (megamenú, pills, tienda, categoría, carrito)
 * respeten el mismo orden manual y la visibilidad.
 * =========================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
 * 1. HELPER CENTRAL DE NAVEGACIÓN
 *    Una sola fuente de verdad para listar categorías en el front.
 * ============================================================ */

if ( ! function_exists( 'bgm_get_nav_cats' ) ) {
	/**
	 * Devuelve las categorías de producto para la navegación del sitio,
	 * ordenadas por el orden manual (menu_order / term meta 'order') y
	 * excluyendo las ocultas según el contexto ($args['context']: 'pc'|'mobile'|'any').
	 *
	 * @param int|null $parent term_id del padre (0 = nivel superior, null = todos los niveles).
	 * @param array    $args   overrides para get_terms (number, exclude, hide_empty, …).
	 * @return WP_Term[]       array de términos (nunca WP_Error).
	 */
	function bgm_get_nav_cats( $parent = 0, $args = array() ) {
		// Contexto de navegación: 'pc' (megamenú), 'mobile' (hoja móvil) o 'any'
		// (vitrinas compartidas: muestra salvo que esté oculta en AMBOS). Default 'any'.
		$context = isset( $args['context'] ) ? $args['context'] : 'any';
		unset( $args['context'] );

		$defaults = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => ( null === $parent ) ? '' : (int) $parent, // '' = sin filtro de nivel
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		);
		$args = wp_parse_args( $args, $defaults );

		// Excluir "uncategorized" salvo que el caller ya defina su propio exclude.
		if ( ! isset( $args['exclude'] ) ) {
			$default_cat = (int) get_option( 'default_product_cat' );
			if ( $default_cat ) {
				$args['exclude'] = array( $default_cat );
			}
		}

		// Excluir las ocultas según el contexto (metas opt-in: solo existen al ocultar).
		$hide_clause = bgm_cat_visibility_clause( $context );
		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = $hide_clause;
		} else {
			$args['meta_query'] = array( 'relation' => 'AND', $args['meta_query'], $hide_clause );
		}

		$terms = get_terms( $args );
		return is_wp_error( $terms ) ? array() : $terms;
	}
}

if ( ! function_exists( 'bgm_cat_visibility_clause' ) ) {
	/**
	 * Devuelve la cláusula meta_query que filtra categorías ocultas según contexto.
	 *
	 *   - 'pc'     → visible si bgm_cat_hide_pc     != '1'
	 *   - 'mobile' → visible si bgm_cat_hide_mobile != '1'
	 *   - 'any'    → visible si NO está oculta en AMBOS (pc O móvil visible)
	 *
	 * @param string $context 'pc' | 'mobile' | 'any'
	 * @return array meta_query clause
	 */
	function bgm_cat_visibility_clause( $context ) {
		$visible = function ( $key ) {
			// El meta es opt-in (solo existe al ocultar): visible = no existe o != '1'.
			return array(
				'relation' => 'OR',
				array( 'key' => $key, 'compare' => 'NOT EXISTS' ),
				array( 'key' => $key, 'value' => '1', 'compare' => '!=' ),
			);
		};

		if ( 'pc' === $context ) {
			return $visible( 'bgm_cat_hide_pc' );
		}
		if ( 'mobile' === $context ) {
			return $visible( 'bgm_cat_hide_mobile' );
		}
		// 'any': basta con que sea visible en PC O en móvil.
		return array(
			'relation' => 'OR',
			$visible( 'bgm_cat_hide_pc' ),
			$visible( 'bgm_cat_hide_mobile' ),
		);
	}
}

/* ============================================================
 * 2. PÁGINA DE ADMIN — registro del submenú
 * ============================================================ */

add_action( 'admin_menu', 'bgm_cat_organizer_menu' );
function bgm_cat_organizer_menu() {
	add_submenu_page(
		'edit.php?post_type=product',
		__( 'Organizar categorías', 'bgmg' ),
		__( 'Organizar categorías', 'bgmg' ),
		'manage_product_terms',
		'bgmg-cat-organizer',
		'bgm_cat_organizer_render_page'
	);
}

/* ============================================================
 * 3. ASSETS — solo en la pantalla del organizador
 * ============================================================ */

add_action( 'admin_enqueue_scripts', 'bgm_cat_organizer_assets' );
function bgm_cat_organizer_assets( $hook ) {
	if ( 'product_page_bgmg-cat-organizer' !== $hook ) {
		return;
	}

	$assets_url = plugins_url( 'assets/', dirname( __DIR__ ) . '/bgmg-landing.php' );

	wp_enqueue_style(
		'bgm-cat-organizer',
		$assets_url . 'category-organizer.css',
		array(),
		BGMG_LANDING_VERSION
	);
	wp_enqueue_script(
		'bgm-cat-organizer',
		$assets_url . 'category-organizer.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		BGMG_LANDING_VERSION,
		true
	);
	wp_localize_script( 'bgm-cat-organizer', 'BGM_CAT_ORG', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'bgm_cat_tree_save' ),
		'i18n'  => array(
			'saving'    => __( 'Guardando…', 'bgmg' ),
			'saved'     => __( '✓ Cambios guardados', 'bgmg' ),
			'error'     => __( 'Hubo un error al guardar. Revisa e intenta de nuevo.', 'bgmg' ),
			'dirty'     => __( 'Tienes cambios sin guardar.', 'bgmg' ),
			'depth'     => __( 'Solo se permiten 2 niveles (padre → hija).', 'bgmg' ),
			'leaveWarn' => __( 'Tienes cambios sin guardar. ¿Salir de todos modos?', 'bgmg' ),
		),
	) );
}

/* ============================================================
 * 4. RENDER DE LA PÁGINA
 * ============================================================ */

/**
 * Pinta un <li> del árbol para un término dado.
 *
 * @param WP_Term $term
 * @param bool    $is_parent  Si es nivel superior (lleva sublista de hijas).
 * @param WP_Term[] $children Hijas a renderizar dentro (solo si $is_parent).
 */
function bgm_cat_org_render_item( $term, $is_parent, $children = array() ) {
	$hide_pc     = get_term_meta( $term->term_id, 'bgm_cat_hide_pc', true ) === '1';
	$hide_mobile = get_term_meta( $term->term_id, 'bgm_cat_hide_mobile', true ) === '1';
	$edit_link   = get_edit_term_link( $term->term_id, 'product_cat' );
	$count       = (int) $term->count;
	?>
	<li class="bgm-cat-item<?php echo $is_parent ? ' is-parent' : ' is-child'; ?>" data-id="<?php echo esc_attr( $term->term_id ); ?>">
		<div class="bgm-cat-row">
			<span class="bgm-cat-handle" title="<?php esc_attr_e( 'Arrastrar', 'bgmg' ); ?>">⋮⋮</span>
			<span class="bgm-cat-name"><?php echo esc_html( $term->name ); ?></span>
			<span class="bgm-cat-count"><?php echo esc_html( sprintf( _n( '%d producto', '%d productos', $count, 'bgmg' ), $count ) ); ?></span>
			<span class="bgm-cat-vis" role="group" aria-label="<?php esc_attr_e( 'Visibilidad en navegación', 'bgmg' ); ?>">
				<label class="bgm-cat-vis-opt" title="<?php esc_attr_e( 'Mostrar en el megamenú de escritorio (PC)', 'bgmg' ); ?>">
					<input type="checkbox" class="bgm-cat-pc-cb" <?php checked( ! $hide_pc ); ?> />
					<span>PC</span>
				</label>
				<label class="bgm-cat-vis-opt" title="<?php esc_attr_e( 'Mostrar en la hoja de categorías de móvil', 'bgmg' ); ?>">
					<input type="checkbox" class="bgm-cat-mobile-cb" <?php checked( ! $hide_mobile ); ?> />
					<span><?php esc_html_e( 'Móvil', 'bgmg' ); ?></span>
				</label>
			</span>
			<?php if ( $edit_link ) : ?>
				<a class="bgm-cat-edit" href="<?php echo esc_url( $edit_link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'editar', 'bgmg' ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( $is_parent ) : ?>
			<ol class="bgm-cat-children">
				<?php foreach ( $children as $child ) : ?>
					<?php bgm_cat_org_render_item( $child, false ); ?>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</li>
	<?php
}

function bgm_cat_organizer_render_page() {
	if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'No tienes permisos para gestionar categorías.', 'bgmg' ) );
	}

	// Todas las categorías, ya en orden manual (menu_order). hide_empty=false:
	// en el organizador se muestran TODAS (incluidas vacías y ocultas) para poder
	// gestionarlas; el filtrado de visibilidad/empty es cosa del front-end.
	$all = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
	) );
	$all = is_wp_error( $all ) ? array() : $all;

	$default_cat = (int) get_option( 'default_product_cat' );

	// Agrupar por padre, saltando "uncategorized".
	$by_parent = array();
	foreach ( $all as $t ) {
		if ( $t->term_id === $default_cat || $t->slug === 'uncategorized' ) {
			continue;
		}
		$by_parent[ (int) $t->parent ][] = $t;
	}

	$parents  = isset( $by_parent[0] ) ? $by_parent[0] : array();
	$rendered = array(); // term_ids ya pintados (para detectar huérfanos/profundidad>2)
	?>
	<div class="wrap bgm-cat-org-wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Organizar categorías', 'bgmg' ); ?></h1>
		<p class="bgm-cat-org-intro">
			<?php esc_html_e( 'Arrastra para reordenar. Suelta una categoría dentro de otra para hacerla "hija"; súbela al nivel superior para hacerla "padre". Los checks deciden dónde se muestra: "PC" = megamenú de escritorio, "Móvil" = hoja de categorías del celular (no borran la categoría). Si destildas ambos, desaparece de toda la navegación. Recuerda Guardar.', 'bgmg' ); ?>
		</p>

		<?php if ( empty( $parents ) ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Aún no hay categorías de producto.', 'bgmg' ); ?></p></div>
		<?php else : ?>

		<div class="bgm-cat-org-toolbar">
			<button type="button" class="button button-primary bgm-cat-save" disabled><?php esc_html_e( 'Guardar cambios', 'bgmg' ); ?></button>
			<span class="bgm-cat-status" aria-live="polite"></span>
		</div>

		<ol class="bgm-cat-tree" id="bgm-cat-tree">
			<?php
			foreach ( $parents as $parent ) {
				$rendered[ $parent->term_id ] = true;
				$children = isset( $by_parent[ $parent->term_id ] ) ? $by_parent[ $parent->term_id ] : array();
				foreach ( $children as $c ) {
					$rendered[ $c->term_id ] = true;
				}
				bgm_cat_org_render_item( $parent, true, $children );
			}

			// Huérfanas / profundidad > 2: cualquier término no pintado se ofrece como
			// padre "sin ubicar" para que se pueda arrastrar a su lugar (nada se pierde).
			$orphans = array();
			foreach ( $all as $t ) {
				if ( $t->term_id === $default_cat || $t->slug === 'uncategorized' ) {
					continue;
				}
				if ( empty( $rendered[ $t->term_id ] ) ) {
					$orphans[] = $t;
				}
			}
			foreach ( $orphans as $o ) {
				bgm_cat_org_render_item( $o, true, array() );
			}
			?>
		</ol>

		<div class="bgm-cat-org-toolbar bottom">
			<button type="button" class="button button-primary bgm-cat-save" disabled><?php esc_html_e( 'Guardar cambios', 'bgmg' ); ?></button>
			<span class="bgm-cat-status" aria-live="polite"></span>
		</div>

		<?php endif; ?>
	</div>
	<?php
}

/* ============================================================
 * 5. AJAX — guardar el árbol completo (orden + jerarquía + visibilidad)
 * ============================================================ */

add_action( 'wp_ajax_bgm_cat_tree_save', 'bgm_cat_tree_save' );
function bgm_cat_tree_save() {
	if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'msg' => __( 'Sin permisos.', 'bgmg' ) ), 403 );
	}
	check_ajax_referer( 'bgm_cat_tree_save', 'nonce' );

	$raw   = isset( $_POST['tree'] ) ? wp_unslash( $_POST['tree'] ) : '';
	$nodes = json_decode( $raw, true );
	if ( ! is_array( $nodes ) || empty( $nodes ) ) {
		wp_send_json_error( array( 'msg' => __( 'Datos inválidos.', 'bgmg' ) ) );
	}

	// Normalizar nodos y validar que cada id sea un término product_cat real.
	$clean = array();
	foreach ( $nodes as $n ) {
		$id = isset( $n['id'] ) ? absint( $n['id'] ) : 0;
		if ( ! $id ) {
			continue;
		}
		$term = get_term( $id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$clean[ $id ] = array(
			'id'          => $id,
			'parent'      => isset( $n['parent'] ) ? absint( $n['parent'] ) : 0,
			'order'       => isset( $n['order'] ) ? (int) $n['order'] : 0,
			'hide_pc'     => ! empty( $n['hide_pc'] ) ? '1' : '',
			'hide_mobile' => ! empty( $n['hide_mobile'] ) ? '1' : '',
		);
	}

	if ( empty( $clean ) ) {
		wp_send_json_error( array( 'msg' => __( 'No se reconoció ninguna categoría válida.', 'bgmg' ) ) );
	}

	// Validar jerarquía: el parent debe ser 0 o un nodo de nivel superior del set
	// (máx. 2 niveles). Esto evita ciclos y profundidad inesperada.
	foreach ( $clean as $node ) {
		$p = $node['parent'];
		if ( 0 === $p ) {
			continue;
		}
		if ( ! isset( $clean[ $p ] ) ) {
			wp_send_json_error( array( 'msg' => __( 'Jerarquía inválida (padre desconocido).', 'bgmg' ) ) );
		}
		if ( 0 !== $clean[ $p ]['parent'] ) {
			wp_send_json_error( array( 'msg' => __( 'Solo se permiten 2 niveles (padre → hija).', 'bgmg' ) ) );
		}
		if ( $p === $node['id'] ) {
			wp_send_json_error( array( 'msg' => __( 'Una categoría no puede ser su propio padre.', 'bgmg' ) ) );
		}
	}

	// Aplicar cambios.
	foreach ( $clean as $node ) {
		$term = get_term( $node['id'], 'product_cat' );

		// Parent (solo si cambió, para no disparar trabajo de más).
		if ( (int) $term->parent !== $node['parent'] ) {
			wp_update_term( $node['id'], 'product_cat', array( 'parent' => $node['parent'] ) );
		}

		// Orden (term meta 'order' = lo que WC lee con orderby 'menu_order').
		update_term_meta( $node['id'], 'order', $node['order'] );

		// Visibilidad por contexto (opt-in: si es visible, borramos el meta).
		foreach ( array( 'bgm_cat_hide_pc' => $node['hide_pc'], 'bgm_cat_hide_mobile' => $node['hide_mobile'] ) as $key => $val ) {
			if ( '1' === $val ) {
				update_term_meta( $node['id'], $key, '1' );
			} else {
				delete_term_meta( $node['id'], $key );
			}
		}
	}

	// Limpiar cachés de términos para que el front refleje los cambios al instante.
	clean_term_cache( array_keys( $clean ), 'product_cat' );

	wp_send_json_success( array( 'count' => count( $clean ) ) );
}
