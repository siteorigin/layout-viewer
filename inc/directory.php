<?php

/**
 * Class SiteOrigin_Layout_Directory
 */
class SiteOrigin_Layout_Directory {
	
	private $style_fields;

	function __construct(){
		add_action( 'after_setup_theme', array($this, 'theme_setup') );
		add_action( 'after_setup_theme', array($this, 'register_post_type') );
		add_action( 'template_redirect', array($this, 'handle_layout_download') );

		add_action( 'wp_ajax_nopriv_query_layouts', array($this, 'query_layouts') );
		add_action( 'wp_ajax_query_layouts', array($this, 'query_layouts') );
		
		$this->style_fields = array();
	}

	static function single(){
		static $single;
		return empty( $single ) ? $single = new self() : $single;
	}

	function theme_setup(){
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 700, 525, array( 'center', 'top' ) );
	}

	function register_post_type(){
		$labels = array(
			'name'               => _x( 'Layouts', 'post type general name', 'layout-viewer' ),
			'singular_name'      => _x( 'Layout', 'post type singular name', 'layout-viewer' ),
			'menu_name'          => _x( 'Layouts', 'admin menu', 'layout-viewer' ),
			'name_admin_bar'     => _x( 'Layout', 'add new on admin bar', 'layout-viewer' ),
			'add_new'            => _x( 'Add New', 'book', 'layout-viewer' ),
			'add_new_item'       => __( 'Add New Layout', 'layout-viewer' ),
			'new_item'           => __( 'New Layout', 'layout-viewer' ),
			'edit_item'          => __( 'Edit Layout', 'layout-viewer' ),
			'view_item'          => __( 'View Layout', 'layout-viewer' ),
			'all_items'          => __( 'All Layouts', 'layout-viewer' ),
			'search_items'       => __( 'Search Layouts', 'layout-viewer' ),
			'parent_item_colon'  => __( 'Parent Layouts:', 'layout-viewer' ),
			'not_found'          => __( 'No layouts found.', 'layout-viewer' ),
			'not_found_in_trash' => __( 'No layouts found in Trash.', 'layout-viewer' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'layout' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'taxonomies'         => array('post_tag'),
			'supports'           => array( 'title', 'excerpt', 'editor', 'author', 'thumbnail' )
		);

		register_post_type( 'layout', $args );

		register_taxonomy(
			'layout_tag',
			'layout',
			array(
				'label' => __( 'Layout Tags' ),
				'public' => true,
				'hierarchical' => false,
			)
		);
	}

	/**
	 * Download the JSON file
	 */
	function handle_layout_download(){
		if( !is_singular('layout') ) return;

		global $post;

		if( !empty($_GET['action']) && $_GET['action'] == 'download' ) {
			header('content-type: application/json');
			$panels_data = get_post_meta( $post->ID, 'panels_data', true );
			$panels_data = apply_filters( 'siteorigin_layout_viewer_panels_data', $panels_data, $post );

			// Convert all fields that have a fallback option
			$panels_data = $this->convert_to_fallback( $panels_data );

			echo json_encode( $panels_data );
			exit();
		}
	}

	/**
	 * Convert a layout to use all external fields.
	 *
	 * @param $panels_data
	 * @return mixed
	 */
	function 	convert_to_fallback( $panels_data ) {

		foreach( $panels_data['widgets'] as & $widget ) {
			// Convert the widget styles
			if( ! empty( $widget[ 'panels_info' ][ 'style' ] ) ) {
				$widget[ 'panels_info' ][ 'style' ] = $this->convert_styles_to_fallback( $widget[ 'panels_info' ][ 'style' ], 'widget' );
			}
			
			// Check if we're going to convert the widget
			if( empty( $widget['panels_info']['class'] ) || ! class_exists( $widget['panels_info']['class'] ) ) continue;
			$widget_obj = new $widget['panels_info']['class']();
			if( !is_a( $widget_obj, 'SiteOrigin_Widget' ) ) continue;

			$form_options = $widget_obj->form_options();
			$widget = $this->convert_widget_fields_to_fallback( $form_options, $widget );
		}
		
		foreach( $panels_data['grids'] as & $row ) {
			if( empty( $row['style'] ) ) continue;
			$row['style'] = $this->convert_styles_to_fallback( $row['style'], 'row' );
		}
		
		foreach( $panels_data['grid_cells'] as & $cell ) {
			if( empty( $cell['style'] ) ) continue;
			$cell['style'] = $this->convert_styles_to_fallback( $cell['style'], 'cell' );
		}

		return $panels_data;

	}

	/**
	 * Convert SiteOrigin widgets to fallback
	 *
	 * @param $form
	 * @param $instance
	 * @param int $level
	 *
	 * @return mixed
	 */
	function convert_widget_fields_to_fallback($form, $instance, $level = 0){
		if( $level > 10 ) return $instance;

		foreach($form as $id => $field) {

			if( $field['type'] == 'repeater' ) {
				if( !empty($instance[$id]) ) {
					foreach( array_keys($instance[$id]) as $i ){
						$instance[$id][$i] = $this->convert_fields_to_fallback( $field['fields'], $instance[$id][$i], $level + 1 );
					}
				}
			}
			else if( $field['type'] == 'section' ) {
				if( empty($instance[$id]) ) {
					$instance[$id] = array();
				}
				$instance[$id] = $this->convert_fields_to_fallback( $field['fields'], $instance[$id], $level + 1 );
			}
			else {
				if( $field['type'] == 'media' && !empty( $field[ 'fallback' ] ) ) {
					$image_src = wp_get_attachment_image_src( intval($instance[$id]), 'full' );
					$instance[$id] = false;
					if( !empty($image_src[0]) ) {
						$instance[$id . '_fallback'] = $image_src[0] . '#' . $image_src[1] . 'x' . $image_src[2];
					}
				}
			}
		}

		return $instance;
	}
	
	/**
	 * Convert the style fields to an array
	 *
	 * @param $styles
	 * @param $type
	 *
	 * @return mixed
	 */
	function convert_styles_to_fallback( $styles, $type ){
		if( empty( $this->style_fields[ $type ] ) ) {
			$this->style_fields[ $type ] = apply_filters( 'siteorigin_panels_' . $type . '_style_fields', array() );
		}
		
		foreach( $this->style_fields[ $type ] as $field_id => $field ) {
			if( empty( $styles[ $field_id ] ) ) continue;
			
			if( $field[ 'type' ] === 'image' ) {
				$image_src = wp_get_attachment_image_src( $styles[ $field_id ], 'full' );
				if( empty( $image_src ) ) continue;
				
				$styles[ $field_id ] = $image_src[0] . '#' . $image_src[1] . 'x' . $image_src[2];
			}
		}
		
		return $styles;
	}

	/**
	 * This is the thing where we query layouts and return some json.
	 */
	function query_layouts(){

		$query = array(
			'post_status' => 'publish',
			'post_type' => 'layout',
			'posts_per_page' => 16,
			'load_posts' => true,
		);

		if( !empty($_GET['search']) ) {
			$query['s'] = stripslashes( $_GET['search'] );
		}
		if( !empty($_GET['page']) ) {
			$query['paged'] = intval( $_GET['page'] );
		}

		$query = apply_filters( 'siteorigin_layout_viewer_query', $query );

		$results = array(
			'items' => array(),
		);

		if( ! empty( $query ) ) {
			if( class_exists( 'SWP_Query' ) && ! empty( $query['s'] ) ) {
				$layouts_query = new SWP_Query( $query );
			}
			else {
				$layouts_query = new WP_Query( $query );
			}

			$results[ 'found' ] = $layouts_query->found_posts;
			$results[ 'max_num_pages' ] = $layouts_query->max_num_pages;

			foreach( $layouts_query->posts as $post ) {
				$results['items'][] = array(
					'id' => $post->ID,
					'slug' => $post->post_name,
					'title' => $post->post_title,
					'description' => $post->post_excerpt,
					'preview' => get_permalink( $post ),
					'screenshot' => get_the_post_thumbnail_url( $post ),
				);
			}
		}

		$results = apply_filters( 'siteorigin_layout_viewer_results', $results );

		header('content-type: application/json');
		echo json_encode( $results );
		wp_die();
	}

}

SiteOrigin_Layout_Directory::single();