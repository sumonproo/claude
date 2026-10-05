<?php
/**
 * Dynamic tag: Event Address.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Outputs the event address on one line or with line breaks.
 */
class ADE_Tag_Event_Address extends ADE_Meta_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-event-address';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Event Address', 'artist-directory-elementor' );
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
			'lines',
			array(
				'label'   => esc_html__( 'Line breaks', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'comma',
				'options' => array(
					'comma' => esc_html__( 'Single line (commas)', 'artist-directory-elementor' ),
					'br'    => esc_html__( 'Keep line breaks', 'artist-directory-elementor' ),
				),
			)
		);
	}

	/**
	 * Raw value.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function get_raw_value( int $post_id ): string {
		return trim( (string) get_post_meta( $post_id, 'ade_address', true ) );
	}

	/**
	 * Output.
	 */
	public function render() {
		$post_id = $this->post_id();
		$value   = $post_id ? $this->get_raw_value( $post_id ) : '';
		if ( '' === $value ) {
			return;
		}

		if ( 'br' === $this->get_settings( 'lines' ) ) {
			echo nl2br( esc_html( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped before nl2br.
			return;
		}

		echo esc_html( preg_replace( '/\s*\R\s*/', ', ', $value ) );
	}
}
