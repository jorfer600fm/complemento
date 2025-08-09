<?php
/**
 * Funciones del Tema Hijo
 *
 * @package Astra Child Theme
 */

/**
 * Carga de archivos de funciones adicionales.
 */
require_once get_stylesheet_directory() . '/includes/shortcodes.php';


/**
 * Carga las hojas de estilo y scripts del tema hijo.
 */
add_action( 'wp_enqueue_scripts', 'mi_tema_hijo_enqueue_assets', 20 );
function mi_tema_hijo_enqueue_assets() {
    
    // Carga de Estilos
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-variables-style', get_stylesheet_uri(), [ 'parent-style' ], filemtime( get_stylesheet_directory() . '/style.css' ) );
    wp_enqueue_style( 'child-form-publicacion', get_stylesheet_directory_uri() . '/assets/css/form-publicacion.css', [ 'child-variables-style' ], filemtime( get_stylesheet_directory() . '/assets/css/form-publicacion.css' ) );
    wp_enqueue_style( 'child-componente-busqueda', get_stylesheet_directory_uri() . '/assets/css/componente-busqueda.css', [ 'child-variables-style' ], filemtime( get_stylesheet_directory() . '/assets/css/componente-busqueda.css' ) );

    // Cargar estilos de resultados si es una página de búsqueda O si contiene el shortcode de avisos recientes.
    global $post;
    if ( is_search() || ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'avisos_recientes' ) ) ) {
        wp_enqueue_style( 'child-resultados-busqueda', get_stylesheet_directory_uri() . '/assets/css/resultados-busqueda.css', [ 'child-variables-style' ], filemtime( get_stylesheet_directory() . '/assets/css/resultados-busqueda.css' ) );
    }

    // Cargar JS de mejoras de búsqueda SOLO en la página de resultados.
    if ( is_search() ) {
        wp_enqueue_script( 'child-search-enhancements', get_stylesheet_directory_uri() . '/assets/js/search-enhancements.js', [], filemtime( get_stylesheet_directory() . '/assets/js/search-enhancements.js' ), true );
    }
    
    // CAMBIO: Cargar el nuevo script de mejoras globales en todo el sitio.
    wp_enqueue_script( 'child-global-enhancements', get_stylesheet_directory_uri() . '/assets/js/global-enhancements.js', [], filemtime( get_stylesheet_directory() . '/assets/js/global-enhancements.js' ), true );
    
    // CAMBIO: Añadir estilos inline para la animación del error de búsqueda.
    $css_inline = ".ap-input-error { border-color: red !important; animation: shake 0.5s; } @keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }";
    wp_add_inline_style( 'child-componente-busqueda', $css_inline );


    // Carga de scripts para el mapa en la página de detalle.
    if ( is_singular('aviso') ) {
        $post_id = get_the_ID();
        if ( $post_id ) {
            $lat = get_post_meta( $post_id, 'ap_map_lat', true );
            $lng = get_post_meta( $post_id, 'ap_map_lng', true );
            if ( ! empty( $lat ) && ! empty( $lng ) ) {
                wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
                wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
            }
        }
    }
}

// ... (El resto del archivo functions.php permanece sin cambios) ...

/**
 * Crea un shortcode [ap_filtros_busqueda] para mostrar el formulario de filtros.
 */
function ap_filter_form_shortcode() {
    // Recopilar los valores de los filtros actuales para mantener su estado
    $current_ad_types = isset($_GET['ad_type']) && is_array($_GET['ad_type']) ? array_map('sanitize_text_field', $_GET['ad_type']) : [];
    $current_min_price = isset($_GET['min_price']) ? esc_attr(sanitize_text_field($_GET['min_price'])) : '';
    $current_max_price = isset($_GET['max_price']) ? esc_attr(sanitize_text_field($_GET['max_price'])) : '';
    $is_destacado = isset($_GET['destacado']) && $_GET['destacado'] === '1';
    $is_verificado = isset($_GET['verificado']) && $_GET['verificado'] === '1';

    ob_start();
    ?>
    <aside class="ap-results-sidebar">
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="ap-filter-form">
            <h3 class="ap-filter-title">Filtros</h3>
            
            <input type="hidden" name="s" value="<?php echo esc_attr(get_search_query()); ?>">
            <input type="hidden" name="post_type" value="aviso">
            <?php if (isset($_GET['departamento_slug']) && !empty($_GET['departamento_slug'])) : ?>
                <input type="hidden" name="search_scope" value="department">
                <input type="hidden" name="departamento_slug" value="<?php echo esc_attr($_GET['departamento_slug']); ?>">
            <?php endif; ?>

            <div class="ap-filter-block">
                <h4>Tipo de Anuncio</h4>
                <label><input type="checkbox" name="ad_type[]" value="ofrezco" <?php checked(in_array('ofrezco', $current_ad_types)); ?>> Ofrezco / Vendo</label>
                <label><input type="checkbox" name="ad_type[]" value="busco" <?php checked(in_array('busco', $current_ad_types)); ?>> Busco / Compro</label>
            </div>

            <div class="ap-filter-block">
                <h4>Filtrar por Precio (S/)</h4>
                <div class="price-inputs">
                    <input type="text" name="min_price" id="min_price_filter" placeholder="Mínimo" value="<?php echo $current_min_price; ?>" inputmode="numeric">
                    <input type="text" name="max_price" id="max_price_filter" placeholder="Máximo" value="<?php echo $current_max_price; ?>" inputmode="numeric">
                </div>
            </div>

            <div class="ap-filter-block">
                <h4>Atributos Especiales</h4>
                <label><input type="checkbox" name="destacado" value="1" <?php checked($is_destacado); ?>> ⭐ Solo avisos destacados</label>
                <label><input type="checkbox" name="verificado" value="1" <?php checked($is_verificado); ?>> ✅ Solo negocios verificados</label>
            </div>
            
            <button type="submit" class="ap-apply-filters-btn">Aplicar Filtros</button>
        </form>
    </aside>
    <?php
    return ob_get_clean();
}
add_shortcode('ap_filtros_busqueda', 'ap_filter_form_shortcode');

