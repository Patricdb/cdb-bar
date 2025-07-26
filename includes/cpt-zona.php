<?php
/**
 * Registro del Custom Post Type Zona, su meta campo y configuración de columnas personalizadas.
 *
 * @package CdB_Bar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Evitar acceso directo.
}

/**
 * Registra el Custom Post Type Zona.
 */
function cdb_bar_registrar_cpt_zona() {

	$labels = array(
		'name'                  => _x( 'Zonas', 'Post Type General Name', 'cdb_bar' ),
		'singular_name'         => _x( 'Zona', 'Post Type Singular Name', 'cdb_bar' ),
		'menu_name'             => __( 'Zonas', 'cdb_bar' ),
		'name_admin_bar'        => __( 'Zona', 'cdb_bar' ),
		'archives'              => __( 'Archivo de Zonas', 'cdb_bar' ),
		'attributes'            => __( 'Atributos de la Zona', 'cdb_bar' ),
		'parent_item_colon'     => __( 'Zona Padre:', 'cdb_bar' ),
		'all_items'             => __( 'Todas las Zonas', 'cdb_bar' ),
		'add_new_item'          => __( 'Añadir Nueva Zona', 'cdb_bar' ),
		'add_new'               => __( 'Añadir Nueva', 'cdb_bar' ),
		'new_item'              => __( 'Nueva Zona', 'cdb_bar' ),
		'edit_item'             => __( 'Editar Zona', 'cdb_bar' ),
		'update_item'           => __( 'Actualizar Zona', 'cdb_bar' ),
		'view_item'             => __( 'Ver Zona', 'cdb_bar' ),
		'view_items'            => __( 'Ver Zonas', 'cdb_bar' ),
		'search_items'          => __( 'Buscar Zona', 'cdb_bar' ),
		'not_found'             => __( 'No se encontró', 'cdb_bar' ),
		'not_found_in_trash'    => __( 'No se encontró en la papelera', 'cdb_bar' ),
		'featured_image'        => __( 'Imagen destacada', 'cdb_bar' ),
		'set_featured_image'    => __( 'Establecer imagen destacada', 'cdb_bar' ),
		'remove_featured_image' => __( 'Eliminar imagen destacada', 'cdb_bar' ),
		'use_featured_image'    => __( 'Usar como imagen destacada', 'cdb_bar' ),
		'insert_into_item'      => __( 'Insertar en la zona', 'cdb_bar' ),
		'uploaded_to_this_item' => __( 'Subido a esta zona', 'cdb_bar' ),
		'items_list'            => __( 'Lista de zonas', 'cdb_bar' ),
		'items_list_navigation' => __( 'Navegación de la lista de zonas', 'cdb_bar' ),
		'filter_items_list'     => __( 'Filtrar lista de zonas', 'cdb_bar' ),
	);

	$args = array(
		'label'                 => __( 'Zona', 'cdb_bar' ),
		'description'           => __( 'Custom Post Type para gestionar las zonas de bares.', 'cdb_bar' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail' ),
		'taxonomies'            => array(),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-location-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
	);

	register_post_type( 'zona', $args );
}
add_action( 'init', 'cdb_bar_registrar_cpt_zona', 0 );

/**
 * Registra el meta campo puntuacion_zona para el CPT Zona.
 */
function cdb_bar_registrar_meta_puntuacion_zona() {
	register_post_meta( 'zona', 'puntuacion_zona', array(
		'type'         => 'number',
		'description'  => __( 'Puntuación asignada a la zona', 'cdb_bar' ),
		'single'       => true,
		'default'      => 0,
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'cdb_bar_registrar_meta_puntuacion_zona' );

/*---------------------------------------------------------------
 * META BOX PARA EL VALOR DE PUNTUACIÓN EN EL CPT 'zona'
 *---------------------------------------------------------------*/

/**
 * Agrega un meta box en el editor de 'zona' para definir el valor de puntuación.
 */
function cdb_bar_add_meta_box_zona_score() {
	add_meta_box(
		'cdb_zona_score',                         // ID del meta box.
		__( 'Valor de Puntuación', 'cdb_bar' ),     // Título que se muestra.
		'cdb_bar_render_zona_score_meta_box',       // Función que renderiza el contenido.
		'zona',                                     // CPT donde se agrega.
		'side',                                     // Ubicación.
		'default'                                   // Prioridad.
	);
}
add_action( 'add_meta_boxes', 'cdb_bar_add_meta_box_zona_score' );

/**
 * Renderiza el meta box para el valor de puntuación en el CPT 'zona'.
 *
 * @param WP_Post $post Objeto del post actual.
 */
function cdb_bar_render_zona_score_meta_box( $post ) {
	wp_nonce_field( 'cdb_bar_save_zona_score', 'cdb_bar_zona_score_nonce' );

	$score_value = get_post_meta( $post->ID, 'puntuacion_zona', true );
	?>
	<label for="cdb_bar_zona_score_field"><?php _e( 'Valor de Puntuación:', 'cdb_bar' ); ?></label>
	<input type="number" id="cdb_bar_zona_score_field" name="cdb_bar_zona_score_field" value="<?php echo esc_attr( $score_value ); ?>" step="1" min="0" />
	<?php
}

/**
 * Guarda el valor del meta box de puntuación cuando se guarda o actualiza un post de tipo 'zona'.
 *
 * @param int $post_id ID del post.
 */
function cdb_bar_save_zona_score_meta_box( $post_id ) {
	// Verificar el nonce.
	if ( ! isset( $_POST['cdb_bar_zona_score_nonce'] ) || ! wp_verify_nonce( $_POST['cdb_bar_zona_score_nonce'], 'cdb_bar_save_zona_score' ) ) {
		return;
	}

	// Evitar autosaves.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Verificar permisos.
	if ( isset( $_POST['post_type'] ) && 'zona' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	// Actualizar el valor del campo si está definido.
	if ( isset( $_POST['cdb_bar_zona_score_field'] ) ) {
		$score = intval( $_POST['cdb_bar_zona_score_field'] );
		update_post_meta( $post_id, 'puntuacion_zona', $score );
	}
}
add_action( 'save_post_zona', 'cdb_bar_save_zona_score_meta_box' );

/*---------------------------------------------------------------
 * COLUMNAS PERSONALIZADAS Y ORDENACIÓN POR PUNTUACIÓN EN EL ADMIN
 *---------------------------------------------------------------*/

/**
 * Agrega una columna personalizada para mostrar la puntuación en la lista de zonas.
 *
 * @param array $columns Columnas existentes.
 * @return array Columnas modificadas.
 */
function cdb_bar_add_zona_custom_column( $columns ) {
	$columns['puntuacion_zona'] = __( 'Puntuación', 'cdb_bar' );
	return $columns;
}
add_filter( 'manage_zona_posts_columns', 'cdb_bar_add_zona_custom_column' );

/**
 * Muestra el valor de puntuación en la columna personalizada en la lista de zonas.
 *
 * @param string $column  Nombre de la columna.
 * @param int    $post_id ID del post.
 */
function cdb_bar_show_zona_custom_column( $column, $post_id ) {
	if ( 'puntuacion_zona' === $column ) {
		$score = get_post_meta( $post_id, 'puntuacion_zona', true );
		echo $score ? esc_html( $score ) : '—';
	}
}
add_action( 'manage_zona_posts_custom_column', 'cdb_bar_show_zona_custom_column', 10, 2 );

/**
 * Hace que la columna de puntuación sea ordenable.
 *
 * @param array $columns Columnas existentes.
 * @return array Columnas modificadas.
 */
function cdb_bar_make_zona_score_column_sortable( $columns ) {
	$columns['puntuacion_zona'] = 'puntuacion_zona';
	return $columns;
}
add_filter( 'manage_edit-zona_sortable_columns', 'cdb_bar_make_zona_score_column_sortable' );

/**
 * Modifica la consulta para ordenar por puntuación en el listado de zonas.
 *
 * @param WP_Query $query Consulta actual.
 */
function cdb_bar_sort_zonas_by_score( $query ) {
	if ( ! is_admin() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	if ( 'puntuacion_zona' === $orderby ) {
		$query->set( 'meta_key', 'puntuacion_zona' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'cdb_bar_sort_zonas_by_score' );
