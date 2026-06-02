/**
 * Admin de tarifas RM — interactividad client-side.
 *
 * Responsabilidades:
 *   1. Filtros por estado (Todas / Custom / Default / Por pagar).
 *   2. Buscador instantáneo por nombre de comuna.
 *   3. Reactividad: al cambiar el radio "tipo" de una fila → habilitar/
 *      deshabilitar el input precio, actualizar el badge, recalcular los
 *      contadores del resumen y de los filtros.
 *   4. Auto-completar precio al pasar de "default" a "custom" (sugerir default).
 *
 * Vanilla JS (sin jQuery) — más rápido y sin deps.
 *
 * @since 1.11.1
 */
(function () {
	'use strict';

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

	var tabla    = $('.bgmg-tarifas-tabla');
	if (!tabla) return;

	var tbody     = $('tbody', tabla);
	var rows      = $$('tbody tr', tabla);
	var searchInp = $('#bgmg-tarifas-search-input');
	var filters   = $$('.bgmg-filter');
	var emptyMsg  = $('.bgmg-tarifas-empty');

	var activeFilter = 'all';
	var searchTerm   = '';

	/* ── 1. RECALCULAR estado de una fila ───────────────────────── */

	function applyRowState(row) {
		var checked = row.querySelector('input.bgmg-tipo-radio:checked');
		var tipo    = checked ? checked.value : 'default';
		var precioInp = row.querySelector('.bgmg-precio-input');
		var badge   = row.querySelector('[data-badge]');

		row.setAttribute('data-tipo', tipo);

		if (precioInp) {
			if (tipo === 'custom') {
				precioInp.disabled = false;
				// Si está vacío y la dueña recién eligió custom, sugerir el default.
				if (!precioInp.value || precioInp.value === '0') {
					var defaultHint = parseInt(precioInp.getAttribute('placeholder'), 10);
					if (!isNaN(defaultHint) && defaultHint > 0) {
						precioInp.value = defaultHint;
					}
				}
				precioInp.focus({ preventScroll: true });
				precioInp.select();
			} else {
				precioInp.disabled = true;
			}
		}

		if (badge) {
			var precioVal = precioInp ? parseInt(precioInp.value || '0', 10) : 0;
			var defaultHint = precioInp ? parseInt(precioInp.getAttribute('placeholder'), 10) : 0;
			badge.className = 'bgmg-badge bgmg-badge-' + tipo;
			if (tipo === 'custom') {
				badge.innerHTML = precioVal > 0 ? formatCLP(precioVal) : '—';
			} else if (tipo === 'por_pagar') {
				badge.textContent = 'Por pagar';
			} else {
				badge.innerHTML = formatCLP(defaultHint) + ' <span style="opacity:0.7;font-size:0.9em;">(default)</span>';
			}
		}
	}

	function formatCLP(n) {
		n = parseInt(n, 10);
		if (isNaN(n) || n <= 0) return '—';
		return '$' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	}

	/* ── 2. ACTUALIZAR CONTADORES (resumen + filtros) ───────────── */

	function recountAll() {
		var counts = { custom: 0, 'default': 0, por_pagar: 0, retiro: 0 };
		rows.forEach(function (row) {
			var tipo = row.getAttribute('data-tipo') || 'default';
			counts[tipo] = (counts[tipo] || 0) + 1;
			var retiroCb = row.querySelector('.bgmg-retiro-cb');
			if (retiroCb && retiroCb.checked) counts.retiro++;
		});

		$$('[data-count]').forEach(function (el) {
			var key = el.getAttribute('data-count');
			if (typeof counts[key] !== 'undefined') {
				el.textContent = counts[key];
			}
		});
	}

	/* ── 3. APLICAR FILTROS + BÚSQUEDA ───────────────────────────── */

	function applyFiltersAndSearch() {
		var matched = 0;
		rows.forEach(function (row) {
			var tipo = row.getAttribute('data-tipo') || 'default';
			var search = row.getAttribute('data-search') || '';

			var matchFilter = (activeFilter === 'all') || (activeFilter === tipo);
			var matchSearch = !searchTerm || search.indexOf(searchTerm) !== -1;

			if (matchFilter && matchSearch) {
				row.classList.remove('is-hidden');
				matched++;
			} else {
				row.classList.add('is-hidden');
			}
		});

		if (emptyMsg) {
			emptyMsg.style.display = matched === 0 ? 'block' : 'none';
		}
	}

	/* ── 4. LISTENERS ────────────────────────────────────────────── */

	// Radio cambio de tipo: actualizar fila + contadores.
	tbody.addEventListener('change', function (e) {
		if (e.target.classList.contains('bgmg-tipo-radio')) {
			var row = e.target.closest('tr');
			if (row) {
				applyRowState(row);
				recountAll();
				applyFiltersAndSearch();
			}
		} else if (e.target.classList.contains('bgmg-retiro-cb')) {
			recountAll();
		}
	});

	// Precio input cambio: actualizar badge en vivo.
	tbody.addEventListener('input', function (e) {
		if (e.target.classList.contains('bgmg-precio-input')) {
			var row = e.target.closest('tr');
			var badge = row && row.querySelector('[data-badge]');
			if (badge) {
				var tipo = row.getAttribute('data-tipo');
				if (tipo === 'custom') {
					var val = parseInt(e.target.value || '0', 10);
					badge.textContent = val > 0 ? formatCLP(val) : '—';
				}
			}
		}
	});

	// Filtros.
	filters.forEach(function (btn) {
		btn.addEventListener('click', function () {
			filters.forEach(function (b) { b.classList.remove('is-active'); });
			btn.classList.add('is-active');
			activeFilter = btn.getAttribute('data-filter') || 'all';
			applyFiltersAndSearch();
		});
	});

	// Buscador (instantáneo, sin debounce porque son solo 52 filas).
	if (searchInp) {
		searchInp.addEventListener('input', function () {
			var val = (searchInp.value || '').toLowerCase().trim();
			// Quitar acentos para que "vitacura" matche "Vitacura" y "ñunoa" matche "Ñuñoa".
			val = val.normalize('NFD').replace(/[̀-ͯ]/g, '');
			searchTerm = val;
			applyFiltersAndSearch();
		});
	}

	// Inicialización: aplicar estado de cada fila para que badges + disabled
	// estén correctos si la página recarga después de guardar.
	rows.forEach(applyRowState);
	recountAll();
})();
