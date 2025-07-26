<?php
// Bloqueo de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT "Equipo"
function cdb_register_cpt_equipo() {
    $labels = array(
        'name'               => __('Equipos', 'cdb-bar'),
        'singular_name'      => __('Equipo', 'cdb-bar'),
        'menu_name'          => __('Equipos', 'cdb-bar'),
        'add_new'            => __('Añadir Nuevo', 'cdb-bar'),
        'add_new_item'       => __('Añadir Nuevo Equipo', 'cdb-bar'),
        'edit_item'          => __('Editar Equipo', 'cdb-bar'),
        'new_item'           => __('Nuevo Equipo', 'cdb-bar'),
        'view_item'          => __('Ver Equipo', 'cdb-bar'),
        'all_items'          => __('Todos los Equipos', 'cdb-bar'),
        'search_items'       => __('Buscar Equipos', 'cdb-bar'),
        'not_found'          => __('No se encontraron equipos.', 'cdb-bar'),
        'not_found_in_trash' => __('No hay equipos en la papelera.', 'cdb-bar'),
    );

    $args = array(
        'label'               => __('Equipo', 'cdb-bar'),
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,  // Asegura que se puedan consultar desde el frontend
        'show_in_menu'        => 'edit.php?post_type=bar', // Agrupa los equipos dentro de "Bares"
        'menu_position'       => 10,
        'supports'            => array('title', 'editor', 'custom-fields'),
        'has_archive'         => false,
        'rewrite'             => array('slug' => 'equipos'),
        'show_in_rest'        => true, // Activar soporte para Gutenberg
        'hierarchical'        => false,
    );

    register_post_type('equipo', $args);
}

add_action('init', 'cdb_register_cpt_equipo');

// Agregar metabox para seleccionar el Bar y Año del Equipo
function cdb_equipo_add_meta_box() {
    add_meta_box(
        'cdb_equipo_meta_box',
        __('Datos del Equipo', 'cdb-bar'),
        'cdb_equipo_meta_box_callback',
        'equipo',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'cdb_equipo_add_meta_box');

function cdb_equipo_meta_box_callback($post) {
    $selected_bar = get_post_meta($post->ID, '_cdb_equipo_bar', true);
    $selected_year = get_post_meta($post->ID, '_cdb_equipo_year', true);

    // Obtener los bares disponibles
    $bares = get_posts(array(
        'post_type'      => 'bar',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ));

    echo '<label for="cdb_equipo_bar">' . __('Seleccionar un Bar:', 'cdb-bar') . '</label>';
    echo '<select name="cdb_equipo_bar" id="cdb_equipo_bar" required>';
    echo '<option value="">' . __('Seleccionar un Bar', 'cdb-bar') . '</option>';
    foreach ($bares as $bar) {
        echo '<option value="' . esc_attr($bar->ID) . '" ' . selected($selected_bar, $bar->ID, false) . '>' . esc_html($bar->post_title) . '</option>';
    }
    echo '</select><br><br>';

    // Campo para seleccionar el año del equipo
    echo '<label for="cdb_equipo_year">' . __('Año del Equipo:', 'cdb-bar') . '</label>';
    echo '<select name="cdb_equipo_year" id="cdb_equipo_year" required>';
    echo '<option value="">' . __('Selecciona un año', 'cdb-bar') . '</option>';
    echo '</select>';

    // Obtener datos de los bares para JavaScript
    $baresData = [];
    foreach ($bares as $bar) {
        $apertura = get_post_meta($bar->ID, "_cdb_bar_apertura", true);
        $cierre = get_post_meta($bar->ID, "_cdb_bar_cierre", true);
        $max_year = ($cierre) ? $cierre : date("Y");

        // Solo añadir si hay un año de apertura válido
        if (!empty($apertura) && is_numeric($apertura)) {
            $baresData[$bar->ID] = [
                'apertura' => intval($apertura),
                'cierre'   => intval($max_year)
            ];
        }
    }

    // Pasar datos en JSON para evitar problemas de interpretación en JS
    echo '<script>
        var baresData = ' . json_encode($baresData) . ';
    </script>';

    // Agregar JavaScript para actualizar dinámicamente los años disponibles
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var barSelect = document.getElementById("cdb_equipo_bar");
            var yearSelect = document.getElementById("cdb_equipo_year");

            function updateYears() {
                var selectedBar = barSelect.value;
                yearSelect.innerHTML = "<option value=\'\'>Selecciona un año</option>";

                if (selectedBar && baresData[selectedBar]) {
                    var apertura = baresData[selectedBar].apertura;
                    var cierre = baresData[selectedBar].cierre;

                    if (apertura && cierre && apertura <= cierre) {
                        for (var year = apertura; year <= cierre; year++) {
                            var option = document.createElement("option");
                            option.value = year;
                            option.textContent = year;
                            yearSelect.appendChild(option);
                        }
                    }
                }
            }

            barSelect.addEventListener("change", updateYears);
            updateYears(); // Inicializar los años al cargar
        });
    </script>';
}

