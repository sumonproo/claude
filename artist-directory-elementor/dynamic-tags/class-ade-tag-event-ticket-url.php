<?php
/**
 * Dynamic tag: Event Ticket URL.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outputs the `ade_ticket_url` meta of the current event.
 */
class ADE_Tag_Event_Ticket_Url extends ADE_Simple_Meta_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-event-ticket-url';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Event Ticket URL', 'artist-directory-elementor' );
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
		return 'ade_ticket_url';
	}
}
