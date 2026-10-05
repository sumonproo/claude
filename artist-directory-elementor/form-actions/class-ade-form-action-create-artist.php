<?php
/**
 * Elementor Pro Forms action: Create Pending Artist.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Action_Base;

/**
 * Turns a form submission into a pending `artist` post. Runs alongside
 * Elementor's own Email / Redirect / Collect Submissions actions.
 */
class ADE_Form_Action_Create_Artist extends Action_Base {

	const MAX_IMAGES = 10;

	/**
	 * Field map: setting key => [ label, default field ID ].
	 */
	private function field_map(): array {
		return array(
			'name'       => array( esc_html__( 'Name field ID', 'artist-directory-elementor' ), 'name' ),
			'email'      => array( esc_html__( 'Email field ID', 'artist-directory-elementor' ), 'email' ),
			'discipline' => array( esc_html__( 'Discipline field ID', 'artist-directory-elementor' ), 'discipline' ),
			'location'   => array( esc_html__( 'Location field ID', 'artist-directory-elementor' ), 'location' ),
			'bio'        => array( esc_html__( 'Bio field ID', 'artist-directory-elementor' ), 'bio' ),
			'portfolio'  => array( esc_html__( 'Portfolio link field ID', 'artist-directory-elementor' ), 'portfolio' ),
			'images'     => array( esc_html__( 'Image upload field ID', 'artist-directory-elementor' ), 'images' ),
		);
	}

	/**
	 * Action name.
	 */
	public function get_name() {
		return 'ade_create_artist';
	}

	/**
	 * Action label (shown in "Actions After Submit").
	 */
	public function get_label() {
		return esc_html__( 'Create Pending Artist', 'artist-directory-elementor' );
	}

	/**
	 * Settings section in the Form widget.
	 *
	 * @param \ElementorPro\Modules\Forms\Widgets\Form $widget Form widget.
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_ade_create_artist',
			array(
				'label'     => $this->get_label(),
				'condition' => array( 'submit_actions' => $this->get_name() ),
			)
		);

		$widget->add_control(
			'ade_map_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Enter the field IDs (Form Fields → Advanced → ID) that hold each value. Discipline / location values must match a term name or slug.', 'artist-directory-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		foreach ( $this->field_map() as $key => $field ) {
			$widget->add_control(
				'ade_map_' . $key,
				array(
					'label'   => $field[0],
					'type'    => Controls_Manager::TEXT,
					'default' => $field[1],
					'ai'      => array( 'active' => false ),
				)
			);
		}

		$widget->add_control(
			'ade_notify',
			array(
				'label'     => esc_html__( 'Email admin about new submissions', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$widget->add_control(
			'ade_notify_to',
			array(
				'label'       => esc_html__( 'Send to', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => get_option( 'admin_email' ),
				'description' => esc_html__( 'Comma-separated. Empty = site admin email.', 'artist-directory-elementor' ),
				'condition'   => array( 'ade_notify' => 'yes' ),
				'ai'          => array( 'active' => false ),
			)
		);

		$widget->end_controls_section();
	}

	/**
	 * Strip our settings from exported templates.
	 *
	 * @param array $element Element data.
	 */
	public function on_export( $element ) {
		foreach ( array_merge( array_keys( $this->field_map() ), array( 'notify', 'notify_to' ) ) as $key ) {
			unset( $element['settings'][ 'ade_map_' . $key ], $element['settings'][ 'ade_' . $key ] );
		}
		return $element;
	}

	/**
	 * Run on submit.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record       Record.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler Handler.
	 */
	public function run( $record, $ajax_handler ) {
		$settings = (array) $record->get( 'form_settings' );
		$fields   = (array) $record->get( 'fields' );
		$id_for   = static fn( string $key ) => sanitize_key( (string) ( $settings[ 'ade_map_' . $key ] ?? '' ) );

		$name = sanitize_text_field( $this->value( $fields, $id_for( 'name' ) ) );
		if ( '' === $name ) {
			$ajax_handler->add_error_message( esc_html__( 'Please enter your name.', 'artist-directory-elementor' ) );
			return;
		}

		$email     = sanitize_email( $this->value( $fields, $id_for( 'email' ) ) );
		$bio       = sanitize_textarea_field( $this->value( $fields, $id_for( 'bio' ) ) );
		$portfolio = ade_sanitize_profile_url( $this->value( $fields, $id_for( 'portfolio' ) ) );
		$notes     = array();

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'artist',
				'post_status'  => 'pending',
				'post_title'   => $name,
				'post_content' => $bio ? wpautop( esc_html( $bio ) ) : '',
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$ajax_handler->add_error_message( esc_html__( 'Sorry, your submission could not be saved. Please try again.', 'artist-directory-elementor' ) );
			$ajax_handler->add_admin_error_message( $post_id->get_error_message() );
			return;
		}

