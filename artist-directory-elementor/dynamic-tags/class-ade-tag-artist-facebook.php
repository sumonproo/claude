<?php
/**
 * Dynamic tag: Artist Facebook URL.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outputs the `ade_facebook` meta of the current artist.
 */
class ADE_Tag_Artist_Facebook extends ADE_Simple_Meta_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-artist-facebook';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Artist Facebook URL', 'artist-directory-elementor' );
	}

	/**
	 * Post type.
	 */
	protected function context_post_type(): string {
		return 'artist';
	}

	/**
	 * Meta key.
	 */
	protected function meta_key(): string {
		return 'ade_facebook';
	}
}
