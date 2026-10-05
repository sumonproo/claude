<?php
/**
 * Public read-only REST endpoints used by the widgets.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * GET /ade/v1/artists and GET /ade/v1/calendar.
 */
final class ADE_REST {

	const NAMESPACE = 'ade/v1';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register routes. Both are public (published content only), read-only and
	 * validated; logged-in requests also carry the wp_rest nonce from the widget.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/artists',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'artists' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					's'           => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'discipline'  => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_title',
					),
					'location'    => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_title',
					),
					'sort'        => array(
						'type'    => 'string',
						'default' => 'az',
						'enum'    => ADE_Directory::SORTS,
					),
					'page'        => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page'    => array(
						'type'    => 'integer',
						'default' => 12,
						'minimum' => 1,
						'maximum' => ADE_Directory::MAX_PER_PAGE,
					),
					'template_id' => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
					),
					'pagination'  => array(
						'type'    => 'string',
						'default' => 'load_more',
						'enum'    => array( 'load_more', 'numbers', 'none' ),
					),
					'more_text'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/calendar',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'calendar' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'month'      => array(
						'type'    => 'string',
						'default' => '',
						'pattern' => '^(\d{4}-\d{2})?$',
					),
					'view'       => array(
						'type'    => 'string',
						'default' => 'month',
						'enum'    => array( 'month', 'list' ),
					),
					'week_start' => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 0,
						'maximum' => 6,
					),
					'show_time'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'show_venue' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'show_thumb' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'toggle'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'tag'        => array(
						'type'    => 'string',
						'default' => 'h2',
						'enum'    => ADE_Calendar::HEADING_TAGS,
					),
					'empty_text' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Directory results.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function artists( WP_REST_Request $request ): WP_REST_Response {
		$params      = ADE_Directory::params( $request->get_params() );
		$per_page    = (int) $request['per_page'];
		$template_id = (int) $request['template_id'];
		$query       = ADE_Directory::query( $params, $per_page );
		$pages       = (int) $query->max_num_pages;

		return new WP_REST_Response(
			array(
				'html'       => ADE_Directory::render_items( $query, $template_id, 1 === $params['page'] ),
				'total'      => (int) $query->found_posts,
				'pages'      => $pages,
				'page'       => $params['page'],
				'count_text' => ADE_Directory::count_text( (int) $query->found_posts ),
				'pagination' => ADE_Directory::render_pagination( $params, $pages, (string) $request['pagination'], (string) $request['more_text'] ),
				'query'      => ltrim( ADE_Directory::page_href( $params, $params['page'] ), '?' ),
			)
		);
	}

	/**
	 * Calendar month / list.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function calendar( WP_REST_Request $request ): WP_REST_Response {
		$args  = ADE_Calendar::args( $request->get_params() );
		$month = ADE_Calendar::parse_month( (string) $request['month'] );

		return new WP_REST_Response(
			array(
				'html'   => ADE_Calendar::render( $month, $args ),
				'month'  => $month->format( 'Y-m' ),
				'view'   => $args['view'],
				'status' => ADE_Calendar::status_text( $month, $args['view'] ),
			)
		);
	}
}
