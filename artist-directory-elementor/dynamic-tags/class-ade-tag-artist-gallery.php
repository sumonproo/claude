<?php
/**
 * Dynamic tag: Artist Gallery.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

/**
 * Gallery images for the Elementor Gallery / Basic Gallery / Image Carousel widgets.
 */
class ADE_Tag_Artist_Gallery extends Data_Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-artist-gallery';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Artist Gallery', 'artist-directory-elementor' );
	}

	/**
	 * Tag group.
	 */
	public function get_group() {
		return 'ade-artist';
	}

	/**
	 * Gallery controls only.
	 */
	public function get_categories() {
		return array( TagsModule::GALLERY_CATEGORY );
	}

	/**
	 * Gallery value: [ [ 'id' => int, 'url' => string ], ... ].
	 *
	 * @param array $options Unused.
	 */
	public function get_value( array $options = array() ) {
		$artist_id = ade_context_post_id( 'artist' );
		if ( ! $artist_id ) {
			return array();
		}

		$images = array();
		foreach ( ade_get_artist_gallery_ids( $artist_id ) as $id ) {
			$images[] = array(
				'id'  => $id,
				'url' => (string) wp_get_attachment_image_url( $id, 'full' ),
			);
		}

		return $images;
	}
}
