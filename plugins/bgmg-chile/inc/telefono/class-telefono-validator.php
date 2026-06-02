<?php
/**
 * Validador de teléfono móvil chileno.
 *
 * Reglas:
 *   - Solo móviles: el número significativo es 9 dígitos y empieza con 9.
 *   - Aceptamos cualquier prefijo internacional razonable (+56, 56, 0056) o
 *     ningún prefijo. Lo normalizamos y devolvemos siempre el formato:
 *
 *       +56 9 XXXX XXXX
 *
 *   - No aceptamos fijos (códigos de área 2, 32, 41, etc.) por decisión
 *     del 2026-05-16: el negocio coordina despachos por WhatsApp y necesita
 *     un número móvil obligatorio.
 *
 * Clase PURA: sin dependencia de WP/WC, testeable aislada.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BGMG_Chile_Telefono_Validator {

	/**
	 * Quita TODO lo que no sea dígito. Conserva el orden original.
	 * Útil para procesar prefijos.
	 *
	 * @param string $telefono
	 * @return string Solo dígitos.
	 */
	public static function normalize_digits( $telefono ) {
		$telefono = is_scalar( $telefono ) ? (string) $telefono : '';
		return preg_replace( '/\D+/', '', $telefono );
	}

	/**
	 * Extrae el número significativo chileno (9 dígitos empezando con 9)
	 * desde cualquier formato razonable de entrada. Devuelve '' si no es
	 * un móvil chileno reconocible.
	 *
	 * Lógica:
	 *   - Después de quitar todo lo que no sea dígito:
	 *     - Si quedan 9 dígitos y empieza con "9" → es el número.
	 *     - Si quedan 11 dígitos y empieza con "569" → quitamos "56".
	 *     - Si quedan 12 o más y empieza con "0056" → quitamos "0056".
	 *     - Cualquier otra cosa → no es móvil chileno válido.
	 *
	 * @param string $telefono
	 * @return string '9XXXXXXXX' o ''.
	 */
	public static function extract_movil( $telefono ) {

		$d = self::normalize_digits( $telefono );

		if ( '' === $d ) {
			return '';
		}

		// Caso "0056 9 XXXX XXXX": 13 dígitos.
		if ( 13 === strlen( $d ) && 0 === strpos( $d, '00569' ) ) {
			$d = substr( $d, 4 ); // quita "0056"
		}

		// Caso "+56 9 XXXX XXXX" o "56 9 XXXX XXXX": 11 dígitos.
		if ( 11 === strlen( $d ) && 0 === strpos( $d, '569' ) ) {
			$d = substr( $d, 2 ); // quita "56"
		}

		// Lo que queremos: 9 dígitos empezando con 9.
		if ( 9 === strlen( $d ) && '9' === $d[0] ) {
			return $d;
		}

		return '';
	}

	/**
	 * ¿El input corresponde a un móvil chileno válido?
	 *
	 * @param string $telefono
	 * @return bool
	 */
	public static function is_valid_movil( $telefono ) {
		return '' !== self::extract_movil( $telefono );
	}

	/**
	 * Formato canónico para guardar y mostrar: "+56 9 XXXX XXXX".
	 * Devuelve cadena vacía si la entrada no es móvil chileno válido.
	 *
	 * @param string $telefono
	 * @return string
	 */
	public static function format_internacional( $telefono ) {
		$movil = self::extract_movil( $telefono );
		if ( '' === $movil ) {
			return '';
		}
		// $movil tiene 9 dígitos: 9XXXXXXXX
		return '+56 ' . $movil[0] . ' ' . substr( $movil, 1, 4 ) . ' ' . substr( $movil, 5, 4 );
	}

	/**
	 * Formato e164 puro (para APIs que lo requieran): "+56912345678".
	 *
	 * @param string $telefono
	 * @return string
	 */
	public static function format_e164( $telefono ) {
		$movil = self::extract_movil( $telefono );
		if ( '' === $movil ) {
			return '';
		}
		return '+56' . $movil;
	}
}
