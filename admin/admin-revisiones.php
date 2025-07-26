<?php
/**
 * Archivo: admin/admin-revisiones.php
 * Función: Panel de administración para gestionar las "Experiencias en Revisión".
 */

// Evitar el acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Agregar un submenú para "Experiencias en Revisión" bajo el menú del CPT "bar".
 */
function cdb_agregar_menu_revisiones() {
    add_submenu_page(
        'edit.php?post_type=bar',            // Menú padre: dentro del CPT "bar".
        'Experiencias en Revisión',          // Título de la página.
        'Experiencias en Revisión',          // Título del menú.
        'manage_options',                    // Capacidad requerida.
        'cdb_experiencias_revision',         // Slug del menú.
        'cdb_experiencias_revision_callback' // Callback para renderizar la página.
    );
}
add_action( 'admin_menu', 'cdb_agregar_menu_revisiones' );

// Registrar y encolar scripts para la página de revisiones
function cdb_revisiones_admin_assets( $hook ) {
    if ( 'bar_page_cdb_experiencias_revision' !== $hook ) {
        return;
    }
    wp_enqueue_script( 'cdb-admin-revisiones', CDB_BAR_PLUGIN_URL . 'assets/js/admin-revisiones.js', array( 'jquery' ), '1.0.0', true );
    wp_localize_script( 'cdb-admin-revisiones', 'cdb_revisiones', array( 'nonce' => wp_create_nonce( 'delete_experience_review_nonce' ) ) );
}
add_action( 'admin_enqueue_scripts', 'cdb_revisiones_admin_assets' );

/**
 * Callback para renderizar la página de "Experiencias en Revisión".
 */
function cdb_experiencias_revision_callback() {
    global $wpdb;
    $table_revision = $wpdb->prefix . 'cdb_experiencia_revision';

    // Obtener todas las revisiones ordenadas por fecha descendente.
    $revisiones = $wpdb->get_results( "SELECT * FROM $table_revision ORDER BY fecha DESC" );
    ?>
    <div class="wrap">
        <h1>Experiencias en Revisión</h1>
        <?php if ( ! empty( $revisiones ) ) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Empleado</th>
                        <th>Equipo</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $revisiones as $rev ) : 
                        // Obtener datos del usuario.
                        $user = get_userdata( $rev->user_id );
                        $user_name = $user ? $user->display_name : $rev->user_id;

                        // Obtener título y enlace del empleado.
                        $empleado_title = get_the_title( $rev->empleado_id );
                        $empleado_link  = get_edit_post_link( $rev->empleado_id );

                        // Obtener título y enlace del equipo.
                        $equipo_title = get_the_title( $rev->equipo_id );
                        $equipo_link  = get_edit_post_link( $rev->equipo_id );
                        ?>
                        <tr id="revision-<?php echo esc_attr( $rev->id ); ?>">
                            <td><?php echo esc_html( $rev->id ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $rev->user_id ) ); ?>">
                                    <?php echo esc_html( $user_name ); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ( $empleado_title ) : ?>
                                    <a href="<?php echo esc_url( $empleado_link ); ?>">
                                        <?php echo esc_html( $empleado_title ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $rev->empleado_id ); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $equipo_title ) : ?>
                                    <a href="<?php echo esc_url( $equipo_link ); ?>">
                                        <?php echo esc_html( $equipo_title ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $rev->equipo_id ); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $rev->fecha ); ?></td>
                            <td>
                                <button class="button delete-review" data-revision="<?php echo esc_attr( $rev->id ); ?>">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No se han marcado experiencias para revisión.</p>
        <?php endif; ?>
    </div>


    <?php
}

/**
 * AJAX handler para eliminar una revisión.
 */
function cdb_delete_experience_review() {
    // Verificar nonce de seguridad.
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'delete_experience_review_nonce' ) ) {
        wp_send_json_error( 'Permiso denegado.' );
    }

    // Verificar permisos del usuario (por ejemplo, solo administradores).
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'No tienes permisos suficientes.' );
    }

    $revision_id = isset( $_POST['revision_id'] ) ? intval( $_POST['revision_id'] ) : 0;
    if ( ! $revision_id ) {
        wp_send_json_error( 'ID de revisión inválido.' );
    }

    global $wpdb;
    $table_revision = $wpdb->prefix . 'cdb_experiencia_revision';
    $deleted = $wpdb->delete( $table_revision, array( 'id' => $revision_id ), array( '%d' ) );

    if ( false === $deleted ) {
        wp_send_json_error( 'Error al eliminar la revisión.' );
    }

    wp_send_json_success( 'Revisión eliminada.' );
}
add_action( 'wp_ajax_delete_experience_review', 'cdb_delete_experience_review' );
