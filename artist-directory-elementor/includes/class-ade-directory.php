<?php
/**
 * Artist directory: shared query building and result rendering.
 *
 * Used by the Directory Filter widget (first render), the REST endpoint (AJAX)
 * and the `ade_artist_directory` Loop Grid query ID, so all three agree.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Directory helpers.
 */
final class ADE_Directory {

	const SORTS        = array( 'az', 'za', 'newest' );
	const MAX_PER_PAGE = 48;

	/**
	 * Normalise filter params from any source (REST request or $_GET).
	 *
	 * Accepts both REST names (s, discipline, …) and URL names (ade_s, ade_discipline, …).
	 *
	 * @param array $source Raw params.
	 * @return array{s: string, discipline: string, location: string, sort: string, page: int}
	 */
	public static function params( array $source ): array {
		$get = static function ( string $key ) use ( $source ) {
			$value = $source[ $key ] ?? $source[ 'ade_' . $key ] ?? '';
			return is_scalar( $value ) ? (string) $value : '';
		};

		$sort = sanitize_key( $get( 'sort' ) );

		return array(
			's'          => mb_substr( sanitize_text_field( $get( 's' ) ), 0, 100 ),
			'discipline' => sanitize_title( $get( 'discipline' ) ),
			'location'   => sanitize_title( $get( 'location' ) ),
			'sort'       => in_array( $sort, self::SORTS, true ) ? $sort : 'az',
			'page'       => max( 1, absint( $get( 'page' ) ) ),
		);
	}

