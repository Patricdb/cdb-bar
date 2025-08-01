<?php
// Asegurar que el archivo no se acceda directamente.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Registrar los estilos y scripts utilizados por los shortcodes
function cdb_shortcodes_register_assets() {
    wp_register_style( 'cdb-tabla-equipo', CDB_BAR_PLUGIN_URL . 'assets/css/tabla-equipo.css', array(), '1.0.0' );
    wp_register_script( 'cdb-tabla-equipo', CDB_BAR_PLUGIN_URL . 'assets/js/tabla-equipo.js', array( 'jquery' ), '1.0.0', true );
    wp_localize_script( 'cdb-tabla-equipo', 'tabla_equipo', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'wp_enqueue_scripts', 'cdb_shortcodes_register_assets' );

/**
 * Shortcode: [equipo_del_bar bar_id="123"]
 * Muestra los equipos de un Bar (ordenados por año DESC) y los Empleados de cada equipo (ordenados por posicion_id ASC).
 * Sin usar _cdb_empleado_equipo, consultamos cdb_experiencia directamente.
 */
function cdb_equipo_del_bar_shortcode($atts) {
    // 1. Atributos por defecto
    $atts = shortcode_atts(array(
        'bar_id' => 0
    ), $atts, 'equipo_del_bar');

    $bar_id = (int) $atts['bar_id'];

    // 2. Si no se especifica bar_id, usar get_the_ID() (pensado para single-bar)
    if (!$bar_id) {
        $bar_id = get_the_ID();
    }

    // Verificar si es un CPT 'bar'
    if (get_post_type($bar_id) !== 'bar') {
        return '<p>No se ha encontrado un bar válido para mostrar el equipo.</p>';
    }

    global $wpdb;
    $tabla_exp = $wpdb->prefix . 'cdb_experiencia';

    // 3. Obtener los EQUIPOS de este bar, ordenados por _cdb_equipo_year DESC
    //    Filtramos por meta "_cdb_equipo_bar" = $bar_id
    //    Ordenamos por meta_key "_cdb_equipo_year" como número
    $equipos = get_posts(array(
        'post_type'      => 'equipo',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_cdb_equipo_bar',
                'value'   => $bar_id,
                'compare' => '='
            ),
        ),
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_cdb_equipo_year',
        'order'          => 'DESC'
    ));

    // Si no hay equipos, mostramos mensaje
    if (empty($equipos)) {
        return '<p>No se encontraron equipos para este bar.</p>';
    }

    ob_start();
    ?>
    <div class="equipo-bar">
        <h3>Equipos del Bar:</h3>

        <?php
        // 4. Recorrer cada Equipo
        foreach ($equipos as $equipo_post) :
            $equipo_id = $equipo_post->ID;
            $equipo_titulo = get_the_title($equipo_id);
            ?>
            
            <div class="equipo-item">
                <h4>
                <a href="<?php echo esc_url(get_permalink($equipo_id)); ?>">
                <?php echo esc_html($equipo_titulo); ?>
                </a>
                </h4>
                <?php
