<?php
/**
 * Validador de RUT chileno.
 *
 * Implementa:
 *   - Normalización (quita puntos, guiones, espacios; uppercase a la K).
 *   - Formateo "12.345.678-9" / "12.345.678-K".
 *   - Verificación del dígito verificador con algoritmo módulo 11.
 *   - Detección si el RUT es persona natural o empresa por rango numérico.
 *
 * El algoritmo módulo 11 está definido por el Servicio de Impuestos Internos (SII)
 * de Chile. Es exactamente el mismo para personas naturales y empresas: lo único
 * que cambia es el rango de numeración.
 *
 * Reglas SII para tipo:
 *   - Persona natural:   1.000.000   ≤ N ≤ 49.999.999
 *   - Empresa / jurídica: 50.000.000 ≤ N
 *   (los RUT 1.000.000–4.999.999 son antiguos pero válidos)
 *
 * Esta clase es PURA: no toca WP, no toca WC. Eso permite testearla aislada
 * y reusarla desde cualquier submódulo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BGMG_Chile_RUT_Validator {

	/**
	 * Normaliza un RUT a "NNNNNNNNK" (sin puntos, sin guion, K en mayúscula).
	 * Útil para guardar en BD y comparar.
	 *
	 * @param string $rut
	 * @return string
	 */
	public static function normalize( $rut ) {
		$rut = is_scalar( $rut ) ? (string) $rut : '';
		$rut = strtoupper( $rut );
		// Quitamos cualquier caracter que no sea dígito o K.
		$rut = preg_replace( '/[^0-9K]/', '', $rut );
		return (string) $rut;
	}

	/**
	 * Da formato visual "12.345.678-9" a partir de cualquier entrada razonable.
	 * Si la entrada no es un RUT válido en estructura, devuelve cadena vacía.
	 *
	 * @param string $rut
	 * @return string
	 */
	public static function format( $rut ) {
		$normalizado = self::normalize( $rut );

		// El RUT mínimo válido tiene 2 caracteres (1 dígito + DV).
		if ( strlen( $normalizado ) < 2 ) {
			return '';
		}

		$cuerpo = substr( $normalizado, 0, -1 );
		$dv     = substr( $normalizado, -1 );

		// Solo dígitos en el cuerpo. La K solo es válida como DV.
		if ( ! ctype_digit( $cuerpo ) ) {
			return '';
		}

		// Insertamos puntos cada 3 dígitos desde la derecha.
		$cuerpo_formateado = number_format( (int) $cuerpo, 0, '', '.' );

		return $cuerpo_formateado . '-' . $dv;
	}

	/**
	 * Calcula el dígito verificador esperado para un cuerpo numérico.
	 * Algoritmo módulo 11 oficial del SII.
	 *
	 * @param string $cuerpo Solo dígitos, sin DV.
	 * @return string '0'-'9' o 'K'.
	 */
	public static function calculate_dv( $cuerpo ) {
		$cuerpo = (string) $cuerpo;
		if ( ! ctype_digit( $cuerpo ) || '' === $cuerpo ) {
			return '';
		}

		// Multiplicador cíclico 2..7 desde el dígito menos significativo.
		$suma         = 0;
		$multiplicador = 2;
		for ( $i = strlen( $cuerpo ) - 1; $i >= 0; $i-- ) {
			$suma          += ( (int) $cuerpo[ $i ] ) * $multiplicador;
			$multiplicador  = ( 7 === $multiplicador ) ? 2 : $multiplicador + 1;
		}

		$resto = 11 - ( $suma % 11 );

		// Casos especiales del SII.
		if ( 11 === $resto ) {
			return '0';
		}
		if ( 10 === $resto ) {
			return 'K';
		}
		return (string) $resto;
	}

	/**
	 * Valida un RUT completo (cuerpo + DV).
	 *
	 * @param string $rut Cualquier formato.
	 * @return bool true si pasa módulo 11 y tiene estructura razonable.
	 */
	public static function is_valid( $rut ) {
		$normalizado = self::normalize( $rut );

		// Mínimo razonable: 7 caracteres (RUT 100.000-9). Por debajo
		// son RUT de prueba o errores tipográficos.
		if ( strlen( $normalizado ) < 7 || strlen( $normalizado ) > 9 ) {
			return false;
		}

		$cuerpo = substr( $normalizado, 0, -1 );
		$dv     = substr( $normalizado, -1 );

		if ( ! ctype_digit( $cuerpo ) ) {
			return false;
		}

		// El cuerpo no puede ser todo ceros ni empezar con cero.
		if ( '0' === $cuerpo[0] ) {
			return false;
		}

		return self::calculate_dv( $cuerpo ) === $dv;
	}

	/**
	 * Determina si el RUT corresponde a persona natural o empresa.
	 *
	 * @param string $rut
	 * @return string 'natural' | 'empresa' | 'desconocido'
	 */
	public static function tipo( $rut ) {
		if ( ! self::is_valid( $rut ) ) {
			return 'desconocido';
		}

		$normalizado = self::normalize( $rut );
		$numero      = (int) substr( $normalizado, 0, -1 );

		if ( $numero >= 50000000 ) {
			return 'empresa';
		}
		if ( $numero >= 1000000 ) {
			return 'natural';
		}
		// RUTs antiguos < 1.000.000: los tratamos como natural por compatibilidad.
		return 'natural';
	}

	/**
	 * Compara dos RUT como iguales independiente del formato de entrada.
	 *
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	public static function equals( $a, $b ) {
		return self::normalize( $a ) === self::normalize( $b ) && '' !== self::normalize( $a );
	}
}
