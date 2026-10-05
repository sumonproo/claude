<?php
/**
 * Dynamic tag: Event Date / Time.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Formatted event start/end.
 */
class ADE_Tag_Event_Datetime extends ADE_Meta_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-event-datetime';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Event Date / Time', 'artist-directory-elementor' );
	}

	/**
	 * Post type.
	 */
	protected function context_post_type(): string {
		return 'event';
	}

	/**
	 * Plain text value.
	 */
	protected function is_url(): bool {
		return false;
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->add_control(
			'part',
			array(
				'label'   => esc_html__( 'Show', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'range',
				'options' => array(
					'range'      => esc_html__( 'Start – end (smart range)', 'artist-directory-elementor' ),
					'start'      => esc_html__( 'Start date + time', 'artist-directory-elementor' ),
					'start_date' => esc_html__( 'Start date', 'artist-directory-elementor' ),
					'start_time' => esc_html__( 'Start time', 'artist-directory-elementor' ),
					'end'        => esc_html__( 'End date + time', 'artist-directory-elementor' ),
					'end_date'   => esc_html__( 'End date', 'artist-directory-elementor' ),
					'end_time'   => esc_html__( 'End time', 'artist-directory-elementor' ),
				),
			)
		);

		$this->add_control(
			'date_format',
			array(
				'label'       => esc_html__( 'Date format', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => get_option( 'date_format' ),
				'description' => esc_html__( 'PHP date format, e.g. "D, M j". Empty = site setting.', 'artist-directory-elementor' ),
			)
		);

		$this->add_control(
			'time_format',
			array(
				'label'       => esc_html__( 'Time format', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => get_option( 'time_format' ),
				'description' => esc_html__( 'Empty = site setting.', 'artist-directory-elementor' ),
			)
		);
	}

	/**
	 * Raw value.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function get_raw_value( int $post_id ): string {
		return ade_format_event_datetime(
			$post_id,
			(string) $this->get_settings( 'part' ),
			sanitize_text_field( (string) $this->get_settings( 'date_format' ) ),
			sanitize_text_field( (string) $this->get_settings( 'time_format' ) )
		);
	}
}
