/**
 * BGM Frontend — Lógica común
 *
 * bgmAfterAddToCart() se llama desde modo-auto y modo-manual tras un
 * add_to_cart vía AJAX exitoso.
 *
 * IMPORTANTE: el tema (bgmg-landing/bgmg-product.php) define su propia versión
 * de window.bgmAfterAddToCart para abrir su side-cart e inyectar minicart_html.
 * Por eso aquí usamos una GUARD: solo definimos bgmAfterAddToCart como fallback
 * cuando el tema no la haya definido. Esto evita pisar la integración del tema.
 */
( function( $ ) {
    'use strict';

    /**
     * Heurística para actualizar el contador del carrito en el header
     * de temas que no expongan su propia bgmAfterAddToCart.
     */
    function actualizarContadorHeader( cartCount ) {
        if ( typeof cartCount !== 'number' || cartCount < 0 ) return;

        var selectores = [
            '.bgmg-cart-count',
            '.cart-count',
            '.cart-counter',
            '.cart-contents-count',
            '.header-cart-count',
            '.mini-cart-count',
            '.site-header-cart .count',
            '[data-cart-count]',
            '.menu-item-cart .count',
            'a[href*="/carrito"] .count',
            'a[href*="/cart"] .count'
        ];

        $.each( selectores, function( _, sel ) {
            var $els = $( sel );
            if ( $els.length ) {
                $els.text( cartCount > 0 ? cartCount : '' );
                $els.attr( 'data-count', cartCount );
            }
        } );
    }

    function asegurarContenedorToast() {
        var $existente = $( '#bgm-toast-container' );
        if ( $existente.length ) return $existente;
        var $cont = $( '<div id="bgm-toast-container" aria-live="polite" aria-atomic="true"></div>' );
        $( 'body' ).append( $cont );
        return $cont;
    }

    function mostrarToast( respData ) {
        var $cont = asegurarContenedorToast();
        var t     = ( typeof window.BGM_COMMON === 'object' ) ? window.BGM_COMMON : {};

        var count    = ( respData && typeof respData.cart_count === 'number' ) ? respData.cart_count : 0;
        var cartUrl  = ( respData && respData.cart_url ) ? respData.cart_url : ( t.cart_url_fallback || '/carrito/' );
        var label    = count === 1 ? ( t.txt_producto || 'producto' ) : ( t.txt_productos || 'productos' );
        var titulo   = t.txt_agregado    || '¡Agregado al carrito!';
        var subTxt   = t.txt_en_carrito  || 'en tu carrito';
        var verTxt   = t.txt_ver_carrito || 'Ver carrito';
        var cerrarTx = t.txt_cerrar      || 'Cerrar';

        function escapeHtml( s ) {
            return String( s ).replace( /[&<>"']/g, function( c ) {
                return ( { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' } )[ c ];
            } );
        }

        var $toast = $(
            '<div class="bgm-toast" role="status">' +
                '<div class="bgm-toast-icon" aria-hidden="true">✓</div>' +
                '<div class="bgm-toast-body">' +
                    '<div class="bgm-toast-titulo">' + escapeHtml( titulo ) + '</div>' +
                    '<div class="bgm-toast-sub">' + count + ' ' + escapeHtml( label ) + ' ' + escapeHtml( subTxt ) + '</div>' +
                '</div>' +
                '<a class="bgm-toast-cta" href="' + encodeURI( cartUrl ) + '">' + escapeHtml( verTxt ) + '</a>' +
                '<button type="button" class="bgm-toast-cerrar" aria-label="' + escapeHtml( cerrarTx ) + '">×</button>' +
            '</div>'
        );

        $cont.append( $toast );
        requestAnimationFrame( function() { $toast.addClass( 'is-visible' ); } );

        var timer = setTimeout( cerrar, 5000 );

        function cerrar() {
            clearTimeout( timer );
            $toast.removeClass( 'is-visible' ).addClass( 'is-leaving' );
            setTimeout( function() { $toast.remove(); }, 300 );
        }

        $toast.on( 'click', '.bgm-toast-cerrar', cerrar );
        $toast.on( 'mouseenter', function() { clearTimeout( timer ); } );
        $toast.on( 'mouseleave', function() { timer = setTimeout( cerrar, 3000 ); } );
    }

    /**
     * Fallback genérico: solo se define si el tema NO definió ya bgmAfterAddToCart.
     * El tema BGMG-Landing define su propia versión en bgmg-product.php (y otros
     * templates) que abre su side-cart usando data.minicart_html.
     */
    if ( typeof window.bgmAfterAddToCart !== 'function' ) {
        window.bgmAfterAddToCart = function( respData ) {
            respData = respData || {};

            $( document.body ).trigger( 'wc_fragment_refresh' );
            $( document.body ).trigger( 'added_to_cart', [
                respData.fragments || {},
                respData.cart_hash || '',
                null
            ] );

            if ( typeof respData.cart_count === 'number' ) {
                actualizarContadorHeader( respData.cart_count );
            }

            mostrarToast( respData );
        };
    }

} )( jQuery );