// 5. Obtener todas las filas de cdb_experiencia con equipo_id = $equipo_id
$filas = $wpdb->get_results($wpdb->prepare("
    SELECT empleado_id, posicion_id
    FROM $tabla_exp
    WHERE equipo_id = %d
", $equipo_id));

if ($filas) {
    // 1. Para cada fila, obtener la puntuación de la posición
    foreach ($filas as $fila) {
        // cdb_posiciones_score es la meta key donde guardamos la puntuación
        $fila->pos_score = (int) get_post_meta($fila->posicion_id, '_cdb_posiciones_score', true);
    }

    // 2. Ordenar las filas por la puntuación de la posición (descendente)
    usort($filas, function($a, $b) {
        // Si quieres de mayor a menor:
        return $b->pos_score <=> $a->pos_score;

        // Si prefieres de menor a mayor, invierte la comparación:
        // return $a->pos_score <=> $b->pos_score;
    });

    echo '<ul>';
    foreach ($filas as $fila) {
        $empleado_id = (int) $fila->empleado_id;
        $pos_id      = (int) $fila->posicion_id;

        // Cargar el post "empleado"
        $emp_post = get_post($empleado_id);
        if (!$emp_post || $emp_post->post_type !== 'empleado') {
            continue;
        }

        $emp_title = get_the_title($empleado_id);
        $emp_link  = get_permalink($empleado_id);

        // Obtener la posición como post 'cdb_posiciones'
        $pos_nombre = $wpdb->get_var($wpdb->prepare("
            SELECT post_title
            FROM {$wpdb->posts}
            WHERE ID = %d
              AND post_type = 'cdb_posiciones'
            LIMIT 1
        ", $pos_id));
        ?>
        <li>
            <a href="<?php echo esc_url($emp_link); ?>">
                <?php echo esc_html($emp_title); ?>
            </a>
            <?php if ($pos_nombre) : ?>
                (<?php echo esc_html($pos_nombre); ?>)
            <?php endif; ?>

        </li>
        <?php
    }
    echo '</ul>';
} else {
    echo '<p>No hay empleados asignados a este equipo.</p>';
}

                ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php

    return ob_get_clean();
}

function cdb_equipo_del_bar_registrar_shortcode() {
    add_shortcode('equipo_del_bar', 'cdb_equipo_del_bar_shortcode');
}
add_action('init', 'cdb_equipo_del_bar_registrar_shortcode');

/**
 * Shortcode: [tabla_equipo equipo_id="123"]
 * Muestra una tabla con los empleados asignados a un Equipo, ordenados según la puntuación total gráfica del empleado.
 */
function cdb_tabla_equipo_shortcode( $atts ) {
    // Atributos por defecto: si no se especifica equipo_id, se usa el ID del post actual.
    $atts = shortcode_atts( array(
        'equipo_id' => 0,
    ), $atts, 'tabla_equipo' );

    $equipo_id = intval( $atts['equipo_id'] );
    if ( ! $equipo_id ) {
        $equipo_id = get_the_ID();
    }
    if ( get_post_type( $equipo_id ) !== 'equipo' ) {
        return '<p>ID de Equipo no válido.</p>';
    }

    // Columnas por defecto de la tabla.
    $columns = array(
        'score'    => __( 'P.T. Gráfica', 'cdb-bar' ),
        'empleado' => __( 'Empleado', 'cdb-bar' ),
        'posicion' => __( 'Posición', 'cdb-bar' ),
        'acciones' => __( 'Acciones', 'cdb-bar' ),
    );
    /*
     * Permite personalizar las columnas mostradas.
     * Snippets pueden eliminar o añadir columnas modificando el array.
     */
    $columns = apply_filters( 'cdb_tabla_equipo_columns', $columns, $equipo_id );

    global $wpdb;
    $tabla_exp = $wpdb->prefix . 'cdb_experiencia';

    // Consultar todas las experiencias asociadas al equipo.
    $filas = $wpdb->get_results( $wpdb->prepare( "
        SELECT empleado_id, posicion_id
        FROM $tabla_exp
        WHERE equipo_id = %d
    ", $equipo_id ) );

    if ( $filas ) {
        // Para cada registro, asignar la puntuación total gráfica del empleado (usando la meta key 'cdb_puntuacion_total')
        foreach ( $filas as $fila ) {
            $fila->emp_score = (int) get_post_meta( $fila->empleado_id, 'cdb_puntuacion_total', true );
        }
        // Ordenar las filas de mayor a menor según la puntuación total gráfica.
        usort( $filas, function( $a, $b ) {
            return $b->emp_score <=> $a->emp_score;
        } );

        /*
         * Permite a otros desarrolladores modificar o reordenar las filas antes
         * de mostrar la tabla. Por ejemplo, un snippet podría ocultar empleados
         * con puntuación 0 o alterar el orden.
         */
    $filas = apply_filters( 'cdb_tabla_equipo_rows', $filas, $equipo_id );
    }

    wp_enqueue_style( 'cdb-tabla-equipo' );
    wp_enqueue_script( 'cdb-tabla-equipo' );

    // Clase CSS por defecto para el contenedor de la tabla.
    $table_class = 'tabla-equipo-container';
    /*
     * Permite personalizar la clase CSS del contenedor de la tabla.
     * El filtro recibe la clase actual, el ID del equipo y las columnas.
     */
    $table_class = apply_filters( 'cdb_tabla_equipo_table_class', $table_class, $equipo_id, $columns );

    /*
     * (Opcional) Permite añadir otros atributos HTML al contenedor
     * a través de un array clave => valor.
     */
    $table_attrs = apply_filters( 'cdb_tabla_equipo_table_attrs', array(), $equipo_id, $columns );
    $table_attrs_html = '';
    if ( ! empty( $table_attrs ) && is_array( $table_attrs ) ) {
        foreach ( $table_attrs as $attr_key => $attr_val ) {
            $table_attrs_html .= sprintf( ' %s="%s"', esc_attr( $attr_key ), esc_attr( $attr_val ) );
        }
    }

    ob_start();
    ?>
    <div class="<?php echo esc_attr( $table_class ); ?>"<?php echo $table_attrs_html; ?>>
        <table>
            <thead>
                <tr>
                    <?php foreach ( $columns as $col_key => $col_label ) : ?>
                        <th><?php echo esc_html( $col_label ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ( $filas ) : ?>
                    <?php foreach ( $filas as $fila ) : 
                        $empleado_id = intval( $fila->empleado_id );
                        $pos_id      = intval( $fila->posicion_id );

                        // Cargar el post del empleado y validar que su tipo sea "empleado".
                        $emp_post = get_post( $empleado_id );
                        if ( ! $emp_post || $emp_post->post_type !== 'empleado' ) {
                            continue;
                        }
                        $emp_title = get_the_title( $empleado_id );
                        $emp_link  = get_permalink( $empleado_id );

                        // Obtener el post de la posición y sus datos.
                        $pos_post = get_post( $pos_id );
                        if ( $pos_post && $pos_post->post_type === 'cdb_posiciones' ) {
                            $pos_nombre = get_the_title( $pos_post );
                            $pos_link   = get_permalink( $pos_post );
                        } else {
                            $pos_nombre = '';
                            $pos_link   = '#';
                        }
                        ?>
                        <tr>
                            <?php foreach ( $columns as $col_key => $col_label ) : ?>
                                <?php switch ( $col_key ) {
                                    case 'score':
                                        ?>
                                        <td><?php echo esc_html( $fila->emp_score ); ?></td>
                                        <?php
                                        break;
                                    case 'empleado':
                                        ?>
                                        <td>
                                            <a href="<?php echo esc_url( $emp_link ); ?>">
                                                <?php echo esc_html( $emp_title ); ?>
                                            </a>
                                        </td>
                                        <?php
                                        break;
                                    case 'posicion':
                                        ?>
                                        <td>
                                            <?php if ( $pos_nombre ) : ?>
                                                <a href="<?php echo esc_url( $pos_link ); ?>">
                                                    <?php echo esc_html( $pos_nombre ); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <?php
                                        break;
                                    case 'acciones':
                                        ?>
                                        <td>
                                            <!-- Botón interactivo para marcar experiencia para revisión -->
                                            <button class="btn-accion mark-review"
                                                data-empleado="<?php echo esc_attr( $empleado_id ); ?>"
                                                data-equipo="<?php echo esc_attr( $equipo_id ); ?>"
                                                title="Marcar para revisión">
                                                <span class="dashicons dashicons-warning"></span>
                                            </button>
                                        </td>
                                        <?php
                                        break;
                                    default:
                                        ?>
                                        <td></td>
                                        <?php
                                        break;
                                } ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count( $columns ); ?>"><?php _e( 'No hay empleados asignados a este equipo.', 'cdb-bar' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'tabla_equipo', 'cdb_tabla_equipo_shortcode' );

/*
 * Ejemplo de uso del filtro 'cdb_tabla_equipo_columns':
 *
 * add_filter( 'cdb_tabla_equipo_columns', function( $columns ) {
 *     // Ocultar la columna de acciones
 *     unset( $columns['acciones'] );
 *     return $columns;
 * } );
 */

/*
 * Ejemplo de uso del filtro 'cdb_tabla_equipo_table_class':
 *
 * add_filter( 'cdb_tabla_equipo_table_class', function( $class, $equipo_id, $columns ) {
 *     if ( 42 === $equipo_id ) {
 *         $class .= ' tabla-especial';
 *     }
 *     return $class;
 * }, 10, 3 );
 *
 * // Añadir atributos HTML al contenedor
 * add_filter( 'cdb_tabla_equipo_table_attrs', function( $attrs ) {
 *     $attrs['data-ejemplo'] = 'demo';
 *     return $attrs;
 * } );
 */

/*
 * Ejemplo de uso del filtro 'cdb_tabla_equipo_rows':
 *
 * add_filter( 'cdb_tabla_equipo_rows', function( $rows ) {
 *     // Ocultar empleados con puntuación 0
 *     $rows = array_filter( $rows, function( $row ) {
 *         return $row->emp_score > 0;
 *     } );
 *
 *     // Reordenar de menor a mayor
 *     usort( $rows, function( $a, $b ) {
 *         return $a->emp_score <=> $b->emp_score;
 *     } );
 *     return $rows;
 * } );
 */


