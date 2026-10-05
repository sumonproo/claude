<?php
/**
 * Elementor widget: Artist Directory Filter.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Keyword + discipline + location + sort filter that refreshes a Loop Grid
 * (or its own results grid) over REST.
 */
class ADE_Widget_Directory_Filter extends Widget_Base {

	/**
	 * Widget name.
	 */
	public function get_name() {
		return 'ade-directory-filter';
	}

	/**
	 * Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Artist Directory Filter', 'artist-directory-elementor' );
	}

	/**
	 * Panel icon.
	 */
	public function get_icon() {
		return 'eicon-filter';
	}

	/**
	 * Panel categories.
	 */
	public function get_categories() {
		return array( 'ade' );
	}

	/**
	 * Search keywords.
	 */
	public function get_keywords() {
		return array( 'artist', 'directory', 'filter', 'search', 'ajax' );
	}

	/**
	 * Script handles.
	 */
	public function get_script_depends() {
		return array( 'ade-directory' );
	}

	/**
	 * Style handles.
	 */
	public function get_style_depends() {
		return array( 'ade-directory' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->register_results_controls();
		$this->register_field_controls();
		$this->register_style_controls();
	}

	/**
	 * Content → Results.
	 */
	private function register_results_controls(): void {
		$this->start_controls_section( 'section_results', array( 'label' => esc_html__( 'Results', 'artist-directory-elementor' ) ) );

		$this->add_control(
			'template_id',
			array(
				'label'       => esc_html__( 'Loop item template', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '0',
				'options'     => array( '0' => esc_html__( '— Built-in simple card —', 'artist-directory-elementor' ) ) + array_map( 'esc_html', ADE_Elementor::loop_templates() ),
				'description' => esc_html__( 'Templates → Theme Builder → Loop Item. Use the same template as your Loop Grid.', 'artist-directory-elementor' ),
			)
		);

		$this->add_control(
			'target_id',
			array(
				'label'       => esc_html__( 'Loop Grid CSS ID (optional)', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'artist-grid',
				'description' => esc_html__( 'Give a Loop Grid on this page this CSS ID (Advanced → CSS ID) and Query ID "ade_artist_directory". Leave empty to show results under the filter.', 'artist-directory-elementor' ),
				'ai'          => array( 'active' => false ),
			)
		);

		$this->add_control(
			'per_page',
			array(
				'label'       => esc_html__( 'Artists per page', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 12,
				'min'         => 1,
				'max'         => ADE_Directory::MAX_PER_PAGE,
				'description' => esc_html__( 'Match the Loop Grid "Posts Per Page" when targeting a Loop Grid.', 'artist-directory-elementor' ),
			)
		);

		$this->add_control(
			'pagination',
			array(
				'label'   => esc_html__( 'Pagination', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'load_more',
				'options' => array(
					'load_more' => esc_html__( 'Load more button', 'artist-directory-elementor' ),
					'numbers'   => esc_html__( 'Page numbers', 'artist-directory-elementor' ),
					'none'      => esc_html__( 'None', 'artist-directory-elementor' ),
				),
			)
		);

		$this->add_control(
			'load_more_text',
			array(
				'label'     => esc_html__( 'Load more text', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Load more artists', 'artist-directory-elementor' ),
				'condition' => array( 'pagination' => 'load_more' ),
			)
		);

		$this->add_control(
			'update_url',
			array(
				'label'       => esc_html__( 'Keep filters in the URL', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => esc_html__( 'Makes filtered views shareable and back-button friendly.', 'artist-directory-elementor' ),
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'   => esc_html__( 'Show results count', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content → Fields.
	 */
	private function register_field_controls(): void {
		$this->start_controls_section( 'section_fields', array( 'label' => esc_html__( 'Fields', 'artist-directory-elementor' ) ) );

		$this->add_control(
			'live',
			array(
				'label'       => esc_html__( 'Filter as you type / select', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => esc_html__( 'Off = results update when the button is pressed.', 'artist-directory-elementor' ),
			)
		);

		$this->add_control(
			'show_labels',
			array(
				'label'       => esc_html__( 'Visible labels', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => esc_html__( 'When hidden, labels stay available to screen readers.', 'artist-directory-elementor' ),
			)
		);

		$fields = array(
			'search'     => array( __( 'Keyword search', 'artist-directory-elementor' ), __( 'Search', 'artist-directory-elementor' ) ),
			'discipline' => array( __( 'Discipline dropdown', 'artist-directory-elementor' ), __( 'Discipline', 'artist-directory-elementor' ) ),
			'location'   => array( __( 'Location dropdown', 'artist-directory-elementor' ), __( 'Location', 'artist-directory-elementor' ) ),
			'sort'       => array( __( 'Sort dropdown', 'artist-directory-elementor' ), __( 'Sort by', 'artist-directory-elementor' ) ),
		);

		foreach ( $fields as $key => $labels ) {
			$this->add_control(
				'heading_' . $key,
				array(
					'label'     => esc_html( $labels[0] ),
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);
			$this->add_control(
				'show_' . $key,
				array(
					'label'   => esc_html__( 'Show', 'artist-directory-elementor' ),
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				)
			);
			$this->add_control(
				$key . '_label',
				array(
					'label'     => esc_html__( 'Label', 'artist-directory-elementor' ),
					'type'      => Controls_Manager::TEXT,
					'default'   => esc_html( $labels[1] ),
					'condition' => array( 'show_' . $key => 'yes' ),
				)
			);
		}

		$this->add_control(
			'search_placeholder',
			array(
				'label'     => esc_html__( 'Search placeholder', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Name or keyword', 'artist-directory-elementor' ),
				'condition' => array( 'show_search' => 'yes' ),
			)
		);

		$this->add_control(
			'discipline_all',
			array(
				'label'     => esc_html__( '"All disciplines" text', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All disciplines', 'artist-directory-elementor' ),
				'condition' => array( 'show_discipline' => 'yes' ),
			)
		);

		$this->add_control(
			'location_all',
			array(
				'label'     => esc_html__( '"All locations" text', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All locations', 'artist-directory-elementor' ),
				'condition' => array( 'show_location' => 'yes' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'     => esc_html__( 'Button text', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Search', 'artist-directory-elementor' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'reset_text',
			array(
				'label'       => esc_html__( 'Reset text', 'artist-directory-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Clear', 'artist-directory-elementor' ),
				'description' => esc_html__( 'Leave empty to hide the reset button.', 'artist-directory-elementor' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab.
	 */
	private function register_style_controls(): void {
		// Layout.
		$this->start_controls_section(
			'section_style_form',
			array(
				'label' => esc_html__( 'Form', 'artist-directory-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'field_min_width',
			array(
				'label'      => esc_html__( 'Field min width', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 100,
						'max' => 500,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__form' => '--ade-field-min: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'form_gap',
			array(
				'label'      => esc_html__( 'Gap', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__form' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'form_margin',
			array(
				'label'      => esc_html__( 'Space below form', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__form' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => esc_html__( 'Label color', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .ade-dir__label' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => esc_html__( 'Label typography', 'artist-directory-elementor' ),
				'selector' => '{{WRAPPER}} .ade-dir__label',
			)
		);

		$this->end_controls_section();

		// Inputs.
		$this->start_controls_section(
			'section_style_inputs',
			array(
				'label' => esc_html__( 'Inputs', 'artist-directory-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'input_typography',
				'selector' => '{{WRAPPER}} .ade-dir__input',
			)
		);

		$this->add_control(
			'input_color',
			array(
				'label'     => esc_html__( 'Text color', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ade-dir__input' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'input_bg',
			array(
				'label'     => esc_html__( 'Background', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ade-dir__input' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'input_border',
				'selector' => '{{WRAPPER}} .ade-dir__input',
			)
		);

		$this->add_control(
			'input_focus',
			array(
				'label'     => esc_html__( 'Focus ring color', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ade-dir' => '--ade-focus: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'input_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__input, {{WRAPPER}} .ade-dir__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'input_padding',
			array(
				'label'      => esc_html__( 'Padding', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		// Buttons.
		$this->register_button_style( 'button', esc_html__( 'Buttons', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-dir__button', '{{WRAPPER}} .ade-dir__button:hover, {{WRAPPER}} .ade-dir__button:focus-visible' );

		// Count.
		$this->start_controls_section(
			'section_style_count',
			array(
				'label'     => esc_html__( 'Results count', 'artist-directory-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_count' => 'yes' ),
			)
		);

		$this->add_control(
			'count_color',
			array(
				'label'     => esc_html__( 'Color', 'artist-directory-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ade-dir__count' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'count_typography',
				'selector' => '{{WRAPPER}} .ade-dir__count',
			)
		);

		$this->add_responsive_control(
			'count_margin',
			array(
				'label'      => esc_html__( 'Space below', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__count' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		// Results grid (only when the widget renders its own results).
		$this->start_controls_section(
			'section_style_results',
			array(
				'label'     => esc_html__( 'Results grid', 'artist-directory-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'target_id' => '' ),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'artist-directory-elementor' ),
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 6,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'selectors'      => array( '{{WRAPPER}} .ade-dir__results' => '--ade-columns: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => esc_html__( 'Column gap', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__results' => 'column-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => esc_html__( 'Row gap', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-dir__results' => 'row-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		// Pagination.
		$this->register_button_style( 'pager', esc_html__( 'Pagination / Load more', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-dir__more, {{WRAPPER}} .ade-dir__page-link', '{{WRAPPER}} .ade-dir__more:hover, {{WRAPPER}} .ade-dir__more:focus-visible, {{WRAPPER}} .ade-dir__page-link:hover, {{WRAPPER}} .ade-dir__page-link.is-current' );
	}

	/**
	 * Reusable button style section with normal / hover tabs.
	 *
	 * @param string $prefix   Control prefix.
	 * @param string $label    Section label.
	 * @param string $selector Normal selector.
	 * @param string $hover    Hover/active selector.
	 */
	private function register_button_style( string $prefix, string $label, string $selector, string $hover ): void {
		$this->start_controls_section(
			'section_style_' . $prefix,
			array(
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $prefix . '_typography',
				'selector' => $selector,
			)
		);

		$this->start_controls_tabs( $prefix . '_tabs' );

		foreach ( array(
			'normal' => array( esc_html__( 'Normal', 'artist-directory-elementor' ), $selector ),
			'hover'  => array( esc_html__( 'Hover / active', 'artist-directory-elementor' ), $hover ),
		) as $state => $data ) {
			$this->start_controls_tab( $prefix . '_tab_' . $state, array( 'label' => $data[0] ) );

			$this->add_control(
				$prefix . '_color_' . $state,
				array(
					'label'     => esc_html__( 'Text color', 'artist-directory-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $data[1] => 'color: {{VALUE}};' ),
				)
			);

			$this->add_control(
				$prefix . '_bg_' . $state,
				array(
					'label'     => esc_html__( 'Background', 'artist-directory-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $data[1] => 'background-color: {{VALUE}};' ),
				)
			);

			$this->add_control(
				$prefix . '_border_color_' . $state,
				array(
					'label'     => esc_html__( 'Border color', 'artist-directory-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $data[1] => 'border-color: {{VALUE}};' ),
				)
			);

			$this->end_controls_tab();
		}

		$this->end_controls_tabs();

		$this->add_responsive_control(
			$prefix . '_padding',
			array(
				'label'      => esc_html__( 'Padding', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'separator'  => 'before',
				'selectors'  => array( $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			$prefix . '_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		if ( 'pager' === $prefix ) {
			$this->add_responsive_control(
				'pager_align',
				array(
					'label'     => esc_html__( 'Alignment', 'artist-directory-elementor' ),
					'type'      => Controls_Manager::CHOOSE,
					'options'   => array(
						'flex-start' => array(
							'title' => esc_html__( 'Left', 'artist-directory-elementor' ),
							'icon'  => 'eicon-text-align-left',
						),
						'center'     => array(
							'title' => esc_html__( 'Center', 'artist-directory-elementor' ),
							'icon'  => 'eicon-text-align-center',
						),
						'flex-end'   => array(
							'title' => esc_html__( 'Right', 'artist-directory-elementor' ),
							'icon'  => 'eicon-text-align-right',
						),
					),
					'selectors' => array( '{{WRAPPER}} .ade-dir__pager' => 'justify-content: {{VALUE}};' ),
				)
			);

			$this->add_responsive_control(
				'pager_margin',
				array(
					'label'      => esc_html__( 'Space above', 'artist-directory-elementor' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px', 'em', 'rem' ),
					'selectors'  => array( '{{WRAPPER}} .ade-dir__pager' => 'margin-top: {{SIZE}}{{UNIT}};' ),
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Term options for a taxonomy dropdown.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $current  Selected slug.
	 */
	private function term_options( string $taxonomy, string $current ): string {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
			)
		);
		if ( is_wp_error( $terms ) ) {
			return '';
		}

		$html = '';
		foreach ( $terms as $term ) {
			$html .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $term->slug ), selected( $current, $term->slug, false ), esc_html( $term->name ) );
		}

		return $html;
	}

	/**
	 * Output one labelled field.
	 *
	 * @param string $id      Input ID.
	 * @param string $label   Label text.
	 * @param string $control Trusted control HTML.
	 * @param string $mod     Class modifier.
	 */
	private function field( string $id, string $label, string $control, string $mod ): void {
		$label_class = 'yes' === $this->get_settings_for_display( 'show_labels' ) ? 'ade-dir__label' : 'ade-dir__label ade-sr-only';
		printf(
			'<div class="ade-dir__field ade-dir__field--%1$s"><label class="%2$s" for="%3$s">%4$s</label>%5$s</div>',
			esc_attr( $mod ),
			esc_attr( $label_class ),
			esc_attr( $id ),
			esc_html( $label ),
			$control // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts in render().
		);
	}

	/**
	 * Front-end / editor output.
	 */
	protected function render() {
		$s           = $this->get_settings_for_display();
		$uid         = 'ade-dir-' . $this->get_id();
		$params      = ADE_Directory::params_from_url();
		$per_page    = max( 1, min( ADE_Directory::MAX_PER_PAGE, (int) $s['per_page'] ) );
		$template_id = (int) $s['template_id'];
		$target      = sanitize_html_class( (string) $s['target_id'] );
		$pagination  = in_array( $s['pagination'], array( 'load_more', 'numbers', 'none' ), true ) ? $s['pagination'] : 'load_more';
		$more_text   = (string) ( $s['load_more_text'] ?? '' );
		$is_editor   = ADE_Elementor::is_editor();

		$query = ADE_Directory::query( $params, $per_page );
		$pages = (int) $query->max_num_pages;
		$total = (int) $query->found_posts;

		$config = array(
			'templateId' => $template_id,
			'perPage'    => $per_page,
			'target'     => $target,
			'pagination' => $pagination,
			'moreText'   => $more_text,
			'live'       => 'yes' === $s['live'],
			'updateUrl'  => 'yes' === $s['update_url'] && ! $is_editor,
			'editor'     => $is_editor,
			'page'       => $params['page'],
			'pages'      => $pages,
			'widgetId'   => $this->get_id(),
		);
		?>
		<div class="ade-dir" data-ade-dir="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<form class="ade-dir__form" role="search" method="get" action="" aria-label="<?php esc_attr_e( 'Filter artists', 'artist-directory-elementor' ); ?>">
				<?php
				if ( 'yes' === $s['show_search'] ) {
					$this->field(
						$uid . '-s',
						(string) $s['search_label'],
						sprintf(
							'<input class="ade-dir__input" type="search" id="%1$s" name="ade_s" value="%2$s" placeholder="%3$s" autocomplete="off" />',
							esc_attr( $uid . '-s' ),
							esc_attr( $params['s'] ),
							esc_attr( (string) $s['search_placeholder'] )
						),
						'search'
					);
				}

				if ( 'yes' === $s['show_discipline'] ) {
					$this->field(
						$uid . '-discipline',
						(string) $s['discipline_label'],
						sprintf(
							'<select class="ade-dir__input" id="%1$s" name="ade_discipline"><option value="">%2$s</option>%3$s</select>',
							esc_attr( $uid . '-discipline' ),
							esc_html( (string) $s['discipline_all'] ),
							$this->term_options( 'discipline', $params['discipline'] )
						),
						'discipline'
					);
				}

				if ( 'yes' === $s['show_location'] ) {
					$this->field(
						$uid . '-location',
						(string) $s['location_label'],
						sprintf(
							'<select class="ade-dir__input" id="%1$s" name="ade_location"><option value="">%2$s</option>%3$s</select>',
							esc_attr( $uid . '-location' ),
							esc_html( (string) $s['location_all'] ),
							$this->term_options( 'artist_location', $params['location'] )
						),
						'location'
					);
				}

				if ( 'yes' === $s['show_sort'] ) {
					$sorts = array(
						'az'     => __( 'Name A–Z', 'artist-directory-elementor' ),
						'za'     => __( 'Name Z–A', 'artist-directory-elementor' ),
						'newest' => __( 'Newest first', 'artist-directory-elementor' ),
					);
					$opts  = '';
					foreach ( $sorts as $value => $label ) {
						$opts .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( $params['sort'], $value, false ), esc_html( $label ) );
					}
					$this->field(
						$uid . '-sort',
						(string) $s['sort_label'],
						sprintf( '<select class="ade-dir__input" id="%1$s" name="ade_sort">%2$s</select>', esc_attr( $uid . '-sort' ), $opts ),
						'sort'
					);
				}
				?>
				<div class="ade-dir__actions">
					<button type="submit" class="ade-dir__button ade-dir__submit"><?php echo esc_html( (string) $s['button_text'] ); ?></button>
					<?php if ( '' !== (string) $s['reset_text'] ) : ?>
						<button type="reset" class="ade-dir__button ade-dir__reset"><?php echo esc_html( (string) $s['reset_text'] ); ?></button>
					<?php endif; ?>
				</div>
			</form>

			<p class="ade-dir__count<?php echo 'yes' === $s['show_count'] ? '' : ' ade-sr-only'; ?>" role="status" aria-live="polite" aria-atomic="true"><?php echo esc_html( ADE_Directory::count_text( $total ) ); ?></p>

			<?php if ( '' === $target ) : ?>
				<div class="ade-dir__results" id="<?php echo esc_attr( $uid . '-results' ); ?>" tabindex="-1" aria-label="<?php esc_attr_e( 'Artist results', 'artist-directory-elementor' ); ?>">
					<?php echo ADE_Directory::render_items( $query, $template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor template output / escaped fallback cards. ?>
				</div>
			<?php else : ?>
				<?php
				if ( $is_editor ) {
					printf(
						'<p class="ade-dir__note">%s</p>',
						/* translators: %s: CSS ID. */
						esc_html( sprintf( __( 'Results load into the Loop Grid with CSS ID "%s".', 'artist-directory-elementor' ), $target ) )
					);
				}
				?>
			<?php endif; ?>

			<nav class="ade-dir__pager" data-ade-for="<?php echo esc_attr( $this->get_id() ); ?>" aria-label="<?php esc_attr_e( 'Artist results pages', 'artist-directory-elementor' ); ?>">
				<?php echo ADE_Directory::render_pagination( $params, $pages, $pagination, $more_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in render_pagination(). ?>
			</nav>
		</div>
		<?php
	}
}
