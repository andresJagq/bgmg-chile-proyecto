/**
 * Botón "📋 Copiar" del código de tracking en frontend.
 *
 * Se usa en:
 *   - Mi cuenta → Detalle del pedido
 *   - Thank you page (después de pagar)
 *
 * Sin link al courier por diseño (Opción C): el código puede venir de
 * Starken, Chilexpress, moto propia, Uber, etc., y mantener una whitelist
 * de URLs sería frágil. El botón se limita a copiar — la cliente decide
 * en qué sitio lo pega.
 */
(function () {
	'use strict';

	function flash(btn, msg) {
		var orig = btn.innerHTML;
		btn.innerHTML = msg;
		btn.disabled = true;
		setTimeout(function () {
			btn.innerHTML = orig;
			btn.disabled = false;
		}, 1500);
	}

	function copiar(texto) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(texto);
		}
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
				ok ? resolve() : reject();
			} catch (err) {
				reject(err);
			}
		});
	}

	function onClick(e) {
		var btn = e.currentTarget;
		var codigo = btn.getAttribute('data-codigo') || '';
		if (!codigo) return;
		copiar(codigo).then(
			function () { flash(btn, '✅ Copiado'); },
			function () { flash(btn, '⚠️'); }
		);
	}

	function init() {
		var btns = document.querySelectorAll('.bgmg-chile-copiar-tracking');
		Array.prototype.forEach.call(btns, function (b) {
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
