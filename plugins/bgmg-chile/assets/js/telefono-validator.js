/**
 * Validador de teléfono móvil chileno — espejo JS de
 * inc/telefono/class-telefono-validator.php.
 *
 * Expone window.BgmgChileTelefono con la misma API que la clase PHP:
 *   normalizeDigits, extractMovil, isValidMovil,
 *   formatInternacional, formatE164, bindInput.
 *
 * Sin dependencias. Misma lógica que PHP para evitar discrepancias
 * UX/seguridad.
 */
(function (window) {
	'use strict';

	function normalizeDigits(tel) {
		if (tel === null || tel === undefined) return '';
		return String(tel).replace(/\D+/g, '');
	}

	function extractMovil(tel) {
		var d = normalizeDigits(tel);
		if (d === '') return '';

		// "0056 9 ..." → 13 dígitos
		if (d.length === 13 && d.indexOf('00569') === 0) {
			d = d.substring(4);
		}
		// "+56 9 ..." o "56 9 ..." → 11 dígitos
		if (d.length === 11 && d.indexOf('569') === 0) {
			d = d.substring(2);
		}
		// 9 dígitos empezando con 9
		if (d.length === 9 && d.charAt(0) === '9') {
			return d;
		}
		return '';
	}

	function isValidMovil(tel) {
		return extractMovil(tel) !== '';
	}

	function formatInternacional(tel) {
		var m = extractMovil(tel);
		if (m === '') return '';
		return '+56 ' + m.charAt(0) + ' ' + m.substring(1, 5) + ' ' + m.substring(5, 9);
	}

	function formatE164(tel) {
		var m = extractMovil(tel);
		if (m === '') return '';
		return '+56' + m;
	}

	/**
	 * Liga un input al validador: formatea al blur, valida y muestra
	 * clase visual de error/éxito.
	 *
	 * @param {HTMLInputElement} input
	 * @param {Object} options
	 *   - errorTarget: nodo donde imprimir el mensaje de error
	 *   - onValidate(valid): callback opcional
	 */
	function bindInput(input, options) {
		if (!input) return null;
		options = options || {};

		function clear() {
			input.classList.remove('bgmg-tel-error');
			input.classList.remove('bgmg-tel-ok');
			if (options.errorTarget) {
				options.errorTarget.textContent = '';
				options.errorTarget.style.display = 'none';
			}
		}

		function err(msg) {
			input.classList.add('bgmg-tel-error');
			input.classList.remove('bgmg-tel-ok');
			if (options.errorTarget) {
				options.errorTarget.textContent = msg;
				options.errorTarget.style.display = 'block';
			}
		}

		function ok() {
			input.classList.remove('bgmg-tel-error');
			input.classList.add('bgmg-tel-ok');
			if (options.errorTarget) {
				options.errorTarget.textContent = '';
				options.errorTarget.style.display = 'none';
			}
		}

		function validateNow() {
			var val = input.value || '';
			if (val.trim() === '') {
				clear();
				if (options.onValidate) options.onValidate(false);
				return false;
			}
			var formatted = formatInternacional(val);
			if (formatted !== '') {
				input.value = formatted;
				ok();
				if (options.onValidate) options.onValidate(true);
				return true;
			}
			err(
				(window.BGMG_CHILE_I18N && window.BGMG_CHILE_I18N.telefonoInvalido) ||
					'Ingresa un móvil chileno válido (+56 9 XXXX XXXX).'
			);
			if (options.onValidate) options.onValidate(false);
			return false;
		}

		input.addEventListener('blur', validateNow);
		input.addEventListener('input', clear);

		return { validate: validateNow, clear: clear };
	}

	window.BgmgChileTelefono = {
		normalizeDigits: normalizeDigits,
		extractMovil: extractMovil,
		isValidMovil: isValidMovil,
		formatInternacional: formatInternacional,
		formatE164: formatE164,
		bindInput: bindInput
	};
})(window);
