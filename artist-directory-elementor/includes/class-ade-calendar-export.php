<?php
/**
 * .ics download for single events: /?ade_ics={event_id}.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serves an iCalendar file for a published event.
 */
final class ADE_Calendar_Export {

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve' ) );
	}

	/**
	 * Register the query var.
	 *
	 * @param array $vars Public query vars.
	 */
	public static function query_vars( array $vars ): array {
		$vars[] = 'ade_ics';
		return $vars;
	}

	/**
	 * Serve the file when requested.
	 */
	public static function maybe_serve(): void {
		$event_id = absint( get_query_var( 'ade_ics' ) );
		if ( ! $event_id ) {
			return;
		}

		$post = get_post( $event_id );
		if ( ! $post || 'event' !== $post->post_type || 'publish' !== $post->post_status || post_password_required( $post ) ) {
			status_header( 404 );
			nocache_headers();
			wp_die( esc_html__( 'Event not found.', 'artist-directory-elementor' ), '', array( 'response' => 404 ) );
		}

		$ics = self::build( $event_id );
		if ( '' === $ics ) {
			wp_die( esc_html__( 'This event has no date yet.', 'artist-directory-elementor' ), '', array( 'response' => 404 ) );
		}

		$filename = sanitize_file_name( $post->post_name ? $post->post_name : 'event-' . $event_id ) . '.ics';

		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: public, max-age=300' );
		echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iCalendar text, escaped per RFC 5545 in build().
		exit;
	}

	/**
	 * Build the VCALENDAR text.
	 *
	 * @param int $event_id Event ID.
	 */
	public static function build( int $event_id ): string {
		$dates = ade_get_event_dates( $event_id );
		if ( ! $dates['start'] ) {
			return '';
		}

		$utc    = new DateTimeZone( 'UTC' );
		$format = 'Ymd\THis\Z';
		$host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$lines  = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//' . self::escape( get_bloginfo( 'name' ) ) . '//Artist Directory for Elementor//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:event-' . $event_id . '@' . $host,
			'DTSTAMP:' . gmdate( $format ),
			'DTSTART:' . $dates['start']->setTimezone( $utc )->format( $format ),
			'DTEND:' . $dates['end']->setTimezone( $utc )->format( $format ),
			'SUMMARY:' . self::escape( html_entity_decode( get_the_title( $event_id ), ENT_QUOTES, 'UTF-8' ) ),
			'URL:' . esc_url_raw( get_permalink( $event_id ) ),
		);

		$description = trim( ade_plain_excerpt( $event_id, 80 ) . "\n\n" . get_permalink( $event_id ) );
		$lines[]     = 'DESCRIPTION:' . self::escape( $description );

		$location = ade_event_location_text( $event_id );
		if ( '' !== $location ) {
			$lines[] = 'LOCATION:' . self::escape( $location );
		}

		$modified = get_post_modified_time( $format, true, $event_id );
		if ( $modified ) {
			$lines[] = 'LAST-MODIFIED:' . $modified;
		}

		$lines[] = 'END:VEVENT';
		$lines[] = 'END:VCALENDAR';

		return implode( "\r\n", array_map( array( __CLASS__, 'fold' ), $lines ) ) . "\r\n";
	}

	/**
	 * Escape a TEXT value (RFC 5545 §3.3.11).
	 *
	 * @param string $text Text.
	 */
	private static function escape( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\;', '\,' ), $text );

		return preg_replace( '/\r\n|\r|\n/', '\\n', $text );
	}

	/**
	 * Fold lines longer than 75 octets without splitting UTF-8 characters.
	 *
	 * @param string $line Content line.
	 */
	private static function fold( string $line ): string {
		if ( strlen( $line ) <= 75 ) {
			return $line;
		}

		$out   = '';
		$chunk = '';
		$limit = 75;
		foreach ( preg_split( '//u', $line, -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
			if ( strlen( $chunk ) + strlen( $char ) > $limit ) {
				$out  .= $chunk . "\r\n ";
				$chunk = '';
				$limit = 74; // Continuation lines start with a space.
			}
			$chunk .= $char;
		}

		return $out . $chunk;
	}
}
