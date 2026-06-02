<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: LOGGER
 *
 * Logger central con marca de modo (auto/manual/core).
 * Solo escribe si la opción 'bgm_debug_activo' está en '1'.
 *
 * Uso:
 *   bgm_log( 'auto',   'Variación elegida', [ 'id' => 42 ] );
 *   bgm_log( 'manual', 'Excedió límite',    [ 'qty' => 5, 'max' => 4 ] );
 *   bgm_log( 'core',   'Aplicado nivel 2',  [ 'qty' => 14 ] );
 * =========================================================
 */

// ─── Ruta del directorio de logs ─────────────────────────────────────────────
function bgm_logger_dir() {
    $upload = wp_upload_dir();
    return trailingslashit( $upload['basedir'] ) . 'bgm-logs';
}

function bgm_logger_file() {
    return bgm_logger_dir() . '/bgm.log';
}

// ─── Crear directorio + .htaccess de protección ──────────────────────────────
function bgm_logger_crear_directorio() {
    $dir = bgm_logger_dir();
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }

    // Protección: deny all (Apache)
    $htaccess = $dir . '/.htaccess';
    if ( ! file_exists( $htaccess ) ) {
        file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" );
    }

    // Protección: index.php vacío
    $index = $dir . '/index.php';
    if ( ! file_exists( $index ) ) {
        file_put_contents( $index, "<?php // Silence is golden.\n" );
    }
}

// ─── Tope máximo del archivo de log (bytes) ──────────────────────────────────
// Cuando se supera, el archivo se rota a bgm.log.1 (overwriting) y se
// reinicia. Evita que el log crezca indefinidamente si debug queda activo.
if ( ! defined( 'BGM_LOG_MAX_BYTES' ) ) {
    define( 'BGM_LOG_MAX_BYTES', 1024 * 1024 ); // 1 MB
}

// ─── Función principal de logging ────────────────────────────────────────────
function bgm_log( $modo, $mensaje, $contexto = [], $nivel = 'info' ) {
    if ( get_option( 'bgm_debug_activo', '0' ) !== '1' ) {
        return;
    }

    $modos_validos   = [ 'auto', 'manual', 'core', 'admin', 'cart' ];
    $niveles_validos = [ 'info', 'warning', 'error' ];

    if ( ! in_array( $modo, $modos_validos, true ) )     $modo   = 'core';
    if ( ! in_array( $nivel, $niveles_validos, true ) )  $nivel  = 'info';

    $file = bgm_logger_file();

    // Crear directorio si no existe (defensivo)
    if ( ! file_exists( dirname( $file ) ) ) {
        bgm_logger_crear_directorio();
    }

    // Rotar si excede el tope
    bgm_logger_rotar_si_necesario( $file );

    $timestamp = current_time( 'Y-m-d H:i:s' );
    $ctx_json  = ! empty( $contexto ) ? wp_json_encode( $contexto, JSON_UNESCAPED_UNICODE ) : '';

    $linea = sprintf(
        "[%s] [%s] [%s] %s %s\n",
        $timestamp,
        strtoupper( $nivel ),
        str_pad( $modo, 6 ),
        $mensaje,
        $ctx_json
    );

    $resultado = @file_put_contents( $file, $linea, FILE_APPEND | LOCK_EX );

    // Si falla la escritura (disco lleno, permisos), reportar UNA vez por request
    // al error_log del sistema para no perder señal silenciosamente.
    if ( $resultado === false ) {
        static $aviso_emitido = false;
        if ( ! $aviso_emitido ) {
            error_log( '[BGM] No se pudo escribir log en: ' . $file );
            $aviso_emitido = true;
        }
    }
}

/**
 * Rota el archivo a .1 si supera BGM_LOG_MAX_BYTES. Sin gzip, sin historial
 * extendido; basta para evitar logs descontrolados en producción.
 */
function bgm_logger_rotar_si_necesario( $file ) {
    if ( ! file_exists( $file ) ) return;

    $tam = @filesize( $file );
    if ( $tam === false || $tam < BGM_LOG_MAX_BYTES ) return;

    $rotado = $file . '.1';
    @rename( $file, $rotado ); // sobreescribe .1 si existía
}

// ─── Helpers de uso rápido ───────────────────────────────────────────────────
function bgm_log_warning( $modo, $mensaje, $contexto = [] ) {
    bgm_log( $modo, $mensaje, $contexto, 'warning' );
}

function bgm_log_error( $modo, $mensaje, $contexto = [] ) {
    bgm_log( $modo, $mensaje, $contexto, 'error' );
}

// ─── Lectura de logs (para panel admin) ──────────────────────────────────────
function bgm_logger_leer( $lineas = 200 ) {
    $file = bgm_logger_file();
    if ( ! file_exists( $file ) ) return '';

    $contenido = @file( $file, FILE_IGNORE_NEW_LINES );
    if ( ! $contenido ) return '';

    // Devolver últimas N líneas
    $contenido = array_slice( $contenido, -$lineas );
    return implode( "\n", $contenido );
}

// ─── Vaciar logs ─────────────────────────────────────────────────────────────
function bgm_logger_vaciar() {
    $file = bgm_logger_file();
    if ( file_exists( $file ) ) {
        @file_put_contents( $file, '' );
        return true;
    }
    return false;
}

// ─── Tamaño actual del archivo (KB) ──────────────────────────────────────────
function bgm_logger_tamano_kb() {
    $file = bgm_logger_file();
    if ( ! file_exists( $file ) ) return 0;
    return round( filesize( $file ) / 1024, 2 );
}