/**
 * Muestra el motor de búsqueda en la parte superior de la página de resultados
 * utilizando el 'hook' CORRECTO de Astra para evitar conflictos de maquetación.
 */
add_action( 'astra_content_before', 'ap_display_search_bar_on_results_page' );
function ap_display_search_bar_on_results_page() {
    if ( is_search() ) {
        echo '<div class="ap-fullwidth-search-container">';
        echo do_shortcode('[ap_aviso_buscador]');
        echo '</div>';
    }
}

/**
 * Carga los archivos CSS y JS para la página de detalle del aviso.
 */
function cargar_assets_single_aviso() {
    if ( is_singular( 'aviso' ) ) {
        // Cargar el CSS
        wp_enqueue_style( 
            'child-single-aviso', 
            get_stylesheet_directory_uri() . '/assets/css/single-aviso.css', 
            [ 'child-variables-style' ], 
            filemtime( get_stylesheet_directory() . '/assets/css/single-aviso.css' ) 
        );
        // Cargar el JavaScript
        wp_enqueue_script(
            'child-single-aviso-js',
            get_stylesheet_directory_uri() . '/assets/js/single-aviso.js',
            [],
            filemtime( get_stylesheet_directory() . '/assets/js/single-aviso.js' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'cargar_assets_single_aviso', 21 );

/**
 * Añade metaetiquetas Open Graph para compartir en redes sociales.
 */
function ap_add_open_graph_tags() {
    if ( is_singular( 'aviso' ) ) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $url = get_permalink();
        $excerpt = get_the_excerpt();
        $image_url = get_the_post_thumbnail_url( $post_id, 'full' );

        echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />';
        echo '<meta property="og:type" content="article" />';
        echo '<meta property="og:url" content="' . esc_url( $url ) . '" />';
        if ( $image_url ) {
            echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />';
        }
        echo '<meta property="og:description" content="' . esc_attr( $excerpt ) . '" />';
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />';
    }
}
add_action( 'wp_head', 'ap_add_open_graph_tags' );

/**
 * Corrige automáticamente la orientación de las imágenes subidas desde celulares
 * basándose en los datos EXIF.
 */
add_filter( 'wp_handle_upload', 'ap_fix_image_rotation' );
function ap_fix_image_rotation( $file ) {
    if ( !isset($file['type']) || strpos($file['type'], 'image/') !== 0 || !function_exists('exif_read_data') ) {
        return $file;
    }
    $exif = @exif_read_data( $file['file'] );
    if ( empty( $exif['Orientation'] ) ) {
        return $file;
    }
    $image_editor = wp_get_image_editor( $file['file'] );
    if ( is_wp_error( $image_editor ) ) {
        return $file;
    }
    switch ( $exif['Orientation'] ) {
        case 3:
            $image_editor->rotate( 180 );
            break;
        case 6:
            $image_editor->rotate( -90 );
            break;
        case 8:
            $image_editor->rotate( 90 );
            break;
    }
    $saved = $image_editor->save( $file['file'] );
    if ( ! is_wp_error( $saved ) && isset( $saved['path'] ) ) {
        $file['file'] = $saved['path'];
    }
    return $file;
}
/**
 * Muestra un botón para desplegar los filtros en la vista móvil de la página de resultados.
 */
add_action('astra_content_before', 'ap_display_show_filters_button_on_mobile', 15);
function ap_display_show_filters_button_on_mobile()
{
    if (is_search()) {
        echo '<div class="ap-mobile-filter-trigger-container">';
        echo '<button id="ap-mobile-filter-trigger" class="ap-button">Mostrar Filtros</button>';
        echo '</div>';
    }
}