<?php
/**
 * Gestiona el formulario público de envío de avisos.
 * v6.4.2 - Aumentado el timeout de la API de IA.
 *
 * @package Avisos_Peru
 */
class AP_Form {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        add_shortcode( 'avisos_peru_formulario', [ $this, 'render_form' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
        add_action( 'wp_ajax_ap_get_ai_suggestion', [ $this, 'get_ai_suggestion' ] );
        add_action( 'wp_ajax_nopriv_ap_get_ai_suggestion', [ $this, 'get_ai_suggestion' ] );
        add_action( 'wp_ajax_ap_submit_listing', [ $this, 'submit_listing' ] );
        add_action( 'wp_ajax_nopriv_ap_submit_listing', [ $this, 'submit_listing' ] );
    }
    
    public function enqueue_public_assets() {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'avisos_peru_formulario' ) ) {
            wp_enqueue_style( $this->plugin_name . '-public', AP_PLUGIN_URL . 'public/css/ap-public.css', [], $this->version );
            wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
            
            wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
            wp_enqueue_script( $this->plugin_name . '-public', AP_PLUGIN_URL . 'public/js/ap-public.js', [ 'jquery', 'leaflet-js' ], $this->version, true );
            
            wp_localize_script( $this->plugin_name . '-public', 'ap_ajax_object', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('ap_form_nonce')
            ]);
        }
    }

    public function render_form() {
        ob_start();
        include AP_PLUGIN_PATH . 'public/partials/ap-public-form-display.php';
        return ob_get_clean();
    }
    
    /**
     * Optimiza las imágenes subidas.
     * MEJORA: Ahora solo redimensiona la imagen original (JPG/PNG) para que Bunny.net la convierta a WebP.
     */
    public function optimize_image( $file_info ) {
        $file_path = $file_info['file'];
        
        // Solo procesar si es una imagen
        $mime_type = mime_content_type($file_path);
        if (strpos($mime_type, 'image') === false) {
            return $file_info;
        }

        $image_editor = wp_get_image_editor( $file_path );
        if ( is_wp_error( $image_editor ) ) {
            return $file_info;
        }

        $max_dimension = 1920; // Dimensión máxima (ancho o alto)
        $quality = 75; // Calidad de compresión

        // Redimensionar si es necesario
        $size = $image_editor->get_size();
        if ($size['width'] > $max_dimension || $size['height'] > $max_dimension) {
            $image_editor->resize( $max_dimension, $max_dimension, false );
        }

        // Establecer la calidad de compresión
        $image_editor->set_quality( $quality );

        // Guardar la imagen optimizada, sobrescribiendo el archivo original
        $saved_image = $image_editor->save($file_path);

        // Si el guardado fue exitoso, la información del archivo ya está actualizada
        if (!is_wp_error($saved_image)) {
            $file_info['file'] = $saved_image['path'];
            $file_info['url'] = str_replace(wp_basename($file_info['url']), wp_basename($saved_image['path']), $file_info['url']);
        }
        
        return $file_info;
    }

    private function call_gemini_api($prompt, $api_key) {
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $api_key;
        $body = ['contents' => [['parts' => [['text' => $prompt]]]]];

        $response = wp_remote_post($api_url, [
            'method'  => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($body),
            'timeout' => 40 // CAMBIO: Aumentado de 25 a 40 segundos.
        ]);

        return $response;
    }
    
    public function get_ai_suggestion() {
        check_ajax_referer('ap_form_nonce', 'nonce');
        $options = get_option('ap_options');
        $api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
        if (empty($api_key)) { wp_send_json_error(['message' => 'La API Key de Google Gemini no está configurada.']); }
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        if (empty($title)) { wp_send_json_error(['message' => 'El título es necesario para la sugerencia.']); }
        
        $prompt = "Basado en el título de anuncio \"{$title}\", crea un mensaje de venta amigable y descriptivo para un anuncio clasificado en Perú. El mensaje no debe exceder las 50 palabras.";
        
        $response = $this->call_gemini_api($prompt, $api_key);
        
        if (is_wp_error($response)) { wp_send_json_error(['message' => 'Error de conexión con la API de Gemini.']); }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            wp_send_json_success(['text' => trim($body['candidates'][0]['content']['parts'][0]['text'])]);
        } else {
            $error_message = 'Respuesta inesperada de la API.';
            if (isset($body['error']['message'])) {
                $error_message .= ' Detalle: ' . $body['error']['message'];
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    private function generate_and_save_ai_tags($post_id, $title) {
        $options = get_option('ap_options');
        $api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
        if (empty($api_key) || empty($title)) return;

        $prompt = "Eres un experto académico de la lengua española especializado en el español del Perú. A partir de las palabras del título del anuncio: '{$title}', genera una lista de 5 a 10 palabras clave nuevas, incluyendo plurales, variaciones de género (masculino/femenino), diminutivos y sinónimos relevantes. Devuelve únicamente la lista de palabras separadas por comas, sin numeración ni texto adicional.";

        $response = $this->call_gemini_api($prompt, $api_key);
    
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            $tags_string = trim($body['candidates'][0]['content']['parts'][0]['text']);
            $tags_array = array_map('trim', explode(',', $tags_string));
            $tags_array = array_filter($tags_array); 

            if (!empty($tags_array)) {
                wp_set_object_terms($post_id, $tags_array, 'ap_ai_tags');
            }
        }
    }

    private function moderate_content_with_ai($post_id, $title, $message) {
        $options = get_option('ap_options');
        $api_key = isset($options['api_key']) ? trim($options['api_key']) : '';
        if (empty($api_key) || (empty($title) && empty($message))) return;

        $prompt = "Eres un moderador de contenido para un sitio de clasificados de Perú. Tu tarea es analizar el siguiente título y mensaje de un anuncio.\n\nTítulo del Anuncio: '{$title}'\n\nMensaje del Anuncio: '{$message}'\n\nRevisa el texto en busca de cualquier contenido que sea explícitamente obsceno, ilegal, promueva el odio, la violencia o sea claramente una estafa. No seas demasiado sensible; permite lenguaje coloquial o informal que no viole las reglas.\n\nSi el contenido viola claramente las políticas, responde únicamente con la palabra 'ALERTA'. Si el contenido es aceptable, no respondas nada (devuelve una cadena vacía).";

        $response = $this->call_gemini_api($prompt, $api_key);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            $moderation_result = trim($body['candidates'][0]['content']['parts'][0]['text']);
            
            if (strcasecmp($moderation_result, 'ALERTA') === 0) {
                $current_notes = get_post_meta($post_id, '_ap_notas_internas', true);
                $new_note = "ALERTA DE IA: Contenido potencialmente inapropiado detectado el " . current_time('mysql') . ".";
                $updated_notes = $current_notes ? $current_notes . "\n\n" . $new_note : $new_note;
                update_post_meta($post_id, '_ap_notas_internas', $updated_notes);
            }
        }
    }
    
    public function submit_listing() {
        check_ajax_referer('ap_form_nonce', 'nonce');
        
        $errors = [];
        $required_fields = ['title', 'message', 'name', 'email', 'phone', 'department', 'expiry_date', 'terms'];
        foreach($required_fields as $field) {
            if (empty($_POST[$field]) && $field !== 'terms') { $errors[] = "El campo '" . ucfirst(str_replace('_', ' ', $field)) . "' es obligatorio."; }
        }
        
        if (!empty($_POST['email']) && !is_email($_POST['email'])) { $errors[] = "El formato del correo electrónico no es válido."; }
        if (!empty($_POST['phone']) && !preg_match('/^[0-9]{9}$/', $_POST['phone'])) { $errors[] = "El celular debe contener 9 dígitos."; }
        if (!empty($_POST['website']) && !filter_var($_POST['website'], FILTER_VALIDATE_URL)) { $errors[] = "La URL del sitio web no es válida."; }
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK && $_FILES['pdf']['size'] > (200 * 1024)) { $errors[] = '¡Atención! El archivo PDF no puede superar los 200 KB.'; }
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK && $_FILES['video']['size'] > (3000 * 1024)) { $errors[] = '¡Atención! El archivo de Video no puede superar los 3.0 MB.'; }
        
        if (!empty($errors)) { wp_send_json_error(['message' => implode('<br>', $errors)]); }
        
        $post_title = sanitize_text_field($_POST['title']);
        $post_content = wp_kses_post($_POST['message']);

        $post_data = [
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_type'    => 'aviso',
            'post_status'  => 'pending'
        ];
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) { wp_send_json_error(['message' => 'Error al crear el aviso: ' . $post_id->get_error_message()]); }
        
        $this->generate_and_save_ai_tags($post_id, $post_title);
        $this->moderate_content_with_ai($post_id, $post_title, $post_content);

        if (isset($_POST['ad_type']) && is_array($_POST['ad_type'])) {
            $sanitized_ad_types = array_map('sanitize_text_field', $_POST['ad_type']);
            update_post_meta($post_id, 'ap_ad_type', $sanitized_ad_types);
        }
        
        // MEJORA: Validación y sanitización más específica por campo
        if (isset($_POST['price']) && !empty($_POST['price'])) {
            $price_clean = preg_replace('/[^0-9.]/', '', $_POST['price']);
            update_post_meta($post_id, 'ap_price', floatval($price_clean));
        }
        if (isset($_POST['unit']) && !empty($_POST['unit'])) {
            update_post_meta($post_id, 'ap_unit', sanitize_text_field($_POST['unit']));
        }
        update_post_meta($post_id, 'ap_name', sanitize_text_field($_POST['name']));
        update_post_meta($post_id, 'ap_email', sanitize_email($_POST['email']));
        update_post_meta($post_id, 'ap_phone', sanitize_text_field($_POST['phone']));
        if (isset($_POST['whatsapp']) && !empty($_POST['whatsapp'])) {
             update_post_meta($post_id, 'ap_whatsapp', sanitize_text_field($_POST['whatsapp']));
        }
        if (isset($_POST['website']) && !empty($_POST['website'])) {
             update_post_meta($post_id, 'ap_website', esc_url_raw($_POST['website']));
        }
        if (isset($_POST['address']) && !empty($_POST['address'])) {
             update_post_meta($post_id, 'ap_address', sanitize_text_field($_POST['address']));
        }
        if (isset($_POST['map_lat']) && !empty($_POST['map_lat'])) {
             update_post_meta($post_id, 'ap_map_lat', sanitize_text_field($_POST['map_lat']));
        }
        if (isset($_POST['map_lng']) && !empty($_POST['map_lng'])) {
             update_post_meta($post_id, 'ap_map_lng', sanitize_text_field($_POST['map_lng']));
        }
        if (isset($_POST['expiry_date']) && !empty($_POST['expiry_date'])) {
             update_post_meta($post_id, 'ap_expiry_date', sanitize_text_field($_POST['expiry_date']));
        }
        
        if (!empty($_POST['department'])) { wp_set_object_terms($post_id, sanitize_text_field($_POST['department']), 'departamento'); }
        
        update_post_meta($post_id, '_ap_aviso_codigo', strtoupper(str_pad(base_convert($post_id, 10, 36), 4, '0', STR_PAD_LEFT)));
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        add_filter( 'wp_handle_upload', [ $this, 'optimize_image' ] );

        $image_keys = ['photo_1', 'photo_2', 'photo_3'];
        foreach ($image_keys as $key) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $attachment_id = media_handle_upload($key, $post_id);
                if (!is_wp_error($attachment_id)) {
                    if ($key === 'photo_1') {
                        set_post_thumbnail($post_id, $attachment_id);
                    } else {
                        update_post_meta($post_id, 'ap_' . $key, $attachment_id);
                    }
                }
            }
        }
        
        remove_filter( 'wp_handle_upload', [ $this, 'optimize_image' ] );

        $other_file_keys = ['pdf', 'video'];
        foreach ($other_file_keys as $key) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $attachment_id = media_handle_upload($key, $post_id);
                if (!is_wp_error($attachment_id)) {
                    update_post_meta($post_id, 'ap_' . $key, $attachment_id);
                }
            }
        }
        
        $options = get_option('ap_options');
        $success_message = !empty($options['success_message']) ? $options['success_message'] : '¡Gracias! Tu aviso ha sido enviado y será revisado.';
        wp_send_json_success(['message' => esc_html($success_message)]);
    }
}