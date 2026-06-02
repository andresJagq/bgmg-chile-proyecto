/**
 * BGM Admin — preview en vivo del precio y refresh de tabla resumen
 */
( function( $ ) {
    'use strict';

    if ( typeof BGM_ADMIN === 'undefined' ) return;

    // ─── Formato de moneda según ajustes WC ──────────────────────────────────
    function formatPrecio( valor ) {
        if ( isNaN( valor ) || valor < 0 ) valor = 0;

        var decimals    = parseInt( BGM_ADMIN.decimals, 10 ) || 0;
        var thousandSep = BGM_ADMIN.thousand_sep || '.';
        var decimalSep  = BGM_ADMIN.decimal_sep || ',';
        var symbol      = BGM_ADMIN.currency_symbol || '$';

        var partes = valor.toFixed( decimals ).split( '.' );
        partes[0] = partes[0].replace( /\B(?=(\d{3})+(?!\d))/g, thousandSep );

        return symbol + partes.join( decimalSep );
    }

    // ─── Preview de precio para el panel principal ───────────────────────────
    $( document ).on( 'input change', '.bgm-input-descuento', function() {
        var $input      = $( this );
        var tier        = $input.data( 'tier' );
        var descuento   = parseFloat( $input.val() ) || 0;
        var precioBase  = parseFloat( BGM_ADMIN.precio_base ) || 0;
        var $preview    = $( '.bgm-preview-precio[data-tier="' + tier + '"]' );

        if ( precioBase <= 0 ) {
            $preview.html( '' );
            return;
        }

        if ( descuento <= 0 ) {
            $preview.html( '<span class="bgm-preview-vacio">' + BGM_ADMIN.txt_sin_desc + '</span>' );
            return;
        }

        var precioFinal = Math.max( 0, precioBase - descuento );
        $preview.html( '→ ' + BGM_ADMIN.txt_precio_final + ': <strong>' + formatPrecio( precioFinal ) + '</strong>' );

        actualizarTablaResumen();
    } );

    // ─── Refresh tabla resumen cuando cambia descuento o cantidad ────────────
    $( document ).on( 'input change', '#_bgm_min_1, #_bgm_min_2, #_bgm_descuento_1, #_bgm_descuento_2', function() {
        actualizarTablaResumen();
    } );

    function actualizarTablaResumen() {
        var precioBase = parseFloat( BGM_ADMIN.precio_base ) || 0;
        if ( precioBase <= 0 ) return;

        var min1  = parseInt( $( '#_bgm_min_1' ).val(), 10 )  || parseInt( BGM_ADMIN.min_global_1, 10 );
        var min2  = parseInt( $( '#_bgm_min_2' ).val(), 10 )  || parseInt( BGM_ADMIN.min_global_2, 10 );
        var desc1 = parseFloat( $( '#_bgm_descuento_1' ).val() ) || 0;
        var desc2 = parseFloat( $( '#_bgm_descuento_2' ).val() ) || 0;

        actualizarFilaTier( 1, min1, desc1, precioBase );
        actualizarFilaTier( 2, min2, desc2, precioBase );
    }

    function actualizarFilaTier( tier, min, descuento, precioBase ) {
        var $fila = $( '.bgm-fila-tier' + tier );
        if ( ! $fila.length ) return;

        $fila.find( 'td' ).eq( 1 ).text( min + ' ud.' );

        var $precioTd  = $fila.find( 'td' ).eq( 2 );
        var $ahorroTd  = $fila.find( 'td' ).eq( 3 );

        if ( descuento > 0 ) {
            $fila.removeClass( 'bgm-tier-inactivo' );
            $precioTd.html( '<strong>' + formatPrecio( Math.max( 0, precioBase - descuento ) ) + '</strong>' );
            var pct = ( ( descuento / precioBase ) * 100 ).toFixed( 1 );
            $ahorroTd.html( formatPrecio( descuento ) + ' <span class="bgm-pct">(' + pct + '%)</span>' );
        } else {
            $fila.addClass( 'bgm-tier-inactivo' );
            $precioTd.html( '<span class="bgm-no-config">no configurado</span>' );
            $ahorroTd.html( '—' );
        }
    }

    // ─── Cambio de modo descuento (único / individual) ───────────────────────
    // Cuando se cambia, recargar la página para que los campos se habiliten/deshabiliten correctamente
    $( document ).on( 'change', 'input[name="_bgm_modo_descuento"]', function() {
        var aviso = $( '<div class="notice notice-warning bgm-aviso-cambio-modo"><p><strong>Cambiaste el modo de descuento.</strong> Guarda el producto para que los cambios surtan efecto.</p></div>' );
        $( '.bgm-grupo-modo' ).after( aviso );
        aviso.delay( 5000 ).fadeOut( 400, function() { $( this ).remove(); } );
    } );

    // ─── Preview en cada variación (modo individual) ─────────────────────────
    $( document ).on( 'input change', '.bgm-var-descuento', function() {
        var $input     = $( this );
        var loop       = $input.data( 'loop' );
        var tier       = $input.data( 'tier' );
        var precioBase = parseFloat( $input.data( 'precio-base' ) ) || 0;
        var descuento  = parseFloat( $input.val() ) || 0;
        var $preview   = $( '.bgm-var-preview[data-loop="' + loop + '"][data-tier="' + tier + '"]' );

        if ( precioBase <= 0 ) {
            $preview.html( '' );
            return;
        }

        if ( descuento <= 0 ) {
            $preview.html( '' );
            return;
        }

        $preview.html( '→ ' + formatPrecio( Math.max( 0, precioBase - descuento ) ) );
    } );

} )( jQuery );
