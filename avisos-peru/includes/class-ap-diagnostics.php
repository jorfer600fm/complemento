<?php
/**
 * Gestiona la página de Estado y Diagnóstico del plugin.
 * v1.0.0
 *
 * @package Avisos_Peru
 */
class AP_Diagnostics {

    public function init() {
        add_action('admin_menu', [$this, 'add_diagnostics_page']);
        add_action('wp_ajax_ap_test_email_sending', [$this, 'test_email_sending']);
    }

    public function add_diagnostics_page() {
        add_submenu_page(
            'edit.php?post_type=aviso',
            'Estado y Diagnóstico',
            'Estado y Diagnóstico',
            'manage_options',
            'ap-diagnostics',
            [$this, 'render_diagnostics_page']
        );
    }

    public function render_diagnostics_page() {
        // Recopilamos todos los datos de diagnóstico aquí
        $data = [
            'api_check' => $this->check_gemini_api(),
            'cron_check' => $this->check_cron_status(),
            'post_counts' => $this->get_post_counts(),
            'error_log' => $this->get_error_log(),
        ];
        
        // Incluimos la vista que mostrará los datos
        include_once AP_PLUGIN_PATH . 'admin/partials/ap-diagnostics-display.php';
    }

    /**
     * Realiza una prueba de conexión simple a la API de Gemini.
     */
    private function check_gemini_api() {
        $options = get_option('ap_options');
        $api_key = isset($options['api_key']) ? trim($options['api_key']) : '';

        if (empty($api_key)) {
            return ['status' => 'error', 'message' => 'No se ha configurado una API Key en los ajustes del plugin.'];
        }

        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;
        $response = wp_remote_get($api_url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'message' => 'Error de conexión con el servidor de Google. Detalles: ' . $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            return ['status' => 'ok', 'message' => 'La API Key es válida y la conexión con Google AI es correcta.'];
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = $body['error']['message'] ?? 'Respuesta desconocida del servidor.';
            return ['status' => 'error', 'message' => 'La API Key parece ser inválida o hay un problema de permisos. Código de error: ' . $status_code . '. Mensaje: ' . esc_html($error_message)];
        }
    }

    /**
     * Verifica el estado de las tareas programadas (Cron).
     */
    private function check_cron_status() {
        $next_run = wp_next_scheduled('ap_daily_maintenance_tasks');
        if ($next_run) {
            // Añadir el 'offset' de WordPress para mostrar la hora local correcta.
            $local_time = $next_run + (get_option('gmt_offset') * HOUR_IN_SECONDS);
            return ['status' => 'ok', 'message' => 'La tarea de mantenimiento diario está programada. Próxima ejecución: ' . date_i18n('d-m-Y H:i:s', $local_time)];
        } else {
            return ['status' => 'warning', 'message' => '¡Atención! La tarea de mantenimiento diario no está programada. Desactiva y reactiva el plugin para intentar solucionarlo.'];
        }
    }

    /**
     * Obtiene los contadores de avisos.
     */
    private function get_post_counts() {
        return [
            'pending' => wp_count_posts('aviso')->pending,
            'publish' => wp_count_posts('aviso')->publish,
            'unpaid' => $this->get_unpaid_ads_count(),
        ];
    }
    
    private function get_unpaid_ads_count() {
        $args = [
            'post_type' => 'aviso',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_ap_pago_confirmado',
                    'value' => 'yes',
                    'compare' => '!=',
                ],
                 [
                    'key' => '_ap_pago_confirmado',
                    'compare' => 'NOT EXISTS',
                ],
                'relation' => 'OR'
            ],
            'fields' => 'ids',
        ];
        $query = new WP_Query($args);
        return $query->post_count;
    }

    /**
     * Lee el registro de errores.
     */
    private function get_error_log() {
        $log = get_option('ap_error_log', []);
        // Mostrar los errores más recientes primero
        return array_reverse($log);
    }

    /**
     * Gestiona la petición AJAX para la prueba de envío de correo.
     */
    public function test_email_sending() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No tienes permisos para esta acción.']);
        }
        check_ajax_referer('ap_diagnostics_nonce', 'nonce');

        $admin_email = get_option('admin_email');
        $subject = 'Correo de Prueba desde "Avisos Perú"';
        $body = '¡Hola! Si has recibido este correo, significa que el sistema de envío de emails de tu sitio WordPress funciona correctamente. No es necesario responder a este mensaje. Saludos, El equipo de Avisos Perú.';
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $sent = wp_mail($admin_email, $subject, $body, $headers);

        if ($sent) {
            wp_send_json_success(['message' => '¡Correo de prueba enviado con éxito a ' . $admin_email . '! Revisa tu bandeja de entrada (y la carpeta de spam).']);
        } else {
            global $ts_mail_errors;
            $error_message = 'El sistema de WordPress (wp_mail) no pudo enviar el correo. ';
            if (!empty($ts_mail_errors)) {
                 $error_message .= 'Detalles del error: ' . implode(', ', $ts_mail_errors);
            } else {
                $error_message .= 'Esto suele deberse a una mala configuración del servidor. Se recomienda usar un plugin de SMTP como "WP Mail SMTP" para solucionar este problema.';
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }
    
    /**
     * Registra un error en el log.
     */
    public static function log_error($message) {
        $log = get_option('ap_error_log', []);
        
        // Mantener solo los últimos 20 errores para no sobrecargar la base de datos
        if (count($log) >= 20) {
            array_shift($log); // Elimina el error más antiguo
        }

        $log[] = [
            'timestamp' => current_time('mysql'),
            'message'   => $message,
        ];
        
        update_option('ap_error_log', $log);
    }
}