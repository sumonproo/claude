<?php
/**
 * Plugin loader.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires every module into WordPress.
 */
final class ADE_Plugin {

	/**
	 * Boot all modules. Runs on plugins_loaded.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		ADE_Post_Types::init();
		ADE_Queries::init();
		ADE_REST::init();
		ADE_Calendar_Export::init();
		ADE_Schema::init();
		ADE_Elementor::init();

		if ( is_admin() ) {
			ADE_Admin::init();
			ADE_Submissions::init();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once ADE_DIR . 'includes/class-ade-cli.php';
			WP_CLI::add_command( 'ade', 'ADE_CLI' );
		}
	}

	/**
	 * Load translations.
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'artist-directory-elementor', false, dirname( plugin_basename( ADE_FILE ) ) . '/languages' );
	}

	/**
	 * Register post types then flush rewrites so /artists/ and /events/ resolve immediately.
	 */
	public static function activate(): void {
		ADE_Post_Types::register_post_types();
		ADE_Post_Types::register_taxonomies();
		flush_rewrite_rules();
	}

	/**
	 * Remove our rewrite rules.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
