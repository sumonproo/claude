<?php
/**
 * WP-CLI: demo content.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seed and remove demo artists, events, disciplines and locations.
 */
class ADE_CLI {

	const SEED_KEY = '_ade_seed';

	/**
	 * Demo disciplines.
	 */
	const DISCIPLINES = array( 'Painting', 'Sculpture', 'Photography', 'Music', 'Dance', 'Ceramics' );

	/**
	 * Demo locations.
	 */
	const LOCATIONS = array( 'Downtown', 'Riverside', 'Northside', 'Harbor District' );

	/**
	 * Demo artists: name, discipline indexes, location index, featured, handle, bio.
	 */
	const ARTISTS = array(
		array( 'Maya Okafor', array( 0 ), 0, true, 'mayaokafor', 'Large-scale oil paintings about migration, memory and the colors of West African textiles.' ),
		array( 'Lucas Brennan', array( 1 ), 1, false, 'lucasbrennan', 'Welded steel and reclaimed timber sculpture for public spaces and riverside parks.' ),
		array( 'Ines Duarte', array( 2 ), 2, true, 'inesduarte', 'Documentary photographer following working harbors, night shifts and the people who keep cities running.' ),
		array( 'Theo Lindqvist', array( 3 ), 3, false, 'theolindqvist', 'Composer and cellist blending Nordic folk melodies with ambient electronics.' ),
		array( 'Amara Singh', array( 4 ), 0, true, 'amarasingh', 'Contemporary dancer and choreographer rooted in Kathak, making site-specific work.' ),
		array( 'Kenji Watanabe', array( 5 ), 1, false, 'kenjiwatanabe', 'Wood-fired stoneware and tea bowls shaped by slow kilns and river clay.' ),
		array( 'Sofia Marchetti', array( 0, 2 ), 2, false, 'sofiamarchetti', 'Mixed-media painter who layers her own street photography under translucent glazes.' ),
		array( 'Daniel Mensah', array( 3, 4 ), 3, true, 'danielmensah', 'Percussionist and movement artist leading community drum-and-dance workshops.' ),
		array( 'Elena Petrova', array( 1, 5 ), 0, false, 'elenapetrova', 'Ceramic sculptor building architectural forms from hand-built porcelain slabs.' ),
		array( 'Omar Haddad', array( 2 ), 1, false, 'omarhaddad', 'Portrait photographer creating intimate series with musicians and makers.' ),
		array( 'Grace Whitfield', array( 0 ), 2, false, 'gracewhitfield', 'Watercolor landscapes of the northern wetlands, painted outdoors in all seasons.' ),
		array( 'Rafael Costa', array( 3 ), 3, false, 'rafaelcosta', 'Jazz guitarist and bandleader, regular at harbor venues and summer festivals.' ),
	);

	/**
	 * Demo events: title, start offset (days), start hour, duration (hours), venue index, artist indexes, ticketed.
	 */
	const EVENTS = array(
		array( 'Winter Open Studios', -60, 11, 6, 0, array( 0, 6, 10 ), false ),
		array( 'Clay & Fire: Kiln Opening', -30, 14, 3, 1, array( 5, 8 ), false ),
		array( 'Harbor Nights Photo Walk', -10, 19, 2, 2, array( 2, 9 ), true ),
		array( 'Cello at Dusk', 3, 19, 2, 3, array( 3 ), true ),
		array( 'Rhythm & Motion Workshop', 10, 10, 3, 0, array( 4, 7 ), true ),
		array( 'New Paintings: Opening Reception', 21, 18, 3, 1, array( 0, 6 ), false ),
		array( 'Riverside Sculpture Weekend', 35, 10, 56, 1, array( 1, 8 ), false ),
		array( 'Harbor Jazz Night', 50, 20, 3, 2, array( 11, 7, 3 ), true ),
	);

