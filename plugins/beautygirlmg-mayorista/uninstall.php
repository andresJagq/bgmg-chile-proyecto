<?php
/**
 * Uninstall — solo se ejecuta cuando el admin elimina el plugin desde
 * "Plugins > Eliminar" (no en desactivación).
 *
 * Comportamiento por defecto: NO borra datos. Esto permite reinstalar el
 * plugin sin perder la configuración de productos.
 *
 * Para forzar limpieza completa, define la constante antes de desinstalar:
 *
 *   define( 'BGM_UNINSTALL_LIMPIAR_DATOS', true );
 *
 * (por ejemplo, en wp-config.php). La limpieza:
 *   - Borra todas las opciones bgm_*
 *   - Borra todos los post_meta _bgm_*
 *   - Borra el directorio uploads/bgm-logs/
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

if ( ! defined( 'BGM_UNINSTALL_LIMPIAR_DATOS' ) || ! BGM_UNINSTALL_LIMPIAR_DATOS ) {
    return;
}

global $wpdb;

// ─── Opciones ────────────────────────────────────────────────────────────────
$opciones = [
    'bgm_min_global_1',
    'bgm_min_global_2',
    'bgm_tolerancia_porcentaje',
    'bgm_modo_surtido',
    'bgm_debug_activo',
];
foreach ( $opciones as $opt ) {
    delete_option( $opt );
}

// ─── Post meta ───────────────────────────────────────────────────────────────
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_bgm\_%'" );

// ─── Directorio de logs ──────────────────────────────────────────────────────
$upload = wp_upload_dir();
$dir    = trailingslashit( $upload['basedir'] ) . 'bgm-logs';
if ( is_dir( $dir ) ) {
    foreach ( [ 'bgm.log', '.htaccess', 'index.php' ] as $f ) {
        $path = $dir . '/' . $f;
        if ( file_exists( $path ) ) @unlink( $path );
    }
    @rmdir( $dir );
}
