<?php
/**
 * Schema.org JSON-LD for single artists (Person) and events (Event).
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints JSON-LD in wp_head.
 */
final class ADE_Schema {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'output' ), 20 );
	}

	/**
	 * Output for the current singular page.
	 */
	public static function output(): void {
		if ( ! is_singular( array( 'artist', 'event' ) ) || post_password_required() ) {
			return;
		}

		$post_id = get_queried_object_id();
		$data    = is_singular( 'artist' ) ? self::person( $post_id ) : self::event( $post_id );

		/**
		 * Filter the JSON-LD array. Return an empty value to disable (e.g. when an SEO plugin covers it).
		 *
		 * @param array $data    Schema data.
		 * @param int   $post_id Post ID.
		 */
		$data = apply_filters( 'ade_schema', $data, $post_id );
		if ( empty( $data ) ) {
			return;
		}

		echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n";
	}

	/**
	 * Remove empty values recursively.
	 *
	 * @param array $data Data.
	 */
	private static function clean( array $data ): array {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = self::clean( $value );
			}
			if ( '' === $value || null === $value || array() === $value ) {
				unset( $data[ $key ] );
			} else {
				$data[ $key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Term names for a post.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return string[]
	 */
	private static function term_names( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );

		return is_array( $terms ) ? array_values( wp_list_pluck( $terms, 'name' ) ) : array();
	}

	/**
	 * Person node for an artist.
	 *
	 * @param int  $artist_id Artist ID.
	 * @param bool $with_context Include @context.
	 */
	public static function person( int $artist_id, bool $with_context = true ): array {
		$same_as = array();
		foreach ( array( 'ade_website', 'ade_instagram', 'ade_facebook', 'ade_youtube' ) as $key ) {
			$url = (string) get_post_meta( $artist_id, $key, true );
			if ( $url ) {
				$same_as[] = esc_url_raw( $url );
			}
		}

		$locations = self::term_names( $artist_id, 'artist_location' );

		$data = array(
			'@context'     => $with_context ? 'https://schema.org' : '',
			'@type'        => 'Person',
			'@id'          => get_permalink( $artist_id ) . '#person',
			'name'         => html_entity_decode( get_the_title( $artist_id ), ENT_QUOTES, 'UTF-8' ),
			'url'          => get_permalink( $artist_id ),
			'image'        => (string) get_the_post_thumbnail_url( $artist_id, 'full' ),
			'description'  => $with_context ? ade_plain_excerpt( $artist_id, 60 ) : '',
			'jobTitle'     => $with_context ? implode( ', ', self::term_names( $artist_id, 'discipline' ) ) : '',
			'knowsAbout'   => $with_context ? self::term_names( $artist_id, 'discipline' ) : array(),
			'homeLocation' => $locations && $with_context ? array(
				'@type' => 'Place',
				'name'  => $locations[0],
			) : array(),
			'sameAs'       => $same_as,
		);

		return self::clean( $data );
	}

	/**
	 * Event node.
	 *
	 * @param int $event_id Event ID.
	 */
	public static function event( int $event_id ): array {
		$dates = ade_get_event_dates( $event_id );
		if ( ! $dates['start'] ) {
			return array();
		}

		$venue   = (string) get_post_meta( $event_id, 'ade_venue', true );
		$address = trim( preg_replace( '/\s*\R\s*/', ', ', (string) get_post_meta( $event_id, 'ade_address', true ) ) );
		$ticket  = (string) get_post_meta( $event_id, 'ade_ticket_url', true );

		$performers = array();
		foreach ( ade_get_related_artist_ids( $event_id ) as $artist_id ) {
			if ( 'publish' === get_post_status( $artist_id ) ) {
				$performers[] = self::person( $artist_id, false );
			}
		}

		$data = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'name'                => html_entity_decode( get_the_title( $event_id ), ENT_QUOTES, 'UTF-8' ),
			'url'                 => get_permalink( $event_id ),
			'description'         => ade_plain_excerpt( $event_id, 60 ),
			'image'               => (string) get_the_post_thumbnail_url( $event_id, 'full' ),
			'startDate'           => $dates['start']->format( DATE_ATOM ),
			'endDate'             => $dates['end']->format( DATE_ATOM ),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'location'            => ( $venue || $address ) ? array(
				'@type'   => 'Place',
				'name'    => $venue ? $venue : $address,
				'address' => $address ? array(
					'@type'         => 'PostalAddress',
					'streetAddress' => $address,
				) : '',
			) : array(),
			'performer'           => $performers,
			'organizer'           => array(
				'@type' => 'Organization',
				'name'  => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
				'url'   => home_url( '/' ),
			),
			'offers'              => $ticket ? array(
				'@type'        => 'Offer',
				'url'          => esc_url_raw( $ticket ),
				'availability' => 'https://schema.org/InStock',
				'validFrom'    => get_post_time( DATE_ATOM, false, $event_id ),
			) : array(),
		);

		return self::clean( $data );
	}
}
