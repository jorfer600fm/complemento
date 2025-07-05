<?php
/**
 * Fichero que se ejecuta al desinstalar el plugin Avisos Perú.
 * Limpia las opciones de la base de datos.
 *
 * @package Avisos_Peru
 */

// Si no se está desinstalando, no hacer nada.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Borrar las opciones guardadas en la base de datos
delete_option('ap_options');
delete_option('ap_email_templates');

// --- SECCIÓN OPCIONAL Y DESTRUCTIVA ---
// Descomenta las siguientes líneas si quieres que AL BORRAR EL PLUGIN,
// se eliminen TAMBIÉN TODOS LOS AVISOS PUBLICADOS.
// ADVERTENCIA: Esta acción es irreversible.

/*
global $wpdb;
$posts_table = $wpdb->prefix . 'posts';
$postmeta_table = $wpdb->prefix . 'postmeta';
$term_relationships_table = $wpdb->prefix . 'term_relationships';

// Obtener los IDs de todos los avisos
$aviso_ids = $wpdb->get_col("SELECT ID FROM $posts_table WHERE post_type = 'aviso'");

if (!empty($aviso_ids)) {
    $ids_string = implode(',', array_map('absint', $aviso_ids));

    // Borrar los posts y sus relaciones
    $wpdb->query("DELETE FROM $posts_table WHERE ID IN ($ids_string)");
    $wpdb->query("DELETE FROM $postmeta_table WHERE post_id IN ($ids_string)");
    $wpdb->query("DELETE FROM $term_relationships_table WHERE object_id IN ($ids_string)");
}
*/

// Refrescar las reglas de reescritura una última vez.
flush_rewrite_rules();