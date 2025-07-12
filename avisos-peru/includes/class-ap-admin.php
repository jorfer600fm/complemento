<?php
/**
 * Gestiona el área de administración del plugin.
 * v7.0.0 - Filtros de Búsqueda Avanzados
 * - Renombrados los campos de "Distintivo" a "Aviso destacado" y "Aviso verificado".
 *
 * @package Avisos_Peru
 */
class AP_Admin {

    private $plugin_name;
    private $version;
    private $options;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        add_action( 'admin_init', [ $this, 'load_options' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_options_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_aviso', [ $this, 'save_meta_boxes' ], 10, 2 );
        add_action( 'transition_post_status', [ $this, 'on_status_transition' ], 10, 3 );
        add_action( 'admin_post_ap_approve_ad', [ $this, 'handle_approve_ad' ] );
        add_action( 'admin_post_ap_reject_ad', [ $this, 'handle_reject_ad' ] );
        add_action( 'admin_post_ap_confirm_payment', [ $this, 'handle_confirm_payment' ] );
        add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
        add_filter( 'manage_aviso_posts_columns', [ $this, 'add_admin_columns' ] );
        add_action( 'manage_aviso_posts_custom_column', [ $this, 'render_admin_columns' ], 10, 2 );
        add_filter( 'manage_edit-aviso_sortable_columns', [ $this, 'make_columns_sortable' ] );
    }

    public function load_options() {
        $this->options = get_option( 'ap_options' );
    }

    public function add_options_page() {
        add_options_page( 'Ajustes de Avisos Perú', 'Avisos Perú', 'manage_options', 'avisos-peru-settings', [ $this, 'create_admin_page' ] );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Ajustes de Avisos Perú</h1>
            <p>Ajustes generales del plugin. Las plantillas de correo se gestionan en el menú <a href="edit.php?post_type=aviso&page=ap-email-settings">Avisos > Plantillas de Correo</a>.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'ap_general_settings' );
                do_settings_sections( 'avisos-peru-general' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting( 'ap_general_settings', 'ap_options' );
        add_settings_section( 'ap_general_section', 'Ajustes Generales', null, 'avisos-peru-general' );
        add_settings_field( 'api_key', 'API Key de IA (Google Gemini)', [ $this, 'render_field_callback' ], 'avisos-peru-general', 'ap_general_section', ['type' => 'text', 'id' => 'api_key', 'desc' => 'Clave de la API de Google AI para usar los modelos Gemini.'] );
        add_settings_field( 'success_message', 'Mensaje de Éxito', [ $this, 'render_field_callback' ], 'avisos-peru-general', 'ap_general_section', ['type' => 'textarea', 'id' => 'success_message', 'desc' => 'Mensaje que ve el usuario tras enviar el formulario.'] );
        add_settings_section( 'ap_email_sender_section', 'Ajustes del Remitente de Correos', null, 'avisos-peru-general' );
        add_settings_field( 'from_name', 'Nombre del Remitente', [ $this, 'render_field_callback' ], 'avisos-peru-general', 'ap_email_sender_section', ['type' => 'text', 'id' => 'from_name', 'desc' => 'El nombre que aparecerá en los correos enviados (Ej: Chamba y Negocios).'] );
        add_settings_field( 'from_email', 'Email del Remitente', [ $this, 'render_field_callback' ], 'avisos-peru-general', 'ap_email_sender_section', ['type' => 'email', 'id' => 'from_email', 'desc' => 'La dirección de correo desde la que se enviarán las notificaciones.'] );
    }

    public function render_field_callback( $args ) {
        $value = isset( $this->options[$args['id']] ) ? $this->options[$args['id']] : '';
        if ( $args['type'] === 'textarea' ) {
            echo "<textarea id='{$args['id']}' name='ap_options[{$args['id']}]' rows='5' cols='50' class='large-text'>" . esc_textarea( $value ) . "</textarea>";
        } else {
            echo "<input type='{$args['type']}' id='{$args['id']}' name='ap_options[{$args['id']}]' value='" . esc_attr( $value ) . "' class='regular-text' />";
        }
        if ( ! empty( $args['desc'] ) ) {
            echo "<p class='description'>" . esc_html( $args['desc'] ) . "</p>";
        }
    }
    
    public function enqueue_admin_assets( $hook ) {
        global $post;
        if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && isset( $post->post_type ) && $post->post_type == 'aviso' ) {
            wp_enqueue_media();
            wp_enqueue_script( 'ap-admin-uploader', AP_PLUGIN_URL . 'admin/js/ap-admin-uploader.js', [ 'jquery', 'media-upload' ], $this->version, true );
        }
    }
    
