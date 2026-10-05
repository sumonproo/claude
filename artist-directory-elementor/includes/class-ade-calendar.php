<?php
/**
 * Events calendar renderer (month grid + list), shared by the widget and REST.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Calendar markup builder.
 */
final class ADE_Calendar {

	const HEADING_TAGS = array( 'h2', 'h3', 'h4', 'div' );

	/**
	 * Events found by the last render() call.
	 *
	 * @var int
	 */
	private static int $last_count = 0;

	/**
	 * Normalise display options.
	 *
	 * @param array $raw Raw options (REST params or widget settings mapped to the same keys).
	 * @return array{view: string, week_start: int, show_time: bool, show_venue: bool, show_thumb: bool, toggle: bool, tag: string, empty_text: string}
	 */
	public static function args( array $raw ): array {
		$view       = isset( $raw['view'] ) && 'list' === $raw['view'] ? 'list' : 'month';
		$week_start = isset( $raw['week_start'] ) && '' !== $raw['week_start'] ? (int) $raw['week_start'] : (int) get_option( 'start_of_week', 1 );
		$tag        = isset( $raw['tag'] ) && in_array( $raw['tag'], self::HEADING_TAGS, true ) ? $raw['tag'] : 'h2';
		$bool       = static fn( string $key, bool $fallback ) => isset( $raw[ $key ] ) ? rest_sanitize_boolean( $raw[ $key ] ) : $fallback;

		return array(
			'view'       => $view,
			'week_start' => min( 6, max( 0, $week_start ) ),
			'show_time'  => $bool( 'show_time', true ),
			'show_venue' => $bool( 'show_venue', true ),
			'show_thumb' => $bool( 'show_thumb', false ),
			'toggle'     => $bool( 'toggle', true ),
			'tag'        => $tag,
			'empty_text' => isset( $raw['empty_text'] ) ? sanitize_text_field( (string) $raw['empty_text'] ) : '',
		);
	}

	/**
	 * First day of a "YYYY-MM" month in the site timezone; current month when invalid.
	 *
	 * @param string $ym Month string.
	 */
	public static function parse_month( string $ym ): DateTimeImmutable {
		if ( preg_match( '/^(\d{4})-(\d{2})$/', $ym, $m ) && (int) $m[2] >= 1 && (int) $m[2] <= 12 && (int) $m[1] >= 1970 && (int) $m[1] <= 2100 ) {
			return new DateTimeImmutable( sprintf( '%04d-%02d-01 00:00:00', $m[1], $m[2] ), wp_timezone() );
		}

		return new DateTimeImmutable( current_time( 'Y-m-01 00:00:00' ), wp_timezone() );
	}

