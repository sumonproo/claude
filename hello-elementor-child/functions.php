<?php
/**
 * Hello Elementor child theme.
 *
 * @package HelloElementorChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Child stylesheet, loaded after the parent theme styles.
 */
function hello_elementor_child_enqueue_styles() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_styles', 20 );

/**
 * WooCommerce support (product gallery features used by Elementor Pro's WooCommerce widgets).
 */
function hello_elementor_child_woocommerce_support() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 1200,
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'hello_elementor_child_woocommerce_support' );