    public function on_status_transition( $new_status, $old_status, $post ) {
        if ( $post->post_type !== 'aviso' || $new_status === $old_status ) return;
        if ( $new_status === 'trash' ) {
            AP_Cron::unschedule_payment_check( $post->ID );
        }
    }

    public function handle_approve_ad() {
        if ( ! isset( $_GET['post_id'], $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ap_approve_ad_' . $_GET['post_id'] ) ) wp_die( 'Error de seguridad.' );
        if ( ! current_user_can( 'edit_post', $_GET['post_id'] ) ) wp_die( 'No tienes permiso.' );
        $post_id = absint( $_GET['post_id'] );
        wp_update_post( ['ID' => $post_id, 'post_status' => 'publish'] );
        $email_sender = new AP_Emails();
        $user_email = get_post_meta( $post_id, 'ap_email', true );
        if ( $user_email ) $email_sender->send_notification( $user_email, 'approval_payment_user', $post_id );
        AP_Cron::schedule_payment_check( $post_id );
        wp_redirect( add_query_arg( ['message' => 'ap_ad_approved'], get_edit_post_link( $post_id, 'raw' ) ) );
        exit;
    }

    public function handle_reject_ad() {
        if ( ! isset( $_GET['post_id'], $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ap_reject_ad_' . $_GET['post_id'] ) ) wp_die( 'Error de seguridad.' );
        if ( ! current_user_can( 'delete_post', $_GET['post_id'] ) ) wp_die( 'No tienes permiso.' );
        $post_id = absint( $_GET['post_id'] );
        $email_sender = new AP_Emails();
        $user_email = get_post_meta( $post_id, 'ap_email', true );
        if ( $user_email ) $email_sender->send_notification( $user_email, 'ad_rejected_user', $post_id );
        wp_delete_post( $post_id, true );
        wp_redirect( add_query_arg( ['post_type' => 'aviso', 'message' => 'ap_ad_rejected'], admin_url( 'edit.php' ) ) );
        exit;
    }

    public function handle_confirm_payment() {
        if ( ! isset( $_GET['post_id'], $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ap_confirm_payment_' . $_GET['post_id'] ) ) wp_die( 'Error de seguridad.' );
        if ( ! current_user_can( 'edit_post', $_GET['post_id'] ) ) wp_die( 'No tienes permiso.' );
        $post_id = absint( $_GET['post_id'] );
        update_post_meta( $post_id, '_ap_pago_confirmado', 'yes' );
        AP_Cron::unschedule_payment_check( $post_id );
        $email_sender = new AP_Emails();
        $user_email = get_post_meta( $post_id, 'ap_email', true );
        if ( $user_email ) $email_sender->send_notification( $user_email, 'payment_confirmed_user', $post_id );
        $current_notes = get_post_meta( $post_id, '_ap_notas_internas', true );
        $new_note = "Pago confirmado manualmente por " . wp_get_current_user()->display_name . " el " . current_time( 'mysql' ) . ".";
        update_post_meta( $post_id, '_ap_notas_internas', $current_notes . "\n" . $new_note );
        wp_redirect( add_query_arg( ['message' => 'ap_payment_confirmed'], get_edit_post_link( $post_id, 'raw' ) ) );
        exit;
    }

    public function show_admin_notices() {
        $screen = get_current_screen();
        if ( 'aviso' !== $screen->post_type ) {
            return;
        }
        if ( empty( $_GET['message'] ) ) return;
        $messages = [
            'ap_ad_approved'       => 'Aviso aprobado y publicado. Se ha enviado el correo de solicitud de pago.',
            'ap_payment_confirmed' => 'Pago confirmado exitosamente.',
            'ap_ad_rejected'       => 'El aviso ha sido rechazado y eliminado permanentemente.'
        ];
        if ( array_key_exists( $_GET['message'], $messages ) ) {
            $notice_class = ( $_GET['message'] == 'ap_ad_rejected' ) ? 'notice-warning' : 'notice-success';
            echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . esc_html( $messages[$_GET['message']] ) . '</p></div>';
        }
    }

    public function add_meta_boxes() {
        add_meta_box( 'ap_status_meta_box', 'Estado y Acciones del Aviso', [ $this, 'render_status_meta_box' ], 'aviso', 'side', 'high' );
        add_meta_box( 'ap_ai_tags_meta_box', 'Etiquetas de Búsqueda (IA)', [ $this, 'render_ai_tags_meta_box' ], 'aviso', 'side', 'default' );
        add_meta_box( 'ap_details_meta_box', 'Detalles del Anuncio', [ $this, 'render_details_meta_box' ], 'aviso', 'normal', 'high' );
    }
    
    public function render_ai_tags_meta_box($post) {
        $tags = get_the_terms($post->ID, 'ap_ai_tags');
        echo '<div style="padding:10px;">';
        if ( !empty($tags) && !is_wp_error($tags) ) {
            echo '<p>Estas etiquetas fueron generadas por la IA para mejorar la búsqueda. Relevanssi las usará automáticamente.</p>';
            $tag_list = [];
            foreach ($tags as $tag) {
                $tag_list[] = '<span style="display:inline-block; background-color:#e9ecef; color:#495057; padding: 4px 8px; margin: 3px; border-radius: 4px; font-size: 12px;">' . esc_html($tag->name) . '</span>';
            }
            echo implode(' ', $tag_list);
        } else {
            echo '<p>Aún no se han generado etiquetas de IA para este aviso.</p>';
        }
        echo '</div>';
    }

    public function render_status_meta_box( $post ) {
        $status = get_post_status( $post->ID );
        $pago_confirmado = get_post_meta( $post->ID, '_ap_pago_confirmado', true );
        echo '<div style="padding:10px;">';
        if ( $status === 'pending' ) {
            echo '<p>Este aviso está pendiente de revisión.</p>';
            $approve_url = wp_nonce_url( admin_url( 'admin-post.php?action=ap_approve_ad&post_id=' . $post->ID ), 'ap_approve_ad_' . $post->ID );
            $reject_url = wp_nonce_url( admin_url( 'admin-post.php?action=ap_reject_ad&post_id=' . $post->ID ), 'ap_reject_ad_' . $post->ID );
            echo '<a href="' . esc_url( $approve_url ) . '" class="button button-primary button-large" style="width:100%; text-align:center; margin-bottom: 5px;">Aprobar y Publicar</a>';
            echo '<a href="' . esc_url( $reject_url ) . '" class="button button-secondary button-large" style="width:100%; text-align:center; color:#a00; border-color:#a00;" onclick="return confirm(\'¿Estás seguro de que quieres rechazar y ELIMINAR PERMANENTEMENTE este aviso? Esta acción es irreversible.\');">Rechazar y Eliminar</a>';
        } elseif ( $status === 'publish' && $pago_confirmado !== 'yes' ) {
            echo '<p>El aviso está publicado y <strong>esperando el pago</strong> del usuario.</p>';
            $confirm_payment_url = wp_nonce_url( admin_url( 'admin-post.php?action=ap_confirm_payment&post_id=' . $post->ID ), 'ap_confirm_payment_' . $post->ID );
            echo '<a href="' . esc_url( $confirm_payment_url ) . '" class="button button-primary button-large" style="width:100%; text-align:center;">Confirmar Pago Manualmente</a>';
        } elseif ( $pago_confirmado === 'yes' ) {
            echo '<p style="color:#227122; font-weight:bold;">Este aviso está publicado y el pago ha sido confirmado.</p>';
        } else {
            echo '<p>Estado: ' . esc_html( get_post_status_object( $status )->label ) . '</p>';
        }
        echo '</div>';
    }

public function render_details_meta_box( $post ) {
        wp_nonce_field( 'ap_save_meta_boxes', 'ap_details_nonce' );
        echo '<style>.ap-meta-box-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px 20px; } .ap-meta-box-grid .ap-meta-box-field { padding: 10px 0; } .ap-meta-box-grid .ap-meta-box-field label { display: block; font-weight: bold; margin-bottom: 5px; } .ap-meta-box-grid input[type=text], .ap-meta-box-grid input[type=email], .ap-meta-box-grid input[type=tel], .ap-meta-box-grid input[type=url], .ap-meta-box-grid input[type=date], .ap-meta-box-grid input[type=number], .ap-meta-box-grid select, .ap-meta-box-grid textarea { width: 100%; padding: 8px; } .ap-meta-box-grid .grid-col-full { grid-column: 1 / -1; } .ap-section-header { grid-column: 1 / -1; background: #f0f0f1; padding: 10px; margin: 20px -12px 10px; font-size: 1.2em; border-top: 1px solid #c3c4c7; border-bottom: 1px solid #c3c4c7; }</style>';
        
        $ad_type_values = get_post_meta($post->ID, 'ap_ad_type', true);
        if (!is_array($ad_type_values)) {
            $ad_type_values = [];
        }

        echo '<div class="ap-meta-box-grid">';
        
        echo '<h3 class="ap-section-header">Atributos Especiales</h3>';
        $distintivos = [
            'ap_distintivo_1' => 'Aviso Destacado',
            'ap_distintivo_2' => 'Aviso Verificado',
            'ap_distintivo_3' => 'Distintivo Adicional'
        ];
        foreach ( $distintivos as $key => $label ) {
            $value = get_post_meta( $post->ID, $key, true );
            // --- ¡CORRECCIÓN! Se eliminó el placeholder ---
            echo '<div class="ap-meta-box-field"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" /></div>';
        }

        echo '<h3 class="ap-section-header">Detalles de Contacto y Precio</h3>';
        
        echo '<div class="ap-meta-box-field grid-col-full"><label>Tipo de Anuncio</label>';
        $tipos_anuncio = ['ofrezco' => 'Ofrezco / Vendo', 'busco' => 'Busco / Compro'];
        foreach ($tipos_anuncio as $value => $label) {
            echo '<label style="font-weight:normal; margin-right:15px;"><input type="checkbox" name="ap_ad_type[]" value="' . esc_attr($value) . '" ' . checked(in_array($value, $ad_type_values), true, false) . '> ' . esc_html($label) . '</label>';
        }
        echo '</div>';

        $fields = [
            'ap_price' => ['label' => 'Precio (S/)', 'type' => 'number', 'step' => '0.01'], 'ap_unit' => ['label' => 'Unidad', 'type' => 'text', 'placeholder' => 'Ej: por kg, servicio'],
            'ap_name' => ['label' => 'Nombre de Contacto', 'type' => 'text'], 'ap_email' => ['label' => 'Email de Contacto', 'type' => 'email'], 'ap_phone' => ['label' => 'Celular de Contacto', 'type' => 'tel'],
            'ap_whatsapp' => ['label' => 'WhatsApp', 'type' => 'tel'], 'ap_website' => ['label' => 'Sitio Web', 'type' => 'url'], 'ap_expiry_date' => ['label' => 'Fecha de Vencimiento', 'type' => 'date'],
            'ap_address' => ['label' => 'Dirección', 'type' => 'text', 'class' => 'grid-col-full'],
            'ap_map_lat' => ['label' => 'Latitud Mapa', 'type' => 'text'], 'ap_map_lng' => ['label' => 'Longitud Mapa', 'type' => 'text'],
            '_ap_notas_internas' => ['label' => 'Notas Internas', 'type' => 'textarea', 'class' => 'grid-col-full']
        ];
        foreach ($fields as $key => $props) {
            $value = get_post_meta($post->ID, $key, true);
            $class = isset($props['class']) ? ' ' . $props['class'] : '';
            echo '<div class="ap-meta-box-field' . esc_attr($class) . '"><label for="' . esc_attr($key) . '">' . esc_html($props['label']) . '</label>';
            if ($props['type'] === 'textarea') { echo '<textarea name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" rows="3">' . esc_textarea($value) . '</textarea>';
            } else {
                $attrs = 'type="' . esc_attr($props['type']) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"';
                if (isset($props['step'])) $attrs .= ' step="' . esc_attr($props['step']) . '"';
                if (isset($props['placeholder'])) $attrs .= ' placeholder="' . esc_attr($props['placeholder']) . '"';
                echo '<input ' . $attrs . ' />';
            }
            echo '</div>';
        }
        echo '<h3 class="ap-section-header">Archivos Multimedia</h3>';
        echo '<div class="ap-meta-box-field grid-col-full" style="padding-bottom:20px; border-bottom:1px solid #eee;"><p>La <strong>Foto Principal</strong> se asigna usando el recuadro de <strong>"Imagen destacada"</strong> que aparece a la derecha de la pantalla.</p></div>';
        $this->render_file_uploader_field( $post->ID, 'ap_photo_2', 'Foto 2' );
        $this->render_file_uploader_field( $post->ID, 'ap_photo_3', 'Foto 3' );
        $this->render_file_uploader_field( $post->ID, 'ap_pdf', 'Archivo PDF' );
        $this->render_file_uploader_field( $post->ID, 'ap_video', 'Archivo de Video' );
        echo '</div>';
    }

    private function render_file_uploader_field($post_id, $meta_key, $label) {
        $attachment_id = get_post_meta( $post_id, $meta_key, true );
        $preview = '';
        if ( $attachment_id ) {
            $url = wp_get_attachment_url( $attachment_id );
            $filename = basename( get_attached_file( $attachment_id ) );
            if ( wp_attachment_is_image( $attachment_id ) ) {
                $preview = '<img src="' . esc_url( $url ) . '" style="max-width:150px; height:auto;" />';
            } else {
                $preview = '<a href="' . esc_url( $url ) . '" target="_blank">Ver Archivo: ' . esc_html( $filename ) . '</a>';
            }
        }
        echo '<div class="ap-meta-box-field"><label>' . esc_html( $label ) . '</label><div id="' . esc_attr( $meta_key ) . '_preview" style="margin-bottom:10px;">' . $preview . '</div><input type="hidden" id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '" value="' . esc_attr( $attachment_id ) . '" /><button type="button" class="button ap-upload-button" data-field-id="' . esc_attr( $meta_key ) . '">Seleccionar/Cambiar Archivo</button><button type="button" class="button ap-remove-button" data-field-id="' . esc_attr( $meta_key ) . '" style="' . ( $attachment_id ? '' : 'display:none;' ) . '">Quitar</button></div>';
    }
    
    public function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['ap_details_nonce'] ) || ! wp_verify_nonce( $_POST['ap_details_nonce'], 'ap_save_meta_boxes' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( $post->post_type != 'aviso' ) return;
        
        // Guardar tipo de anuncio (checkbox)
        if (isset($_POST['ap_ad_type']) && is_array($_POST['ap_ad_type'])) {
            $sanitized_ad_types = array_map('sanitize_text_field', $_POST['ap_ad_type']);
            update_post_meta($post_id, 'ap_ad_type', $sanitized_ad_types);
        } else {
            delete_post_meta($post_id, 'ap_ad_type');
        }

        // MEJORA: Sanitización más estricta por tipo de campo
        $meta_to_save = [
            'ap_distintivo_1' => 'sanitize_text_field',
            'ap_distintivo_2' => 'sanitize_text_field',
            'ap_distintivo_3' => 'sanitize_text_field',
            'ap_price'        => 'floatval',
            'ap_unit'         => 'sanitize_text_field',
            'ap_name'         => 'sanitize_text_field',
            'ap_email'        => 'sanitize_email',
            'ap_phone'        => 'sanitize_text_field',
            'ap_whatsapp'     => 'sanitize_text_field',
            'ap_website'      => 'esc_url_raw',
            'ap_address'      => 'sanitize_text_field',
            'ap_map_lat'      => 'sanitize_text_field',
            'ap_map_lng'      => 'sanitize_text_field',
            'ap_expiry_date'  => 'sanitize_text_field',
            '_ap_notas_internas' => 'sanitize_textarea_field',
        ];
        
        foreach ( $meta_to_save as $key => $sanitizer ) {
            if ( isset( $_POST[$key] ) ) {
                $value = call_user_func( $sanitizer, $_POST[$key] );
                update_post_meta( $post_id, $key, $value );
            }
        }
        
        // Guardar archivos (IDs de adjuntos)
        $file_fields = ['ap_photo_2', 'ap_photo_3', 'ap_pdf', 'ap_video'];
        foreach ( $file_fields as $field ) {
            if ( isset( $_POST[$field] ) ) {
                update_post_meta( $post_id, $field, absint( $_POST[$field] ) );
            }
        }
    }

    public function add_admin_columns( $columns ) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'Título del Aviso';
        $new_columns['ap_ad_status'] = 'Estado del Aviso';
        $new_columns['ap_ad_code'] = 'Código';
        $new_columns['ap_user_name'] = 'Nombre de Usuario';
        $new_columns['ap_user_phone'] = 'Celular';
        $new_columns['ap_expiry_date'] = 'Vencimiento';
        $new_columns['taxonomy-departamento'] = 'Departamento';
        $new_columns['date'] = 'Publicado';
        unset($columns['author']);
        return $new_columns;
    }

    public function render_admin_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'ap_ad_code':
                echo '<strong>' . esc_html( get_post_meta( $post_id, '_ap_aviso_codigo', true ) ) . '</strong>';
                break;
            case 'ap_ad_status':
                $status = get_post_status( $post_id );
                $pago_confirmado = get_post_meta( $post_id, '_ap_pago_confirmado', true );
                $styles = 'padding: 5px 10px; border-radius: 4px; color: #fff; display: inline-block; font-size:12px;';
                if ( $pago_confirmado === 'yes' ) {
                    echo '<span style="background-color: #198754; ' . $styles . '">Publicado y Pagado</span>';
                } elseif ( $status === 'publish' ) {
                    echo '<span style="background-color: #ffc107; color: #000; ' . $styles . '">Esperando Pago</span>';
                } elseif ( $status === 'pending' ) {
                    echo '<span style="background-color: #6c757d; ' . $styles . '">Pendiente de Revisión</span>';
                } else {
                    $status_obj = get_post_status_object( $status );
                    echo esc_html( $status_obj ? $status_obj->label : 'Desconocido' );
                }
                break;
            case 'ap_user_name':
                echo esc_html( get_post_meta( $post_id, 'ap_name', true ) );
                break;
            case 'ap_user_phone':
                echo esc_html( get_post_meta( $post_id, 'ap_phone', true ) );
                break;
            case 'ap_expiry_date':
                $expiry_date = get_post_meta( $post_id, 'ap_expiry_date', true );
                echo $expiry_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expiry_date ) ) ) : '—';
                break;
        }
    }

    public function make_columns_sortable( $columns ) {
        $columns['ap_ad_status'] = 'ap_ad_status';
        $columns['ap_expiry_date'] = 'ap_expiry_date';
        return $columns;
    }
}