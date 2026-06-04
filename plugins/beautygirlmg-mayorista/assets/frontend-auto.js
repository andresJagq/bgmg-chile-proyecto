/**
 * BGM Frontend — Modo Auto ("Sorpréndeme")
 */
( function( $ ) {
    'use strict';

    if ( typeof BGM_AUTO === 'undefined' ) return;

    var $bloque = $( '.bgm-bloque-auto' );
    if ( ! $bloque.length ) return;

    var min        = parseInt( $bloque.data( 'min' ), 10 ) || 1;
    var min2       = parseInt( $bloque.data( 'min-2' ), 10 ) || 0; // 0 = sin nivel 2
    var precio1    = parseFloat( $bloque.data( 'precio-aprox' ) ) || 0;
    var precio2    = parseFloat( $bloque.data( 'precio-2' ) );
    if ( isNaN( precio2 ) ) precio2 = precio1;
    var productId   = parseInt( $bloque.data( 'product-id' ), 10 );

    // Precio unitario según el nivel que corresponde a la cantidad (igual que el
    // carrito): desde min_2 aplica el precio de nivel 2 si está configurado.
    function precioUnitario( qty ) {
        return ( min2 > 0 && qty >= min2 ) ? precio2 : precio1;
    }

    function actualizarSubtotal() {
        var qty = parseInt( $bloque.find( '.bgm-qty-input' ).val(), 10 ) || min;
        var subtotal = qty * precioUnitario( qty );
        $bloque.find( '.bgm-subtotal-valor' ).html( formatPrecio( subtotal ) );
    }

    // ─── Botones +/- ─────────────────────────────────────────────────────────
    $bloque.on( 'click', '.bgm-qty-menos', function() {
        var $input = $bloque.find( '.bgm-qty-input' );
        var actual = parseInt( $input.val(), 10 ) || min;
        if ( actual > min ) {
            $input.val( actual - 1 ).trigger( 'change' );
        }
    } );

    $bloque.on( 'click', '.bgm-qty-mas', function() {
        var $input = $bloque.find( '.bgm-qty-input' );
        var actual = parseInt( $input.val(), 10 ) || min;
        $input.val( actual + 1 ).trigger( 'change' );
    } );

    // Mientras escribe: solo refrescar subtotal (sin capear, para que pueda
    // tipear "10" sin que el "1" inicial se convierta en mínimo y rompa la
    // edición). La validación de mínimo se aplica cuando el campo pierde el
    // foco o se dispara change (incluido el trigger de los botones +/-).
    $bloque.on( 'input', '.bgm-qty-input', function() {
        actualizarSubtotal();
    } );

    $bloque.on( 'change blur', '.bgm-qty-input', function() {
        var actual = parseInt( $( this ).val(), 10 ) || min;
        if ( actual < min ) {
            $( this ).val( min );
        }
        actualizarSubtotal();
    } );

    // ─── Click en agregar ────────────────────────────────────────────────────
    $bloque.on( 'click', '.bgm-btn-agregar-auto', function( e ) {
        e.preventDefault();

        var $btn = $( this );
        var $fb  = $bloque.find( '.bgm-feedback' );
        var qty  = parseInt( $bloque.find( '.bgm-qty-input' ).val(), 10 ) || min;

        if ( qty < min ) {
            var msgMin = ( BGM_AUTO.txt_min_qty || 'Mínimo %d unidades' ).replace( '%d', min );
            mostrarFeedback( $fb, 'error', msgMin );
            return;
        }

        $btn.prop( 'disabled', true ).text( BGM_AUTO.txt_adding );
        $fb.empty().removeClass( 'bgm-feedback-error bgm-feedback-exito' );

        $.post( BGM_AUTO.ajax_url, {
            action:     'bgm_agregar_auto',
            nonce:      BGM_AUTO.nonce,
            product_id: productId,
            qty:        qty
        } )
        .done( function( resp ) {
            if ( resp.success ) {
                mostrarFeedback( $fb, 'exito', resp.data.message );

                // Reset cantidad a mínimo para permitir agregar más
                $bloque.find( '.bgm-qty-input' ).val( min );
                actualizarSubtotal();

                $btn.prop( 'disabled', false ).text( $btn.data( 'original' ) || BGM_AUTO.txt_agregar );

                // Refresh fragments + abrir minicart del tema
                if ( typeof window.bgmAfterAddToCart === 'function' ) {
                    window.bgmAfterAddToCart( resp.data );
                } else {
                    $( document.body ).trigger( 'wc_fragment_refresh' );
                }
            } else {
                var msg = ( resp.data && resp.data.message ) ? resp.data.message : BGM_AUTO.txt_error;
                mostrarFeedback( $fb, 'error', msg );
                $btn.prop( 'disabled', false ).text( $btn.data( 'original' ) || BGM_AUTO.txt_agregar );
            }
        } )
        .fail( function() {
            mostrarFeedback( $fb, 'error', BGM_AUTO.txt_error );
            $btn.prop( 'disabled', false ).text( $btn.data( 'original' ) || BGM_AUTO.txt_agregar );
        } );
    } );

    function mostrarFeedback( $fb, tipo, msg ) {
        $fb.removeClass( 'bgm-feedback-error bgm-feedback-exito' )
           .addClass( 'bgm-feedback-' + tipo )
           .text( msg )
           .show();
    }

    function formatPrecio( valor ) {
        if ( isNaN( valor ) || valor < 0 ) valor = 0;
        var decimals    = parseInt( BGM_AUTO.decimals, 10 ) || 0;
        var thousandSep = BGM_AUTO.thousand_sep || '.';
        var decimalSep  = BGM_AUTO.decimal_sep || ',';
        var symbol      = BGM_AUTO.currency_symbol || '$';
        var partes      = valor.toFixed( decimals ).split( '.' );
        partes[0] = partes[0].replace( /\B(?=(\d{3})+(?!\d))/g, thousandSep );
        return symbol + partes.join( decimalSep );
    }

    // Guardar texto original del botón
    $bloque.find( '.bgm-btn-agregar-auto' ).each( function() {
        $( this ).data( 'original', $( this ).text() );
    } );

    // Subtotal inicial
    actualizarSubtotal();

} )( jQuery );
