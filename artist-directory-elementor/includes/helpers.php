<?php
/**
 * Template helper functions. Safe to call from theme templates and snippets.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the post of a given type for the current rendering context.
 *
 * Inside a Loop Grid item the current global post wins, otherwise the queried
 * object (the single artist / event page) is used.
 *
 * @param string $post_type Post type slug.
 * @return int Post ID or 0.
 */
function ade_context_post_id( string $post_type ): int {
	$id = get_the_ID();
	if ( $id && get_post_type( $id ) === $post_type ) {
		return (int) $id;
	}

	$queried = get_queried_object_id();
	if ( $queried && get_post_type( $queried ) === $post_type ) {
		return (int) $queried;
	}

	return 0;
}

/**
 * Normalise a list of IDs (array, CSV string or single value) to unique positive ints.
 *
 * @param mixed $value Raw value.
 * @return int[]
 */
function ade_sanitize_id_list( $value ): array {
	if ( is_string( $value ) ) {
		$value = explode( ',', $value );
	}
	if ( ! is_array( $value ) ) {
		$value = array( $value );
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
}

/**
 * Convert a datetime-local value ("2026-10-12T19:30"), "Y-m-d H:i" or "Y-m-d H:i:s"
 * to the stored "Y-m-d H:i:s" format (site timezone). Returns '' when invalid.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function ade_sanitize_datetime( $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$value = trim( str_replace( 'T', ' ', $value ) );
	foreach ( array( 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d' ) as $format ) {
		$dt = DateTimeImmutable::createFromFormat( '!' . $format, $value, wp_timezone() );
		if ( $dt && $dt->format( $format ) === $value ) {
			return $dt->format( 'Y-m-d H:i:s' );
		}
	}

	return '';
}

/**
 * Parse a stored datetime string in the site timezone.
 *
 * @param mixed $value Stored value.
 * @return DateTimeImmutable|null
 */
function ade_parse_datetime( $value ): ?DateTimeImmutable {
	if ( ! is_string( $value ) || '' === $value ) {
		return null;
	}

	$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() );

	return $dt ? $dt : null;
}

/**
 * Get an event's start and end. End falls back to start.
 *
 * @param int $event_id Event ID.
 * @return array{start: DateTimeImmutable|null, end: DateTimeImmutable|null}
 */
function ade_get_event_dates( int $event_id ): array {
	$start = ade_parse_datetime( get_post_meta( $event_id, 'ade_start_datetime', true ) );
	$end   = ade_parse_datetime( get_post_meta( $event_id, 'ade_end_datetime', true ) );

	if ( $start && ( ! $end || $end < $start ) ) {
		$end = $start;
	}

	return array(
		'start' => $start,
		'end'   => $end,
	);
}

/**
 * Format an event's date/time.
 *
 * Parts: range (default), start, end, start_date, start_time, end_date, end_time.
 *
 * @param int    $event_id    Event ID.
 * @param string $part        Which part to output.
 * @param string $date_format PHP date format. Empty = site setting.
 * @param string $time_format PHP time format. Empty = site setting.
 * @return string Plain text (not escaped).
 */
function ade_format_event_datetime( int $event_id, string $part = 'range', string $date_format = '', string $time_format = '' ): string {
	$dates = ade_get_event_dates( $event_id );
	if ( ! $dates['start'] ) {
		return '';
	}

	$date_format = '' !== $date_format ? $date_format : get_option( 'date_format' );
	$time_format = '' !== $time_format ? $time_format : get_option( 'time_format' );
	$start       = $dates['start']->getTimestamp();
	$end         = $dates['end']->getTimestamp();
	$separator   = apply_filters( 'ade_datetime_separator', ' · ' );

	switch ( $part ) {
		case 'start':
			return wp_date( $date_format, $start ) . $separator . wp_date( $time_format, $start );
		case 'end':
			return wp_date( $date_format, $end ) . $separator . wp_date( $time_format, $end );
		case 'start_date':
			return wp_date( $date_format, $start );
		case 'start_time':
			return wp_date( $time_format, $start );
		case 'end_date':
			return wp_date( $date_format, $end );
		case 'end_time':
			return wp_date( $time_format, $end );
	}

	// Range.
	if ( $start === $end ) {
		return wp_date( $date_format, $start ) . $separator . wp_date( $time_format, $start );
	}

	if ( wp_date( 'Y-m-d', $start ) === wp_date( 'Y-m-d', $end ) ) {
		return wp_date( $date_format, $start ) . $separator . wp_date( $time_format, $start ) . ' – ' . wp_date( $time_format, $end );
	}

	return wp_date( $date_format, $start ) . ' ' . wp_date( $time_format, $start ) . ' – ' . wp_date( $date_format, $end ) . ' ' . wp_date( $time_format, $end );
}

