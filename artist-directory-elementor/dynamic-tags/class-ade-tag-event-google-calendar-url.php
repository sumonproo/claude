<?php
/**
 * Dynamic tag: Event: Add to Google Calendar URL.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Modules\DynamicTags\Module as TagsModule;

/**
 * URL built by ade_google_calendar_url() for the current event.
 */
class ADE_Tag_Event_Google_Calendar_Url extends ADE_Meta_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-event-gcal-url';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Event: Add to Google Calendar URL', 'artist-directory-elementor' );
	}

	/**
	 * URL controls only.
	 */
	public function get_categories() {
		return array( TagsModule::URL_CATEGORY );
	}

	/**
	 * Post type.
	 */
	protected function context_post_type(): string {
		return 'event';
	}

	/**
	 * Raw value.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function get_raw_value( int $post_id ): string {
		return ade_google_calendar_url( $post_id );
	}
}
