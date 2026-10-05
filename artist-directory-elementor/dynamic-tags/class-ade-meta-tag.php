<?php
/**
 * Shared base for text/URL dynamic tags.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

/**
 * Base tag: resolves the artist/event in context and outputs a single string.
 * Usable in text controls and in link (URL) controls.
 */
abstract class ADE_Meta_Tag extends Tag {

	/**
	 * Post type this tag reads from.
	 */
	abstract protected function context_post_type(): string;

	/**
	 * Raw (unescaped) value for the given post.
	 *
	 * @param int $post_id Post ID.
	 */
	abstract protected function get_raw_value( int $post_id ): string;

	/**
	 * Whether the value is a URL (escaped with esc_url) or text (esc_html).
	 */
	protected function is_url(): bool {
		return true;
	}

	/**
	 * Tag group.
	 */
	public function get_group() {
		return 'artist' === $this->context_post_type() ? 'ade-artist' : 'ade-event';
	}

	/**
	 * Tag categories.
	 */
	public function get_categories() {
		return $this->is_url()
			? array( TagsModule::TEXT_CATEGORY, TagsModule::URL_CATEGORY )
			: array( TagsModule::TEXT_CATEGORY );
	}

	/**
	 * Current context post ID.
	 */
	protected function post_id(): int {
		return ade_context_post_id( $this->context_post_type() );
	}

	/**
	 * Output.
	 */
	public function render() {
		$post_id = $this->post_id();
		if ( ! $post_id ) {
			return;
		}

		$value = $this->get_raw_value( $post_id );
		if ( '' === $value ) {
			return;
		}

		echo $this->is_url() ? esc_url( $value ) : esc_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the ternary.
	}
}
