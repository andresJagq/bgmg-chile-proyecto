<?php
/**
 * =========================================================
 * MÓDULO: RENDERS DE LA PÁGINA "MI CUENTA"
 *
 * Reemplaza por completo el [woocommerce_my_account] shortcode con
 * un layout custom tipo ecommerce moderno (cards + sidebar/tabs).
 *
 * Endpoints cubiertos (override):
 *   '' (dashboard) | orders | view-order | edit-address | edit-account
 *
 * Endpoints NO cubiertos (caen al shortcode original):
 *   downloads | payment-methods | customer-logout (este último redirige)
 *
 * Diseño: mobile-first. En mobile la nav es una tira horizontal con
 * scroll. En desktop ≥768px la nav es sidebar fija a la izquierda.
 *
 * Las funciones de WC nativas siguen vivas (forms, save, hooks) —
 * solo cambia el HTML que las envuelve.
 * =========================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Punto de entrada principal — lo llama bgmg-account.php
 */
function bgmg_account_render() {
    if ( ! is_user_logged_in() ) {
        bgmg_account_render_login();
        return;
    }

    $endpoint = WC()->query->get_current_endpoint();

    // Endpoints no cubiertos → fallback al shortcode original
    $cubiertos = [ '', 'orders', 'view-order', 'edit-address', 'edit-account' ];
    if ( ! in_array( $endpoint, $cubiertos, true ) ) {
        echo do_shortcode( '[woocommerce_my_account]' );
        return;
    }

    echo '<div class="bgmg-acc-layout">';
    bgmg_account_render_nav( $endpoint );

    echo '<div class="bgmg-acc-main">';
    switch ( $endpoint ) {
        case '':
            bgmg_account_render_dashboard();
            break;
        case 'orders':
            bgmg_account_render_orders();
            break;
        case 'view-order':
            $order_id = absint( get_query_var( 'view-order' ) );
            bgmg_account_render_view_order( $order_id );
            break;
        case 'edit-address':
            // WC traduce el slug del subendpoint al idioma del sitio
            // (ej. 'facturacion' en español, 'envio' para shipping). Normalizamos
            // con wc_edit_address_i18n($x, true) que devuelve el slug estándar
            // ('billing'/'shipping') a partir del localizado, así el switch
            // interno del render funciona en cualquier idioma.
            $type_raw = get_query_var( 'edit-address' );
            $type     = $type_raw && function_exists( 'wc_edit_address_i18n' )
                ? wc_edit_address_i18n( $type_raw, true )
                : $type_raw;
            bgmg_account_render_edit_address( $type );
            break;
        case 'edit-account':
            bgmg_account_render_edit_account();
            break;
    }
    echo '</div>'; // /.bgmg-acc-main

    echo '</div>'; // /.bgmg-acc-layout
}

