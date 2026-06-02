/**
 * Cascada Región → Comuna en checkout / direcciones de WooCommerce.
 *
 * Funcionamiento:
 *   - Leemos el dataset desde window.BGMG_CHILE_COMUNAS (inyectado por
 *     wp_localize_script en bgmg-chile.php).
 *   - Para cada par (region_select, city_select) presente en el DOM,
 *     filtramos las opciones del select de comunas según la región elegida.
 *   - Si el cliente cambia la región y la comuna actual ya no pertenece,
 *     limpiamos la selección.
 *   - Sin dependencia dura de jQuery aunque escuchamos eventos de jQuery
 *     porque el checkout de WC los dispara (country_to_state_changed, etc.).
 *
 * Pares manejados: billing_state/billing_city y shipping_state/shipping_city.
 */
(function ($) {
	'use strict';

	if (!window.BGMG_CHILE_COMUNAS || !window.BGMG_CHILE_COMUNAS.comunasPorRegion) {
		return;
	}

	var DATA = window.BGMG_CHILE_COMUNAS.comunasPorRegion;
	var I18N = window.BGMG_CHILE_COMUNAS.i18n || {};

	var PARES = [
		{ region: 'billing_state', comuna: 'billing_city' },
		{ region: 'shipping_state', comuna: 'shipping_city' }
	];

	/**
	 * Construye el HTML de opciones para una región dada.
	 * Si no hay comunas para esa región, devuelve un placeholder vacío.
	 */
	function buildOptionsFor(regionCode, currentValue) {
		var html = '';
		var comunas = DATA[regionCode] || [];

		html += '<option value="">' + (I18N.seleccionaComuna || 'Selecciona tu comuna') + '</option>';

		// Orden alfabético por nombre (ya viene ordenado del server, pero
		// re-ordenamos por si acaso).
		comunas
			.slice()
			.sort(function (a, b) {
				return a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' });
			})
			.forEach(function (c) {
				var sel = c.slug === currentValue ? ' selected' : '';
				html += '<option value="' + c.slug + '"' + sel + '>' + escapeHtml(c.nombre) + '</option>';
			});

		return html;
	}

	function escapeHtml(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	/**
	 * Flag para evitar el loop infinito de updates AJAX:
	 *
	 * WC dispara `updated_checkout` después de cada AJAX. En ese momento
	 * re-bindeamos los listeners y re-sincronizamos las comunas. Si en esa
	 * sincronización disparamos `change` aunque el valor no haya cambiado,
	 * WC pide otro update → otro updated_checkout → ... loop infinito.
	 *
	 * El flag distingue dos contextos:
	 *   - "sincronización pasiva" (rebind tras updated_checkout): no disparar
	 *     change en el select de comuna.
	 *   - "cambio activo del usuario" (cambió la región): SÍ disparar change
	 *     para que WC recalcule envío.
	 */

	/**
	 * Sincroniza un par región/comuna.
	 *
	 * @param {string}  regionId
	 * @param {string}  comunaId
	 * @param {boolean} fireChange  true = disparar `change` si el valor cambió
	 *                              false = solo refrescar visualmente (no AJAX)
	 */
	function syncPair(regionId, comunaId, fireChange) {
		var $region = $('#' + regionId);
		var $comuna = $('#' + comunaId);
		if (!$region.length || !$comuna.length) return;

		// Si el field de comuna no es select (porque el país cambió a otro
		// que no es CL), no hacemos nada.
		if ($comuna.prop('tagName') !== 'SELECT') return;

		var regionVal = String($region.val() || '');
		var prevComuna = String($comuna.val() || '');

		if (!regionVal) {
			$comuna.html(
				'<option value="">' +
					(I18N.primeroRegion || 'Primero elige una región') +
					'</option>'
			);
			$comuna.prop('disabled', true);
			// Solo disparar change si el valor efectivamente cambió a vacío.
			if (fireChange && prevComuna !== '') {
				triggerChange($comuna);
			}
			return;
		}

		var html = buildOptionsFor(regionVal, prevComuna);
		$comuna.html(html);
		$comuna.prop('disabled', false);

		// Si la comuna previa no pertenece a la nueva región, la limpiamos.
		var pertenece = (DATA[regionVal] || []).some(function (c) {
			return c.slug === prevComuna;
		});
		if (!pertenece) {
			$comuna.val('');
		}

		var newComuna = String($comuna.val() || '');
		if (fireChange && newComuna !== prevComuna) {
			triggerChange($comuna);
		}
	}

	function triggerChange($el) {
		// Usar SOLO el trigger de jQuery: WC escucha con jQuery y un solo
		// trigger es suficiente. Disparar DOM nativo + jQuery causaba que
		// el evento se procesara dos veces y duplicaba updates.
		$el.trigger('change');
	}

	function bindAll() {
		PARES.forEach(function (pair) {
			var $region = $('#' + pair.region);
			if (!$region.length) return;

			// Sync pasivo: solo refresca opciones, NO dispara AJAX update.
			// Esta llamada se ejecuta en cada `updated_checkout`, así que si
			// disparara change entraríamos en loop infinito.
			syncPair(pair.region, pair.comuna, false);

			// Listener del cambio REAL del usuario: aquí sí queremos disparar
			// change en comuna para que WC recalcule envío.
			$region.off('change.bgmgChile').on('change.bgmgChile', function () {
				syncPair(pair.region, pair.comuna, true);
			});
		});
	}

	// Inicialización en carga y tras cada re-render del checkout/dirección.
	$(document).ready(bindAll);
	$(document.body).on('updated_checkout country_to_state_changed', bindAll);
})(jQuery);
