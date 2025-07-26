<?php
/**
 * Funciones principales para el proyecto CdB_Bar
 *
 * Archivo: includes/functions.php
 */

// Bloquear el acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 * 1. Inyección automática del shortcode [tabla_equipo] en el contenido
 *    de los posts del CPT "equipo".
 * ------------------------------------------------------------------------- */
function cdb_agregar_tabla_equipo_al_contenido( $content ) {
    // Aplicar solo en vistas singulares del CPT "equipo" en el loop principal.
    if ( is_singular( 'equipo' ) && in_the_loop() && is_main_query() ) {
        // Se agrega el contenido del shortcode al final del contenido.
        $content .= do_shortcode( '[tabla_equipo]' );
    }
    return $content;
}
add_filter( 'the_content', 'cdb_agregar_tabla_equipo_al_contenido' );

/* -------------------------------------------------------------------------
 * 2. Handler AJAX para marcar una experiencia para revisión.
 *
 * Esta función verifica que el usuario esté autenticado, valida los
 * parámetros y registra la acción en la tabla personalizada.
 * ------------------------------------------------------------------------- */
function cdb_mark_experience_review() {
    // Verificar que el usuario esté autenticado.
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Usuario no autenticado.' );
    }

    // Obtener y validar parámetros.
    $empleado_id = isset( $_POST['empleado_id'] ) ? intval( $_POST['empleado_id'] ) : 0;
    $equipo_id   = isset( $_POST['equipo_id'] ) ? intval( $_POST['equipo_id'] ) : 0;
    if ( ! $empleado_id || ! $equipo_id ) {
        wp_send_json_error( 'Parámetros inválidos.' );
    }

    global $wpdb;
    $table_revision = $wpdb->prefix . 'cdb_experiencia_revision';
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Evitar marcas duplicadas: verificar si ya existe un registro para esta combinación.
    $exists = $wpdb->get_var( $wpdb->prepare( "
        SELECT COUNT(*) FROM $table_revision
        WHERE user_id = %d AND empleado_id = %d AND equipo_id = %d
    ", $user_id, $empleado_id, $equipo_id ) );
    if ( $exists > 0 ) {
        wp_send_json_error( 'Ya has marcado esta experiencia.' );
    }

    // Registrar la acción en la tabla de revisiones.
    $result = $wpdb->insert(
        $table_revision,
        array(
            'user_id'     => $user_id,
            'empleado_id' => $empleado_id,
            'equipo_id'   => $equipo_id,
            'fecha'       => current_time( 'mysql' )
        ),
        array( '%d', '%d', '%d', '%s' )
    );

    if ( false === $result ) {
        wp_send_json_error( 'Error al registrar la revisión.' );
    }

    wp_send_json_success( 'Experiencia marcada para revisión.' );
}
add_action( 'wp_ajax_mark_experience_review', 'cdb_mark_experience_review' );

/* -------------------------------------------------------------------------
 * 3. Crear la tabla personalizada para registrar las experiencias en revisión.
 *
 * Se utiliza el hook de activación para crear (o actualizar) la tabla 
 * "cdb_experiencia_revision". Asegúrate de que este archivo se incluya 
 * en un plugin que utilice register_activation_hook().
 * ------------------------------------------------------------------------- */
function cdb_create_experiencia_revision_table() {
    global $wpdb;
    $table_revision = $wpdb->prefix . 'cdb_experiencia_revision';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_revision (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        empleado_id bigint(20) NOT NULL,
        equipo_id bigint(20) NOT NULL,
        fecha datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_empleado_equipo (user_id, empleado_id, equipo_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
