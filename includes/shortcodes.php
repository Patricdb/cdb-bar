<?php
// Asegurar que el archivo no se acceda directamente.
if (!defined('ABSPATH')) {
    exit;
}

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
    }

    ob_start();
    ?>
    <!-- Estilos en línea para mejorar la visual de la tabla (inspirados en "Tu experiencia laboral") -->
    <style>
        .tabla-equipo-container table {
            width: 80%;
            border-collapse: collapse;
        }
        .tabla-equipo-container table th,
        .tabla-equipo-container table td {
            text-align: left;
        padding: 8px;
       }
        .tabla-equipo-container table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .tabla-equipo-container table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .tabla-equipo-container table tr:hover {
            background-color: #f1f1f1;
        }
        .tabla-equipo-container .btn-accion {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            margin: 0;
            color: #555;
        }
        .tabla-equipo-container .btn-accion:hover {
            color: #222;
        }
        .tabla-equipo-container table th:last-child,
    .tabla-equipo-container table td:last-child {
        text-align: right;
         }
    </style>

    <div class="tabla-equipo-container">
        <table>
            <thead>
                <tr>
                    <th>P.T. Gráfica</th>
                    <th>Empleado</th>
                    <th>Posición</th>
                    <th>Acciones</th>
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
                            <td><?php echo esc_html( $fila->emp_score ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( $emp_link ); ?>">
                                    <?php echo esc_html( $emp_title ); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ( $pos_nombre ) : ?>
                                    <a href="<?php echo esc_url( $pos_link ); ?>">
                                        <?php echo esc_html( $pos_nombre ); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Botón interactivo para marcar experiencia para revisión -->
                                <button class="btn-accion mark-review"
                                    data-empleado="<?php echo esc_attr( $empleado_id ); ?>"
                                    data-equipo="<?php echo esc_attr( $equipo_id ); ?>"
                                    title="Marcar para revisión">
                                    <span class="dashicons dashicons-warning"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4"><?php _e( 'No hay empleados asignados a este equipo.', 'text-domain' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Script para gestionar la acción de marcado vía AJAX con confirmación mejorada -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.mark-review').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var empleadoId = button.data('empleado');
            var equipoId = button.data('equipo');

            // Crear el diálogo de confirmación con estilos mejorados
            var confirmDialog = $(
                '<div class="confirm-review-dialog" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; border:4px solid #ccc; border-radius:16px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding:20px; z-index:10000; width:300px;">' +
                    '<p style="margin:0 0 20px; font-size:1.1em; text-align:center;">¿Deseas enviar éste empleado a revisión?</p>' +
                    '<div style="text-align:center;">' +
                        '<a href="#" class="cancel-review" style="margin-right:15px; padding:8px 16px; background:#f5f5f5; border:1px solid #ccc; border-radius:4px; text-decoration:none; color:#333;">Cancelar</a>' +
                        '<a href="#" class="confirm-review" style="padding:8px 16px; background:#404040; border:1px solid #404040; border-radius:4px; text-decoration:none; color:#fff;">Enviar</a>' +
                    '</div>' +
                '</div>'
            );

            // Agregar el diálogo al body y mostrarlo
            $('body').append(confirmDialog);
            confirmDialog.fadeIn(200);

            // Manejar clic en "Cancelar": cerrar el diálogo
            confirmDialog.find('.cancel-review').on('click', function(e) {
                e.preventDefault();
                confirmDialog.fadeOut(200, function() {
                    $(this).remove();
                });
            });

            // Manejar clic en "Enviar": ejecutar la solicitud AJAX
            confirmDialog.find('.confirm-review').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'mark_experience_review',
                        empleado_id: empleadoId,
                        equipo_id: equipoId
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Experiencia marcada para revisión.');
                            // Deshabilitar el botón para evitar marcas duplicadas.
                            button.prop('disabled', true);
                        } else {
                            alert('Error: ' + response.data);
                        }
                        confirmDialog.fadeOut(200, function() {
                            $(this).remove();
                        });
                    },
                    error: function(xhr, status, error) {
                        alert('Error en la solicitud: ' + error);
                        confirmDialog.fadeOut(200, function() {
                            $(this).remove();
                        });
                    }
                });
            });
        });
    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode( 'tabla_equipo', 'cdb_tabla_equipo_shortcode' );


