/**
 * BGM Frontend — Producto Simple
 *
 * Escucha el input cantidad del form nativo de WC y actualiza:
 *   - Precio unitario destacado (según tier que aplica)
 *   - Badge de tier resaltado (is-active-tier)
 *   - Aviso "Agrega N más para Mayorista X a $Y c/u"
 */
( function( $ ) {
    'use strict';

    if ( typeof BGM_SIMPLE === 'undefined' ) return;

    var $preview = $( '.bgm-simple-preview' ).first();
    if ( ! $preview.length ) return;

    var $tiers = $( '.bgm-tiers-row .bgm-tier-row' );

    // Encontrar el input quantity del form nativo de WC en esta página
    function getQty() {
        var $input = $( 'form.cart input[name="quantity"]' ).first();
        if ( ! $input.length ) return 1;
        var v = parseInt( $input.val(), 10 );
        return ( isNaN( v ) || v < 1 ) ? 1 : v;
    }

    var precioBase = parseFloat( BGM_SIMPLE.precio_base ) || 0;
    var min1       = parseInt(   BGM_SIMPLE.min_1,  10 ) || 0;
    var min2       = parseInt(   BGM_SIMPLE.min_2,  10 ) || 0;
    var desc1      = parseFloat( BGM_SIMPLE.desc_1 ) || 0;
    var desc2      = parseFloat( BGM_SIMPLE.desc_2 ) || 0;

    var tier1Activo = desc1 > 0 && min1 > 0;
    var tier2Activo = desc2 > 0 && min2 > 0;

    function formatPrecio( valor ) {
        if ( isNaN( valor ) || valor < 0 ) valor = 0;
        var decimals    = parseInt( BGM_SIMPLE.decimals, 10 ) || 0;
        var thousandSep = BGM_SIMPLE.thousand_sep || '.';
        var decimalSep  = BGM_SIMPLE.decimal_sep || ',';
        var symbol      = BGM_SIMPLE.currency_symbol || '$';
        var partes      = valor.toFixed( decimals ).split( '.' );
        partes[0] = partes[0].replace( /\B(?=(\d{3})+(?!\d))/g, thousandSep );
        return symbol + partes.join( decimalSep );
    }

    function calcularTier( qty ) {
        if ( tier2Activo && qty >= min2 ) {
            return { tier: 2, precio_unit: precioBase - desc2, descuento: desc2 };
        }
        if ( tier1Activo && qty >= min1 ) {
            return { tier: 1, precio_unit: precioBase - desc1, descuento: desc1 };
        }
        return { tier: 0, precio_unit: precioBase, descuento: 0 };
    }

    function actualizar() {
        var qty   = getQty();
        var info  = calcularTier( qty );
        var $valor = $preview.find( '.bgm-simple-precio-valor' );
        var $aviso = $preview.find( '.bgm-simple-preview-aviso' );

        // Precio unitario actual destacado
        $valor.html( formatPrecio( Math.max( 0, info.precio_unit ) ) );

        // Estado visual del bloque preview
        $preview.attr( 'data-tier-activo', info.tier );

        // Resaltar tier activo en los badges
        $tiers.each( function() {
            var t = parseInt( $( this ).data( 'tier' ), 10 );
            $( this ).toggleClass( 'is-active-tier', t === info.tier );
        } );

        // Aviso "Agrega N más para..."
        var avisoHtml = '';
        if ( info.tier === 0 && tier1Activo ) {
            // No hay tier — sugerir el primero
            var falta1 = Math.max( 0, min1 - qty );
            if ( falta1 > 0 ) {
                avisoHtml = BGM_SIMPLE.txt_agrega.replace( '%d', falta1 ) +
                    ' <strong>' + BGM_SIMPLE.txt_tier1 + '</strong> a ' +
                    formatPrecio( precioBase - desc1 ) + ' ' + BGM_SIMPLE.txt_cu;
            }
            $aviso.attr( 'data-estado', 'detalle' );
        } else if ( info.tier === 1 && tier2Activo ) {
            // Está en tier 1 — sugerir tier 2 si falta
            var falta2 = Math.max( 0, min2 - qty );
            if ( falta2 > 0 ) {
                avisoHtml = BGM_SIMPLE.txt_agrega.replace( '%d', falta2 ) +
                    ' <strong>' + BGM_SIMPLE.txt_tier2 + '</strong> a ' +
                    formatPrecio( precioBase - desc2 ) + ' ' + BGM_SIMPLE.txt_cu;
            }
            $aviso.attr( 'data-estado', 'tier1' );
        } else if ( info.tier === 2 ) {
            // Está en tier 2 — máximo descuento ya alcanzado
            $aviso.attr( 'data-estado', 'tier2' );
        }

        $aviso.html( avisoHtml );
        $aviso.toggleClass( 'is-empty', avisoHtml === '' );
    }

    // Listeners: input/change directos en quantity, además del trigger
    // que disparan los botones +/- nativos del tema (bgmg-qty-btn).
    $( document ).on( 'input change keyup', 'form.cart input[name="quantity"]', actualizar );
    $( document ).on( 'click', 'form.cart .bgmg-qty-btn, form.cart .plus, form.cart .minus, form.cart .quantity button', function() {
        // Esperar 1 tick a que el handler del tema actualice el value
        setTimeout( actualizar, 0 );
    } );

    actualizar();

} )( jQuery );
