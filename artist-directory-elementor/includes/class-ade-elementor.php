<?php
/**
 * Elementor integration: widgets, dynamic tags, form action, assets.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers everything that plugs into Elementor / Elementor Pro.
 */
final class ADE_Elementor {

	/**
	 * Dynamic tag classes, keyed by file name (without "class-ade-tag-" prefix).
	 */
	const TAGS = array(
		'artist-website'            => 'ADE_Tag_Artist_Website',
		'artist-instagram'          => 'ADE_Tag_Artist_Instagram',
		'artist-facebook'           => 'ADE_Tag_Artist_Facebook',
		'artist-youtube'            => 'ADE_Tag_Artist_Youtube',
		'artist-gallery'            => 'ADE_Tag_Artist_Gallery',
		'artist-linked-products'    => 'ADE_Tag_Artist_Linked_Products',
		'event-venue'               => 'ADE_Tag_Event_Venue',
		'event-address'             => 'ADE_Tag_Event_Address',
		'event-datetime'            => 'ADE_Tag_Event_Datetime',
		'event-ticket-url'          => 'ADE_Tag_Event_Ticket_Url',
		'event-google-calendar-url' => 'ADE_Tag_Event_Google_Calendar_Url',
		'event-ics-url'             => 'ADE_Tag_Event_Ics_Url',
		'event-map-url'             => 'ADE_Tag_Event_Map_Url',
	);

	/**
	 * Hook registration. Hooks only fire when Elementor (or Pro) is active.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_tags' ) );
		add_action( 'elementor_pro/forms/actions/register', array( __CLASS__, 'register_form_actions' ) );
	}

	/**
	 * Register (not enqueue) front-end assets; widgets declare them as dependencies.
	 */
	public static function register_assets(): void {
		$shared = array(
			'restUrl' => esc_url_raw( rest_url( 'ade/v1/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		);

		wp_register_style( 'ade-directory', ADE_URL . 'assets/css/directory.css', array(), ADE_VERSION );
		wp_register_script( 'ade-directory', ADE_URL . 'assets/js/directory.js', array(), ADE_VERSION, true );
		wp_localize_script(
			'ade-directory',
			'adeDirectory',
			$shared + array(
				'i18n' => array(
					'loading' => __( 'Loading artists…', 'artist-directory-elementor' ),
					'error'   => __( 'Sorry, the artist list could not be loaded. Please try again.', 'artist-directory-elementor' ),
				),
			)
		);

		wp_register_style( 'ade-calendar', ADE_URL . 'assets/css/calendar.css', array(), ADE_VERSION );
		wp_register_script( 'ade-calendar', ADE_URL . 'assets/js/calendar.js', array(), ADE_VERSION, true );
		wp_localize_script(
			'ade-calendar',
			'adeCalendar',
			$shared + array(
				'i18n' => array(
					'loading' => __( 'Loading events…', 'artist-directory-elementor' ),
					'error'   => __( 'Sorry, the calendar could not be loaded. Please try again.', 'artist-directory-elementor' ),
				),
			)
		);
	}

	/**
	 * Panel category for our widgets.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Manager.
	 */
	public static function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			'ade',
			array(
				'title' => esc_html__( 'Artist Directory', 'artist-directory-elementor' ),
				'icon'  => 'eicon-person',
			)
		);
	}

	/**
	 * Register widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
	 */
	public static function register_widgets( $widgets_manager ): void {
		require_once ADE_DIR . 'widgets/class-ade-widget-directory-filter.php';
		require_once ADE_DIR . 'widgets/class-ade-widget-events-calendar.php';

		$widgets_manager->register( new ADE_Widget_Directory_Filter() );
		$widgets_manager->register( new ADE_Widget_Events_Calendar() );
	}

	/**
	 * Register dynamic tag groups and tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $manager Manager.
	 */
	public static function register_tags( $manager ): void {
		$manager->register_group( 'ade-artist', array( 'title' => esc_html__( 'Artist', 'artist-directory-elementor' ) ) );
		$manager->register_group( 'ade-event', array( 'title' => esc_html__( 'Event', 'artist-directory-elementor' ) ) );

		require_once ADE_DIR . 'dynamic-tags/class-ade-meta-tag.php';
		require_once ADE_DIR . 'dynamic-tags/class-ade-simple-meta-tag.php';

		foreach ( self::TAGS as $file => $class ) {
			require_once ADE_DIR . 'dynamic-tags/class-ade-tag-' . $file . '.php';
			$manager->register( new $class() );
		}
	}

	/**
	 * Register the "Create Pending Artist" form action (Elementor Pro 3.5+).
	 *
	 * @param \ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar $registrar Registrar.
	 */
	public static function register_form_actions( $registrar ): void {
		require_once ADE_DIR . 'form-actions/class-ade-form-action-create-artist.php';
		$registrar->register( new ADE_Form_Action_Create_Artist() );
	}

	/**
	 * Published loop-item templates for widget selects.
	 *
	 * @return array<int, string> ID => title.
	 */
	public static function loop_templates(): array {
		$posts = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_key'       => '_elementor_template_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'loop-item', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$out = array();
		foreach ( $posts as $post ) {
			$out[ $post->ID ] = $post->post_title ? $post->post_title : '#' . $post->ID;
		}

		return $out;
	}

	/**
	 * Whether Elementor is in editor / preview mode.
	 */
	public static function is_editor(): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		$plugin = \Elementor\Plugin::instance();

		return $plugin->editor->is_edit_mode() || $plugin->preview->is_preview_mode();
	}
}
