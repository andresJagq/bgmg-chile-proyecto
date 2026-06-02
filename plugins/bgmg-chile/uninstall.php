<?php
/**
 * Desinstalación de BGMG Chile.
 *
 * Se ejecuta SOLO cuando el usuario elimina el plugin desde wp-admin
 * (no en desactivación). Aquí limpiamos datos persistentes.
 *
 * NOTA: NO borramos los meta de cliente/orden (_bgmg_rut, etc.) porque
 * son datos del negocio. La dueña los quiere conservar aunque el plugin
 * se desinstale. Solo borramos la tabla de tarifas RM y opciones.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Tabla de tarifas RM.
$tabla = $wpdb->prefix . 'bgmg_chile_tarifas_rm';
$wpdb->query( "DROP TABLE IF EXISTS {$tabla}" ); // phpcs:ignore WordPress.DB

// Opciones del plugin.
delete_option( 'bgmg_chile_version' );
delete_option( 'bgmg_chile_settings' );
