<?php
/**
 * Admin tools for front-end artist submissions.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Pending submissions" view, Approve row action and bulk action, submission meta box.
 */
final class ADE_Submissions {

	const META_SUBMITTED = '_ade_submitted';
	const META_DATE      = '_ade_submitted_at';
	const META_NOTES     = '_ade_submission_notes';

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_filter( 'views_edit-artist', array( __CLASS__, 'views' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_list' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_ade_approve_artist', array( __CLASS__, 'handle_approve' ) );
		add_filter( 'bulk_actions-edit-artist', array( __CLASS__, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-artist', array( __CLASS__, 'handle_bulk' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'add_meta_boxes_artist', array( __CLASS__, 'meta_box' ) );
		add_filter( 'add_menu_classes', array( __CLASS__, 'menu_bubble' ) );
	}

	/**
	 * Count pending submissions.
	 */
	public static function pending_count(): int {
		$query = new WP_Query(
			array(
				'post_type'      => 'artist',
				'post_status'    => 'pending',
				'meta_key'       => self::META_SUBMITTED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Whether the current list request is the submissions view.
	 */
	private static function is_submissions_view(): bool {
		return isset( $_GET['ade_submissions'] ) && '1' === $_GET['ade_submissions']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Add the "Pending submissions" view link.
	 *
	 * @param array $views Views.
	 */
	public static function views( array $views ): array {
		$url   = add_query_arg(
			array(
				'post_type'       => 'artist',
				'post_status'     => 'pending',
				'ade_submissions' => '1',
			),
			admin_url( 'edit.php' )
		);
		$class = self::is_submissions_view() ? ' class="current" aria-current="page"' : '';

		if ( self::is_submissions_view() && isset( $views['pending'] ) ) {
			$views['pending'] = str_replace( array( ' class="current"', ' aria-current="page"' ), '', $views['pending'] );
		}

		$views['ade_submissions'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%s)</span></a>',
			esc_url( $url ),
			$class,
			esc_html__( 'Pending submissions', 'artist-directory-elementor' ),
			esc_html( number_format_i18n( self::pending_count() ) )
		);

		return $views;
	}

	/**
	 * Restrict the list to submitted posts.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function filter_list( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'artist' !== $query->get( 'post_type' ) || ! self::is_submissions_view() ) {
			return;
		}
		$query->set( 'post_status', 'pending' );
		$query->set( 'meta_key', self::META_SUBMITTED ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	}

	/**
	 * Approve URL for a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function approve_url( int $post_id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'ade_approve_artist',
					'post_id' => $post_id,
				),
				admin_url( 'admin-post.php' )
			),
			'ade_approve_artist_' . $post_id
		);
	}

	/**
	 * Whether the current user may publish this artist.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function can_approve( int $post_id ): bool {
		$type = get_post_type_object( 'artist' );

		return $type && current_user_can( 'edit_post', $post_id ) && current_user_can( $type->cap->publish_posts );
	}

	/**
	 * Quick "Approve" row action.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 */
	public static function row_actions( array $actions, WP_Post $post ): array {
		if ( 'artist' !== $post->post_type || 'pending' !== $post->post_status || ! self::can_approve( $post->ID ) ) {
			return $actions;
		}

		$approve = array(
			'ade_approve' => sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				esc_url( self::approve_url( $post->ID ) ),
				/* translators: %s: artist name. */
				esc_attr( sprintf( __( 'Approve and publish “%s”', 'artist-directory-elementor' ), get_the_title( $post ) ) ),
				esc_html__( 'Approve', 'artist-directory-elementor' )
			),
		);

		return $approve + $actions;
	}

	/**
	 * Publish one artist.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function approve( int $post_id ): bool {
		if ( 'artist' !== get_post_type( $post_id ) || 'pending' !== get_post_status( $post_id ) || ! self::can_approve( $post_id ) ) {
			return false;
		}

		wp_publish_post( $post_id );

		/**
		 * Fires after a submitted artist is approved.
		 *
		 * @param int $post_id Artist ID.
		 */
		do_action( 'ade_artist_approved', $post_id );

		return true;
	}

	/**
	 * Handle the row-action link.
	 */
	public static function handle_approve(): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		check_admin_referer( 'ade_approve_artist_' . $post_id );

		if ( ! self::can_approve( $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to publish this artist.', 'artist-directory-elementor' ), '', array( 'response' => 403 ) );
		}

		$done     = self::approve( $post_id ) ? 1 : 0;
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url( 'edit.php?post_type=artist' );
		}

		wp_safe_redirect( add_query_arg( 'ade_approved', $done, remove_query_arg( array( 'ade_approved' ), $redirect ) ) );
		exit;
	}

	/**
	 * Bulk "Approve" action.
	 *
	 * @param array $actions Bulk actions.
	 */
	public static function bulk_actions( array $actions ): array {
		$actions['ade_approve'] = __( 'Approve', 'artist-directory-elementor' );
		return $actions;
	}

	/**
	 * Handle bulk approve. Nonce is verified by WP_List_Table before this filter runs.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $action   Action.
	 * @param int[]  $post_ids IDs.
	 */
	public static function handle_bulk( string $redirect, string $action, array $post_ids ): string {
		if ( 'ade_approve' !== $action ) {
			return $redirect;
		}

		$done = 0;
		foreach ( $post_ids as $post_id ) {
			$done += self::approve( (int) $post_id ) ? 1 : 0;
		}

		return add_query_arg( 'ade_approved', $done, $redirect );
	}

	/**
	 * Result notice.
	 */
	public static function notices(): void {
		if ( ! isset( $_GET['ade_approved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$count = absint( $_GET['ade_approved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$class = $count ? 'notice-success' : 'notice-warning';
		$text  = $count
			/* translators: %s: number of artists. */
			? sprintf( _n( '%s artist approved and published.', '%s artists approved and published.', $count, 'artist-directory-elementor' ), number_format_i18n( $count ) )
			: __( 'No artists were approved.', 'artist-directory-elementor' );

		printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $text ) );
	}

	/**
	 * Submission details box (only on submitted posts).
	 *
	 * @param WP_Post $post Post.
	 */
	public static function meta_box( WP_Post $post ): void {
		if ( ! get_post_meta( $post->ID, self::META_SUBMITTED, true ) ) {
			return;
		}
		add_meta_box( 'ade_submission', __( 'Submission', 'artist-directory-elementor' ), array( __CLASS__, 'render_meta_box' ), 'artist', 'side', 'high' );
	}

	/**
	 * Render submission box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( WP_Post $post ): void {
		$date  = (string) get_post_meta( $post->ID, self::META_DATE, true );
		$notes = (string) get_post_meta( $post->ID, self::META_NOTES, true );
		?>
		<dl class="ade-submission-meta">
			<?php if ( $date ) : ?>
				<dt><?php esc_html_e( 'Submitted', 'artist-directory-elementor' ); ?></dt>
				<dd><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date . ' UTC' ) ) ); ?></dd>
			<?php endif; ?>
			<?php if ( $notes ) : ?>
				<dt><?php esc_html_e( 'Notes', 'artist-directory-elementor' ); ?></dt>
				<dd><?php echo nl2br( esc_html( $notes ) ); ?></dd>
			<?php endif; ?>
		</dl>
		<?php if ( 'pending' === $post->post_status && self::can_approve( $post->ID ) ) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url( self::approve_url( $post->ID ) ); ?>"><?php esc_html_e( 'Approve & publish', 'artist-directory-elementor' ); ?></a></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Show a count bubble on the Artists menu.
	 *
	 * @param array $menu Admin menu.
	 */
	public static function menu_bubble( array $menu ): array {
		$count = self::pending_count();
		if ( ! $count ) {
			return $menu;
		}

		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=artist' === $item[2] ) {
				$menu[ $i ][0] .= sprintf( ' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>', $count );
				break;
			}
		}

		return $menu;
	}
}