	/**
	 * Params from the current URL (?ade_s=…&ade_discipline=…).
	 */
	public static function params_from_url(): array {
		// Read-only public filters; no state change, so no nonce.
		return self::params( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Whether the URL carries any directory filter.
	 */
	public static function url_has_filters(): bool {
		foreach ( array( 'ade_s', 'ade_discipline', 'ade_location', 'ade_sort', 'ade_page' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}
		}
		return false;
	}

	/**
	 * Filter-specific WP_Query vars (search, taxonomy, order).
	 *
	 * @param array $params Normalised params.
	 */
	public static function filter_vars( array $params ): array {
		$vars = array();

		if ( '' !== $params['s'] ) {
			$vars['s'] = $params['s'];
		}

		$tax_query = array();
		if ( '' !== $params['discipline'] ) {
			$tax_query[] = array(
				'taxonomy' => 'discipline',
				'field'    => 'slug',
				'terms'    => $params['discipline'],
			);
		}
		if ( '' !== $params['location'] ) {
			$tax_query[] = array(
				'taxonomy' => 'artist_location',
				'field'    => 'slug',
				'terms'    => $params['location'],
			);
		}
		if ( $tax_query ) {
			$vars['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		switch ( $params['sort'] ) {
			case 'za':
				$vars['orderby'] = 'title';
				$vars['order']   = 'DESC';
				break;
			case 'newest':
				$vars['orderby'] = 'date';
				$vars['order']   = 'DESC';
				break;
			default:
				$vars['orderby'] = 'title';
				$vars['order']   = 'ASC';
		}

		return $vars;
	}

	/**
	 * Run the directory query.
	 *
	 * @param array $params   Normalised params.
	 * @param int   $per_page Items per page.
	 */
	public static function query( array $params, int $per_page ): WP_Query {
		$args = array(
			'post_type'      => 'artist',
			'post_status'    => 'publish',
			'posts_per_page' => min( max( 1, $per_page ), self::MAX_PER_PAGE ),
			'paged'          => $params['page'],
		) + self::filter_vars( $params );

		/**
		 * Filter the directory query args.
		 *
		 * @param array $args   WP_Query args.
		 * @param array $params Normalised filter params.
		 */
		return new WP_Query( apply_filters( 'ade_directory_query_args', $args, $params ) );
	}

	/**
	 * Whether a template ID is safe to render publicly.
	 *
	 * @param int $template_id Elementor template ID.
	 */
	public static function is_valid_template( int $template_id ): bool {
		return $template_id > 0
			&& 'elementor_library' === get_post_type( $template_id )
			&& 'publish' === get_post_status( $template_id );
	}

	/**
	 * Render each result with the Elementor loop-item template (or a fallback card).
	 *
	 * @param WP_Query $query       Results.
	 * @param int      $template_id Loop item template ID (0 = fallback card).
	 * @param bool     $with_css    Inline the template CSS once (needed for AJAX responses).
	 */
	public static function render_items( WP_Query $query, int $template_id, bool $with_css = false ): string {
		$use_template = self::is_valid_template( $template_id ) && class_exists( '\Elementor\Plugin' );
		$html         = '';
		$first        = true;

		while ( $query->have_posts() ) {
			$query->the_post();

			if ( $use_template ) {
				$html .= \Elementor\Plugin::instance()->frontend->get_builder_content( $template_id, $with_css && $first );
			} else {
				$html .= self::fallback_card( get_the_ID() );
			}
			$first = false;
		}
		wp_reset_postdata();

		return $html;
	}

	/**
	 * Minimal card used when no loop template is chosen.
	 *
	 * @param int $post_id Artist ID.
	 */
	public static function fallback_card( int $post_id ): string {
		$terms = get_the_terms( $post_id, 'discipline' );
		$names = is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();

		return sprintf(
			'<article class="ade-card"><a class="ade-card__link" href="%1$s">%2$s<h3 class="ade-card__title">%3$s</h3></a>%4$s</article>',
			esc_url( get_permalink( $post_id ) ),
			get_the_post_thumbnail( $post_id, 'medium_large', array( 'class' => 'ade-card__image' ) ),
			esc_html( get_the_title( $post_id ) ),
			$names ? '<p class="ade-card__meta">' . esc_html( implode( ', ', $names ) ) . '</p>' : ''
		);
	}

	/**
	 * Localised results count, for the aria-live region.
	 *
	 * @param int $total Total results.
	 */
	public static function count_text( int $total ): string {
		if ( 0 === $total ) {
			return __( 'No artists match your search.', 'artist-directory-elementor' );
		}

		/* translators: %s: number of artists. */
		return sprintf( _n( '%s artist found', '%s artists found', $total, 'artist-directory-elementor' ), number_format_i18n( $total ) );
	}

	/**
	 * Relative URL for a page of the current filters (works without JavaScript).
	 *
	 * @param array $params Normalised params.
	 * @param int   $page   Page number.
	 */
	public static function page_href( array $params, int $page ): string {
		$query = array_filter(
			array(
				'ade_s'          => $params['s'],
				'ade_discipline' => $params['discipline'],
				'ade_location'   => $params['location'],
				'ade_sort'       => 'az' !== $params['sort'] ? $params['sort'] : '',
				'ade_page'       => $page > 1 ? $page : '',
			)
		);

		return '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Pagination markup.
	 *
	 * @param array  $params Normalised params.
	 * @param int    $pages  Total pages.
	 * @param string $type   load_more|numbers|none.
	 * @param string $more   "Load more" label.
	 */
	public static function render_pagination( array $params, int $pages, string $type, string $more = '' ): string {
		$page = $params['page'];
		if ( 'none' === $type || $pages <= 1 ) {
			return '';
		}

		if ( 'load_more' === $type ) {
			if ( $page >= $pages ) {
				return '';
			}
			return sprintf(
				'<a class="ade-dir__more" role="button" href="%1$s" data-page="%2$d">%3$s</a>',
				esc_url( self::page_href( $params, $page + 1 ) ),
				$page + 1,
				esc_html( '' !== $more ? $more : __( 'Load more', 'artist-directory-elementor' ) )
			);
		}

		$numbers = array_unique( array_filter( array( 1, $page - 1, $page, $page + 1, $pages ), static fn( $n ) => $n >= 1 && $n <= $pages ) );
		sort( $numbers );

		$items = array();
		if ( $page > 1 ) {
			$items[] = self::page_link( $params, $page - 1, '<span aria-hidden="true">&lsaquo;</span>', __( 'Previous page', 'artist-directory-elementor' ), 'prev' );
		}

		$last = 0;
		foreach ( $numbers as $n ) {
			if ( $last && $n > $last + 1 ) {
				$items[] = '<li class="ade-dir__page ade-dir__page--gap" aria-hidden="true">&hellip;</li>';
			}
			if ( $n === $page ) {
				/* translators: %d: page number. */
				$items[] = sprintf( '<li class="ade-dir__page"><span class="ade-dir__page-link is-current" aria-current="page"><span class="ade-sr-only">%1$s </span>%2$d</span></li>', esc_html__( 'Page', 'artist-directory-elementor' ), $n );
			} else {
				/* translators: %d: page number. */
				$items[] = self::page_link( $params, $n, (string) $n, sprintf( __( 'Page %d', 'artist-directory-elementor' ), $n ) );
			}
			$last = $n;
		}

		if ( $page < $pages ) {
			$items[] = self::page_link( $params, $page + 1, '<span aria-hidden="true">&rsaquo;</span>', __( 'Next page', 'artist-directory-elementor' ), 'next' );
		}

		return '<ul class="ade-dir__pages">' . implode( '', $items ) . '</ul>';
	}

	/**
	 * One numbered pagination link.
	 *
	 * @param array  $params   Params.
	 * @param int    $page     Target page.
	 * @param string $inner    Trusted inner HTML.
	 * @param string $label    Accessible label.
	 * @param string $modifier Class modifier.
	 */
	private static function page_link( array $params, int $page, string $inner, string $label, string $modifier = '' ): string {
		return sprintf(
			'<li class="ade-dir__page%1$s"><a class="ade-dir__page-link" href="%2$s" data-page="%3$d" aria-label="%4$s">%5$s</a></li>',
			$modifier ? ' ade-dir__page--' . esc_attr( $modifier ) : '',
			esc_url( self::page_href( $params, $page ) ),
			$page,
			esc_attr( $label ),
			$inner
		);
	}
}
