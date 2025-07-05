<?php
/**
 * Gestiona las tareas programadas (WP-Cron) del plugin.
 *
 * @package Avisos_Peru
 */
class AP_Cron {

    public function init() {
        // Acciones que ejecutan las tareas programadas
        add_action( 'ap_check_unpaid_ad', [ $this, 'check_unpaid_ad_action' ], 10, 1 );
        add_action( 'ap_daily_maintenance_tasks', [ $this, 'run_daily_maintenance' ] );
        // Los hooks de activación/desactivación se han movido al archivo principal.
    }
    
    // --- Programación y Desprogramación de Tareas ---

    public static function activate_cron() {
        if ( ! wp_next_scheduled( 'ap_daily_maintenance_tasks' ) ) {
            wp_schedule_event( time(), 'daily', 'ap_daily_maintenance_tasks' );
        }
    }

    public static function deactivate_cron() {
        wp_clear_scheduled_hook( 'ap_daily_maintenance_tasks' );
    }

    public static function schedule_payment_check( $post_id ) {
        if ( ! wp_next_scheduled( 'ap_check_unpaid_ad', [ 'post_id' => $post_id ] ) ) {
            // Programar para 24 horas en el futuro
            wp_schedule_single_event( time() + DAY_IN_SECONDS, 'ap_check_unpaid_ad', [ 'post_id' => $post_id ] );
        }
    }

    public static function unschedule_payment_check( $post_id ) {
        $timestamp = wp_next_scheduled( 'ap_check_unpaid_ad', [ 'post_id' => $post_id ] );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ap_check_unpaid_ad', [ 'post_id' => $post_id ] );
        }
    }

    // --- Acciones de las Tareas Programadas ---

    /**
     * Se ejecuta 24h después de la aprobación para verificar el pago.
     * Procedimiento C.2
     */
    public function check_unpaid_ad_action( $post_id ) {
        if ( ! get_post( $post_id ) ) return;

        $payment_confirmed = get_post_meta( $post_id, '_ap_pago_confirmado', true );
        
        if ( 'yes' !== $payment_confirmed ) {
            $email_sender = new AP_Emails();
            $user_email = get_post_meta( $post_id, 'ap_email', true );

            if ( $user_email ) {
                $email_sender->send_notification( $user_email, 'ad_deleted_unpaid', $post_id );
            }
            // Borrado permanente e irreversible
            wp_delete_post( $post_id, true );
        }
    }

    /**
     * Ejecuta las tareas de mantenimiento diarias.
     * Procedimiento C.1
     */
    public function run_daily_maintenance() {
        $this->handle_expired_ads();
        $this->handle_old_trashed_ads();
    }

    /**
     * Mueve a la papelera los avisos que han superado su fecha de vencimiento.
     */
    private function handle_expired_ads() {
        $today = date( 'Y-m-d' );
        $args = [
            'post_type'      => 'aviso',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'ap_expiry_date',
                    'value'   => $today,
                    'compare' => '<',
                    'type'    => 'DATE',
                ],
            ],
        ];

        $expired_ads = get_posts( $args );

        foreach ( $expired_ads as $ad ) {
            wp_trash_post( $ad->ID );
        }
    }

    /**
     * Elimina permanentemente los avisos que llevan más de 5 días en la papelera.
     */
    private function handle_old_trashed_ads() {
        $five_days_ago = date( 'Y-m-d H:i:s', time() - ( 5 * DAY_IN_SECONDS ) );
        $args = [
            'post_type'      => 'aviso',
            'post_status'    => 'trash',
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'column' => 'post_modified_gmt',
                    'before' => $five_days_ago,
                ],
            ],
        ];

        $old_trashed_ads = get_posts( $args );

        foreach ( $old_trashed_ads as $ad ) {
            wp_delete_post( $ad->ID, true );
        }
    }
}