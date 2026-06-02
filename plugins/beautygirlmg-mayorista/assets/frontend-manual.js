/**
 * BGM Frontend — Modo Manual (cliente arma su surtido)
 *
 * Funcionalidad:
 *   - Contador en vivo con tier aplicado
 *   - Validación de regla de surtido equilibrado
 *   - Mensajes específicos: dice EXACTAMENTE qué falta o sobra
 *   - Resaltado visual: faltan en verde, sobran en rojo
 *   - Botón "Sugerir surtido" que auto-rellena distribución óptima
 *   - Reset post-agregar para permitir múltiples adiciones sin recargar
 *   - Switch entre sub-tabs Sorpréndeme / Manual
 */
( function( $ ) {
    'use strict';

    if ( typeof BGM_MANUAL === 'undefined' ) return;

    var $bloque = $( '.bgm-bloque-manual' );
    if ( ! $bloque.length ) {
        // El modo manual no está activo en esta página: igual registrar el switch
        // de sub-tabs para que el de Sorpréndeme siga funcionando.
        registerSubtabHandler();
        return;
    }

    var productId     = parseInt( $bloque.data( 'product-id' ), 10 );
    var min1          = Math.max( 0, parseInt( $bloque.data( 'min-1' ), 10 ) || 0 );
    var precioDetalle = parseFloat( $bloque.data( 'precio-detalle' ) ) || 0;
    var nDisponibles  = $bloque.find( '.bgm-variacion-row' ).length;
    // El resto de la lógica (min2, tiers, tolerancia) la evalúa el servidor
    // en bgm_evaluar_distribucion vía AJAX. min1 y precioDetalle se
    // mantienen para Sugerir surtido y el estado optimista del subtotal
    // antes de que llegue la respuesta. nDisponibles solo para Sugerir.

    // Debug interno: el server ya no expone el flag al frontend.
    // Para depurar manualmente, ejecutar en consola: window.BGM_DEBUG_MANUAL = true;
    function dbg() {
        if ( window.BGM_DEBUG_MANUAL && window.console && console.log ) {
            console.log.apply( console, [ '[BGM manual]' ].concat( [].slice.call( arguments ) ) );
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────────────
    function getRowsData() {
        var rows = [];
        $bloque.find( '.bgm-variacion-row' ).each( function() {
            var $row = $( this );
            var stock = parseInt( $row.data( 'stock' ), 10 );
            rows.push( {
                $el:    $row,
                vid:    parseInt( $row.data( 'vid' ), 10 ),
                nombre: $row.find( '.bgm-variacion-nombre' ).text().trim(),
                qty:    parseInt( $row.find( '.bgm-qty-input' ).val(), 10 ) || 0,
                stock:  stock === -1 ? Infinity : ( isNaN( stock ) ? Infinity : stock )
            } );
        } );
        return rows;
    }

    function setRowQty( $row, qty ) {
        $row.find( '.bgm-qty-input' ).val( qty ).trigger( 'input' );
    }

    function limpiarMarcas() {
        $bloque.find( '.bgm-variacion-row' ).removeClass( 'bgm-row-falta bgm-row-sobra bgm-row-balance' );
    }

    // ─── Botones +/- por variación ──────────────────────────────────────────
    $bloque.on( 'click', '.bgm-qty-menos', function() {
        var $row   = $( this ).closest( '.bgm-variacion-row' );
        var $input = $row.find( '.bgm-qty-input' );
        var actual = parseInt( $input.val(), 10 ) || 0;
        if ( actual > 0 ) {
            $input.val( actual - 1 ).trigger( 'change' );
        }
    } );

    $bloque.on( 'click', '.bgm-qty-mas', function() {
        var $row   = $( this ).closest( '.bgm-variacion-row' );
        var $input = $row.find( '.bgm-qty-input' );
        var actual = parseInt( $input.val(), 10 ) || 0;
        var stock  = parseInt( $row.data( 'stock' ), 10 );
        if ( stock !== -1 && !isNaN( stock ) && actual >= stock ) return;
        $input.val( actual + 1 ).trigger( 'change' );
    } );

    // Mientras escribe: refrescar estado sin capear el valor, para no
    // entorpecer la edición (ej: si stock es 3 y escribe "10", capear al
    // primer dígito "1" haría que el siguiente "0" produzca "30").
    $bloque.on( 'input', '.bgm-qty-input', function() {
        actualizarEstado();
    } );

    // Al terminar (blur o change real): capear a [0, stock] y refrescar.
    $bloque.on( 'change blur', '.bgm-qty-input', function() {
        var $input = $( this );
        var $row   = $input.closest( '.bgm-variacion-row' );
        var stock  = parseInt( $row.data( 'stock' ), 10 );
        var val    = parseInt( $input.val(), 10 ) || 0;
        if ( val < 0 ) val = 0;
        if ( stock !== -1 && !isNaN( stock ) && val > stock ) val = stock;
        $input.val( val );
        actualizarEstado();
    } );

    // ─── Botón "Sugerir surtido" ────────────────────────────────────────────
    $bloque.on( 'click', '.bgm-btn-sugerir', function( e ) {
        e.preventDefault();
        dbg( 'click Sugerir surtido' );

        var rows = getRowsData();
        if ( ! rows.length ) {
            dbg( 'sin filas en DOM — abort' );
            return;
        }

        var totalActual = rows.reduce( function( a, r ) { return a + r.qty; }, 0 );

        // Si no hay nada → sugerir min_1 (con piso de nDisponibles para llenar todas).
        // Si hay algo → mantener total actual y redistribuir balanceado.
        var minObjetivo = Math.max( min1 || 0, nDisponibles || 1 );
        var totalObjetivo = totalActual > 0 ? totalActual : minObjetivo;

        // Filtrar solo variaciones con stock disponible
        var disponibles = rows.filter( function( r ) { return r.stock > 0; } );
        if ( disponibles.length === 0 ) {
            dbg( 'sin variaciones disponibles — abort' );
            return;
        }

        var n = disponibles.length;
        var base = Math.floor( totalObjetivo / n );
        var sobrantes = totalObjetivo % n;

        // Aleatoriedad para los sobrantes
        var indices = disponibles.map( function( _, i ) { return i; } );
        for ( var i = indices.length - 1; i > 0; i-- ) {
            var j = Math.floor( Math.random() * ( i + 1 ) );
            var t = indices[ i ];
            indices[ i ] = indices[ j ];
            indices[ j ] = t;
        }

        // Asignar base + 1 a los sobrantes
        var asignaciones = disponibles.map( function() { return base; } );
        for ( var s = 0; s < sobrantes; s++ ) {
            asignaciones[ indices[ s ] ] += 1;
        }

        // Cap por stock + redistribuir excedente
        var excedente = 0;
        for ( var i2 = 0; i2 < n; i2++ ) {
            if ( asignaciones[ i2 ] > disponibles[ i2 ].stock ) {
                excedente += asignaciones[ i2 ] - disponibles[ i2 ].stock;
                asignaciones[ i2 ] = disponibles[ i2 ].stock;
            }
        }
        var loop = 1000;
        while ( excedente > 0 && loop-- > 0 ) {
            var asignado = false;
            for ( var i3 = 0; i3 < n; i3++ ) {
                if ( excedente === 0 ) break;
                if ( asignaciones[ i3 ] < disponibles[ i3 ].stock ) {
                    asignaciones[ i3 ] += 1;
                    excedente -= 1;
                    asignado = true;
                }
            }
            if ( ! asignado ) break;
        }

        // Resetear todas a 0 primero (incluyendo las no-disponibles)
        rows.forEach( function( r ) {
            r.$el.find( '.bgm-qty-input' ).val( 0 );
        } );

        // Aplicar asignaciones a las disponibles
        for ( var i4 = 0; i4 < n; i4++ ) {
            disponibles[ i4 ].$el.find( '.bgm-qty-input' ).val( asignaciones[ i4 ] );
        }

        // Disparar evento input/change para que listeners reaccionen
        $bloque.find( '.bgm-qty-input' ).trigger( 'input' );

        dbg( 'sugerencia aplicada', asignaciones );
        actualizarEstado();
    } );

    // ─── Evaluación centralizada vía AJAX (debounced) ──────────────────────
    //
    // La regla de surtido vive 100% en PHP (bgm_evaluar_distribucion). El JS:
    //   1. Pinta inmediatamente el estado "optimista" (qty total + subtotal a
    //      precio detalle) para feedback instantáneo.
    //   2. Hace una request al endpoint bgm_evaluar_surtido con debounce.
    //   3. Aplica el resultado del servidor (mensaje real, tier, resaltado).
    //
    // Cancela requests en vuelo si llega un cambio nuevo, así no se aplica
    // un resultado obsoleto sobre uno más reciente.

    var evalTimer = null;
    var evalXhr   = null;
    var evalSeq   = 0;  // descarta respuestas tardías

    function recolectarCantidades() {
        var cantidades = {};
        var total = 0;
        $bloque.find( '.bgm-variacion-row' ).each( function() {
            var $row = $( this );
            var vid  = parseInt( $row.data( 'vid' ), 10 );
            var qty  = parseInt( $row.find( '.bgm-qty-input' ).val(), 10 ) || 0;
            if ( vid && qty > 0 ) {
                cantidades[ vid ] = qty;
                total += qty;
            }
        } );
        return { cantidades: cantidades, total: total };
    }

    function actualizarEstado() {
        var datos     = recolectarCantidades();
        var $msg      = $bloque.find( '.bgm-contador-mensaje' );
        var $subtotal = $bloque.find( '.bgm-subtotal-valor' );
        var $btn      = $bloque.find( '.bgm-btn-agregar-manual' );

        // 1) Estado optimista inmediato (sin esperar al servidor)
        limpiarMarcas();

        if ( datos.total === 0 ) {
            $msg.attr( 'data-estado', 'detalle' ).text( BGM_MANUAL.txt_seleccionar );
            $subtotal.html( formatPrecio( 0 ) );
            $btn.prop( 'disabled', true );
            actualizarTierBadges( 0 );
            if ( evalTimer ) clearTimeout( evalTimer );
            if ( evalXhr && evalXhr.abort ) try { evalXhr.abort(); } catch ( e ) {}
            return;
        }

        // Subtotal optimista a precio detalle (el real llega del servidor)
        $subtotal.html( formatPrecio( datos.total * precioDetalle ) );
        $btn.prop( 'disabled', false );

        // 2) AJAX con debounce
        if ( evalTimer ) clearTimeout( evalTimer );
        evalTimer = setTimeout( function() { ejecutarEvaluacion( datos ); }, 300 );
    }

    function ejecutarEvaluacion( datos ) {
        if ( evalXhr && evalXhr.abort ) try { evalXhr.abort(); } catch ( e ) {}

        var miSeq = ++evalSeq;
        dbg( 'eval AJAX seq=' + miSeq, datos.cantidades );

        evalXhr = $.post( BGM_MANUAL.ajax_url, {
            action:     'bgm_evaluar_surtido',
            nonce:      BGM_MANUAL.nonce,
            product_id: productId,
            cantidades: datos.cantidades
        } )
        .done( function( resp ) {
            if ( miSeq !== evalSeq ) return; // descartar respuesta obsoleta
            if ( ! resp || ! resp.success ) return;
            aplicarResultadoEvaluacion( resp.data );
        } )
        .fail( function( jqXHR, textStatus ) {
            if ( textStatus === 'abort' ) return;
            if ( window.console && console.warn ) {
                console.warn( '[BGM manual] eval AJAX failed:', textStatus );
            }
        } );
    }

    function aplicarResultadoEvaluacion( r ) {
        var $msg      = $bloque.find( '.bgm-contador-mensaje' );
        var $subtotal = $bloque.find( '.bgm-subtotal-valor' );
        var $btn      = $bloque.find( '.bgm-btn-agregar-manual' );

        limpiarMarcas();

        if ( Array.isArray( r.faltan ) ) {
            r.faltan.forEach( function( vid ) {
                $bloque.find( '.bgm-variacion-row[data-vid="' + vid + '"]' ).addClass( 'bgm-row-falta' );
            } );
        }
        if ( Array.isArray( r.sobran ) ) {
            r.sobran.forEach( function( vid ) {
                $bloque.find( '.bgm-variacion-row[data-vid="' + vid + '"]' ).addClass( 'bgm-row-sobra' );
            } );
        }
        if ( r.califica && r.tier > 0 ) {
            $bloque.find( '.bgm-variacion-row' ).each( function() {
                var qty = parseInt( $( this ).find( '.bgm-qty-input' ).val(), 10 ) || 0;
                if ( qty > 0 ) $( this ).addClass( 'bgm-row-balance' );
            } );
        }

        $msg.attr( 'data-estado', r.estado || 'detalle' ).text( r.mensaje || '' );
        $subtotal.html( formatPrecio( r.subtotal || 0 ) );
        $btn.prop( 'disabled', ( r.qty_total || 0 ) === 0 );
        actualizarTierBadges( r.tier || 0 );
    }

    /**
     * Resalta el tier activo en los badges del top del bloque.
     */
    function actualizarTierBadges( tierActivo ) {
        var $bloqueMayor = $bloque.closest( '.bgm-bloque-mayor' );
        if ( ! $bloqueMayor.length ) return;
        $bloqueMayor.find( '.bgm-tier-row' ).each( function() {
            var t = parseInt( $( this ).data( 'tier' ), 10 );
            $( this ).toggleClass( 'is-active-tier', t === tierActivo );
        } );
    }

    // ─── Click en agregar ────────────────────────────────────────────────────
    $bloque.on( 'click', '.bgm-btn-agregar-manual', function( e ) {
        e.preventDefault();

        var $btn = $( this );
        var $fb  = $bloque.find( '.bgm-feedback' );

        var cantidades = {};
        $bloque.find( '.bgm-variacion-row' ).each( function() {
            var $row = $( this );
            var vid  = parseInt( $row.data( 'vid' ), 10 );
            var qty  = parseInt( $row.find( '.bgm-qty-input' ).val(), 10 ) || 0;
            if ( vid && qty > 0 ) cantidades[ vid ] = qty;
        } );

        if ( Object.keys( cantidades ).length === 0 ) {
            mostrarFeedback( $fb, 'error', BGM_MANUAL.txt_minimo_una );
            return;
        }

        var textoOriginal = $btn.data( 'original' ) || $btn.text();
        $btn.data( 'original', textoOriginal );
        $btn.prop( 'disabled', true ).addClass( 'is-loading' ).text( BGM_MANUAL.txt_adding );
        $fb.empty().removeClass( 'bgm-feedback-error bgm-feedback-exito' );

        $.post( BGM_MANUAL.ajax_url, {
            action:     'bgm_agregar_manual',
            nonce:      BGM_MANUAL.nonce,
            product_id: productId,
            cantidades: cantidades
        } )
        .done( function( resp ) {
            $btn.removeClass( 'is-loading' );
            if ( resp.success ) {
                var totalMsg = ( BGM_MANUAL.txt_total_cart || '' ).replace( '%d', resp.data.cart_count );
                mostrarFeedback( $fb, 'exito',
                    resp.data.message + ' ' + totalMsg
                );
                $bloque.find( '.bgm-qty-input' ).val( 0 );
                $btn.prop( 'disabled', true ).text( textoOriginal );
                limpiarMarcas();
                actualizarEstado();

                if ( typeof window.bgmAfterAddToCart === 'function' ) {
                    window.bgmAfterAddToCart( resp.data );
                } else {
                    $( document.body ).trigger( 'wc_fragment_refresh' );
                }
            } else {
                var msg = ( resp.data && resp.data.message ) ? resp.data.message : BGM_MANUAL.txt_error;
                mostrarFeedback( $fb, 'error', msg );
                $btn.prop( 'disabled', false ).text( textoOriginal );
            }
        } )
        .fail( function( jqXHR ) {
            $btn.removeClass( 'is-loading' );
            var msgErr = BGM_MANUAL.txt_error;
            if ( jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message ) {
                msgErr = jqXHR.responseJSON.data.message;
            }
            mostrarFeedback( $fb, 'error', msgErr );
            $btn.prop( 'disabled', false ).text( textoOriginal );
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
        var decimals    = parseInt( BGM_MANUAL.decimals, 10 ) || 0;
        var thousandSep = BGM_MANUAL.thousand_sep || '.';
        var decimalSep  = BGM_MANUAL.decimal_sep || ',';
        var symbol      = BGM_MANUAL.currency_symbol || '$';
        var partes      = valor.toFixed( decimals ).split( '.' );
        partes[0] = partes[0].replace( /\B(?=(\d{3})+(?!\d))/g, thousandSep );
        return symbol + partes.join( decimalSep );
    }

    // ─── Sub-tabs Sorpréndeme / Manual ──────────────────────────────────────
    function registerSubtabHandler() {
        $( document ).on( 'click', '.bgm-subtabs .bgm-subtab', function() {
            var $tab = $( this );
            var target = $tab.data( 'subtab' );
            var $cont = $tab.closest( '.bgm-bloque-mayor' );

            $cont.find( '.bgm-subtab' ).removeClass( 'is-active' );
            $tab.addClass( 'is-active' );

            $cont.find( '.bgm-subpanel' ).each( function() {
                var $p = $( this );
                var active = $p.data( 'subpanel' ) === target;
                $p.toggleClass( 'is-active', active );
                if ( active ) {
                    $p.removeAttr( 'hidden' );
                } else {
                    $p.attr( 'hidden', 'hidden' );
                }
            } );
        } );
    }
    registerSubtabHandler();

    actualizarEstado();

} )( jQuery );
