<?php
/**
 * Dynamic tag: Artist Linked Products.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

/**
 * List of WooCommerce products linked to the artist. Outputs nothing when
 * WooCommerce is inactive or no products are linked.
 */
class ADE_Tag_Artist_Linked_Products extends Tag {

	/**
	 * Tag name.
	 */
	public function get_name() {
		return 'ade-artist-linked-products';
	}

	/**
	 * Tag title.
	 */
	public function get_title() {
		return esc_html__( 'Artist Linked Products', 'artist-directory-elementor' );
	}

	/**
	 * Tag group.
	 */
	public function get_group() {
		return 'ade-artist';
	}

	/**
	 * Text controls (use in Text Editor / HTML-capable widgets).
	 */
	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->add_control(
			'show_price',
			array(
				'label'   => esc_html__( 'Show price', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label' => esc_html__( 'Show thumbnail', 'artist-directory-elementor' ),
				'type'  => Controls_Manager::SWITCHER,
			)
		);
	}

	/**
	 * Output.
	 */
	public function render() {
		if ( ! ade_is_woocommerce_active() ) {
			return;
		}

		$artist_id = ade_context_post_id( 'artist' );
		$ids       = $artist_id ? ade_get_linked_product_ids( $artist_id ) : array();
		$items     = array();

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
				continue;
			}

			$image = 'yes' === $this->get_settings( 'show_image' ) ? $product->get_image( 'woocommerce_thumbnail' ) : '';
			$price = 'yes' === $this->get_settings( 'show_price' ) ? $product->get_price_html() : '';

			$items[] = sprintf(
				'<li class="ade-linked-products__item"><a class="ade-linked-products__link" href="%1$s">%2$s<span class="ade-linked-products__name">%3$s</span></a>%4$s</li>',
				esc_url( $product->get_permalink() ),
				wp_kses_post( $image ),
				esc_html( $product->get_name() ),
				$price ? ' <span class="ade-linked-products__price">' . wp_kses_post( $price ) . '</span>' : ''
			);
		}

		if ( $items ) {
			echo '<ul class="ade-linked-products">' . implode( '', $items ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Items escaped above.
		}
	}
}
