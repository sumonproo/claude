<?php
/**
 * Dynamic tag: Event Venue.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outputs the `ade_venue` meta of the current event.
 */
class ADE_Tag_Event_Venue extends ADE_Simple_Meta_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-event-venue';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Event Venue', 'artist-directory-elementor' );
	}

	/**
	 * Post type.
	 */
	protected function context_post_type(): string {
		return 'event';
	}

	/**
	 * Meta key.
	 */
	protected function meta_key(): string {
		return 'ade_venue';
	}

	/**
	 * Plain text value.
	 */
	protected function is_url(): bool {
		return false;
	}
}
