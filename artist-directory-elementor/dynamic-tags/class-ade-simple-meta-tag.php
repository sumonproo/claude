<?php
/**
 * Base for tags that output a single post meta value.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base for a tag that simply outputs one post meta value.
 */
abstract class ADE_Simple_Meta_Tag extends ADE_Meta_Tag {

	/**
	 * Meta key to read.
	 */
	abstract protected function meta_key(): string;

	/**
	 * Raw value.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function get_raw_value( int $post_id ): string {
		return (string) get_post_meta( $post_id, $this->meta_key(), true );
	}
}
