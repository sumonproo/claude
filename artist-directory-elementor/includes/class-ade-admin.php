<?php
/**
 * Admin meta boxes and list-table columns.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta box UI for artists and events.
 */
final class ADE_Admin {

	const NONCE_ACTION = 'ade_save_meta';
	const NONCE_FIELD  = 'ade_meta_nonce';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes_artist', array( __CLASS__, 'add_artist_boxes' ) );
		add_action( 'add_meta_boxes_event', array( __CLASS__, 'add_event_boxes' ) );
		add_action( 'save_post_artist', array( __CLASS__, 'save_artist' ), 10, 2 );
		add_action( 'save_post_event', array( __CLASS__, 'save_event' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

		add_filter( 'manage_artist_posts_columns', array( __CLASS__, 'artist_columns' ) );
		add_action( 'manage_artist_posts_custom_column', array( __CLASS__, 'artist_column' ), 10, 2 );
		add_filter( 'manage_event_posts_columns', array( __CLASS__, 'event_columns' ) );
		add_action( 'manage_event_posts_custom_column', array( __CLASS__, 'event_column' ), 10, 2 );
		add_filter( 'manage_edit-event_sortable_columns', array( __CLASS__, 'event_sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_events' ) );
	}

	/**
	 * Admin assets on artist/event edit screens only.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function enqueue( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( 'artist', 'event' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'ade-admin', ADE_URL . 'assets/css/admin.css', array(), ADE_VERSION );

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'ade-admin', ADE_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), ADE_VERSION, true );
			wp_localize_script(
				'ade-admin',
				'adeAdmin',
				array(
					'frameTitle'  => __( 'Select gallery images', 'artist-directory-elementor' ),
					'frameButton' => __( 'Add to gallery', 'artist-directory-elementor' ),
					'remove'      => __( 'Remove image', 'artist-directory-elementor' ),
				)
			);
		}
	}

	/**
	 * Artist meta boxes.
	 */
	public static function add_artist_boxes(): void {
		add_meta_box( 'ade_artist_details', __( 'Artist Details', 'artist-directory-elementor' ), array( __CLASS__, 'render_artist_details' ), 'artist', 'normal', 'high' );
		add_meta_box( 'ade_artist_gallery', __( 'Artist Gallery', 'artist-directory-elementor' ), array( __CLASS__, 'render_gallery' ), 'artist', 'normal', 'default' );
		add_meta_box( 'ade_artist_products', __( 'Linked Products', 'artist-directory-elementor' ), array( __CLASS__, 'render_products' ), 'artist', 'side', 'default' );
	}

	/**
	 * Event meta boxes.
	 */
	public static function add_event_boxes(): void {
		add_meta_box( 'ade_event_details', __( 'Event Details', 'artist-directory-elementor' ), array( __CLASS__, 'render_event_details' ), 'event', 'normal', 'high' );
		add_meta_box( 'ade_event_artists', __( 'Related Artists', 'artist-directory-elementor' ), array( __CLASS__, 'render_event_artists' ), 'event', 'side', 'default' );
	}

	/**
	 * Text-style input row.
	 *
	 * @param string $name  Field name / meta key.
	 * @param string $label Label.
	 * @param string $value Current value.
	 * @param string $type  Input type.
	 * @param string $help  Optional help text.
	 */
	private static function input_row( string $name, string $label, string $value, string $type = 'text', string $help = '' ): void {
		$id = 'ade-field-' . sanitize_html_class( $name );
		?>
		<p class="ade-field">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="widefat"<?php echo $help ? ' aria-describedby="' . esc_attr( $id ) . '-help"' : ''; ?> />
			<?php if ( $help ) : ?>
				<span class="description" id="<?php echo esc_attr( $id ); ?>-help"><?php echo esc_html( $help ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Artist details box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_artist_details( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<div class="ade-grid">
			<?php
			self::input_row( 'ade_website', __( 'Website', 'artist-directory-elementor' ), (string) get_post_meta( $post->ID, 'ade_website', true ), 'url' );
			self::input_row( 'ade_instagram', __( 'Instagram', 'artist-directory-elementor' ), (string) get_post_meta( $post->ID, 'ade_instagram', true ), 'text', __( 'Full URL or @handle', 'artist-directory-elementor' ) );
			self::input_row( 'ade_facebook', __( 'Facebook', 'artist-directory-elementor' ), (string) get_post_meta( $post->ID, 'ade_facebook', true ), 'text', __( 'Full URL or page name', 'artist-directory-elementor' ) );
			self::input_row( 'ade_youtube', __( 'YouTube', 'artist-directory-elementor' ), (string) get_post_meta( $post->ID, 'ade_youtube', true ), 'text', __( 'Full URL or @handle', 'artist-directory-elementor' ) );
			self::input_row( '_ade_contact_email', __( 'Contact email (private)', 'artist-directory-elementor' ), (string) get_post_meta( $post->ID, '_ade_contact_email', true ), 'email', __( 'Never shown on the site or in the REST API.', 'artist-directory-elementor' ) );
			?>
		</div>
		<p class="ade-field ade-field--check">
			<label>
				<input type="checkbox" name="ade_featured" value="1" <?php checked( (bool) get_post_meta( $post->ID, 'ade_featured', true ) ); ?> />
				<?php esc_html_e( 'Featured artist', 'artist-directory-elementor' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Gallery box with media uploader.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_gallery( WP_Post $post ): void {
		$ids = ade_get_artist_gallery_ids( $post->ID );
		?>
		<div class="ade-gallery" data-ade-gallery>
			<input type="hidden" name="ade_gallery" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" data-ade-gallery-input />
			<ul class="ade-gallery__list" data-ade-gallery-list>
				<?php foreach ( $ids as $id ) : ?>
					<li class="ade-gallery__item" data-id="<?php echo esc_attr( (string) $id ); ?>">
						<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
						<button type="button" class="ade-gallery__remove" aria-label="<?php esc_attr_e( 'Remove image', 'artist-directory-elementor' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<p>
				<button type="button" class="button" data-ade-gallery-add><?php esc_html_e( 'Add images', 'artist-directory-elementor' ); ?></button>
				<span class="description"><?php esc_html_e( 'Drag to reorder.', 'artist-directory-elementor' ); ?></span>
			</p>
		</div>
		<?php
	}

	/**
	 * Searchable checkbox picker for related posts.
	 *
	 * @param string $name      Input name (array).
	 * @param string $post_type Post type to list.
	 * @param int[]  $selected  Selected IDs.
	 * @param string $label     Search label.
	 */
	private static function render_picker( string $name, string $post_type, array $selected, string $label ): void {
		$posts = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page'   => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Admin-only picker list.
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		// Selected first, then the rest alphabetically.
		usort(
			$posts,
			static function ( $a, $b ) use ( $selected ) {
				$sa = in_array( $a->ID, $selected, true ) ? 0 : 1;
				$sb = in_array( $b->ID, $selected, true ) ? 0 : 1;
				return $sa !== $sb ? $sa <=> $sb : strcasecmp( $a->post_title, $b->post_title );
			}
		);

		$search_id = 'ade-picker-' . sanitize_html_class( $name );
		?>
		<div class="ade-picker" data-ade-picker>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="0" />
			<label class="screen-reader-text" for="<?php echo esc_attr( $search_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="search" id="<?php echo esc_attr( $search_id ); ?>" class="widefat ade-picker__search" placeholder="<?php echo esc_attr( $label ); ?>" data-ade-picker-search />
			<?php if ( ! $posts ) : ?>
				<p class="description"><?php esc_html_e( 'Nothing to pick yet.', 'artist-directory-elementor' ); ?></p>
			<?php else : ?>
				<ul class="ade-picker__list">
					<?php foreach ( $posts as $item ) : ?>
						<li data-title="<?php echo esc_attr( strtolower( $item->post_title ) ); ?>">
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( (string) $item->ID ); ?>" <?php checked( in_array( $item->ID, $selected, true ) ); ?> />
								<?php echo esc_html( $item->post_title ? $item->post_title : __( '(no title)', 'artist-directory-elementor' ) ); ?>
								<?php if ( 'publish' !== $item->post_status ) : ?>
									<em>(<?php echo esc_html( get_post_status_object( $item->post_status )->label ?? $item->post_status ); ?>)</em>
								<?php endif; ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Linked WooCommerce products.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_products( WP_Post $post ): void {
		if ( ! ade_is_woocommerce_active() ) {
			$ids = ade_get_linked_product_ids( $post->ID );
			echo '<p class="description">' . esc_html__( 'Activate WooCommerce to pick products. You can still enter product IDs:', 'artist-directory-elementor' ) . '</p>';
			echo '<input type="text" class="widefat" name="ade_linked_products_csv" aria-label="' . esc_attr__( 'Product IDs, comma separated', 'artist-directory-elementor' ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '" />';
			return;
		}

		self::render_picker( 'ade_linked_products', 'product', ade_get_linked_product_ids( $post->ID ), __( 'Search products…', 'artist-directory-elementor' ) );
	}

	/**
	 * Event details box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_event_details( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$dates = ade_get_event_dates( $post->ID );
		$start = $dates['start'] ? $dates['start']->format( 'Y-m-d\TH:i' ) : '';
		$end   = $dates['end'] ? $dates['end']->format( 'Y-m-d\TH:i' ) : '';
		?>
		<div class="ade-grid">
			<?php
			self::input_row( 'ade_start_datetime', __( 'Starts', 'artist-directory-elementor' ), $start, 'datetime-local' );
			self::input_row( 'ade_end_datetime', __( 'Ends', 'artist-directory-elementor' ), $end, 'datetime-local', __( 'Leave empty to use the start time.', 'artist-directory-elementor' ) );
			self::input_row( 'ade_venue', __( 'Venue', 'artist-directory-elementor' ), (string) get_post_meta( $post->ID, 'ade_venue', true ) );
			self::input_row( 'ade_ticket_url', __( 'Ticket URL', 'artist-directory-elementor' ), (string) get_post_meta( $post->ID, 'ade_ticket_url', true ), 'url' );
			?>
		</div>
		<p class="ade-field">
			<label for="ade-field-ade_address"><?php esc_html_e( 'Address', 'artist-directory-elementor' ); ?></label>
			<textarea id="ade-field-ade_address" name="ade_address" rows="3" class="widefat"><?php echo esc_textarea( (string) get_post_meta( $post->ID, 'ade_address', true ) ); ?></textarea>
		</p>
		<p class="description">
			<?php
			/* translators: %s: timezone name. */
			printf( esc_html__( 'Times are in the site timezone (%s).', 'artist-directory-elementor' ), esc_html( wp_timezone_string() ) );
			?>
		</p>
		<?php
	}

	/**
	 * Related artists picker.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_event_artists( WP_Post $post ): void {
		self::render_picker( 'ade_related_artists', 'artist', ade_get_related_artist_ids( $post->ID ), __( 'Search artists…', 'artist-directory-elementor' ) );
	}

	/**
	 * Common save guard.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function can_save( int $post_id ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Save artist meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_artist( int $post_id, WP_Post $post ): void {
		unset( $post );
		if ( ! self::can_save( $post_id ) ) {
			return;
		}

		// Nonce verified in can_save(). Values are sanitized by the registered sanitize_callback on update_post_meta().
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( array( 'ade_website', 'ade_instagram', 'ade_facebook', 'ade_youtube', '_ade_contact_email' ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? trim( wp_unslash( $_POST[ $key ] ) ) : '';
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		if ( ! empty( $_POST['ade_featured'] ) ) {
			update_post_meta( $post_id, 'ade_featured', true );
		} else {
			delete_post_meta( $post_id, 'ade_featured' );
		}

		if ( isset( $_POST['ade_gallery'] ) ) {
			update_post_meta( $post_id, 'ade_gallery', ade_sanitize_id_list( wp_unslash( $_POST['ade_gallery'] ) ) );
		}

		if ( isset( $_POST['ade_linked_products'] ) ) {
			update_post_meta( $post_id, 'ade_linked_products', ade_sanitize_id_list( wp_unslash( $_POST['ade_linked_products'] ) ) );
		} elseif ( isset( $_POST['ade_linked_products_csv'] ) ) {
			update_post_meta( $post_id, 'ade_linked_products', ade_sanitize_id_list( wp_unslash( $_POST['ade_linked_products_csv'] ) ) );
		}
		// phpcs:enable
	}

	/**
	 * Save event meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_event( int $post_id, WP_Post $post ): void {
		unset( $post );
		if ( ! self::can_save( $post_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in can_save().
		$start = isset( $_POST['ade_start_datetime'] ) ? ade_sanitize_datetime( sanitize_text_field( wp_unslash( $_POST['ade_start_datetime'] ) ) ) : '';
		$end   = isset( $_POST['ade_end_datetime'] ) ? ade_sanitize_datetime( sanitize_text_field( wp_unslash( $_POST['ade_end_datetime'] ) ) ) : '';

		if ( '' === $start ) {
			delete_post_meta( $post_id, 'ade_start_datetime' );
			delete_post_meta( $post_id, 'ade_end_datetime' );
		} else {
			// The end is always stored so month queries can use start <= X AND end >= Y.
			if ( '' === $end || $end < $start ) {
				$end = $start;
			}
			update_post_meta( $post_id, 'ade_start_datetime', $start );
			update_post_meta( $post_id, 'ade_end_datetime', $end );
		}

		$text_fields = array(
			'ade_venue'      => 'sanitize_text_field',
			'ade_address'    => 'sanitize_textarea_field',
			'ade_ticket_url' => 'esc_url_raw',
		);
		foreach ( $text_fields as $key => $sanitize ) {
			$value = isset( $_POST[ $key ] ) ? call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		if ( isset( $_POST['ade_related_artists'] ) ) {
			$ids = ade_sanitize_id_list( wp_unslash( $_POST['ade_related_artists'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			self::set_related_artists( $post_id, $ids );
		}
		// phpcs:enable
	}

	/**
	 * Replace an event's related artists (one meta row per artist).
	 *
	 * @param int   $event_id Event ID.
	 * @param int[] $ids      Artist IDs.
	 */
	public static function set_related_artists( int $event_id, array $ids ): void {
		delete_post_meta( $event_id, 'ade_related_artists' );
		foreach ( $ids as $id ) {
			if ( 'artist' === get_post_type( $id ) ) {
				add_post_meta( $event_id, 'ade_related_artists', $id );
			}
		}
	}

	/**
	 * Artist list columns.
	 *
	 * @param array $columns Columns.
	 */
	public static function artist_columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$out['ade_thumb'] = '<span class="screen-reader-text">' . esc_html__( 'Photo', 'artist-directory-elementor' ) . '</span>';
			}
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['ade_featured'] = esc_html__( 'Featured', 'artist-directory-elementor' );
			}
		}
		return $out;
	}

	/**
	 * Artist column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function artist_column( string $column, int $post_id ): void {
		if ( 'ade_thumb' === $column ) {
			echo get_the_post_thumbnail( $post_id, array( 48, 48 ) );
		} elseif ( 'ade_featured' === $column && get_post_meta( $post_id, 'ade_featured', true ) ) {
			echo '<span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Featured', 'artist-directory-elementor' ) . '</span>';
		}
	}

	/**
	 * Event list columns.
	 *
	 * @param array $columns Columns.
	 */
	public static function event_columns( array $columns ): array {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );
		$columns['ade_start'] = esc_html__( 'Starts', 'artist-directory-elementor' );
		$columns['ade_venue'] = esc_html__( 'Venue', 'artist-directory-elementor' );
		if ( $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}

	/**
	 * Event column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function event_column( string $column, int $post_id ): void {
		if ( 'ade_start' === $column ) {
			echo esc_html( ade_format_event_datetime( $post_id, 'start' ) );
		} elseif ( 'ade_venue' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, 'ade_venue', true ) );
		}
	}

	/**
	 * Make "Starts" sortable.
	 *
	 * @param array $columns Sortable columns.
	 */
	public static function event_sortable( array $columns ): array {
		$columns['ade_start'] = 'ade_start';
		return $columns;
	}

	/**
	 * Apply the "Starts" sort in the admin list.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function sort_events( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'ade_start' !== $query->get( 'orderby' ) ) {
			return;
		}
		$query->set( 'meta_key', 'ade_start_datetime' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->set( 'orderby', 'meta_value' );
	}
}