/**
 * Artist gallery attachment IDs (only attachments that still exist).
 *
 * @param int $artist_id Artist ID.
 * @return int[]
 */
function ade_get_artist_gallery_ids( int $artist_id ): array {
	$ids = ade_sanitize_id_list( get_post_meta( $artist_id, 'ade_gallery', true ) );

	return array_values( array_filter( $ids, static fn( $id ) => 'attachment' === get_post_type( $id ) ) );
}

/**
 * Artist IDs related to an event, in saved order.
 *
 * @param int $event_id Event ID.
 * @return int[]
 */
function ade_get_related_artist_ids( int $event_id ): array {
	return ade_sanitize_id_list( get_post_meta( $event_id, 'ade_related_artists', false ) );
}

/**
 * WooCommerce product IDs linked to an artist.
 *
 * @param int $artist_id Artist ID.
 * @return int[]
 */
function ade_get_linked_product_ids( int $artist_id ): array {
	return ade_sanitize_id_list( get_post_meta( $artist_id, 'ade_linked_products', true ) );
}

/**
 * Whether WooCommerce is active.
 */
function ade_is_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
}

/**
 * Plain-text description for an event (excerpt or trimmed content).
 *
 * @param int $post_id Post ID.
 * @param int $words   Max words.
 */
function ade_plain_excerpt( int $post_id, int $words = 50 ): string {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}

	$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );

	return wp_trim_words( wp_strip_all_tags( $text ), $words, '…' );
}

/**
 * Single-line location string for an event: "Venue, Address".
 *
 * @param int $event_id Event ID.
 */
function ade_event_location_text( int $event_id ): string {
	$parts = array_filter(
		array(
			(string) get_post_meta( $event_id, 'ade_venue', true ),
			preg_replace( '/\s*\R\s*/', ', ', (string) get_post_meta( $event_id, 'ade_address', true ) ),
		)
	);

	return implode( ', ', $parts );
}

/**
 * "Add to Google Calendar" URL for an event.
 *
 * @param int $event_id Event ID.
 */
function ade_google_calendar_url( int $event_id ): string {
	$dates = ade_get_event_dates( $event_id );
	if ( ! $dates['start'] ) {
		return '';
	}

	$utc  = new DateTimeZone( 'UTC' );
	$args = array(
		'action'   => 'TEMPLATE',
		'text'     => html_entity_decode( get_the_title( $event_id ), ENT_QUOTES, 'UTF-8' ),
		'dates'    => $dates['start']->setTimezone( $utc )->format( 'Ymd\THis\Z' ) . '/' . $dates['end']->setTimezone( $utc )->format( 'Ymd\THis\Z' ),
		'details'  => trim( ade_plain_excerpt( $event_id, 40 ) . "\n\n" . get_permalink( $event_id ) ),
		'location' => ade_event_location_text( $event_id ),
	);

	return 'https://calendar.google.com/calendar/render?' . http_build_query( array_filter( $args ), '', '&', PHP_QUERY_RFC3986 );
}

/**
 * .ics download URL for an event.
 *
 * @param int $event_id Event ID.
 */
function ade_ics_url( int $event_id ): string {
	if ( ! $event_id || 'event' !== get_post_type( $event_id ) ) {
		return '';
	}

	return add_query_arg( 'ade_ics', $event_id, home_url( '/' ) );
}

/**
 * Google Maps search URL for the event address (or venue).
 *
 * @param int $event_id Event ID.
 */
function ade_map_url( int $event_id ): string {
	$location = ade_event_location_text( $event_id );
	if ( '' === $location ) {
		return '';
	}

	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $location );
}

/**
 * Turn "@handle" / "handle" into a full profile URL for the given network,
 * pass full URLs through esc_url_raw().
 *
 * @param mixed  $value   Raw value.
 * @param string $network instagram|facebook|youtube|'' (website).
 */
function ade_sanitize_profile_url( $value, string $network = '' ): string {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	$bases = array(
		'instagram' => 'https://www.instagram.com/',
		'facebook'  => 'https://www.facebook.com/',
		'youtube'   => 'https://www.youtube.com/@',
	);

	if ( isset( $bases[ $network ] ) && preg_match( '/^@?([A-Za-z0-9._-]{1,100})$/', $value, $m ) ) {
		return esc_url_raw( $bases[ $network ] . $m[1] );
	}

	if ( ! preg_match( '#^https?://#i', $value ) ) {
		$value = 'https://' . $value;
	}

	return esc_url_raw( $value, array( 'http', 'https' ) );
}
