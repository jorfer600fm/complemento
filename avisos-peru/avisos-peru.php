<?php
/**
 * Plugin Name:       Avisos Perú - Chamba y Negocios
 * Plugin URI:        https://chambaynegocios.com
 * Description:       Gestiona la publicación de avisos clasificados, incluyendo un flujo de aprobación y pago.
 * Version:           5.1.0
 * Author:            Tu Nombre (desarrollado con Gemini)
 * Author URI:        https://chambaynegocios.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       avisos-peru
 * Domain Path:       /languages
 */

// Evitar el acceso directo al archivo.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Constantes del Plugin.
define( 'AP_VERSION', '5.1.0' );
define( 'AP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Carga e inicializa todos los componentes del plugin.
 * Esta función actúa como el cargador principal.
 */
function run_avisos_peru() {
    // Carga de clases principales.
    require_once AP_PLUGIN_PATH . 'includes/class-ap-cpt.php';
    require_once AP_PLUGIN_PATH . 'includes/class-ap-admin.php';
    require_once AP_PLUGIN_PATH . 'includes/class-ap-form.php';
    require_once AP_PLUGIN_PATH . 'includes/class-ap-emails.php';
    require_once AP_PLUGIN_PATH . 'includes/class-ap-cron.php';
    require_once AP_PLUGIN_PATH . 'includes/class-ap-email-settings.php';
    require_once AP_PLUGIN_PATH . 'includes/class-ap-search.php';
    require_once AP_PLUGIN_PATH . 'includes/class-ap-diagnostics.php'; // NUEVO

    // Instanciar e inicializar cada componente.
    (new AP_CPT('avisos-peru', AP_VERSION))->init();
    (new AP_Admin('avisos-peru', AP_VERSION))->init();
    (new AP_Form('avisos-peru', AP_VERSION))->init();
    new AP_Emails();
    (new AP_Cron())->init();
    (new AP_Email_Settings())->init();
    (new AP_Search())->init();
    (new AP_Diagnostics())->init(); // NUEVO
}
// Ejecutar el cargador del plugin.
run_avisos_peru();

/**
 * Acciones a ejecutar en la activación del plugin.
 */
function ap_plugin_activation() {
    require_once AP_PLUGIN_PATH . 'includes/class-ap-cron.php';
    AP_Cron::activate_cron(); 
    
    require_once AP_PLUGIN_PATH . 'includes/class-ap-email-settings.php';
    (new AP_Email_Settings())->check_default_emails();
    
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ap_plugin_activation' );

/**
 * Acciones a ejecutar en la desactivación del plugin.
 */
function ap_plugin_deactivation() {
    require_once AP_PLUGIN_PATH . 'includes/class-ap-cron.php';
    AP_Cron::deactivate_cron();
    
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ap_plugin_deactivation' );