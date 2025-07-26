<?php
// Bloqueo de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT "Bar"
function cdb_register_cpt_bar() {
    $labels = array(
        'name'               => __('Bares', 'cdb-bar'),
        'singular_name'      => __('Bar', 'cdb-bar'),
        'menu_name'          => __('Bares', 'cdb-bar'),
        'add_new'            => __('Añadir Nuevo', 'cdb-bar'),
        'add_new_item'       => __('Añadir Nuevo Bar', 'cdb-bar'),
        'edit_item'          => __('Editar Bar', 'cdb-bar'),
        'new_item'           => __('Nuevo Bar', 'cdb-bar'),
        'view_item'          => __('Ver Bar', 'cdb-bar'),
        'all_items'          => __('Todos los Bares', 'cdb-bar'),
        'search_items'       => __('Buscar Bares', 'cdb-bar'),
        'not_found'          => __('No se encontraron bares.', 'cdb-bar'),
        'not_found_in_trash' => __('No hay bares en la papelera.', 'cdb-bar'),
    );

    $args = array(
        'label'               => __('Bar', 'cdb-bar'),
        'labels'              => $labels,
        'public'              => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-store',
        'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'has_archive'         => true,
        'rewrite'             => array('slug' => 'bares'),
        'show_in_rest'        => true, // Activar soporte para Gutenberg
    );

    register_post_type('bar', $args);
}

add_action('init', 'cdb_register_cpt_bar');

// Agregar metabox para Año de Apertura y Cierre
function cdb_bar_add_meta_box() {
    add_meta_box(
        'cdb_bar_meta_box',
        __('Datos de Apertura y Cierre', 'cdb-bar'),
        'cdb_bar_meta_box_callback',
        'bar',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'cdb_bar_add_meta_box');

function cdb_bar_meta_box_callback($post) {
    // Obtener valores actuales
    $apertura = get_post_meta($post->ID, '_cdb_bar_apertura', true);
    $cierre = get_post_meta($post->ID, '_cdb_bar_cierre', true);

    // Campo de seguridad para evitar ataques CSRF
    wp_nonce_field('cdb_bar_nonce_action', 'cdb_bar_nonce');

    echo '<label for="cdb_bar_apertura">' . __('Año de Apertura:', 'cdb-bar') . '</label>';
    echo '<input type="number" id="cdb_bar_apertura" name="cdb_bar_apertura" value="' . esc_attr($apertura) . '" min="1900" max="' . date('Y') . '" required><br><br>';

    echo '<label for="cdb_bar_cierre">' . __('Año de Cierre (opcional):', 'cdb-bar') . '</label>';
    echo '<input type="number" id="cdb_bar_cierre" name="cdb_bar_cierre" value="' . esc_attr($cierre) . '" min="1900" max="' . date('Y') . '">';
}

// Guardar los valores de Año de Apertura y Cierre
function cdb_bar_save_meta($post_id) {
    // Verificar si el nonce es válido
    if (!isset($_POST['cdb_bar_nonce']) || !wp_verify_nonce($_POST['cdb_bar_nonce'], 'cdb_bar_nonce_action')) {
        return;
    }

    // Evitar guardado en autosave o revisiones
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verificar permisos de usuario
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Guardar Año de Apertura (requerido)
    if (isset($_POST['cdb_bar_apertura']) && is_numeric($_POST['cdb_bar_apertura'])) {
        update_post_meta($post_id, '_cdb_bar_apertura', absint($_POST['cdb_bar_apertura']));
    }

    // Guardar Año de Cierre (opcional, debe ser mayor o igual que el año de apertura)
    if (isset($_POST['cdb_bar_cierre']) && is_numeric($_POST['cdb_bar_cierre'])) {
        $apertura = get_post_meta($post_id, '_cdb_bar_apertura', true);
        $cierre = absint($_POST['cdb_bar_cierre']);
        
        if ($cierre >= $apertura) {
            update_post_meta($post_id, '_cdb_bar_cierre', $cierre);
        } else {
            delete_post_meta($post_id, '_cdb_bar_cierre'); // No guardar valores incorrectos
        }
    }

    // NUEVO: Sincronizar los “Equipos” (opcional)
    // Para cada año entre apertura y cierre, crear/buscar cpt “equipo”
    // (Necesita que cdb_get_or_create_equipo() exista)
    if (function_exists('cdb_get_or_create_equipo')) {
        $apertura = intval(get_post_meta($post_id, '_cdb_bar_apertura', true));
        $cierre   = get_post_meta($post_id, '_cdb_bar_cierre', true);
        $cierre   = !empty($cierre) ? intval($cierre) : intval(date('Y'));

        if ($apertura > 0 && $apertura <= $cierre) {
            for ($year = $apertura; $year <= $cierre; $year++) {
                // Crear o encontrar el “Equipo” para (bar = $post_id, año = $year)
                cdb_get_or_create_equipo($post_id, $year);
            }
        }
    }
}
add_action('save_post_bar', 'cdb_bar_save_meta');