// Guardar la selección del Bar y Año del Equipo
function cdb_save_equipo_meta($post_id) {
    if (isset($_POST['cdb_equipo_bar'])) {
        update_post_meta($post_id, '_cdb_equipo_bar', absint($_POST['cdb_equipo_bar']));
    }
    if (isset($_POST['cdb_equipo_year'])) {
        update_post_meta($post_id, '_cdb_equipo_year', absint($_POST['cdb_equipo_year']));
    }
}
add_action('save_post_equipo', 'cdb_save_equipo_meta');

// Generar automáticamente el título del equipo basado en el bar y el año
function cdb_generar_titulo_equipo($data, $postarr) {
    if ($data['post_type'] === 'equipo' && !empty($_POST['cdb_equipo_bar']) && !empty($_POST['cdb_equipo_year'])) {
        $bar_id = absint($_POST['cdb_equipo_bar']);
        $year = absint($_POST['cdb_equipo_year']);

        // Obtener el nombre del bar asignado
        $bar_name = get_the_title($bar_id);

        if (!empty($bar_name) && !empty($year)) {
            $data['post_title'] = "Equipo $bar_name $year";
            $data['post_name'] = sanitize_title($data['post_title']); // Slug basado en el título
        }
    }
    return $data;
}
add_filter('wp_insert_post_data', 'cdb_generar_titulo_equipo', 10, 2);

// Ocultar el campo de título en la edición del CPT "Equipo"
function cdb_ocultar_titulo_equipo() {
    global $post;
    if ($post && isset($post->post_type) && $post->post_type === 'equipo') {
        echo '<style>#titlediv { display: none; }</style>';
    }
}
add_action('admin_head', 'cdb_ocultar_titulo_equipo');

// Mostrar empleados asignados a un equipo en la edición del CPT "Equipo"
function cdb_equipo_show_empleados($post) {
    $equipo_id = $post->ID;

    // Obtener empleados asignados a este equipo
    $empleados = get_posts(array(
        'post_type'      => 'empleado',
        'meta_key'       => '_cdb_empleado_equipo',
        'meta_value'     => $equipo_id,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC'
    ));

    echo '<h4>' . __('Empleados en este equipo:', 'cdb-bar') . ' (' . count($empleados) . ')</h4>';

    if ($empleados) {
        echo '<label for="cdb_equipo_filter_year">' . __('Filtrar por año:', 'cdb-bar') . '</label>';
        echo '<select id="cdb_equipo_filter_year">';
        echo '<option value="">' . __('Todos los años', 'cdb-bar') . '</option>';

        // Obtener años únicos de los empleados
        $years = [];
        foreach ($empleados as $empleado) {
            $year = get_post_meta($empleado->ID, '_cdb_empleado_year', true);
            if ($year && !in_array($year, $years)) {
                $years[] = $year;
                echo '<option value="' . esc_attr($year) . '">' . esc_html($year) . '</option>';
            }
        }
        echo '</select>';

        echo '<ul id="cdb_equipo_empleados_list">';
        foreach ($empleados as $empleado) {
            $year = get_post_meta($empleado->ID, '_cdb_empleado_year', true);
            echo '<li data-year="' . esc_attr($year) . '">
                    <a href="' . get_edit_post_link($empleado->ID) . '">' . esc_html($empleado->post_title) . '</a> (' . esc_html($year) . ')
                 </li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . __('No hay empleados asignados a este equipo.', 'cdb-bar') . '</p>';
    }

    // JavaScript para filtrar empleados por año
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var filterSelect = document.getElementById("cdb_equipo_filter_year");
            var empleadosList = document.getElementById("cdb_equipo_empleados_list") ? document.getElementById("cdb_equipo_empleados_list").getElementsByTagName("li") : [];

            filterSelect.addEventListener("change", function() {
                var selectedYear = this.value;
                for (var i = 0; i < empleadosList.length; i++) {
                    var empleadoYear = empleadosList[i].getAttribute("data-year");
                    if (selectedYear === "" || empleadoYear === selectedYear) {
                        empleadosList[i].style.display = "list-item";
                    } else {
                        empleadosList[i].style.display = "none";
                    }
                }
            });
        });
    </script>';
}

/**
 * Obtener o crear un post de tipo 'equipo' para la combinación (bar, año).
 * Devuelve el ID del post 'equipo' correspondiente.
 */
function cdb_get_or_create_equipo($bar_id, $year) {
    // Buscar si ya existe un CPT 'equipo' con esa combinación
    $query = new WP_Query(array(
        'post_type'      => 'equipo',
        'posts_per_page' => 1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'   => '_cdb_equipo_bar',
                'value' => $bar_id,
            ),
            array(
                'key'   => '_cdb_equipo_year',
                'value' => $year,
            )
        )
    ));
    if ($query->have_posts()) {
        // Ya existe un "Equipo" con ese bar y año; usar el primero
        $equipo_id = $query->posts[0]->ID;
    } else {
        // Crear un nuevo "Equipo"
        $bar_name = get_the_title($bar_id);
        $post_title = sprintf(__('Equipo %s %s', 'cdb-bar'), $bar_name, $year);

        $equipo_id = wp_insert_post(array(
            'post_type'   => 'equipo',
            'post_status' => 'publish',
            'post_title'  => $post_title,
            'meta_input'  => array(
                '_cdb_equipo_bar'  => $bar_id,
                '_cdb_equipo_year' => $year,
            ),
        ));
    }
    return $equipo_id;
}