	/**
	 * Demo venues: name, address.
	 */
	const VENUES = array(
		array( 'Main Street Arts Center', "120 Main Street\nDowntown" ),
		array( 'Riverside Pavilion', "8 River Walk\nRiverside" ),
		array( 'Pier 4 Warehouse', "4 Harbor Road\nHarbor District" ),
		array( 'Northside Chapel Hall', "51 Elm Avenue\nNorthside" ),
	);

	/**
	 * Create demo disciplines, locations, artists and events.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Remove existing demo content first, then seed again.
	 *
	 * [--no-images]
	 * : Skip generating placeholder images (faster; needs no GD).
	 *
	 * ## EXAMPLES
	 *
	 *     wp ade seed
	 *     wp ade seed --force
	 *     wp ade seed --no-images
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function seed( $args, $assoc_args ) {
		unset( $args );

		if ( $this->seeded_post_ids() ) {
			if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false ) ) {
				WP_CLI::error( 'Demo content already exists. Use --force to recreate it, or `wp ade unseed`.' );
			}
			$this->unseed( array(), array( 'yes' => true ) );
		}

		$images = \WP_CLI\Utils\get_flag_value( $assoc_args, 'images', true ) && function_exists( 'imagecreatetruecolor' );
		if ( ! $images ) {
			WP_CLI::log( 'Images: skipped.' );
		}

		$disciplines = $this->terms( self::DISCIPLINES, 'discipline' );
		$locations   = $this->terms( self::LOCATIONS, 'artist_location' );
		WP_CLI::log( sprintf( 'Terms: %d disciplines, %d locations.', count( $disciplines ), count( $locations ) ) );

		$artist_ids = array();
		$progress   = \WP_CLI\Utils\make_progress_bar( 'Artists', count( self::ARTISTS ) );
		foreach ( self::ARTISTS as $i => $artist ) {
			list( $name, $disc, $loc, $featured, $handle, $bio ) = $artist;

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'artist',
					'post_status'  => 'publish',
					'post_title'   => $name,
					'post_excerpt' => $bio,
					'post_content' => wpautop( $bio . "\n\n" . 'Based in ' . self::LOCATIONS[ $loc ] . ', ' . strtok( $name, ' ' ) . ' exhibits regularly across the region and teaches community classes. This is demo content created by `wp ade seed`.' ),
					'post_date'    => gmdate( 'Y-m-d H:i:s', time() - ( 12 - $i ) * DAY_IN_SECONDS ),
					'meta_input'   => array_filter(
						array(
							self::SEED_KEY       => 1,
							'ade_website'        => 'https://example.com/' . $handle,
							'ade_instagram'      => '@' . $handle,
							'ade_facebook'       => 0 === $i % 2 ? $handle : '',
							'ade_youtube'        => in_array( 3, $disc, true ) || in_array( 4, $disc, true ) ? '@' . $handle : '',
							'_ade_contact_email' => $handle . '@example.com',
						)
					),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( $name . ': ' . $post_id->get_error_message() );
				continue;
			}

			if ( $featured ) {
				update_post_meta( $post_id, 'ade_featured', true );
			}

			wp_set_object_terms( $post_id, array_map( static fn( $d ) => $disciplines[ $d ], $disc ), 'discipline' );
			wp_set_object_terms( $post_id, array( $locations[ $loc ] ), 'artist_location' );

			if ( $images ) {
				$hue      = ( $i * 30 ) % 360;
				$portrait = $this->image( $name, $hue, 900, 900, $post_id );
				if ( $portrait ) {
					set_post_thumbnail( $post_id, $portrait );
				}
				$gallery = array();
				for ( $g = 1; $g <= 3; $g++ ) {
					$image = $this->image( $name . ' – work ' . $g, ( $hue + $g * 40 ) % 360, 1200, 900, $post_id );
					if ( $image ) {
						$gallery[] = $image;
					}
				}
				update_post_meta( $post_id, 'ade_gallery', $gallery );
			}

			$artist_ids[ $i ] = $post_id;
			$progress->tick();
		}
		$progress->finish();

		$now      = new DateTimeImmutable( 'now', wp_timezone() );
		$progress = \WP_CLI\Utils\make_progress_bar( 'Events', count( self::EVENTS ) );
		foreach ( self::EVENTS as $i => $event ) {
			list( $title, $offset, $hour, $hours, $venue, $artists, $ticketed ) = $event;

			$start = $now->setTime( $hour, 0 )->modify( sprintf( '%+d days', $offset ) );
			$end   = $start->modify( sprintf( '+%d hours', $hours ) );
			$names = array_map( static fn( $a ) => self::ARTISTS[ $a ][0], $artists );

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'event',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => 'With ' . implode( ', ', $names ) . ' at ' . self::VENUES[ $venue ][0] . '.',
					'post_content' => wpautop( 'Join ' . implode( ', ', $names ) . ' at ' . self::VENUES[ $venue ][0] . ". Free parking nearby, step-free access, and refreshments from local makers.\n\nThis is demo content created by `wp ade seed`." ),
					'meta_input'   => array_filter(
						array(
							self::SEED_KEY       => 1,
							'ade_start_datetime' => $start->format( 'Y-m-d H:i:s' ),
							'ade_end_datetime'   => $end->format( 'Y-m-d H:i:s' ),
							'ade_venue'          => self::VENUES[ $venue ][0],
							'ade_address'        => self::VENUES[ $venue ][1],
							'ade_ticket_url'     => $ticketed ? 'https://example.com/tickets/' . sanitize_title( $title ) : '',
						)
					),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( $title . ': ' . $post_id->get_error_message() );
				continue;
			}

			foreach ( $artists as $a ) {
				if ( isset( $artist_ids[ $a ] ) ) {
					add_post_meta( $post_id, 'ade_related_artists', $artist_ids[ $a ] );
				}
			}

			if ( $images ) {
				$image = $this->image( $title, ( $i * 45 + 200 ) % 360, 1600, 900, $post_id );
				if ( $image ) {
					set_post_thumbnail( $post_id, $image );
				}
			}

			$progress->tick();
		}
		$progress->finish();

		flush_rewrite_rules( false );

		WP_CLI::success( sprintf( 'Seeded %d artists and %d events.', count( $artist_ids ), count( self::EVENTS ) ) );
	}

	/**
	 * Delete all demo content created by `wp ade seed`.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp ade unseed --yes
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function unseed( $args, $assoc_args ) {
		unset( $args );
		WP_CLI::confirm( 'Delete all demo artists, events, images and demo terms?', $assoc_args );

		$posts = $this->seeded_post_ids( array( 'artist', 'event', 'attachment' ) );
		foreach ( $posts as $post_id ) {
			if ( 'attachment' === get_post_type( $post_id ) ) {
				wp_delete_attachment( $post_id, true );
			} else {
				wp_delete_post( $post_id, true );
			}
		}

		$terms = get_terms(
			array(
				'taxonomy'   => array( 'discipline', 'artist_location' ),
				'hide_empty' => false,
				'meta_key'   => self::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'fields'     => 'all',
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}

		WP_CLI::success( sprintf( 'Removed %d demo posts/attachments.', count( $posts ) ) );
	}

	/**
	 * IDs of seeded posts.
	 *
	 * @param string[] $types Post types.
	 * @return int[]
	 */
	private function seeded_post_ids( array $types = array( 'artist', 'event' ) ): array {
		return get_posts(
			array(
				'post_type'      => $types,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
	}

	/**
	 * Get or create terms; new terms are marked for unseed.
	 *
	 * @param string[] $names    Term names.
	 * @param string   $taxonomy Taxonomy.
	 * @return int[] Term IDs in the same order.
	 */
	private function terms( array $names, string $taxonomy ): array {
		$ids = array();
		foreach ( $names as $name ) {
			$existing = term_exists( $name, $taxonomy );
			if ( $existing ) {
				$ids[] = (int) $existing['term_id'];
				continue;
			}
			$created = wp_insert_term( $name, $taxonomy );
			if ( is_wp_error( $created ) ) {
				WP_CLI::warning( $name . ': ' . $created->get_error_message() );
				$ids[] = 0;
				continue;
			}
			add_term_meta( $created['term_id'], self::SEED_KEY, 1, true );
			$ids[] = (int) $created['term_id'];
		}

		return $ids;
	}

	/**
	 * Generate a placeholder JPEG with GD and add it to the Media Library.
	 *
	 * @param string $title   Title / alt text.
	 * @param int    $hue     Base hue 0-359.
	 * @param int    $width   Width.
	 * @param int    $height  Height.
	 * @param int    $post_id Parent post.
	 * @return int Attachment ID or 0.
	 */
	private function image( string $title, int $hue, int $width, int $height, int $post_id ): int {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$img                  = imagecreatetruecolor( $width, $height );
		list( $r1, $g1, $b1 ) = $this->hsl( $hue, 0.55, 0.45 );
		list( $r2, $g2, $b2 ) = $this->hsl( ( $hue + 50 ) % 360, 0.6, 0.7 );

		// Diagonal gradient.
		for ( $y = 0; $y < $height; $y += 2 ) {
			$t     = $y / $height;
			$color = imagecolorallocate( $img, (int) ( $r1 + ( $r2 - $r1 ) * $t ), (int) ( $g1 + ( $g2 - $g1 ) * $t ), (int) ( $b1 + ( $b2 - $b1 ) * $t ) );
			imagefilledrectangle( $img, 0, $y, $width, $y + 1, $color );
		}

		// A few soft shapes so gallery images differ. Seeded so re-runs give the same images.
		// phpcs:disable WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand, WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_srand( crc32( $title ) );
		for ( $s = 0; $s < 5; $s++ ) {
			list( $r, $g, $b ) = $this->hsl( ( $hue + mt_rand( 0, 120 ) ) % 360, 0.5, 0.6 + $s * 0.05 );
			$shape             = imagecolorallocatealpha( $img, $r, $g, $b, 70 );
			$size              = mt_rand( (int) ( $height * 0.2 ), (int) ( $height * 0.7 ) );
			imagefilledellipse( $img, mt_rand( 0, $width ), mt_rand( 0, $height ), $size, $size, $shape );
		}
		mt_srand();
		// phpcs:enable

		$tmp = wp_tempnam( 'ade-seed.jpg' );
		imagejpeg( $img, $tmp, 82 );
		imagedestroy( $img );

		$attachment_id = media_handle_sideload(
			array(
				'name'     => sanitize_title( $title ) . '.jpg',
				'tmp_name' => $tmp,
			),
			$post_id,
			$title
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $tmp );
			WP_CLI::warning( 'Image: ' . $attachment_id->get_error_message() );
			return 0;
		}

		update_post_meta( $attachment_id, self::SEED_KEY, 1 );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );

		return (int) $attachment_id;
	}

	/**
	 * HSL → RGB.
	 *
	 * @param int   $h Hue 0-359.
	 * @param float $s Saturation 0-1.
	 * @param float $l Lightness 0-1.
	 * @return int[]
	 */
	private function hsl( int $h, float $s, float $l ): array {
		$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
		$x = $c * ( 1 - abs( fmod( $h / 60, 2 ) - 1 ) );
		$m = $l - $c / 2;

		$map = array(
			array( $c, $x, 0 ),
			array( $x, $c, 0 ),
			array( 0, $c, $x ),
			array( 0, $x, $c ),
			array( $x, 0, $c ),
			array( $c, 0, $x ),
		)[ intdiv( $h, 60 ) % 6 ];

		return array_map( static fn( $v ) => (int) round( ( $v + $m ) * 255 ), $map );
	}
}
