<?php
/**
 * Plugin Name:       Artist Directory for Elementor
 * Description:       Artist and event post types, Elementor dynamic tags, Loop Grid query IDs, directory filter and events calendar widgets, and an Elementor Pro form action for artist submissions.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Text Domain:       artist-directory-elementor
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Elementor tested up to: 3.30
 * Elementor Pro tested up to: 3.30
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

define( 'ADE_VERSION', '1.0.0' );
define( 'ADE_FILE', __FILE__ );
define( 'ADE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADE_URL', plugin_dir_url( __FILE__ ) );

require_once ADE_DIR . 'includes/helpers.php';
require_once ADE_DIR . 'includes/class-ade-post-types.php';
require_once ADE_DIR . 'includes/class-ade-admin.php';
require_once ADE_DIR . 'includes/class-ade-submissions.php';
require_once ADE_DIR . 'includes/class-ade-directory.php';
require_once ADE_DIR . 'includes/class-ade-calendar.php';
require_once ADE_DIR . 'includes/class-ade-queries.php';
require_once ADE_DIR . 'includes/class-ade-rest.php';
require_once ADE_DIR . 'includes/class-ade-calendar-export.php';
require_once ADE_DIR . 'includes/class-ade-schema.php';
require_once ADE_DIR . 'includes/class-ade-elementor.php';
require_once ADE_DIR . 'includes/class-ade-plugin.php';

register_activation_hook( __FILE__, array( 'ADE_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ADE_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'ADE_Plugin', 'init' ) );
