<?php
/**
 * Clase dedicada exclusivamente a la gestión de las plantillas de correo.
 * VERSIÓN 1.1.0 - MEJORA DE EXPERIENCIA DE USUARIO
 * - Al guardar una plantilla, la página ahora se recarga en el mismo editor en lugar de volver a la lista.
 *
 * @package Avisos_Peru
 */
class AP_Email_Settings {

    private $option_name = 'ap_email_templates';

    public function init() {
        add_action( 'admin_init', [ $this, 'check_default_emails' ] );
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_post_ap_save_email_template', [ $this, 'save_template_action' ] );
        add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
    }

    public function add_menu_page() {
        add_submenu_page( 'edit.php?post_type=aviso', 'Plantillas de Correo', 'Plantillas de Correo', 'manage_options', 'ap-email-settings', [ $this, 'render_page' ] );
    }

    public function render_page() {
        $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
        $email_id = isset( $_GET['email_id'] ) ? sanitize_key( $_GET['email_id'] ) : '';
        echo '<div class="wrap">';
        if ( $action === 'edit' && ! empty( $email_id ) ) {
            $this->render_edit_screen( $email_id );
        } else {
            $this->render_list_screen();
        }
        echo '</div>';
    }
    
    private function render_list_screen() {
        $email_definitions = ( new AP_Emails() )->get_email_definitions();
        ?>
        <h1>Plantillas de Correo</h1>
        <p>Aquí puedes personalizar el contenido de los correos automáticos que envía el sistema.</p>
        <p><strong>Sobre el pago con Yape/Plin:</strong> Para añadir tu QR, edita la plantilla de "Aviso Aprobado", haz clic en "Añadir Objeto" y sube la imagen de tu QR directamente en el contenido del correo.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Correo</th><th>Descripción</th><th style="text-align:right;">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ( $email_definitions as $id => $details ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $details['label'] ); ?></strong></td>
                    <td><?php echo esc_html( $details['description'] ); ?></td>
                    <td style="text-align:right;">
                        <a href="?post_type=aviso&page=ap-email-settings&action=edit&email_id=<?php echo esc_attr( $id ); ?>" class="button button-primary">Configurar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_edit_screen( $email_id ) {
        $templates = get_option( $this->option_name, [] );
        $email_data = $templates[$email_id] ?? [];
        $email_def = ( new AP_Emails() )->get_email_definitions()[$email_id] ?? [];
        if ( empty($email_def) ) {
            echo '<div class="notice notice-error"><p>La plantilla de correo solicitada no existe.</p></div>';
            return;
        }
        ?>
        <a href="?post_type=aviso&page=ap-email-settings">&larr; Volver a la lista de plantillas</a>
        <h1>Editando Plantilla: <?php echo esc_html( $email_def['label'] ); ?></h1>
        <p><?php echo esc_html( $email_def['description'] ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ap_save_email_template">
            <input type="hidden" name="email_id" value="<?php echo esc_attr( $email_id ); ?>">
            <?php wp_nonce_field( 'ap_save_email_template_nonce', 'ap_email_nonce' ); ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="subject">Asunto</label></th>
                        <td><input name="subject" id="subject" type="text" value="<?php echo esc_attr( stripslashes( $email_data['subject'] ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="heading">Encabezado</label></th>
                        <td><input name="heading" id="heading" type="text" value="<?php echo esc_attr( stripslashes( $email_data['heading'] ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="content">Contenido del Correo</label></th>
                        <td>
                            <?php wp_editor( stripslashes( $email_data['content'] ), 'content', ['textarea_name' => 'content', 'textarea_rows' => 15] ); ?>
                            <p class="description">Placeholders disponibles: <code>{nombre_usuario}</code>, <code>{titulo_aviso}</code>, <code>{codigo_aviso}</code>, <code>{enlace_aviso}</code>.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button( 'Guardar Cambios' ); ?>
        </form>
        <?php
    }

    public function save_template_action() {
        if ( ! isset( $_POST['ap_email_nonce'] ) || ! wp_verify_nonce( $_POST['ap_email_nonce'], 'ap_save_email_template_nonce' ) ) wp_die( 'Error de seguridad.' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No tienes permisos para esta acción.' );

        $email_id = sanitize_key( $_POST['email_id'] );
        $templates = get_option( $this->option_name, [] );
        $templates[$email_id]['subject'] = sanitize_text_field( stripslashes( $_POST['subject'] ) );
        $templates[$email_id]['heading'] = sanitize_text_field( stripslashes( $_POST['heading'] ) );
        $templates[$email_id]['content'] = wp_kses_post( stripslashes( $_POST['content'] ) );
        update_option( $this->option_name, $templates );
        
        // ¡MEJORA! Redirigir de vuelta a la misma pantalla de edición.
        $redirect_url = add_query_arg( [
            'post_type' => 'aviso',
            'page'      => 'ap-email-settings',
            'action'    => 'edit',
            'email_id'  => $email_id,
            'message'   => 'template_saved'
        ], admin_url( 'edit.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }

    public function check_default_emails() {
        $email_definitions = ( new AP_Emails() )->get_email_definitions();
        $templates = get_option( $this->option_name, [] );
        $update_needed = false;
        foreach ( $email_definitions as $id => $def ) {
            if ( ! isset( $templates[$id] ) ) {
                $templates[$id] = [ 'subject' => $def['subject'], 'heading' => $def['heading'], 'content' => $def['content'] ];
                $update_needed = true;
            }
        }
        if ( $update_needed ) {
            update_option( $this->option_name, $templates );
        }
    }

    public function show_admin_notices() {
        $current_screen = get_current_screen();
        if ($current_screen->id !== 'aviso_page_ap-email-settings') {
            return;
        }
        if ( isset( $_GET['message'] ) && $_GET['message'] === 'template_saved' ) {
            echo '<div class="notice notice-success is-dismissible"><p>Plantilla de correo guardada correctamente.</p></div>';
        }
    }
}