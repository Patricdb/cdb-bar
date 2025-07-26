<?php
/**
 * Añade un meta box para asignar una Zona al CPT Bar.
 *
 * @package CdB_Bar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Evitar acceso directo.
}

/**
 * Registra el meta box para la relación de Zona en el CPT Bar.
 */
function cdb_bar_agregar_meta_box_zona() {
	add_meta_box(
		'cdb_bar_meta_box_zona',          // ID del meta box.
		__( 'Asignar Zona', 'cdb_bar' ),    // Título visible.
		'cdb_bar_meta_box_zona_callback',   // Función callback.
		'bar',                            // Pantalla: CPT Bar.
		'side',                           // Contexto.
		'default'
	);
}
add_action( 'add_meta_boxes', 'cdb_bar_agregar_meta_box_zona' );

/**
 * Callback que muestra el contenido del meta box.
 *
 * @param WP_Post $post Objeto del post actual.
 */
function cdb_bar_meta_box_zona_callback( $post ) {
	// Campo nonce para la verificación de seguridad.
	wp_nonce_field( 'cdb_bar_guardar_meta_box_zona', 'cdb_bar_meta_box_zona_nonce' );
	
	// Obtener el valor actual de la meta, si existe.
	$selected_zona = get_post_meta( $post->ID, '_cdb_bar_zona_id', true );

	// Obtener todos los posts del CPT Zona.
	$zonas = get_posts( array(
		'post_type'   => 'zona',
		'numberposts' => -1,
		'post_status' => 'publish',
		'orderby'     => 'title',
		'order'       => 'ASC',
	) );
	?>
	<p>
		<label for="cdb_bar_zona_field"><?php esc_html_e( 'Selecciona una Zona para este Bar:', 'cdb_bar' ); ?></label>
	</p>
	<select name="cdb_bar_zona_field" id="cdb_bar_zona_field" style="width:100%;">
		<option value=""><?php esc_html_e( '-- Sin Zona --', 'cdb_bar' ); ?></option>
		<?php foreach ( $zonas as $zona ) : ?>
			<option value="<?php echo esc_attr( $zona->ID ); ?>" <?php selected( $selected_zona, $zona->ID ); ?>>
				<?php echo esc_html( $zona->post_title ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Guarda el valor seleccionado en el meta box de Zona.
 *
 * @param int $post_id ID del post.
 */
function cdb_bar_guardar_meta_box_zona( $post_id ) {
	// Verificar el nonce.
	if ( ! isset( $_POST['cdb_bar_meta_box_zona_nonce'] ) || ! wp_verify_nonce( $_POST['cdb_bar_meta_box_zona_nonce'], 'cdb_bar_guardar_meta_box_zona' ) ) {
		return;
	}
	
	// Evitar guardados automáticos.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	
	// Verificar permisos.
	if ( isset( $_POST['post_type'] ) && 'bar' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}
	
	// Guardar o limpiar el valor del campo.
	if ( isset( $_POST['cdb_bar_zona_field'] ) ) {
		$zona_id = sanitize_text_field( $_POST['cdb_bar_zona_field'] );
		update_post_meta( $post_id, '_cdb_bar_zona_id', $zona_id );
	}
}
add_action( 'save_post', 'cdb_bar_guardar_meta_box_zona' );
