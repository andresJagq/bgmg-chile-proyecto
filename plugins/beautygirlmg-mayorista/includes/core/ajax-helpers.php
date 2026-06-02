<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================
 * MÓDULO: AJAX HELPERS COMPARTIDOS
 *
 * Helpers de respuesta usados por ajax-auto, ajax-manual y
 * ajax-evaluar. Antes vivían en ajax-auto.php, pero en modo
 * "manual" puro ajax-auto NO se cargaba y los manual fallaban
 * con fatal por función indefinida.
 *
 * Cargado SIEMPRE desde el bootstrap.
 * =========================================================
 */

if ( ! function_exists( 'bgm_ajax_responder_exito' ) ) {
    function bgm_ajax_responder_exito( $modo, $data = [] ) {
        // Si el tema expone bgmg_minicart_inner(), inyectar su HTML para que
        // el side-cart del tema (bgmg-landing) se actualice tras el add-to-cart.
        if ( ! isset( $data['minicart_html'] ) && function_exists( 'bgmg_minicart_inner' ) ) {
            ob_start();
            bgmg_minicart_inner();
            $data['minicart_html'] = ob_get_clean();
        }

        $data['_debug'] = [
            'modo'    => $modo,
            'version' => BGM_VERSION,
        ];
        wp_send_json_success( $data );
    }
}

if ( ! function_exists( 'bgm_ajax_responder_error' ) ) {
    function bgm_ajax_responder_error( $modo, $message, $status = 200 ) {
        $resp = [
            'message' => $message,
            '_debug'  => [
                'modo'    => $modo,
                'version' => BGM_VERSION,
            ],
        ];
        wp_send_json_error( $resp, $status );
    }
}

if ( ! function_exists( 'bgm_rate_limit_exceeded' ) ) {
    /**
     * Rate-limiting básico por IP+acción para los endpoints AJAX públicos (nopriv).
     * Devuelve true si la IP superó el límite en la ventana de 1 minuto.
     * Transient por IP+acción (TTL 60s). Espejo del helper del tema
     * (bgmg-landing → bgmg_rate_limit_exceeded), pero autónomo en este plugin.
     *
     * @param string $accion         Clave de la acción ('auto'|'manual'|'evaluar').
     * @param int    $max_por_minuto Máx. llamadas/minuto antes de bloquear.
     * @return bool
     */
    function bgm_rate_limit_exceeded( $accion, $max_por_minuto = 30 ) {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        $ip = preg_replace( '/[^0-9a-fA-F:.]/', '', $ip ); // sanitiza IP (v4/v6)
        $key = 'bgm_rl_' . md5( $accion . '|' . $ip );
        $count = (int) get_transient( $key );
        if ( $count >= $max_por_minuto ) {
            return true;
        }
        set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
        return false;
    }
}
