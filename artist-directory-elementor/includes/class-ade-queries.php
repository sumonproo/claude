<?php
/**
 * Custom Elementor Pro query IDs (Loop Grid / Posts / Loop Carousel → Query → Query ID).
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handlers for `elementor/query/{query_id}`.
 */
final class ADE_Queries {

	/**
	 * Query ID => handler method.
	 */
	const QUERIES = array(
		'ade_upcoming_events'        => 'upcoming_events',
		'ade_past_events'            => 'past_events',
		'ade_featured_artists'       => 'featured_artists',
		'ade_artist_events'          => 'artist_events',
		'ade_artist_upcoming_events' => 'artist_upcoming_events',
		'ade_event_artists'          => 'event_artists',
		'ade_artist_directory'       => 'artist_directory',
		'ade_artist_products'        => 'artist_products',
	);

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		foreach ( self::QUERIES as $query_id => $method ) {
			add_action( 'elementor/query/' . $query_id, array( __CLASS__, $method ) );
		}
	}

	/**
	 * Add a meta clause, keeping any clauses Elementor already set.
	 *
	 * @param WP_Query $query  Query.
	 * @param string   $name   Clause name (usable in orderby).
	 * @param array    $clause Clause.
	 */
	private static function add_meta_clause( WP_Query $query, string $name, array $clause ): void {
		$meta_query          = $query->get( 'meta_query' );
		$meta_query          = is_array( $meta_query ) ? $meta_query : array();
		$meta_query[ $name ] = $clause;
		$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	/**
	 * Restrict to events by start date and sort by start.
	 *
	 * @param WP_Query    $query   Query.
	 * @param string|null $compare '>=' upcoming, '<' past, null = all.
	 * @param string      $order   ASC|DESC.
	 */
	private static function events_by_start( WP_Query $query, ?string $compare, string $order ): void {
		$query->set( 'post_type', 'event' );

		$clause = array(
			'key'  => 'ade_start_datetime',
			'type' => 'DATETIME',
		);
		if ( $compare ) {
			$clause['value']   = current_time( 'mysql' );
			$clause['compare'] = $compare;
		} else {
			$clause['compare'] = 'EXISTS';
		}

		self::add_meta_clause( $query, 'ade_start', $clause );
		$query->set( 'orderby', array( 'ade_start' => $order ) );
	}

	/**
	 * `ade_upcoming_events`: start >= now, soonest first.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function upcoming_events( WP_Query $query ): void {
		self::events_by_start( $query, '>=', 'ASC' );
	}

	/**
	 * `ade_past_events`: start < now, most recent first.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function past_events( WP_Query $query ): void {
		self::events_by_start( $query, '<', 'DESC' );
	}

	/**
	 * `ade_featured_artists`: featured = true.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function featured_artists( WP_Query $query ): void {
		$query->set( 'post_type', 'artist' );
		self::add_meta_clause(
			$query,
			'ade_featured',
			array(
				'key'   => 'ade_featured',
				'value' => '1',
			)
		);
	}

	/**
	 * Restrict an event query to the artist in context.
	 *
	 * @param WP_Query $query Query.
	 */
	private static function limit_to_context_artist( WP_Query $query ): void {
		$artist_id = ade_context_post_id( 'artist' );

		self::add_meta_clause(
			$query,
			'ade_artist',
			array(
				'key'   => 'ade_related_artists',
				'value' => $artist_id ? $artist_id : -1,
				'type'  => 'NUMERIC',
			)
		);
	}

	/**
	 * `ade_artist_events`: every event linked to the current artist, chronological.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function artist_events( WP_Query $query ): void {
		self::events_by_start( $query, null, 'ASC' );
		self::limit_to_context_artist( $query );
	}

	/**
	 * `ade_artist_upcoming_events`: the current artist's upcoming events only.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function artist_upcoming_events( WP_Query $query ): void {
		self::events_by_start( $query, '>=', 'ASC' );
		self::limit_to_context_artist( $query );
	}

	/**
	 * `ade_event_artists`: the current event's related artists, in saved order.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function event_artists( WP_Query $query ): void {
		$event_id = ade_context_post_id( 'event' );
		$ids      = $event_id ? ade_get_related_artist_ids( $event_id ) : array();

		$query->set( 'post_type', 'artist' );
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		$query->set( 'orderby', 'post__in' );
		$query->set( 'ignore_sticky_posts', true );
	}

	/**
	 * `ade_artist_directory`: applies the Directory Filter widget's URL params
	 * (?ade_s, ade_discipline, ade_location, ade_sort, ade_page) so the Loop Grid's
	 * first render matches shared / bookmarked filter URLs.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function artist_directory( WP_Query $query ): void {
		$params = ADE_Directory::params_from_url();

		$query->set( 'post_type', 'artist' );
		foreach ( ADE_Directory::filter_vars( $params ) as $key => $value ) {
			$query->set( $key, $value );
		}
		if ( $params['page'] > 1 ) {
			$query->set( 'paged', $params['page'] );
		}
	}

	/**
	 * `ade_artist_products`: WooCommerce products linked to the current artist.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function artist_products( WP_Query $query ): void {
		$artist_id = ade_context_post_id( 'artist' );
		$ids       = $artist_id ? ade_get_linked_product_ids( $artist_id ) : array();

		$query->set( 'post_type', 'product' );
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		$query->set( 'orderby', 'post__in' );
	}
}
