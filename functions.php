<?php
/**
 * layout-viewer functions and definitions
 *
 * @package layout-viewer
 */


function layout_viewer_after_setup_theme(){
	add_theme_support( 'title-tag' );
}
add_action('after_setup_theme', 'layout_viewer_after_setup_theme');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function layout_viewer_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'layout_viewer_content_width', 640 );
}
add_action( 'after_setup_theme', 'layout_viewer_content_width', 0 );

/**
 * Check if this is an actual browser
 *
 * @return bool|string
 */
function layout_viewer_is_browser(){
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
		return 'IE';
	elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
		return 'IE';
	elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
		return 'Firefox';
	elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
		return 'Chrome';
	elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
		return "Opera Mini";
	elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
		return "Opera";
	elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
		return "Safari";
	else
		return false;
}

/**
 * Enqueue scripts and styles.
 */
function layout_viewer_scripts() {
	wp_enqueue_style( 'layout-viewer-style', get_stylesheet_uri() );

	if( empty( $_GET[ 'screenshot_preview' ] ) && ! current_user_can( 'manage_options' ) ) {
		wp_enqueue_script( 'layout-viewer-script', get_template_directory_uri() . '/js/layout-viewer.js', array( 'jquery' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'layout_viewer_scripts' );

/**
 *
 */
function layout_viewer_footer(){
	?>
	<div id="layout-viewer-bar">
		<a id="bar-link" href="https://siteorigin.com/downloads/premium/">
			<span id="bar-text">Get The Most From Page Builder with SiteOrigin Premium</span>
			<span id="bar-button">Find Out More</span>
		</a>
	</div>
	<?php
}
add_action( 'wp_footer', 'layout_viewer_footer' );

function siteorigin_analytics_code(){
	if( defined( 'WP_DEBUG' ) && WP_DEBUG ) return;

	// Skip on optimizely pages.
	if( !empty( $_GET['optimizely_editor'] ) || !empty( $_GET['optimizely_token'] ) ) return;

	?>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		ga('create', 'UA-15939505-1', 'auto');
		ga('send', 'pageview');
	</script>
	<?php
}
add_action('wp_footer', 'siteorigin_analytics_code');


/**
 * Class SiteOrigin_Layout_Directory
 */
class SiteOrigin_Layout_Directory {

	function __construct(){
		add_action( 'after_setup_theme', array($this, 'theme_setup') );
		add_action( 'after_setup_theme', array($this, 'register_post_type') );
		add_action( 'template_redirect', array($this, 'handle_layout_download') );

		add_action( 'wp_ajax_nopriv_query_layouts', array($this, 'query_layouts') );
		add_action( 'wp_ajax_query_layouts', array($this, 'query_layouts') );
	}

	static function single(){
		static $single;
		if( empty($single) ){
			$single = new self();
		}
		return $single;
	}

	function theme_setup(){
		add_theme_support( 'post-thumbnails' );
	}

	function register_post_type(){
		$labels = array(
			'name'               => _x( 'Layouts', 'post type general name', 'layout-directory' ),
			'singular_name'      => _x( 'Layout', 'post type singular name', 'layout-directory' ),
			'menu_name'          => _x( 'Layouts', 'admin menu', 'layout-directory' ),
			'name_admin_bar'     => _x( 'Layout', 'add new on admin bar', 'layout-directory' ),
			'add_new'            => _x( 'Add New', 'book', 'layout-directory' ),
			'add_new_item'       => __( 'Add New Layout', 'layout-directory' ),
			'new_item'           => __( 'New Layout', 'layout-directory' ),
			'edit_item'          => __( 'Edit Layout', 'layout-directory' ),
			'view_item'          => __( 'View Layout', 'layout-directory' ),
			'all_items'          => __( 'All Layouts', 'layout-directory' ),
			'search_items'       => __( 'Search Layouts', 'layout-directory' ),
			'parent_item_colon'  => __( 'Parent Layouts:', 'layout-directory' ),
			'not_found'          => __( 'No layouts found.', 'layout-directory' ),
			'not_found_in_trash' => __( 'No layouts found in Trash.', 'layout-directory' )
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
			'supports'           => array( 'title', 'excerpt', 'comments', 'editor', 'author' )
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
	function convert_to_fallback( $panels_data ) {

		foreach( $panels_data['widgets'] as &$widget ) {
			if( empty($widget['panels_info']['class']) || !class_exists($widget['panels_info']['class']) ) continue;
			$widget_obj = new $widget['panels_info']['class']();
			if( !is_a( $widget_obj, 'SiteOrigin_Widget' ) ) continue;

			$form_options = $widget_obj->form_options();

			$widget = $this->convert_fields_to_fallback( $form_options, $widget );
		}

		return $panels_data;

	}

	function convert_fields_to_fallback($form, $instance, $level = 0){
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
				if( $field['type'] == 'media' && !empty($field['fallback']) ) {
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
	 * This is the thing where we query layouts and return some json.
	 */
	function query_layouts(){

		$query = array(
			'post_status' => 'publish',
			'post_type' => 'layout',
			'posts_per_page' => 1,
			'load_posts' => true,
		);

		if( !empty($_GET['search']) ) {
			$query['s'] = stripslashes( $_GET['search'] );
		}
		if( !empty($_GET['page']) ) {
			$query['paged'] = intval( $_GET['page'] );
		}

		if( !empty($query['s']) ) {
			$layouts_query = new SWP_Query( $query );
		}
		else {
			$layouts_query = new WP_Query( $query );
		}

		$results = array(
			'items' => array(),
			'found' => $layouts_query->found_posts,
			'max_num_pages' => $layouts_query->max_num_pages
		);

		foreach( $layouts_query->posts as $post ) {
			$results['items'][] = array(
				'id' => $post->ID,
				'slug' => $post->post_name,
				'title' => $post->post_title,
				'description' => $post->post_excerpt,
				'preview' => get_permalink( $post )
			);
		}

		header('content-type: application/json');
		echo json_encode( $results );
		wp_die();
	}

}

SiteOrigin_Layout_Directory::single();