// ─── Pantalla de login (no logueado) ─────────────────────────────────────────
function bgmg_account_render_login() {
    // Recuperar contraseña: render simple, sin tabs.
    $endpoint = function_exists( 'WC' ) && WC()->query ? WC()->query->get_current_endpoint() : '';
    if ( 'lost-password' === $endpoint ) {
        echo '<div class="bgmg-acc-login-wrap">';
        echo '<div class="bgmg-acc-login-card">';
        echo '<h2 class="bgmg-acc-login-title">Recuperar contraseña</h2>';
        echo '<p class="bgmg-acc-login-sub">Te enviamos un email para crear una nueva.</p>';
        echo do_shortcode( '[woocommerce_my_account]' );
        echo '</div>';
        echo '</div>';
        return;
    }

    $register_enabled = ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) );

    // Si el cliente submeteó el form de registro y volvió con error,
    // mantenemos el tab "register" activo para no perder contexto.
    $initial_tab = ( $register_enabled && ! empty( $_POST['register'] ) ) ? 'register' : 'login';

    echo '<div class="bgmg-acc-login-wrap">';
    echo '<div class="bgmg-acc-login-card">';

    if ( $register_enabled ) {
        $is_login    = ( 'login' === $initial_tab );
        $is_register = ( 'register' === $initial_tab );
        echo '<div class="bgmg-acc-auth-tabs" role="tablist">';
        echo '<button type="button" class="bgmg-acc-auth-tab' . ( $is_login ? ' is-active' : '' )
            . '" data-tab="login" role="tab" aria-selected="' . ( $is_login ? 'true' : 'false' ) . '">Iniciar sesión</button>';
        echo '<button type="button" class="bgmg-acc-auth-tab' . ( $is_register ? ' is-active' : '' )
            . '" data-tab="register" role="tab" aria-selected="' . ( $is_register ? 'true' : 'false' ) . '">Crear cuenta</button>';
        echo '</div>';
    } else {
        // Sin registro habilitado: layout original con título simple.
        echo '<h2 class="bgmg-acc-login-title">Inicia sesión</h2>';
        echo '<p class="bgmg-acc-login-sub">Accede a tus pedidos, direcciones y datos.</p>';
    }

    echo '<div class="bgmg-acc-auth-body" data-active-tab="' . esc_attr( $initial_tab ) . '">';
    echo do_shortcode( '[woocommerce_my_account]' );
    echo '</div>';

    echo '</div>'; // card
    echo '</div>'; // wrap

    if ( $register_enabled ) {
        ?>
        <script>
        (function(){
            var body = document.querySelector('.bgmg-acc-auth-body');
            if (!body) return;
            var tabs = document.querySelectorAll('.bgmg-acc-auth-tab');

            function activate(target) {
                body.setAttribute('data-active-tab', target);
                tabs.forEach(function(t){
                    var active = (t.getAttribute('data-tab') === target);
                    t.classList.toggle('is-active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                // Foco automático en el primer input visible para mejorar UX.
                var firstInput = body.querySelector(
                    target === 'login'
                        ? '.u-column1 input[type="text"], .u-column1 input[type="email"]'
                        : '.u-column2 input[type="text"], .u-column2 input[type="email"]'
                );
                if (firstInput) firstInput.focus({ preventScroll: true });
            }

            tabs.forEach(function(t){
                t.addEventListener('click', function(){
                    activate(t.getAttribute('data-tab'));
                });
            });

            // Insertar links de switch al final de cada form.
            var loginCol    = body.querySelector('.u-column1, .col-1');
            var registerCol = body.querySelector('.u-column2, .col-2');
            if (loginCol) {
                var sLogin = document.createElement('p');
                sLogin.className = 'bgmg-acc-auth-switch';
                sLogin.innerHTML = '¿No tienes cuenta? <button type="button" data-auth-switch="register">Crea una</button>';
                loginCol.appendChild(sLogin);
            }
            if (registerCol) {
                var sReg = document.createElement('p');
                sReg.className = 'bgmg-acc-auth-switch';
                sReg.innerHTML = '¿Ya tienes cuenta? <button type="button" data-auth-switch="login">Iniciar sesión</button>';
                registerCol.appendChild(sReg);
            }
            body.querySelectorAll('[data-auth-switch]').forEach(function(btn){
                btn.addEventListener('click', function(){
                    activate(btn.getAttribute('data-auth-switch'));
                });
            });
        })();
        </script>
        <?php
    }
}

// ─── NAVEGACIÓN ──────────────────────────────────────────────────────────────
function bgmg_account_render_nav( $active_endpoint ) {
    $items = [
        [ 'endpoint' => '',             'label' => 'Resumen',     'icon' => 'home' ],
        [ 'endpoint' => 'orders',       'label' => 'Pedidos',     'icon' => 'box' ],
        [ 'endpoint' => 'edit-address', 'label' => 'Direcciones', 'icon' => 'pin' ],
        [ 'endpoint' => 'edit-account', 'label' => 'Datos',       'icon' => 'user' ],
    ];

    echo '<nav class="bgmg-acc-nav" aria-label="Mi cuenta">';
    echo '<ul class="bgmg-acc-nav-list">';

    foreach ( $items as $item ) {
        $active = ( $active_endpoint === $item['endpoint'] ) ||
                  ( $active_endpoint === 'view-order' && $item['endpoint'] === 'orders' );
        $url    = wc_get_account_endpoint_url( $item['endpoint'] );
        $cls    = 'bgmg-acc-nav-link' . ( $active ? ' is-active' : '' );

        echo '<li>';
        echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $cls ) . '">';
        echo bgmg_account_icon( $item['icon'] );
        echo '<span>' . esc_html( $item['label'] ) . '</span>';
        echo '</a>';
        echo '</li>';
    }

    // Logout (separado)
    $logout_url = wc_logout_url( wc_get_page_permalink( 'myaccount' ) );
    echo '<li class="bgmg-acc-nav-logout">';
    echo '<a href="' . esc_url( $logout_url ) . '" class="bgmg-acc-nav-link">';
    echo bgmg_account_icon( 'logout' );
    echo '<span>Cerrar sesión</span>';
    echo '</a>';
    echo '</li>';

    echo '</ul>';
    echo '</nav>';
}

// ─── DASHBOARD ───────────────────────────────────────────────────────────────
function bgmg_account_render_dashboard() {
    $user      = wp_get_current_user();
    $name      = $user->display_name ?: $user->user_login;
    $first     = explode( ' ', trim( $name ) )[0];
    $member_since = date_i18n( 'F Y', strtotime( $user->user_registered ) );

    // Datos
    $orders = wc_get_orders( [
        'customer'    => $user->ID,
        'limit'       => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ] );
    $last_order = ! empty( $orders ) ? $orders[0] : null;

    $total_orders = wc_get_customer_order_count( $user->ID );

    $address_ship = get_user_meta( $user->ID, 'shipping_address_1', true );
    $address_city = get_user_meta( $user->ID, 'shipping_city', true );

    ?>
    <header class="bgmg-acc-hero">
        <h1 class="bgmg-acc-hello">Hola <?php echo esc_html( $first ); ?> <span aria-hidden="true">👋</span></h1>
        <p class="bgmg-acc-hello-sub">Cliente desde <?php echo esc_html( $member_since ); ?></p>
    </header>

    <div class="bgmg-acc-cards-grid">

        <!-- Card 1: Último pedido (ancho doble) -->
        <article class="bgmg-acc-card bgmg-acc-card-feat">
            <div class="bgmg-acc-card-head">
                <span class="bgmg-acc-card-icon"><?php echo bgmg_account_icon( 'box' ); ?></span>
                <h3 class="bgmg-acc-card-title">Último pedido</h3>
            </div>
            <?php if ( $last_order ) :
                $status_slug  = $last_order->get_status();
                $status_label = wc_get_order_status_name( $status_slug );
                $created      = $last_order->get_date_created();
                ?>
                <div class="bgmg-acc-card-body">
                    <div class="bgmg-acc-order-mini">
                        <span class="bgmg-acc-order-num">#<?php echo esc_html( $last_order->get_order_number() ); ?></span>
                        <span class="bgmg-acc-order-status bgmg-status-<?php echo esc_attr( $status_slug ); ?>">
                            <?php echo esc_html( $status_label ); ?>
                        </span>
                    </div>
                    <div class="bgmg-acc-order-meta">
                        <?php if ( $created ) : ?>
                            <span><?php echo esc_html( $created->date_i18n( 'd \d\e F, Y' ) ); ?></span>
                        <?php endif; ?>
                        <span class="bgmg-acc-order-total"><?php echo wp_kses_post( wc_price( $last_order->get_total() ) ); ?></span>
                    </div>
                    <a href="<?php echo esc_url( $last_order->get_view_order_url() ); ?>" class="bgmg-acc-btn bgmg-acc-btn-primary">
                        Ver detalle <span aria-hidden="true">→</span>
                    </a>
                </div>
            <?php else : ?>
                <div class="bgmg-acc-card-body">
                    <p class="bgmg-acc-empty-text">Aún no has hecho ningún pedido.</p>
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-primary">
                        Ver productos <span aria-hidden="true">→</span>
                    </a>
                </div>
            <?php endif; ?>
        </article>

        <!-- Card 2: Estadísticas -->
        <article class="bgmg-acc-card bgmg-acc-card-stat">
            <div class="bgmg-acc-stat-number"><?php echo (int) $total_orders; ?></div>
            <div class="bgmg-acc-stat-label">
                <?php echo $total_orders === 1 ? 'Pedido total' : 'Pedidos en total'; ?>
            </div>
            <?php if ( $total_orders > 0 ) : ?>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="bgmg-acc-card-link">
                    Ver historial →
                </a>
            <?php endif; ?>
        </article>

        <!-- Card 3: Dirección de envío -->
        <article class="bgmg-acc-card">
            <div class="bgmg-acc-card-head">
                <span class="bgmg-acc-card-icon"><?php echo bgmg_account_icon( 'pin' ); ?></span>
                <h3 class="bgmg-acc-card-title">Dirección de envío</h3>
            </div>
            <div class="bgmg-acc-card-body">
                <?php if ( $address_ship ) : ?>
                    <p class="bgmg-acc-card-text">
                        <?php echo esc_html( $address_ship ); ?>
                        <?php if ( $address_city ) : ?><br><?php echo esc_html( $address_city ); ?><?php endif; ?>
                    </p>
                <?php else : ?>
                    <p class="bgmg-acc-empty-text">Aún no agregaste una dirección de envío.</p>
                <?php endif; ?>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-ghost">
                    <?php echo $address_ship ? 'Editar' : 'Agregar dirección'; ?> <span aria-hidden="true">→</span>
                </a>
            </div>
        </article>

        <!-- Card 4: Datos personales -->
        <article class="bgmg-acc-card">
            <div class="bgmg-acc-card-head">
                <span class="bgmg-acc-card-icon"><?php echo bgmg_account_icon( 'user' ); ?></span>
                <h3 class="bgmg-acc-card-title">Tus datos</h3>
            </div>
            <div class="bgmg-acc-card-body">
                <p class="bgmg-acc-card-text">
                    <strong><?php echo esc_html( $name ); ?></strong><br>
                    <span class="bgmg-acc-mid"><?php echo esc_html( $user->user_email ); ?></span>
                </p>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-ghost">
                    Editar perfil <span aria-hidden="true">→</span>
                </a>
            </div>
        </article>

    </div>
    <?php
}

// ─── PEDIDOS (lista) ─────────────────────────────────────────────────────────
function bgmg_account_render_orders() {
    $user_id    = get_current_user_id();
    $page       = max( 1, (int) get_query_var( 'orders' ) );
    $per_page   = 10;

    $customer_orders = wc_get_orders( [
        'customer' => $user_id,
        'page'     => $page,
        'paginate' => true,
        'limit'    => $per_page,
        'orderby'  => 'date',
        'order'    => 'DESC',
    ] );

    ?>
    <header class="bgmg-acc-page-head">
        <h1 class="bgmg-acc-page-title">Mis pedidos</h1>
        <p class="bgmg-acc-page-sub">Tu historial de compras en BeautyGirlMG</p>
    </header>

    <?php if ( empty( $customer_orders->orders ) ) : ?>
        <div class="bgmg-acc-empty">
            <div class="bgmg-acc-empty-icon">📦</div>
            <h3 class="bgmg-acc-empty-title">Sin pedidos todavía</h3>
            <p class="bgmg-acc-empty-text">Cuando hagas tu primera compra, va a aparecer acá.</p>
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-primary">
                Empezar a comprar <span aria-hidden="true">→</span>
            </a>
        </div>
    <?php else : ?>

        <div class="bgmg-acc-orders-list">
        <?php foreach ( $customer_orders->orders as $order ) :
            $status_slug  = $order->get_status();
            $status_label = wc_get_order_status_name( $status_slug );
            $items_count  = $order->get_item_count();
            $created      = $order->get_date_created();
            ?>
            <article class="bgmg-acc-order-card">
                <div class="bgmg-acc-order-card-head">
                    <div>
                        <span class="bgmg-acc-order-num">Pedido #<?php echo esc_html( $order->get_order_number() ); ?></span>
                        <?php if ( $created ) : ?>
                            <span class="bgmg-acc-order-date"><?php echo esc_html( $created->date_i18n( 'd \d\e F, Y' ) ); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="bgmg-acc-order-status bgmg-status-<?php echo esc_attr( $status_slug ); ?>">
                        <?php echo esc_html( $status_label ); ?>
                    </span>
                </div>
                <div class="bgmg-acc-order-card-body">
                    <div class="bgmg-acc-order-summary">
                        <span><?php echo (int) $items_count; ?> <?php echo $items_count === 1 ? 'producto' : 'productos'; ?></span>
                        <strong class="bgmg-acc-order-total"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></strong>
                    </div>
                    <?php
                    // Badge de estado de despacho (bgmg-chile). No imprime nada si la orden no tiene estado seteado.
                    if ( function_exists( 'bgmg_chile_render_estado_box' ) ) {
                        bgmg_chile_render_estado_box( $order );
                    }
                    ?>
                    <div class="bgmg-acc-order-card-actions">
                        <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="bgmg-acc-btn bgmg-acc-btn-primary">
                            Ver detalle <span aria-hidden="true">→</span>
                        </a>
                        <?php if ( in_array( $status_slug, [ 'completed', 'processing' ], true ) ) : ?>
                            <a href="<?php echo esc_url( bgmg_account_repeat_order_url( $order ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-ghost">
                                Repetir compra
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        </div>

        <?php if ( $customer_orders->max_num_pages > 1 ) : ?>
            <nav class="bgmg-acc-pagination">
                <?php if ( $page > 1 ) : ?>
                    <a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $page - 1 ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-ghost">← Anterior</a>
                <?php endif; ?>
                <span class="bgmg-acc-pagination-info">Página <?php echo (int) $page; ?> de <?php echo (int) $customer_orders->max_num_pages; ?></span>
                <?php if ( $page < $customer_orders->max_num_pages ) : ?>
                    <a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $page + 1 ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-ghost">Siguiente →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
    <?php
}

// ─── DETALLE DE PEDIDO ───────────────────────────────────────────────────────
function bgmg_account_render_view_order( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order || ! current_user_can( 'view_order', $order_id ) ) {
        echo '<p>Pedido no encontrado.</p>';
        return;
    }

    $status_slug  = $order->get_status();
    $status_label = wc_get_order_status_name( $status_slug );
    $created      = $order->get_date_created();

    // Timeline: estados del pedido en orden
    $timeline_states = [
        'pending'    => [ 'label' => 'Pendiente',    'icon' => '⏳' ],
        'processing' => [ 'label' => 'Procesando',   'icon' => '📦' ],
        'completed'  => [ 'label' => 'Completado',   'icon' => '✓' ],
    ];
    if ( in_array( $status_slug, [ 'cancelled', 'refunded', 'failed' ], true ) ) {
        $timeline_states = [ $status_slug => [ 'label' => $status_label, 'icon' => '✕' ] ];
    }

    ?>
    <header class="bgmg-acc-page-head">
        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="bgmg-acc-back">← Volver a pedidos</a>
        <h1 class="bgmg-acc-page-title">Pedido #<?php echo esc_html( $order->get_order_number() ); ?></h1>
        <p class="bgmg-acc-page-sub">
            Hecho el <?php echo esc_html( $created ? $created->date_i18n( 'd \d\e F, Y \a \l\a\s H:i' ) : '—' ); ?>
        </p>
    </header>

    <!-- Estado actual destacado -->
    <div class="bgmg-acc-order-status-banner bgmg-status-<?php echo esc_attr( $status_slug ); ?>">
        <strong>Estado:</strong> <?php echo esc_html( $status_label ); ?>
    </div>

    <!-- Items del pedido -->
    <section class="bgmg-acc-section">
        <h2 class="bgmg-acc-section-title">Productos</h2>
        <div class="bgmg-acc-items-list">
            <?php foreach ( $order->get_items() as $item ) :
                $product   = $item->get_product();
                $img_url   = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
                if ( ! $img_url ) $img_url = wc_placeholder_img_src();
                $name      = $item->get_name();
                $qty       = $item->get_quantity();
                $subtotal  = $order->get_line_subtotal( $item );
                $product_url = $product ? get_permalink( $product->get_id() ) : '#';
                ?>
                <div class="bgmg-acc-item">
                    <a href="<?php echo esc_url( $product_url ); ?>" class="bgmg-acc-item-img-link">
                        <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="bgmg-acc-item-img" loading="lazy">
                    </a>
                    <div class="bgmg-acc-item-info">
                        <a href="<?php echo esc_url( $product_url ); ?>" class="bgmg-acc-item-name"><?php echo esc_html( $name ); ?></a>
                        <span class="bgmg-acc-item-qty">x<?php echo (int) $qty; ?></span>
                    </div>
                    <div class="bgmg-acc-item-price"><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Totales -->
    <section class="bgmg-acc-section">
        <h2 class="bgmg-acc-section-title">Resumen</h2>
        <div class="bgmg-acc-totals">
            <?php foreach ( $order->get_order_item_totals() as $key => $row ) : ?>
                <div class="bgmg-acc-total-row<?php echo $key === 'order_total' ? ' is-grand' : ''; ?>">
                    <span><?php echo esc_html( $row['label'] ); ?></span>
                    <span><?php echo wp_kses_post( $row['value'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Dirección de envío -->
    <?php
    // Mostramos solo una dirección. Preferimos la de facturación porque trae
    // el RUT (vía filtro de bgmg-chile). Si no hay billing por algún motivo
    // raro, cae al shipping.
    $direccion_unica = $order->get_formatted_billing_address();
    if ( ! $direccion_unica ) {
        $direccion_unica = $order->get_formatted_shipping_address();
    }
    if ( $direccion_unica ) : ?>
    <section class="bgmg-acc-section">
        <h2 class="bgmg-acc-section-title">Envío</h2>
        <div class="bgmg-acc-addresses-grid bgmg-acc-addresses-grid--single">
            <div class="bgmg-acc-address-card">
                <address><?php echo wp_kses_post( $direccion_unica ); ?></address>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    // Bloque chileno: estado de despacho + tracking + retiro.
    // API pública de bgmg-chile — ver BGMG-CHILE-API-PARA-LANDING.md.
    // Es seguro llamar siempre: si la orden no tiene datos, no imprime nada.
    // 'mostrar_factura' => false: el bloque "Datos para boleta/factura" ya no
    // se muestra al cliente (decisión 2026-05-27). El RUT aparece dentro de
    // la dirección de envío vía woocommerce_order_formatted_billing_address.
    if ( function_exists( 'bgmg_chile_render_order_summary' ) ) : ?>
        <section class="bgmg-acc-section bgmg-acc-section-chile">
            <?php bgmg_chile_render_order_summary( $order, array( 'mostrar_factura' => false ) ); ?>
        </section>
    <?php endif; ?>
    <?php
}

// ─── DIRECCIONES ─────────────────────────────────────────────────────────────
function bgmg_account_render_edit_address( $type = '' ) {
    $user_id = get_current_user_id();

    // Defensa contra llamadas directas con slug localizado (facturacion/envio):
    // normalizamos al slug estándar de WC.
    if ( $type && function_exists( 'wc_edit_address_i18n' ) ) {
        $type = wc_edit_address_i18n( $type, true );
    }

    // Si llega con tipo específico ('billing' o 'shipping'), mostrar form
    if ( $type && in_array( $type, [ 'billing', 'shipping' ], true ) ) {
        echo '<header class="bgmg-acc-page-head">';
        echo '<a href="' . esc_url( wc_get_account_endpoint_url( 'edit-address' ) ) . '" class="bgmg-acc-back">← Volver</a>';
        echo '<h1 class="bgmg-acc-page-title">Editar dirección de envío</h1>';
        echo '</header>';
        // Delegar el form al template nativo de WC (que el filtro no toca)
        wc_get_template( 'myaccount/form-edit-address.php', [
            'load_address' => $type,
            'address'      => WC()->countries->get_address_fields( get_user_meta( $user_id, $type . '_country', true ), $type . '_' ),
        ] );
        return;
    }

    // Listado de las dos direcciones
    $billing  = wc_get_account_formatted_address( 'billing' );
    $shipping = wc_get_account_formatted_address( 'shipping' );

    ?>
    <header class="bgmg-acc-page-head">
        <h1 class="bgmg-acc-page-title">Mi dirección</h1>
        <p class="bgmg-acc-page-sub">Dirección que usamos para enviarte tus pedidos</p>
    </header>

    <div class="bgmg-acc-addresses-grid bgmg-acc-addresses-grid--single">
        <article class="bgmg-acc-card">
            <div class="bgmg-acc-card-head">
                <span class="bgmg-acc-card-icon"><?php echo bgmg_account_icon( 'pin' ); ?></span>
                <h3 class="bgmg-acc-card-title">Envío</h3>
            </div>
            <div class="bgmg-acc-card-body">
                <?php if ( $billing ) : ?>
                    <address class="bgmg-acc-address-text"><?php echo wp_kses_post( $billing ); ?></address>
                <?php else : ?>
                    <p class="bgmg-acc-empty-text">Aún no agregaste tu dirección.</p>
                <?php endif; ?>
                <a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing' ) ); ?>" class="bgmg-acc-btn bgmg-acc-btn-ghost">
                    <?php echo $billing ? 'Editar' : 'Agregar'; ?> <span aria-hidden="true">→</span>
                </a>
            </div>
        </article>
    </div>
    <?php
}

// ─── DATOS PERSONALES ───────────────────────────────────────────────────────
function bgmg_account_render_edit_account() {
    ?>
    <header class="bgmg-acc-page-head">
        <h1 class="bgmg-acc-page-title">Mis datos</h1>
        <p class="bgmg-acc-page-sub">Nombre, email y contraseña</p>
    </header>

    <div class="bgmg-acc-form-wrap">
        <?php
        // El form nativo de WC ya valida y guarda. Solo lo envolvemos.
        wc_get_template( 'myaccount/form-edit-account.php', [
            'user' => get_user_by( 'id', get_current_user_id() ),
        ] );
        ?>
    </div>
    <?php
}

// ─── HELPERS ─────────────────────────────────────────────────────────────────

/**
 * Devuelve URL para "Repetir compra" — agrega todos los items del pedido al carrito.
 */
function bgmg_account_repeat_order_url( $order ) {
    return wp_nonce_url(
        add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ),
        'woocommerce-order_again'
    );
}

/**
 * Devuelve SVG inline de un icono. Sin dependencias externas.
 */
function bgmg_account_icon( $name ) {
    $icons = [
        'home'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'box'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        'pin'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'user'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'logout'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'invoice' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>',
    ];

    if ( ! isset( $icons[ $name ] ) ) return '';
    return '<span class="bgmg-acc-icon" aria-hidden="true">' . $icons[ $name ] . '</span>';
}
