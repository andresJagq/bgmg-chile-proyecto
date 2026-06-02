/**
 * Botón "Copiar todo" del metabox "🏷️ Datos de despacho".
 *
 * Lee el textarea oculto (data-for-order) con el texto plano de los 8
 * campos y lo manda al clipboard. Si la API moderna falla (navegador viejo
 * o contexto inseguro), cae a document.execCommand('copy') como fallback.
 *
 * Sin dependencia de jQuery: ligero, una sola pantalla.
 */
(function () {
	'use strict';

	function flashLabel(btn, msg) {
		var original = btn.innerHTML;
		btn.innerHTML = msg;
		btn.disabled = true;
		setTimeout(function () {
			btn.innerHTML = original;
			btn.disabled = false;
		}, 1500);
	}

	function copiarTexto(texto) {
		// API moderna (HTTPS / admin de WP).
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(texto);
		}
		// Fallback: textarea temporal + execCommand. Devolvemos una promesa
		// para que el caller no tenga que distinguir.
		return new Promise(function (resolve, reject) {
			try {
				var ta = document.createElement('textarea');
				ta.value = texto;
				ta.style.position = 'fixed';
				ta.style.left = '-9999px';
				document.body.appendChild(ta);
				ta.select();
				var ok = document.execCommand('copy');
				document.body.removeChild(ta);
				ok ? resolve() : reject(new Error('execCommand devolvió false'));
			} catch (err) {
				reject(err);
			}
		});
	}

	function onClick(e) {
		var btn = e.currentTarget;
		var orderId = btn.getAttribute('data-order-id');
		var ta = document.querySelector(
			'.bgmg-chile-etiqueta-texto-plano[data-for-order="' + orderId + '"]'
		);
		if (!ta) return;

		copiarTexto(ta.value).then(
			function () { flashLabel(btn, '✅ Copiado'); },
			function () { flashLabel(btn, '⚠️ No se pudo'); }
		);
	}

	function init() {
		var buttons = document.querySelectorAll('.bgmg-chile-copiar-datos-despacho');
		Array.prototype.forEach.call(buttons, function (b) {
			b.removeEventListener('click', onClick);
			b.addEventListener('click', onClick);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
