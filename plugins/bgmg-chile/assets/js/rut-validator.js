/**
 * Validador de RUT chileno — versión JavaScript (cliente).
 *
 * Espejo exacto de inc/rut/class-rut-validator.php. La validación viaja
 * dos veces (UX en JS + seguridad en PHP) y ambos lados deben coincidir,
 * o el usuario verá "todo bien" y al enviar saldrá un error confuso.
 *
 * Expone window.BgmgChileRut con la misma API que la clase PHP.
 * Sin dependencias: vanilla JS, funciona en todos los navegadores que WC ya soporta.
 */
(function (window) {
	'use strict';

	function normalize(rut) {
		if (rut === null || rut === undefined) return '';
		return String(rut).toUpperCase().replace(/[^0-9K]/g, '');
	}

	function format(rut) {
		var norm = normalize(rut);
		if (norm.length < 2) return '';

		var cuerpo = norm.slice(0, -1);
		var dv = norm.slice(-1);

		if (!/^\d+$/.test(cuerpo)) return '';

		// Insertamos puntos cada 3 dígitos desde la derecha (locale-independent).
		var conPuntos = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
		return conPuntos + '-' + dv;
	}

	function calculateDv(cuerpo) {
		cuerpo = String(cuerpo);
		if (!/^\d+$/.test(cuerpo) || cuerpo === '') return '';

		var suma = 0;
		var multiplicador = 2;
		for (var i = cuerpo.length - 1; i >= 0; i--) {
			suma += parseInt(cuerpo.charAt(i), 10) * multiplicador;
			multiplicador = multiplicador === 7 ? 2 : multiplicador + 1;
		}
		var resto = 11 - (suma % 11);
		if (resto === 11) return '0';
		if (resto === 10) return 'K';
		return String(resto);
	}

	function isValid(rut) {
		var norm = normalize(rut);
		if (norm.length < 7 || norm.length > 9) return false;

		var cuerpo = norm.slice(0, -1);
		var dv = norm.slice(-1);
		if (!/^\d+$/.test(cuerpo)) return false;
		if (cuerpo.charAt(0) === '0') return false;

		return calculateDv(cuerpo) === dv;
	}

	function tipo(rut) {
		if (!isValid(rut)) return 'desconocido';
		var norm = normalize(rut);
		var numero = parseInt(norm.slice(0, -1), 10);
		if (numero >= 50000000) return 'empresa';
		return 'natural';
	}

	function equals(a, b) {
		var na = normalize(a);
		var nb = normalize(b);
		return na !== '' && na === nb;
	}

	/**
	 * Liga un input de RUT al validador: formateo en blur, validación en blur,
	 * pinta clase de error o éxito. Devuelve un objeto con métodos para
	 * el caller en caso de querer chequear el estado.
	 *
	 * @param {HTMLInputElement} input
	 * @param {Object} options
	 *   - errorTarget: nodo donde renderizar el mensaje de error
	 *   - onValidate(valid, tipo): callback para reaccionar
	 */
	function bindInput(input, options) {
		if (!input) return null;
		options = options || {};

		function clearError() {
			input.classList.remove('bgmg-rut-error');
			input.classList.remove('bgmg-rut-ok');
			if (options.errorTarget) {
				options.errorTarget.textContent = '';
				options.errorTarget.style.display = 'none';
			}
		}

		function showError(msg) {
			input.classList.add('bgmg-rut-error');
			input.classList.remove('bgmg-rut-ok');
			if (options.errorTarget) {
				options.errorTarget.textContent = msg;
				options.errorTarget.style.display = 'block';
			}
		}

		function showOk() {
			input.classList.remove('bgmg-rut-error');
			input.classList.add('bgmg-rut-ok');
			if (options.errorTarget) {
				options.errorTarget.textContent = '';
				options.errorTarget.style.display = 'none';
			}
		}

		function validateNow() {
			var val = input.value || '';
			if (val.trim() === '') {
				clearError();
				if (options.onValidate) options.onValidate(false, 'desconocido');
				return false;
			}

			input.value = format(val); // visual feedback

			var ok = isValid(val);
			if (ok) {
				showOk();
			} else {
				showError(
					(window.BGMG_CHILE_I18N && window.BGMG_CHILE_I18N.rutInvalido) ||
						'RUT inválido. Verifica el dígito verificador.'
				);
			}
			var t = tipo(val);
			if (options.onValidate) options.onValidate(ok, t);
			return ok;
		}

		input.addEventListener('blur', validateNow);
		input.addEventListener('input', function () {
			// Mientras escribe, limpiamos el estado y dejamos que blur valide.
			clearError();
		});

		return {
			validate: validateNow,
			clear: clearError
		};
	}

	window.BgmgChileRut = {
		normalize: normalize,
		format: format,
		calculateDv: calculateDv,
		isValid: isValid,
		tipo: tipo,
		equals: equals,
		bindInput: bindInput
	};
})(window);