		if ( is_email( $email ) ) {
			update_post_meta( $post_id, '_ade_contact_email', $email );
		}
		if ( $portfolio ) {
			update_post_meta( $post_id, 'ade_website', $portfolio );
		}

		foreach ( array(
			'discipline'      => 'discipline',
			'artist_location' => 'location',
		) as $taxonomy => $key ) {
			$values    = $this->values( $fields, $id_for( $key ) );
			$term_ids  = array();
			$unmatched = array();
			foreach ( $values as $value ) {
				$term = $this->find_term( $value, $taxonomy );
				if ( $term ) {
					$term_ids[] = $term;
				} else {
					$unmatched[] = $value;
				}
			}
			if ( $term_ids ) {
				wp_set_object_terms( $post_id, $term_ids, $taxonomy );
			}
			if ( $unmatched ) {
				/* translators: 1: taxonomy label, 2: submitted values. */
				$notes[] = sprintf( __( 'Unmatched %1$s: %2$s', 'artist-directory-elementor' ), $key, implode( ', ', $unmatched ) );
			}
		}

		$image_ids = $this->import_images( $record, $fields, $id_for( 'images' ), $post_id, $name );
		if ( $image_ids ) {
			set_post_thumbnail( $post_id, $image_ids[0] );
			update_post_meta( $post_id, 'ade_gallery', $image_ids );
		}

		update_post_meta( $post_id, ADE_Submissions::META_SUBMITTED, 1 );
		update_post_meta( $post_id, ADE_Submissions::META_DATE, current_time( 'mysql', true ) );
		if ( $notes ) {
			update_post_meta( $post_id, ADE_Submissions::META_NOTES, sanitize_textarea_field( implode( "\n", $notes ) ) );
		}

		if ( 'yes' === ( $settings['ade_notify'] ?? '' ) ) {
			$this->notify( $post_id, (string) ( $settings['ade_notify_to'] ?? '' ), $email, $portfolio, count( $image_ids ) );
		}

