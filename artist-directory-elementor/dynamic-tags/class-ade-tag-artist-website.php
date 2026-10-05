<?php
/**
 * Dynamic tag: Artist Website.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Outputs the `ade_website` meta of the current artist.
 */
class ADE_Tag_Artist_Website extends ADE_Simple_Meta_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-artist-website';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Artist Website', 'artist-directory-elementor' );
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
		return 'ade_website';
	}
}
