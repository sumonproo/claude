<?php
/**
 * Post types, taxonomies and registered meta.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers `artist`, `event`, `discipline`, `artist_location` and all meta keys.
 */
final class ADE_Post_Types {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
	}

	/**
	 * Register CPTs.
	 */
	public static function register_post_types(): void {
		register_post_type(
			'artist',
			array(
				'labels'        => array(
					'name'                  => _x( 'Artists', 'post type general name', 'artist-directory-elementor' ),
					'singular_name'         => _x( 'Artist', 'post type singular name', 'artist-directory-elementor' ),
					'add_new_item'          => __( 'Add New Artist', 'artist-directory-elementor' ),
					'edit_item'             => __( 'Edit Artist', 'artist-directory-elementor' ),
					'new_item'              => __( 'New Artist', 'artist-directory-elementor' ),
					'view_item'             => __( 'View Artist', 'artist-directory-elementor' ),
					'view_items'            => __( 'View Artists', 'artist-directory-elementor' ),
					'search_items'          => __( 'Search Artists', 'artist-directory-elementor' ),
					'not_found'             => __( 'No artists found.', 'artist-directory-elementor' ),
					'not_found_in_trash'    => __( 'No artists found in Trash.', 'artist-directory-elementor' ),
					'all_items'             => __( 'All Artists', 'artist-directory-elementor' ),
					'archives'              => __( 'Artist Directory', 'artist-directory-elementor' ),
					'featured_image'        => __( 'Artist photo', 'artist-directory-elementor' ),
					'set_featured_image'    => __( 'Set artist photo', 'artist-directory-elementor' ),
					'remove_featured_image' => __( 'Remove artist photo', 'artist-directory-elementor' ),
					'use_featured_image'    => __( 'Use as artist photo', 'artist-directory-elementor' ),
				),
				'public'        => true,
				'has_archive'   => 'artists',
				'rewrite'       => array(
					'slug'       => 'artists',
					'with_front' => false,
				),
				'show_in_rest'  => true,
				'menu_position' => 20,
				'menu_icon'     => 'dashicons-art',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'elementor' ),
			)
		);

		register_post_type(
			'event',
			array(
				'labels'        => array(
					'name'               => _x( 'Events', 'post type general name', 'artist-directory-elementor' ),
					'singular_name'      => _x( 'Event', 'post type singular name', 'artist-directory-elementor' ),
					'add_new_item'       => __( 'Add New Event', 'artist-directory-elementor' ),
					'edit_item'          => __( 'Edit Event', 'artist-directory-elementor' ),
					'new_item'           => __( 'New Event', 'artist-directory-elementor' ),
					'view_item'          => __( 'View Event', 'artist-directory-elementor' ),
					'view_items'         => __( 'View Events', 'artist-directory-elementor' ),
					'search_items'       => __( 'Search Events', 'artist-directory-elementor' ),
					'not_found'          => __( 'No events found.', 'artist-directory-elementor' ),
					'not_found_in_trash' => __( 'No events found in Trash.', 'artist-directory-elementor' ),
					'all_items'          => __( 'All Events', 'artist-directory-elementor' ),
					'archives'           => __( 'Events', 'artist-directory-elementor' ),
				),
				'public'        => true,
				'has_archive'   => 'events',
				'rewrite'       => array(
					'slug'       => 'events',
					'with_front' => false,
				),
				'show_in_rest'  => true,
				'menu_position' => 21,
				'menu_icon'     => 'dashicons-calendar-alt',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'elementor' ),
			)
		);
	}

	/**
	 * Register taxonomies.
	 */
	public static function register_taxonomies(): void {
		register_taxonomy(
			'discipline',
			array( 'artist' ),
			array(
				'labels'            => array(
					'name'          => _x( 'Disciplines', 'taxonomy general name', 'artist-directory-elementor' ),
					'singular_name' => _x( 'Discipline', 'taxonomy singular name', 'artist-directory-elementor' ),
					'search_items'  => __( 'Search Disciplines', 'artist-directory-elementor' ),
					'all_items'     => __( 'All Disciplines', 'artist-directory-elementor' ),
					'edit_item'     => __( 'Edit Discipline', 'artist-directory-elementor' ),
					'add_new_item'  => __( 'Add New Discipline', 'artist-directory-elementor' ),
					'not_found'     => __( 'No disciplines found.', 'artist-directory-elementor' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'discipline',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'artist_location',
			array( 'artist' ),
			array(
				'labels'            => array(
					'name'          => _x( 'Locations', 'taxonomy general name', 'artist-directory-elementor' ),
					'singular_name' => _x( 'Location', 'taxonomy singular name', 'artist-directory-elementor' ),
					'search_items'  => __( 'Search Locations', 'artist-directory-elementor' ),
					'all_items'     => __( 'All Locations', 'artist-directory-elementor' ),
					'edit_item'     => __( 'Edit Location', 'artist-directory-elementor' ),
					'add_new_item'  => __( 'Add New Location', 'artist-directory-elementor' ),
					'not_found'     => __( 'No locations found.', 'artist-directory-elementor' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'artist-location',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Field definitions, keyed by post type then meta key.
	 *
	 * @return array<string, array<string, array>>
	 */
	public static function fields(): array {
		return array(
			'artist' => array(
				'ade_website'         => array(
					'type'     => 'string',
					'sanitize' => static fn( $v ) => ade_sanitize_profile_url( $v ),
				),
				'ade_instagram'       => array(
					'type'     => 'string',
					'sanitize' => static fn( $v ) => ade_sanitize_profile_url( $v, 'instagram' ),
				),
				'ade_facebook'        => array(
					'type'     => 'string',
					'sanitize' => static fn( $v ) => ade_sanitize_profile_url( $v, 'facebook' ),
				),
				'ade_youtube'         => array(
					'type'     => 'string',
					'sanitize' => static fn( $v ) => ade_sanitize_profile_url( $v, 'youtube' ),
				),
				// Leading underscore = protected: hidden from Custom Fields UIs and never exposed via REST.
				'_ade_contact_email'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_email',
					'private'  => true,
				),
				'ade_featured'        => array(
					'type'     => 'boolean',
					'sanitize' => 'rest_sanitize_boolean',
				),
				'ade_gallery'         => array(
					'type'     => 'array',
					'sanitize' => 'ade_sanitize_id_list',
				),
				'ade_linked_products' => array(
					'type'     => 'array',
					'sanitize' => 'ade_sanitize_id_list',
				),
			),
			'event'  => array(
				'ade_start_datetime'  => array(
					'type'     => 'string',
					'sanitize' => 'ade_sanitize_datetime',
				),
				'ade_end_datetime'    => array(
					'type'     => 'string',
					'sanitize' => 'ade_sanitize_datetime',
				),
				'ade_venue'           => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'ade_address'         => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_textarea_field',
				),
				'ade_ticket_url'      => array(
					'type'     => 'string',
					'sanitize' => 'esc_url_raw',
				),
				// One meta row per artist so meta_query "=" can find events by artist.
				'ade_related_artists' => array(
					'type'     => 'integer',
					'sanitize' => 'absint',
					'multiple' => true,
				),
			),
		);
	}

	/**
	 * Register every meta key so REST, Elementor and sanitization all agree.
	 */
	public static function register_meta(): void {
		foreach ( self::fields() as $post_type => $fields ) {
			foreach ( $fields as $key => $field ) {
				$show_in_rest = empty( $field['private'] );
				if ( $show_in_rest && 'array' === $field['type'] ) {
					$show_in_rest = array(
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					);
				}

				$args = array(
					'type'              => $field['type'],
					'single'            => empty( $field['multiple'] ),
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => $field['sanitize'],
					'auth_callback'     => static fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', $post_id ),
				);

				// Multi-row meta gets no default, otherwise get_post_meta() would return [ default ].
				if ( empty( $field['multiple'] ) ) {
					$args['default'] = match ( $field['type'] ) {
						'array'   => array(),
						'boolean' => false,
						default   => '',
					};
				}

				register_post_meta( $post_type, $key, $args );
			}
		}
	}
}
