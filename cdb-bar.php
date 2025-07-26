<?php
/**
 * Plugin Name: CdB_Bar
 * Plugin URI: https://proyectocdb.es
 * Description: Plugin para la gestión del tipo de contenido "Bar" y sus equipos de trabajo.
 * Version: 1.0.0
 * Author: CdB_
 * Author URI: https://proyectocdb.es
 * License: GPL2
 * Text Domain: cdb-bar
 * Requires at least: 5.0
 * Tested up to: 6.3
 */

// Bloqueo de acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Definir constantes del plugin
define('CDB_BAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CDB_BAR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar el text domain para traducciones
function cdb_bar_load_textdomain() {
    load_plugin_textdomain( 'cdb-bar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'cdb_bar_load_textdomain' );

// Incluir archivos de tipos de contenido personalizados (CPTs)
require_once CDB_BAR_PLUGIN_DIR . 'includes/cpt-bar.php';
require_once CDB_BAR_PLUGIN_DIR . 'includes/cpt-equipo.php';
require_once CDB_BAR_PLUGIN_DIR . 'includes/cpt-zona.php';

// Incluir metaboxes y relaciones
require_once CDB_BAR_PLUGIN_DIR . 'includes/meta-box-zona.php';

// Incluir archivo de relaciones (si existe)
if ( file_exists( CDB_BAR_PLUGIN_DIR . 'includes/relationships.php' ) ) {
    require_once CDB_BAR_PLUGIN_DIR . 'includes/relationships.php';
}

// Incluir shortcodes
if ( file_exists( CDB_BAR_PLUGIN_DIR . 'includes/shortcodes.php' ) ) {
    require_once CDB_BAR_PLUGIN_DIR . 'includes/shortcodes.php';
}

// Incluir el archivo de funciones
if ( file_exists( CDB_BAR_PLUGIN_DIR . 'includes/functions.php' ) ) {
    require_once CDB_BAR_PLUGIN_DIR . 'includes/functions.php';
}

// Incluir archivos de administración, solo en el área de administración.
if ( is_admin() && file_exists( CDB_BAR_PLUGIN_DIR . 'admin/admin-revisiones.php' ) ) {
    require_once CDB_BAR_PLUGIN_DIR . 'admin/admin-revisiones.php';
}

// Función para activar el plugin
function cdb_bar_activate() {
    // Registrar los CPTs y resetear las reglas de reescritura
    cdb_register_cpt_bar();
    cdb_register_cpt_equipo();
    
    // Crear la tabla personalizada para las experiencias en revisión
    cdb_create_experiencia_revision_table();
    
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cdb_bar_activate' );

// Función para desactivar el plugin
function cdb_bar_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cdb_bar_deactivate' );
