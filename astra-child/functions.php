<?php
/**
 * Funciones del Tema Hijo
 *
 * @package Astra Child Theme
 */

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

    // Cargar estilos de la página de resultados SOLO cuando sea una búsqueda.
    if ( is_search() ) {
        wp_enqueue_style( 'child-resultados-busqueda', get_stylesheet_directory_uri() . '/assets/css/resultados-busqueda.css', [ 'child-variables-style' ], filemtime( get_stylesheet_directory() . '/assets/css/resultados-busqueda.css' ) );
        
        // Cargar JS de mejoras de búsqueda SOLO en la página de resultados.
        wp_enqueue_script( 'child-search-enhancements', get_stylesheet_directory_uri() . '/assets/js/search-enhancements.js', [], filemtime( get_stylesheet_directory() . '/assets/js/search-enhancements.js' ), true );
    }

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