	/**
	 * Month from the URL (?ade_month=YYYY-MM).
	 */
	public static function month_from_url(): DateTimeImmutable {
		$ym = isset( $_GET['ade_month'] ) ? sanitize_text_field( wp_unslash( $_GET['ade_month'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return self::parse_month( $ym );
	}

	/**
	 * View from the URL (?ade_view=list), or null.
	 */
	public static function view_from_url(): ?string {
		$view = isset( $_GET['ade_view'] ) ? sanitize_key( wp_unslash( $_GET['ade_view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return in_array( $view, array( 'month', 'list' ), true ) ? $view : null;
	}

	/**
	 * Published events overlapping a range, sorted by start.
	 *
	 * @param DateTimeImmutable $from Range start.
	 * @param DateTimeImmutable $to   Range end.
	 * @return array<int, array{id: int, start: DateTimeImmutable, end: DateTimeImmutable}>
	 */
	public static function get_events( DateTimeImmutable $from, DateTimeImmutable $to ): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => 300, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- One month window; an arts calendar never gets near this.
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation'  => 'AND',
					'ade_start' => array(
						'key'     => 'ade_start_datetime',
						'value'   => $to->format( 'Y-m-d H:i:s' ),
						'compare' => '<=',
						'type'    => 'DATETIME',
					),
					'ade_end'   => array(
						'key'     => 'ade_end_datetime',
						'value'   => $from->format( 'Y-m-d H:i:s' ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
				),
				'orderby'        => array( 'ade_start' => 'ASC' ),
			)
		);

		$events = array();
		foreach ( $query->posts as $id ) {
			$dates = ade_get_event_dates( (int) $id );
			if ( $dates['start'] ) {
				$events[] = array(
					'id'    => (int) $id,
					'start' => $dates['start'],
					'end'   => $dates['end'],
				);
			}
		}

		return $events;
	}

	/**
	 * Screen-reader status after a change.
	 *
	 * @param DateTimeImmutable $month Month.
	 * @param string            $view  View.
	 */
	public static function status_text( DateTimeImmutable $month, string $view ): string {
		$label = wp_date( 'F Y', $month->getTimestamp() );
		$view  = 'list' === $view ? __( 'list view', 'artist-directory-elementor' ) : __( 'month view', 'artist-directory-elementor' );

		/* translators: 1: month and year, 2: view name, 3: number of events. */
		return sprintf( _n( '%1$s, %2$s: %3$s event', '%1$s, %2$s: %3$s events', self::$last_count, 'artist-directory-elementor' ), $label, $view, number_format_i18n( self::$last_count ) );
	}

	/**
	 * Calendar inner markup (header + body).
	 *
	 * @param DateTimeImmutable $month First day of the month.
	 * @param array             $args  Normalised args.
	 */
	public static function render( DateTimeImmutable $month, array $args ): string {
		$from   = $month;
		$to     = $month->modify( 'last day of this month' )->setTime( 23, 59, 59 );
		$events = self::get_events( $from, $to );

		self::$last_count = count( $events );

		$body = 'list' === $args['view'] ? self::render_list( $events, $month, $args ) : self::render_grid( $events, $month, $args );

		return self::render_header( $month, $args ) . '<div class="ade-cal__body">' . $body . '</div>';
	}

	/**
	 * Relative no-JS URL for a month/view.
	 *
	 * @param string $ym   Month.
	 * @param string $view View.
	 */
	private static function href( string $ym, string $view ): string {
		return '?' . http_build_query(
			array(
				'ade_month' => $ym,
				'ade_view'  => $view,
			)
		);
	}

	/**
	 * Header: prev / title / next, view toggle.
	 *
	 * @param DateTimeImmutable $month Month.
	 * @param array             $args  Args.
	 */
	private static function render_header( DateTimeImmutable $month, array $args ): string {
		$prev  = $month->modify( 'first day of previous month' );
		$next  = $month->modify( 'first day of next month' );
		$tag   = $args['tag'];
		$today = current_time( 'Y-m' );

		$nav = static function ( DateTimeImmutable $target, string $css_class, string $label, string $symbol ) use ( $args ): string {
			$name = wp_date( 'F Y', $target->getTimestamp() );
			return sprintf(
				'<a class="ade-cal__nav-btn %1$s" href="%2$s" data-month="%3$s" aria-label="%4$s"><span aria-hidden="true">%5$s</span></a>',
				esc_attr( $css_class ),
				esc_url( self::href( $target->format( 'Y-m' ), $args['view'] ) ),
				esc_attr( $target->format( 'Y-m' ) ),
				/* translators: 1: "Previous month" / "Next month", 2: month and year. */
				esc_attr( sprintf( __( '%1$s, %2$s', 'artist-directory-elementor' ), $label, $name ) ),
				$symbol
			);
		};

		$html  = '<div class="ade-cal__header">';
		$html .= '<div class="ade-cal__nav">';
		$html .= $nav( $prev, 'ade-cal__prev', __( 'Previous month', 'artist-directory-elementor' ), '&#8249;' );
		$html .= sprintf( '<%1$s class="ade-cal__title">%2$s</%1$s>', tag_escape( $tag ), esc_html( wp_date( 'F Y', $month->getTimestamp() ) ) );
		$html .= $nav( $next, 'ade-cal__next', __( 'Next month', 'artist-directory-elementor' ), '&#8250;' );
		$html .= '</div>';

		$html .= '<div class="ade-cal__tools">';
		if ( $month->format( 'Y-m' ) !== $today ) {
			$html .= sprintf(
				'<a class="ade-cal__today" href="%1$s" data-month="%2$s">%3$s</a>',
				esc_url( self::href( $today, $args['view'] ) ),
				esc_attr( $today ),
				esc_html__( 'This month', 'artist-directory-elementor' )
			);
		}

		if ( $args['toggle'] ) {
			$html .= '<div class="ade-cal__views" role="group" aria-label="' . esc_attr__( 'Calendar view', 'artist-directory-elementor' ) . '">';
			foreach ( array(
				'month' => __( 'Month', 'artist-directory-elementor' ),
				'list'  => __( 'List', 'artist-directory-elementor' ),
			) as $view => $label ) {
				$active = $view === $args['view'];
				$html  .= sprintf(
					'<a class="ade-cal__view-btn%1$s" href="%2$s" data-view="%3$s"%4$s>%5$s</a>',
					$active ? ' is-active' : '',
					esc_url( self::href( $month->format( 'Y-m' ), $view ) ),
					esc_attr( $view ),
					$active ? ' aria-current="true"' : '',
					esc_html( $label )
				);
			}
			$html .= '</div>';
		}
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Group events by each local day they cover within the month.
	 *
	 * @param array             $events Events.
	 * @param DateTimeImmutable $month  Month.
	 * @return array<string, array>
	 */
	private static function events_by_day( array $events, DateTimeImmutable $month ): array {
		$first = $month->format( 'Y-m-d' );
		$last  = $month->modify( 'last day of this month' )->format( 'Y-m-d' );
		$days  = array();

		foreach ( $events as $event ) {
			$day = $event['start']->format( 'Y-m-d' ) < $first ? $month : $event['start']->setTime( 0, 0 );
			$end = $event['end']->format( 'Y-m-d' );

			$stop = min( $end, $last );
			$key  = $day->format( 'Y-m-d' );
			while ( $key <= $stop ) {
				$days[ $key ][] = $event + array( 'continuing' => $key !== $event['start']->format( 'Y-m-d' ) );
				$day            = $day->modify( '+1 day' );
				$key            = $day->format( 'Y-m-d' );
			}
		}

		return $days;
	}

	/**
	 * Month grid.
	 *
	 * @param array             $events Events.
	 * @param DateTimeImmutable $month  Month.
	 * @param array             $args   Args.
	 */
	private static function render_grid( array $events, DateTimeImmutable $month, array $args ): string {
		global $wp_locale;

		$by_day      = self::events_by_day( $events, $month );
		$today       = current_time( 'Y-m-d' );
		$days_in     = (int) $month->format( 't' );
		$offset      = ( (int) $month->format( 'w' ) - $args['week_start'] + 7 ) % 7;
		$cells       = (int) ceil( ( $offset + $days_in ) / 7 ) * 7;
		$time_format = get_option( 'time_format' );

		$html  = '<table class="ade-cal__grid">';
		$html .= '<caption class="ade-sr-only">' . esc_html( wp_date( 'F Y', $month->getTimestamp() ) ) . '</caption>';
		$html .= '<thead><tr>';
		for ( $i = 0; $i < 7; $i++ ) {
			$weekday = $wp_locale->get_weekday( ( $args['week_start'] + $i ) % 7 );
			$html   .= sprintf( '<th scope="col"><abbr title="%1$s">%2$s</abbr></th>', esc_attr( $weekday ), esc_html( $wp_locale->get_weekday_abbrev( $weekday ) ) );
		}
		$html .= '</tr></thead><tbody>';

		for ( $cell = 0; $cell < $cells; $cell++ ) {
			if ( 0 === $cell % 7 ) {
				$html .= '<tr>';
			}

			$day_num = $cell - $offset + 1;
			if ( $day_num < 1 || $day_num > $days_in ) {
				$html .= '<td class="ade-cal__day is-outside"></td>';
			} else {
				$date    = $month->setDate( (int) $month->format( 'Y' ), (int) $month->format( 'n' ), $day_num );
				$key     = $date->format( 'Y-m-d' );
				$items   = $by_day[ $key ] ?? array();
				$classes = array( 'ade-cal__day' );
				if ( $key === $today ) {
					$classes[] = 'is-today';
				}
				if ( $key < $today ) {
					$classes[] = 'is-past';
				}
				if ( $items ) {
					$classes[] = 'has-events';
				}

				$html .= '<td class="' . esc_attr( implode( ' ', $classes ) ) . '">';
				$html .= sprintf(
					'<time class="ade-cal__date" datetime="%1$s"%2$s><span class="ade-cal__date-weekday">%3$s </span><span class="ade-cal__date-num">%4$s</span></time>',
					esc_attr( $key ),
					$key === $today ? ' aria-current="date"' : '',
					esc_html( wp_date( 'l, F', $date->getTimestamp() ) ),
					esc_html( number_format_i18n( $day_num ) )
				);

				if ( $items ) {
					$html .= '<ul class="ade-cal__events">';
					foreach ( $items as $item ) {
						$time  = $args['show_time'] && ! $item['continuing']
							? '<span class="ade-cal__event-time">' . esc_html( wp_date( $time_format, $item['start']->getTimestamp() ) ) . '</span> '
							: '';
						$html .= sprintf(
							'<li class="ade-cal__event%1$s"><a href="%2$s">%3$s<span class="ade-cal__event-title">%4$s</span></a></li>',
							$item['continuing'] ? ' is-continuing' : '',
							esc_url( get_permalink( $item['id'] ) ),
							$time,
							esc_html( get_the_title( $item['id'] ) )
						);
					}
					$html .= '</ul>';
				}
				$html .= '</td>';
			}

			if ( 6 === $cell % 7 ) {
				$html .= '</tr>';
			}
		}

		$html .= '</tbody></table>';

		if ( ! $events ) {
			$html .= '<p class="ade-cal__empty ade-cal__empty--grid">' . esc_html( self::empty_text( $args ) ) . '</p>';
		}

		return $html;
	}

	/**
	 * List view.
	 *
	 * @param array             $events Events.
	 * @param DateTimeImmutable $month  Month.
	 * @param array             $args   Args.
	 */
	private static function render_list( array $events, DateTimeImmutable $month, array $args ): string {
		if ( ! $events ) {
			return '<p class="ade-cal__empty">' . esc_html( self::empty_text( $args ) ) . '</p>';
		}

		$item_tag = array(
			'h2'  => 'h3',
			'h3'  => 'h4',
			'h4'  => 'h5',
			'div' => 'div',
		)[ $args['tag'] ];

		$html = '<ol class="ade-cal__list">';
		foreach ( $events as $event ) {
			$id    = $event['id'];
			$badge = $event['start'] < $month ? $month : $event['start'];
			$meta  = array();
			if ( $args['show_time'] ) {
				$meta[] = '<span class="ade-cal__item-when">' . esc_html( ade_format_event_datetime( $id ) ) . '</span>';
			}
			$venue = (string) get_post_meta( $id, 'ade_venue', true );
			if ( $args['show_venue'] && '' !== $venue ) {
				$meta[] = '<span class="ade-cal__item-venue">' . esc_html( $venue ) . '</span>';
			}

			$thumb = '';
			if ( $args['show_thumb'] && has_post_thumbnail( $id ) ) {
				$thumb = sprintf(
					'<a class="ade-cal__item-thumb" href="%1$s" tabindex="-1" aria-hidden="true">%2$s</a>',
					esc_url( get_permalink( $id ) ),
					get_the_post_thumbnail( $id, 'medium', array( 'alt' => '' ) )
				);
			}

			$html .= sprintf(
				'<li class="ade-cal__item%1$s">%2$s<time class="ade-cal__badge" datetime="%3$s"><span class="ade-cal__badge-month">%4$s</span><span class="ade-cal__badge-day">%5$s</span></time><div class="ade-cal__item-body"><%6$s class="ade-cal__item-title"><a href="%7$s">%8$s</a></%6$s>%9$s</div></li>',
				$event['end']->format( 'Y-m-d H:i:s' ) < current_time( 'mysql' ) ? ' is-past' : '',
				$thumb,
				esc_attr( $badge->format( 'Y-m-d' ) ),
				esc_html( wp_date( 'M', $badge->getTimestamp() ) ),
				esc_html( wp_date( 'j', $badge->getTimestamp() ) ),
				tag_escape( $item_tag ),
				esc_url( get_permalink( $id ) ),
				esc_html( get_the_title( $id ) ),
				$meta ? '<p class="ade-cal__item-meta">' . implode( '', $meta ) . '</p>' : ''
			);
		}
		$html .= '</ol>';

		return $html;
	}

	/**
	 * Empty-state text.
	 *
	 * @param array $args Args.
	 */
	private static function empty_text( array $args ): string {
		return '' !== $args['empty_text'] ? $args['empty_text'] : __( 'No events this month.', 'artist-directory-elementor' );
	}
}
