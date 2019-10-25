<?php
/**
 * layout-viewer functions and definitions
 *
 * @package layout-viewer
 */

include get_template_directory() . '/inc/directory.php';

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
 * Enqueue scripts and styles.
 */
function layout_viewer_scripts() {
	wp_enqueue_style( 'layout-viewer-style', get_stylesheet_uri(), array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'layout_viewer_scripts' );