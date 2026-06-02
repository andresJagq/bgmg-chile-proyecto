/**
 * Comportamiento JS del checkout para el módulo RUT + toggle factura.
 *
 *   - Liga el campo RUT al validador BgmgChileRut (live feedback).
 *   - Muestra/oculta los campos de empresa según el checkbox "Necesito factura".
 *   - Si el RUT detectado es de empresa y el toggle está apagado, sugiere
 *     marcarlo (aviso suave, no bloqueante).
 *
 * Dependencias: jquery (lo usa WC para el checkout) + bgmg-chile-rut-validator.
 */
(function ($) {
	'use strict';

	function $form() {
		return $('form.checkout, form.woocommerce-checkout');
	}

	function getRutInput() {
		return document.getElementById('billing_bgmg_rut');
	}

	function getFacturaToggle() {
		return document.getElementById('billing_bgmg_necesita_factura');
	}

	function empresaFieldSelector() {
		return '.bgmg-chile-empresa-field';
	}

	/**
	 * Mostrar u ocultar el bloque de campos de empresa según el toggle.
	 */
	function toggleEmpresaFields(visible) {
		var $fields = $(empresaFieldSelector());
		if (visible) {
			$fields.show().find('input').prop('disabled', false);
		} else {
			$fields.hide().find('input').prop('disabled', false); // no los deshabilitamos para que se posteen vacíos si la usuaria cambia de opinión
		}
	}

	/**
	 * Aviso suave: el RUT parece de empresa pero el toggle está apagado.
	 * Lo mostramos como un mensaje inline debajo del checkbox, no bloquea.
	 */
	function avisoEmpresa(activo) {
		var $toggle = $(getFacturaToggle()).closest('.form-row');
		if (!$toggle.length) return;

		var $aviso = $toggle.find('.bgmg-chile-aviso-empresa');
		if (activo) {
			if (!$aviso.length) {
				$aviso = $(
					'<small class="bgmg-chile-aviso-empresa" style="display:block;margin-top:4px;color:#C4728A;">' +
						'El RUT que ingresaste parece corresponder a una empresa. ¿Quieres factura?' +
						'</small>'
				);
				$toggle.append($aviso);
			}
		} else {
			$aviso.remove();
		}
	}

	function init() {
		var rutInput = getRutInput();
		var toggle = getFacturaToggle();
		if (!rutInput || !window.BgmgChileRut) return;

		// Estado inicial del toggle (puede venir marcado de una recarga).
		toggleEmpresaFields(toggle && toggle.checked);

		// Live binding del validador.
		window.BgmgChileRut.bindInput(rutInput, {
			onValidate: function (valid, tipo) {
				if (valid && tipo === 'empresa' && toggle && !toggle.checked) {
					avisoEmpresa(true);
				} else {
					avisoEmpresa(false);
				}
			}
		});

		// Toggle de factura.
		if (toggle) {
			$(toggle).on('change', function () {
				toggleEmpresaFields(this.checked);
				// Si lo activan, removemos el aviso ya redundante.
				if (this.checked) avisoEmpresa(false);
			});
		}
	}

	// El checkout de WC vuelve a renderizar el form vía AJAX en cada update_checkout.
	// Por eso reinicializamos en ese evento.
	$(document.body).on('updated_checkout', init);
	$(document).ready(init);
})(jQuery);
