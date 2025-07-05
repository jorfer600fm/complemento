<?php
/**
 * Registra el Custom Post Type y las Taxonomías para los Avisos.
 *
 * @package Avisos_Peru
 */
class AP_CPT {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] ); // Cambiado a plural
    }

    public function register_post_type() {
        $labels = [
            'name'                  => _x( 'Avisos', 'Post Type General Name', 'avisos-peru' ),
            'singular_name'         => _x( 'Aviso', 'Post Type Singular Name', 'avisos-peru' ),
            'menu_name'             => __( 'Avisos', 'avisos-peru' ),
            'name_admin_bar'        => __( 'Aviso', 'avisos-peru' ),
            'archives'              => __( 'Archivo de Avisos', 'avisos-peru' ),
            'attributes'            => __( 'Atributos de Aviso', 'avisos-peru' ),
            'parent_item_colon'     => __( 'Aviso Padre:', 'avisos-peru' ),
            'all_items'             => __( 'Todos los Avisos', 'avisos-peru' ),
            'add_new_item'          => __( 'Añadir Nuevo Aviso', 'avisos-peru' ),
            'add_new'               => __( 'Añadir Nuevo', 'avisos-peru' ),
            'new_item'              => __( 'Nuevo Aviso', 'avisos-peru' ),
            'edit_item'             => __( 'Editar Aviso', 'avisos-peru' ),
            'update_item'           => __( 'Actualizar Aviso', 'avisos-peru' ),
            'view_item'             => __( 'Ver Aviso', 'avisos-peru' ),
            'view_items'            => __( 'Ver Avisos', 'avisos-peru' ),
            'search_items'          => __( 'Buscar Aviso', 'avisos-peru' ),
            'not_found'             => __( 'No encontrado', 'avisos-peru' ),
            'not_found_in_trash'    => __( 'No encontrado en la papelera', 'avisos-peru' ),
            'featured_image'        => __( 'Foto Principal', 'avisos-peru' ),
            'set_featured_image'    => __( 'Establecer Foto Principal', 'avisos-peru' ),
            'remove_featured_image' => __( 'Quitar Foto Principal', 'avisos-peru' ),
            'use_featured_image'    => __( 'Usar como Foto Principal', 'avisos-peru' ),
        ];

        $args = [
            'label'               => __( 'Aviso', 'avisos-peru' ),
            'description'         => __( 'Contenido de avisos para Chamba y Negocios', 'avisos-peru' ),
            'labels'              => $labels,
            'supports'            => [ 'title', 'editor', 'thumbnail', 'author' ],
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-megaphone',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => 'avisos',
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
        ];

        register_post_type( 'aviso', $args );
    }

    public function register_taxonomies() {
        // Taxonomía visible de Departamentos
        $this->register_department_taxonomy();
        
        // ¡NUEVO! Taxonomía oculta para etiquetas de IA
        $this->register_ai_tags_taxonomy();
    }

    private function register_department_taxonomy() {
        $labels = [
            'name'              => _x( 'Departamentos', 'taxonomy general name', 'avisos-peru' ),
            'singular_name'     => _x( 'Departamento', 'taxonomy singular name', 'avisos-peru' ),
            'search_items'      => __( 'Buscar Departamentos', 'avisos-peru' ),
            'all_items'         => __( 'Todos los Departamentos', 'avisos-peru' ),
            'parent_item'       => __( 'Departamento Padre', 'avisos-peru' ),
            'parent_item_colon' => __( 'Departamento Padre:', 'avisos-peru' ),
            'edit_item'         => __( 'Editar Departamento', 'avisos-peru' ),
            'update_item'       => __( 'Actualizar Departamento', 'avisos-peru' ),
            'add_new_item'      => __( 'Añadir Nuevo Departamento', 'avisos-peru' ),
            'new_item_name'     => __( 'Nuevo Nombre de Departamento', 'avisos-peru' ),
            'menu_name'         => __( 'Departamentos', 'avisos-peru' ),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'departamento' ],
            'show_in_rest'      => true,
        ];

        register_taxonomy( 'departamento', [ 'aviso' ], $args );
    }

    private function register_ai_tags_taxonomy() {
        $labels = [
            'name' => _x( 'Etiquetas IA', 'taxonomy general name', 'avisos-peru' ),
        ];
    
        $args = [
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => false,  // No es pública
            'show_ui'           => false,  // No se muestra en el panel de admin
            'show_in_menu'      => false,
            'show_admin_column' => false,
            'query_var'         => false,  // No se puede consultar por URL
            'rewrite'           => false,
            'show_in_rest'      => false, // Oculta del editor de bloques
        ];
    
        register_taxonomy( 'ap_ai_tags', 'aviso', $args );
    }
}