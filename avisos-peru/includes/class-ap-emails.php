<?php
/**
 * Gestiona el envío de todas las notificaciones por correo electrónico del plugin.
 * VERSIÓN 10.0.0 - LECTURA DESDE OPCIÓN DEDICADA
 * - Ahora lee las plantillas desde la opción 'ap_email_templates' para desacoplar la lógica.
 *
 * @package Avisos_Peru
 */
class AP_Emails {

    public function __construct() {
        add_filter( 'wp_mail_from', [ $this, 'custom_mail_from' ] );
        add_filter( 'wp_mail_from_name', [ $this, 'custom_mail_from_name' ] );
    }

    public function custom_mail_from( $original_email_address ) {
        $options = get_option('ap_options');
        if ( !empty($options['from_email']) && is_email($options['from_email']) ) {
            return $options['from_email'];
        }
        return get_option( 'admin_email', $original_email_address );
    }

    public function custom_mail_from_name( $original_from_name ) {
        $options = get_option('ap_options');
        if ( !empty($options['from_name']) ) {
            return $options['from_name'];
        }
        return get_option( 'blogname', $original_from_name );
    }

    public function get_email_definitions() {
        return [
            'approval_payment_user' => [
                'label'       => 'Aviso Aprobado (para usuario)',
                'description' => 'Se envía al usuario cuando su aviso es aprobado para que realice el pago.',
                'subject'     => '¡Tu aviso "{titulo_aviso}" ha sido aprobado!',
                'heading'     => '¡Felicitaciones!',
                'content'     => 'Hola {nombre_usuario},<br><br>Tu aviso con código <strong>{codigo_aviso}</strong> ha sido revisado y aprobado. Para completar la publicación, solo necesitas realizar el pago correspondiente dentro de las próximas 24 horas.<br><br>Una vez publicado, tu aviso estará visible aquí: <a href="{enlace_aviso}">Ver mi aviso</a>.<br><br>Gracias por tu confianza.'
            ],
            'payment_confirmed_user' => [
                'label'       => 'Pago Confirmado (para usuario)',
                'description' => 'Se envía después de que el administrador confirma el pago.',
                'subject'     => 'Pago confirmado para tu aviso "{titulo_aviso}"',
                'heading'     => '¡Todo listo!',
                'content'     => 'Hola {nombre_usuario},<br><br>Hemos confirmado tu pago para el aviso <strong>{titulo_aviso}</strong> (Código: {codigo_aviso}).<br><br>Tu anuncio ya está activo y visible para todos. Puedes verlo en: <a href="{enlace_aviso}">Ver mi aviso</a>.<br><br>¡Mucho éxito!'
            ],
            'ad_rejected_user' => [
                'label'       => 'Aviso Rechazado (para usuario)',
                'description' => 'Se envía cuando el administrador rechaza y elimina un aviso.',
                'subject'     => 'Información sobre tu aviso "{titulo_aviso}"',
                'heading'     => 'Aviso Rechazado',
                'content'     => 'Hola {nombre_usuario},<br><br>Te informamos que tu aviso <strong>"{titulo_aviso}"</strong> no ha sido aprobado porque no cumple con nuestras políticas de contenido.<br><br>Te invitamos a revisarlas y a enviar un nuevo anuncio si lo deseas.<br><br>Gracias por tu comprensión.'
            ],
            'ad_deleted_unpaid' => [
                'label'       => 'Aviso Eliminado por Falta de Pago',
                'description' => 'Se envía si el aviso se elimina automáticamente por no pagar en 24 horas.',
                'subject'     => 'Tu aviso "{titulo_aviso}" ha sido eliminado',
                'heading'     => 'Aviso Eliminado',
                'content'     => 'Hola {nombre_usuario},<br><br>Tu aviso <strong>"{titulo_aviso}"</strong> (Código: {codigo_aviso}) fue eliminado porque no se recibió el pago de publicación en el plazo de 24 horas.<br><br>Si aún estás interesado, por favor, envía un nuevo aviso.<br><br>Gracias por tu interés.'
            ],
        ];
    }

    public function send_notification( $to, $email_id, $post_id ) {
        // ¡CAMBIO IMPORTANTE! Lee desde la nueva opción de la base de datos.
        $email_templates = get_option( 'ap_email_templates' );

        if ( !isset( $email_templates[$email_id] ) ) {
            return false; // No se encontró la plantilla
        }

        $email_data = $email_templates[$email_id];
        $post = get_post( $post_id );
        
        $user_name = get_post_meta( $post_id, 'ap_name', true ) ?: 'Usuario';
        $post_title = $post ? $post->post_title : '(aviso eliminado)';
        $codigo_aviso = get_post_meta( $post_id, '_ap_aviso_codigo', true ) ?: 'N/A';
        
        $subject = $this->replace_placeholders( $email_data['subject'], $post_id, $post_title, $user_name, $codigo_aviso );
        $heading = $this->replace_placeholders( $email_data['heading'], $post_id, $post_title, $user_name, $codigo_aviso );
        $content = $this->replace_placeholders( $email_data['content'], $post_id, $post_title, $user_name, $codigo_aviso );

        $message = $this->get_html_email_template( $heading, $content );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        return wp_mail( $to, $subject, $message, $headers );
    }

    private function replace_placeholders( $text, $post_id, $post_title, $user_name, $codigo_aviso ) {
        $placeholders = [
            '{nombre_usuario}' => esc_html($user_name),
            '{titulo_aviso}'   => esc_html($post_title),
            '{codigo_aviso}'   => esc_html($codigo_aviso),
            '{enlace_aviso}'   => get_permalink( $post_id ),
        ];
        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $text );
    }

    private function get_html_email_template( $heading, $content ) {
        ob_start();
        ?>
        <!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body style="margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4"><table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse:collapse;margin-top:20px;box-shadow:0 0 10px rgba(0,0,0,.1)"><tr><td align="center" bgcolor="#0073aa" style="padding:30px 0"><h1 style="color:#fff;margin:0"><?php echo esc_html($heading); ?></h1></td></tr><tr><td bgcolor="#fff" style="padding:40px 30px"><?php echo wp_kses_post(wpautop(stripslashes($content))); ?></td></tr><tr><td bgcolor="#f0f0f1" style="padding:20px 30px;text-align:center;color:#888;font-size:12px"><p>Este es un correo automático de <?php echo esc_html(get_bloginfo('name')); ?>.</p></td></tr></table></body></html>
        <?php
        return ob_get_clean();
    }
}