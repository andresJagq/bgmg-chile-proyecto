/**
 * BGM Frontend — Swatches (pills) para selectores de variación
 *
 * Convierte cada <select> de atributo dentro de form.variations_form
 * en un grupo de botones tipo píldora. Sincroniza el <select> oculto al
 * click para que el form nativo de WC siga funcionando.
 *
 * Refleja el estado disabled de los <option> en los pills usando un
 * MutationObserver: cuando WC marca un option como deshabilitado por
 * combinación sin stock, el pill se ve tachado.
 */
( function( $ ) {
    'use strict';

    function init( $form ) {
        if ( ! $form.length || $form.data( 'bgm-swatches-init' ) ) return;
        $form.data( 'bgm-swatches-init', true );

        var unAtributo = $form.find( 'table.variations select' ).length === 1;

        $form.find( 'table.variations select' ).each( function() {
            convertirSelect( $( this ), unAtributo );
        } );

        // Cuando WC limpia las variaciones (cliente clickea "Limpiar"), resetear pills.
        $form.on( 'reset_data', function() {
            $form.find( '.bgm-swatches' ).each( function() {
                $( this ).find( '.bgm-swatch.is-active' ).removeClass( 'is-active' );
            } );
        } );
    }

    function convertirSelect( $select, unAtributo ) {
        if ( $select.data( 'bgm-converted' ) ) return;
        $select.data( 'bgm-converted', true );

        var attrName = $select.attr( 'name' ) || '';
        var $swatches = $( '<div class="bgm-swatches" role="radiogroup" aria-label="' + ( $select.attr( 'aria-label' ) || attrName ) + '"></div>' );
        if ( unAtributo ) $swatches.addClass( 'is-single' );

        $select.find( 'option' ).each( function() {
            var $opt   = $( this );
            var value  = $opt.attr( 'value' ) || '';
            if ( ! value ) return; // saltarse el placeholder "Elige una opción"

            var label  = $opt.text();
            var $pill = $(
                '<button type="button" class="bgm-swatch" role="radio" data-value="' + encodeAttr( value ) + '" aria-checked="false">' +
                    '<span class="bgm-swatch-label">' + escapeHtml( label ) + '</span>' +
                '</button>'
            );

            reflejarEstadoOption( $opt, $pill );
            $swatches.append( $pill );
        } );

        // Insertar pills justo después del select y ocultar el select.
        $select.after( $swatches );
        $select.addClass( 'bgm-select-oculto' );

        // Click en pill: setear el select y disparar change para que WC reaccione.
        $swatches.on( 'click', '.bgm-swatch', function( e ) {
            e.preventDefault();
            var $pill = $( this );
            if ( $pill.hasClass( 'is-disabled' ) ) return;

            var nuevoValor = $pill.attr( 'data-value' );
            var actual = $select.val();

            // Toggle: si ya estaba activo, deseleccionar (limpiar variación).
            if ( actual === nuevoValor && $pill.hasClass( 'is-active' ) ) {
                $select.val( '' ).trigger( 'change' );
                $swatches.find( '.bgm-swatch.is-active' ).removeClass( 'is-active' ).attr( 'aria-checked', 'false' );
                return;
            }

            $swatches.find( '.bgm-swatch.is-active' ).removeClass( 'is-active' ).attr( 'aria-checked', 'false' );
            $pill.addClass( 'is-active' ).attr( 'aria-checked', 'true' );
            $select.val( nuevoValor ).trigger( 'change' );
        } );

        // Observar cambios en las opciones del select (clases enabled/disabled que WC actualiza
        // según la combinación seleccionada) y reflejarlos en los pills.
        var observer = new MutationObserver( function() {
            $select.find( 'option' ).each( function() {
                var $opt = $( this );
                var value = $opt.attr( 'value' );
                if ( ! value ) return;
                var $pill = $swatches.find( '.bgm-swatch[data-value="' + escapeSelector( value ) + '"]' );
                if ( $pill.length ) reflejarEstadoOption( $opt, $pill );
            } );

            // Sincronizar pill activa con valor actual del select
            var current = $select.val();
            $swatches.find( '.bgm-swatch' ).each( function() {
                var $p = $( this );
                var active = $p.attr( 'data-value' ) === current && current !== '';
                $p.toggleClass( 'is-active', active ).attr( 'aria-checked', active ? 'true' : 'false' );
            } );
        } );

        // Observar el select completo: clases en options, hijos añadidos/removidos por WC.
        observer.observe( $select[0], { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
    }

    function reflejarEstadoOption( $opt, $pill ) {
        var disabled = $opt.hasClass( 'disabled' ) || $opt.is( ':disabled' );
        // Note: WC marca opciones como `class="attached enabled"` o `class="attached disabled"`.
        // También respeta attribute disabled.
        $pill.toggleClass( 'is-disabled', !!disabled );
        if ( disabled ) {
            $pill.attr( 'aria-disabled', 'true' );
        } else {
            $pill.removeAttr( 'aria-disabled' );
        }
    }

    function escapeHtml( s ) {
        return String( s ).replace( /[&<>"']/g, function( c ) {
            return ( { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' } )[ c ];
        } );
    }

    function encodeAttr( s ) {
        return String( s ).replace( /"/g, '&quot;' );
    }

    function escapeSelector( s ) {
        // jQuery 3+ expone $.escapeSelector. Fallback manual cubre el set de
        // metacaracteres CSS que pueden aparecer en slugs de atributo WC.
        if ( typeof $.escapeSelector === 'function' ) {
            return $.escapeSelector( String( s ) );
        }
        return String( s ).replace( /(["'\\\]\[(){}+~>,*=^$:|#.])/g, '\\$1' );
    }

    // Inicializar al cargar. WC también dispara wc_variation_form en el form al estar listo.
    $( function() {
        $( 'form.variations_form' ).each( function() {
            init( $( this ) );
        } );
    } );

    // Por si el form se carga después (ej. AJAX, quick view), engancharse al evento de WC.
    $( document ).on( 'wc_variation_form', 'form.variations_form', function() {
        init( $( this ) );
    } );

} )( jQuery );