		/**
		 * Fires after a pending artist is created from an Elementor form.
		 *
		 * @param int                                             $post_id Artist ID.
		 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record  Form record.
		 */
		do_action( 'ade_artist_submitted', $post_id, $record );
	}

	/**
	 * Raw string value of a form field.
	 *
	 * @param array  $fields   Record fields.
	 * @param string $field_id Field ID.
	 */
	private function value( array $fields, string $field_id ): string {
		if ( '' === $field_id || ! isset( $fields[ $field_id ] ) ) {
			return '';
		}
		$raw = $fields[ $field_id ]['raw_value'] ?? $fields[ $field_id ]['value'] ?? '';

		return is_array( $raw ) ? implode( ', ', array_map( 'strval', $raw ) ) : trim( (string) $raw );
	}

	/**
	 * Multi-value field (select / checkbox / comma list) as a list of strings.
	 *
	 * @param array  $fields   Record fields.
	 * @param string $field_id Field ID.
	 * @return string[]
	 */
	private function values( array $fields, string $field_id ): array {
		if ( '' === $field_id || ! isset( $fields[ $field_id ] ) ) {
			return array();
		}
		$raw  = $fields[ $field_id ]['raw_value'] ?? $fields[ $field_id ]['value'] ?? '';
		$list = is_array( $raw ) ? $raw : explode( ',', (string) $raw );

		return array_values( array_filter( array_map( static fn( $v ) => sanitize_text_field( trim( (string) $v ) ), $list ) ) );
	}

	/**
	 * Find an existing term by slug, name or ID. Never creates terms.
	 *
	 * @param string $value    Submitted value.
	 * @param string $taxonomy Taxonomy.
	 */
	private function find_term( string $value, string $taxonomy ): int {
		$term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
		if ( ! $term ) {
			$term = get_term_by( 'name', $value, $taxonomy );
		}
		if ( ! $term && ctype_digit( $value ) ) {
			$term = get_term_by( 'id', (int) $value, $taxonomy );
		}

		return $term ? (int) $term->term_id : 0;
	}

	/**
	 * Local file paths uploaded through an Elementor upload field.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record   Record.
	 * @param array                                           $fields   Fields.
	 * @param string                                          $field_id Field ID.
	 * @return string[]
	 */
	private function uploaded_paths( $record, array $fields, string $field_id ): array {
		if ( '' === $field_id ) {
			return array();
		}

		$files = (array) $record->get( 'files' );
		if ( ! empty( $files[ $field_id ]['path'] ) ) {
			return array_values( array_filter( (array) $files[ $field_id ]['path'] ) );
		}

		// Fallback: map uploaded URLs back to paths inside the uploads directory.
		$uploads = wp_get_upload_dir();
		$paths   = array();
		foreach ( $this->values( $fields, $field_id ) as $url ) {
			$url = esc_url_raw( $url );
			if ( str_starts_with( $url, $uploads['baseurl'] ) ) {
				$paths[] = $uploads['basedir'] . substr( $url, strlen( $uploads['baseurl'] ) );
			}
		}

		return $paths;
	}

	/**
	 * Copy uploaded images into the Media Library, attached to the artist.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record   Record.
	 * @param array                                           $fields   Fields.
	 * @param string                                          $field_id Upload field ID.
	 * @param int                                             $post_id  Artist ID.
	 * @param string                                          $name     Artist name (image title).
	 * @return int[] Attachment IDs.
	 */
	private function import_images( $record, array $fields, string $field_id, int $post_id, string $name ): array {
		$paths = array_slice( $this->uploaded_paths( $record, $fields, $field_id ), 0, self::MAX_IMAGES );
		if ( ! $paths ) {
			return array();
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$base = realpath( wp_get_upload_dir()['basedir'] );
		$ids  = array();

		foreach ( $paths as $path ) {
			$real = realpath( $path );
			// Only files Elementor stored inside the uploads directory.
			if ( ! $real || ! $base || ! str_starts_with( $real, $base . DIRECTORY_SEPARATOR ) || ! is_file( $real ) ) {
				continue;
			}

			$filename = wp_basename( $real );
			$check    = wp_check_filetype_and_ext( $real, $filename );
			if ( empty( $check['type'] ) || ! str_starts_with( $check['type'], 'image/' ) ) {
				continue;
			}

			// Copy, because Elementor may delete its temp upload after the email action.
			$tmp = wp_tempnam( $filename );
			if ( ! $tmp || ! copy( $real, $tmp ) ) {
				continue;
			}

			$attachment_id = media_handle_sideload(
				array(
					'name'     => $check['proper_filename'] ? $check['proper_filename'] : $filename,
					'tmp_name' => $tmp,
				),
				$post_id,
				$name
			);

			if ( is_wp_error( $attachment_id ) ) {
				wp_delete_file( $tmp );
				continue;
			}

			$ids[] = (int) $attachment_id;
		}

		return $ids;
	}

	/**
	 * Email the site team.
	 *
	 * @param int    $post_id   Artist ID.
	 * @param string $to        Recipients (comma separated) or ''.
	 * @param string $email     Submitter email.
	 * @param string $portfolio Portfolio URL.
	 * @param int    $images    Number of images imported.
	 */
	private function notify( int $post_id, string $to, string $email, string $portfolio, int $images ): void {
		$recipients = array_filter( array_map( 'sanitize_email', explode( ',', $to ) ), 'is_email' );
		if ( ! $recipients ) {
			$recipients = array( get_option( 'admin_email' ) );
		}

		$terms = static function ( string $taxonomy ) use ( $post_id ): string {
			$list = get_the_terms( $post_id, $taxonomy );
			return is_array( $list ) ? implode( ', ', wp_list_pluck( $list, 'name' ) ) : '—';
		};

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$name = wp_specialchars_decode( get_the_title( $post_id ), ENT_QUOTES );

		/* translators: 1: site name, 2: artist name. */
		$subject = sprintf( __( '[%1$s] New artist submission: %2$s', 'artist-directory-elementor' ), $site, $name );

		$lines = array(
			/* translators: %s: artist name. */
			sprintf( __( 'Name: %s', 'artist-directory-elementor' ), $name ),
			/* translators: %s: email address. */
			sprintf( __( 'Email: %s', 'artist-directory-elementor' ), $email ? $email : '—' ),
			/* translators: %s: discipline names. */
			sprintf( __( 'Discipline: %s', 'artist-directory-elementor' ), $terms( 'discipline' ) ),
			/* translators: %s: location names. */
			sprintf( __( 'Location: %s', 'artist-directory-elementor' ), $terms( 'artist_location' ) ),
			/* translators: %s: URL. */
			sprintf( __( 'Portfolio: %s', 'artist-directory-elementor' ), $portfolio ? $portfolio : '—' ),
			/* translators: %d: number of images. */
			sprintf( __( 'Images: %d', 'artist-directory-elementor' ), $images ),
			'',
			/* translators: %s: URL. */
			sprintf( __( 'Review: %s', 'artist-directory-elementor' ), admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ),
			/* translators: %s: URL. */
			sprintf( __( 'All pending submissions: %s', 'artist-directory-elementor' ), admin_url( 'edit.php?post_type=artist&post_status=pending&ade_submissions=1' ) ),
		);

		$headers = array();
		if ( is_email( $email ) ) {
			$headers[] = 'Reply-To: ' . str_replace( array( "\r", "\n" ), '', $name ) . ' <' . $email . '>';
		}

		wp_mail( $recipients, $subject, implode( "\n", $lines ), $headers );
	}
}
