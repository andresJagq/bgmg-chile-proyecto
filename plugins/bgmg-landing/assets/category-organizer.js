/* global jQuery, BGM_CAT_ORG */
/**
 * Organizador de categorías — árbol arrastrable (2 niveles).
 * Depende de jQuery UI Sortable (incluido en WordPress).
 */
( function ( $ ) {
	'use strict';

	if ( typeof BGM_CAT_ORG === 'undefined' ) {
		return;
	}

	var $tree  = $( '#bgm-cat-tree' );
	if ( ! $tree.length ) {
		return;
	}

	var dirty   = false;
	var $status = $( '.bgm-cat-status' );

	/* ---- estado "sin guardar" ---------------------------------------- */

	function setStatus( text, cls ) {
		$status.text( text || '' ).attr( 'class', 'bgm-cat-status' + ( cls ? ' ' + cls : '' ) );
	}
	function markDirty() {
		dirty = true;
		$( '.bgm-cat-save' ).prop( 'disabled', false );
		setStatus( BGM_CAT_ORG.i18n.dirty, 'is-dirty' );
	}
	function clearDirty() {
		dirty = false;
		$( '.bgm-cat-save' ).prop( 'disabled', true );
	}

	/* ---- sortable ----------------------------------------------------- */

	function onReceive( e, ui ) {
		// Si caemos en una sublista de hijas (nivel 2) y el ítem recibido trae
		// hijas propias (es un padre con contenido), no se permite: revertir.
		if ( $( this ).hasClass( 'bgm-cat-children' ) &&
			ui.item.find( '.bgm-cat-children > li' ).length > 0 ) {
			$( ui.sender ).sortable( 'cancel' );
			window.alert( BGM_CAT_ORG.i18n.depth );
		}
	}

	function onUpdate() {
		markDirty();
		normalize();
	}

	function initSortable( $list ) {
		$list.addClass( 'bgm-cat-connected' );
		if ( $list.hasClass( 'ui-sortable' ) ) {
			return;
		}
		$list.sortable( {
			handle: '.bgm-cat-handle',
			items: '> li.bgm-cat-item',
			connectWith: '.bgm-cat-connected',
			placeholder: 'bgm-cat-placeholder',
			tolerance: 'pointer',
			forcePlaceholderSize: true,
			cursor: 'grabbing',
			receive: onReceive,
			update: onUpdate
		} );
	}

	/* ---- normalizar jerarquía (2 niveles) ----------------------------- */

	function normalize() {
		// Nivel 1: hijos directos del árbol = padres. Aseguran sublista para recibir hijas.
		$tree.children( 'li.bgm-cat-item' ).each( function () {
			var $p = $( this );
			$p.removeClass( 'is-child' ).addClass( 'is-parent' );
			if ( $p.children( '.bgm-cat-children' ).length === 0 ) {
				$p.append( '<ol class="bgm-cat-children bgm-cat-connected"></ol>' );
			}
			$p.children( '.bgm-cat-children' ).each( function () {
				if ( ! $( this ).hasClass( 'ui-sortable' ) ) {
					initSortable( $( this ) );
				}
			} );
		} );

		// Nivel 2: ítems dentro de una sublista = hijas. No deben tener sublista propia.
		$tree.find( '.bgm-cat-children > li.bgm-cat-item' ).each( function () {
			var $c   = $( this );
			$c.removeClass( 'is-parent' ).addClass( 'is-child' );
			var $sub = $c.children( '.bgm-cat-children' );
			if ( $sub.length && $sub.children( 'li' ).length === 0 ) {
				$sub.remove();
			}
		} );
	}

	/* ---- serializar + guardar ----------------------------------------- */

	function visFlags( $item ) {
		var $row = $item.children( '.bgm-cat-row' );
		return {
			hide_pc: $row.find( '.bgm-cat-pc-cb' ).is( ':checked' ) ? 0 : 1,
			hide_mobile: $row.find( '.bgm-cat-mobile-cb' ).is( ':checked' ) ? 0 : 1
		};
	}

	function serializeTree() {
		var out = [];
		$tree.children( 'li.bgm-cat-item' ).each( function ( pi ) {
			var $p  = $( this );
			var pid = parseInt( $p.attr( 'data-id' ), 10 );
			var pf  = visFlags( $p );
			out.push( { id: pid, parent: 0, order: pi, hide_pc: pf.hide_pc, hide_mobile: pf.hide_mobile } );
			$p.children( '.bgm-cat-children' ).children( 'li.bgm-cat-item' ).each( function ( ci ) {
				var $c = $( this );
				var cf = visFlags( $c );
				out.push( { id: parseInt( $c.attr( 'data-id' ), 10 ), parent: pid, order: ci, hide_pc: cf.hide_pc, hide_mobile: cf.hide_mobile } );
			} );
		} );
		return out;
	}

	function save() {
		$( '.bgm-cat-save' ).prop( 'disabled', true );
		setStatus( BGM_CAT_ORG.i18n.saving, 'is-saving' );

		$.post( BGM_CAT_ORG.ajax, {
			action: 'bgm_cat_tree_save',
			nonce: BGM_CAT_ORG.nonce,
			tree: JSON.stringify( serializeTree() )
		} ).done( function ( resp ) {
			if ( resp && resp.success ) {
				clearDirty();
				setStatus( BGM_CAT_ORG.i18n.saved, 'is-ok' );
			} else {
				var msg = ( resp && resp.data && resp.data.msg ) ? resp.data.msg : BGM_CAT_ORG.i18n.error;
				setStatus( msg, 'is-error' );
				$( '.bgm-cat-save' ).prop( 'disabled', false );
			}
		} ).fail( function () {
			setStatus( BGM_CAT_ORG.i18n.error, 'is-error' );
			$( '.bgm-cat-save' ).prop( 'disabled', false );
		} );
	}

	/* ---- arranque ----------------------------------------------------- */

	initSortable( $tree );
	$tree.find( '.bgm-cat-children' ).each( function () {
		initSortable( $( this ) );
	} );
	normalize();
	clearDirty();

	$( '.bgm-cat-save' ).on( 'click', save );
	$tree.on( 'change', '.bgm-cat-pc-cb, .bgm-cat-mobile-cb', markDirty );

	$( window ).on( 'beforeunload', function () {
		if ( dirty ) {
			return BGM_CAT_ORG.i18n.leaveWarn;
		}
	} );

} )( jQuery );
