<?php
/**
 * Elementor widget: Events Calendar.
 *
 * @package ArtistDirectoryElementor
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Month grid / list calendar with AJAX month navigation.
 */
class ADE_Widget_Events_Calendar extends Widget_Base {

	/**
	 * Widget name.
	 */
	public function get_name() {
		return 'ade-events-calendar';
	}

	/**
	 * Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Events Calendar', 'artist-directory-elementor' );
	}

	/**
	 * Panel icon.
	 */
	public function get_icon() {
		return 'eicon-calendar';
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
		return array( 'event', 'calendar', 'month', 'agenda' );
	}

	/**
	 * Script handles.
	 */
	public function get_script_depends() {
		return array( 'ade-calendar' );
	}

	/**
	 * Style handles.
	 */
	public function get_style_depends() {
		return array( 'ade-calendar' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_calendar', array( 'label' => esc_html__( 'Calendar', 'artist-directory-elementor' ) ) );

		$this->add_control(
			'view',
			array(
				'label'   => esc_html__( 'Default view', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'month',
				'options' => array(
					'month' => esc_html__( 'Month grid', 'artist-directory-elementor' ),
					'list'  => esc_html__( 'List', 'artist-directory-elementor' ),
				),
			)
		);

		$this->add_control(
			'toggle',
			array(
				'label'   => esc_html__( 'Show Month / List toggle', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$weekdays = array( '' => esc_html__( 'Site setting', 'artist-directory-elementor' ) );
		foreach ( range( 0, 6 ) as $day ) {
			$weekdays[ (string) $day ] = esc_html( $GLOBALS['wp_locale']->get_weekday( $day ) );
		}

		$this->add_control(
			'week_start',
			array(
				'label'   => esc_html__( 'Week starts on', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $weekdays,
			)
		);

		$this->add_control(
			'show_time',
			array(
				'label'   => esc_html__( 'Show times', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_venue',
			array(
				'label'   => esc_html__( 'Show venue (list)', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_thumb',
			array(
				'label' => esc_html__( 'Show image (list)', 'artist-directory-elementor' ),
				'type'  => Controls_Manager::SWITCHER,
			)
		);

		$this->add_control(
			'tag',
			array(
				'label'   => esc_html__( 'Month title tag', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array_combine( ADE_Calendar::HEADING_TAGS, ADE_Calendar::HEADING_TAGS ),
			)
		);

		$this->add_control(
			'empty_text',
			array(
				'label'   => esc_html__( 'No events text', 'artist-directory-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'No events this month.', 'artist-directory-elementor' ),
			)
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	/**
	 * Color control helper.
	 *
	 * @param string $id       Control ID.
	 * @param string $label    Label.
	 * @param string $selector Selector.
	 * @param string $property CSS property.
	 */
	private function color( string $id, string $label, string $selector, string $property = 'color' ): void {
		$this->add_control(
			$id,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $selector => $property . ': {{VALUE}};' ),
			)
		);
	}

	/**
	 * Typography helper.
	 *
	 * @param string $name     Control name.
	 * @param string $selector Selector.
	 * @param string $label    Label.
	 */
	private function typography( string $name, string $selector, string $label = '' ): void {
		$args = array(
			'name'     => $name,
			'selector' => $selector,
		);
		if ( $label ) {
			$args['label'] = $label;
		}
		$this->add_group_control( Group_Control_Typography::get_type(), $args );
	}

	/**
	 * Style tab.
	 */
	private function register_style_controls(): void {
		// Header.
		$this->start_controls_section(
			'section_style_header',
			array(
				'label' => esc_html__( 'Header', 'artist-directory-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->typography( 'title_typography', '{{WRAPPER}} .ade-cal__title', esc_html__( 'Title typography', 'artist-directory-elementor' ) );
		$this->color( 'title_color', esc_html__( 'Title color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__title' );
		$this->typography( 'nav_typography', '{{WRAPPER}} .ade-cal__nav-btn, {{WRAPPER}} .ade-cal__view-btn, {{WRAPPER}} .ade-cal__today', esc_html__( 'Buttons typography', 'artist-directory-elementor' ) );
		$this->color( 'nav_color', esc_html__( 'Button color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__nav-btn, {{WRAPPER}} .ade-cal__view-btn, {{WRAPPER}} .ade-cal__today' );
		$this->color( 'nav_bg', esc_html__( 'Button background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__nav-btn, {{WRAPPER}} .ade-cal__view-btn, {{WRAPPER}} .ade-cal__today', 'background-color' );
		$this->color( 'nav_active_color', esc_html__( 'Active view color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__view-btn.is-active' );
		$this->color( 'nav_active_bg', esc_html__( 'Active view background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__view-btn.is-active', 'background-color' );
		$this->color( 'focus_color', esc_html__( 'Focus ring color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal', '--ade-focus' );
		$this->add_responsive_control(
			'header_margin',
			array(
				'label'      => esc_html__( 'Space below header', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-cal__header' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		// Month grid.
		$this->start_controls_section(
			'section_style_grid',
			array(
				'label' => esc_html__( 'Month grid', 'artist-directory-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->typography( 'weekday_typography', '{{WRAPPER}} .ade-cal__grid th', esc_html__( 'Weekday typography', 'artist-directory-elementor' ) );
		$this->color( 'weekday_color', esc_html__( 'Weekday color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__grid th' );
		$this->color( 'weekday_bg', esc_html__( 'Weekday background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__grid th', 'background-color' );
		$this->color( 'border_color', esc_html__( 'Border color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal', '--ade-border' );
		$this->color( 'day_bg', esc_html__( 'Day background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__day', 'background-color' );
		$this->color( 'outside_bg', esc_html__( 'Empty cell background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__day.is-outside', 'background-color' );
		$this->color( 'today_bg', esc_html__( 'Today background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__day.is-today', 'background-color' );
		$this->typography( 'date_typography', '{{WRAPPER}} .ade-cal__date-num', esc_html__( 'Date typography', 'artist-directory-elementor' ) );
		$this->color( 'date_color', esc_html__( 'Date color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__date' );
		$this->add_responsive_control(
			'cell_height',
			array(
				'label'      => esc_html__( 'Min cell height', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 40,
						'max' => 240,
					),
				),
				'selectors'  => array( '{{WRAPPER}} .ade-cal__day' => 'height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'cell_padding',
			array(
				'label'      => esc_html__( 'Cell padding', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ade-cal__day' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		// Event chips.
		$this->start_controls_section(
			'section_style_events',
			array(
				'label' => esc_html__( 'Events in grid', 'artist-directory-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->typography( 'event_typography', '{{WRAPPER}} .ade-cal__event a' );
		$this->color( 'event_color', esc_html__( 'Text color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__event a' );
		$this->color( 'event_bg', esc_html__( 'Background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__event a', 'background-color' );
		$this->color( 'event_hover_color', esc_html__( 'Hover text color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__event a:hover, {{WRAPPER}} .ade-cal__event a:focus-visible' );
		$this->color( 'event_hover_bg', esc_html__( 'Hover background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__event a:hover, {{WRAPPER}} .ade-cal__event a:focus-visible', 'background-color' );
		$this->add_responsive_control(
			'event_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'selectors'  => array( '{{WRAPPER}} .ade-cal__event a' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		// List.
		$this->start_controls_section(
			'section_style_list',
			array(
				'label' => esc_html__( 'List view', 'artist-directory-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->color( 'badge_color', esc_html__( 'Date badge color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__badge' );
		$this->color( 'badge_bg', esc_html__( 'Date badge background', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__badge', 'background-color' );
		$this->typography( 'badge_typography', '{{WRAPPER}} .ade-cal__badge-day', esc_html__( 'Badge day typography', 'artist-directory-elementor' ) );
		$this->typography( 'item_title_typography', '{{WRAPPER}} .ade-cal__item-title', esc_html__( 'Title typography', 'artist-directory-elementor' ) );
		$this->color( 'item_title_color', esc_html__( 'Title color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__item-title a' );
		$this->typography( 'item_meta_typography', '{{WRAPPER}} .ade-cal__item-meta', esc_html__( 'Meta typography', 'artist-directory-elementor' ) );
		$this->color( 'item_meta_color', esc_html__( 'Meta color', 'artist-directory-elementor' ), '{{WRAPPER}} .ade-cal__item-meta' );
		$this->add_responsive_control(
			'item_gap',
			array(
				'label'      => esc_html__( 'Space between events', 'artist-directory-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} .ade-cal__list' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Map widget settings to ADE_Calendar args.
	 *
	 * @param array $s Settings.
	 */
	private function calendar_args( array $s ): array {
		return ADE_Calendar::args(
			array(
				'view'       => ADE_Calendar::view_from_url() ?? $s['view'],
				'week_start' => $s['week_start'],
				'show_time'  => 'yes' === $s['show_time'],
				'show_venue' => 'yes' === $s['show_venue'],
				'show_thumb' => 'yes' === $s['show_thumb'],
				'toggle'     => 'yes' === $s['toggle'],
				'tag'        => $s['tag'],
				'empty_text' => $s['empty_text'],
			)
		);
	}

	/**
	 * Output.
	 */
	protected function render() {
		$args  = $this->calendar_args( $this->get_settings_for_display() );
		$month = ADE_Calendar::month_from_url();
		$inner = ADE_Calendar::render( $month, $args );

		$config = array(
			'month'     => $month->format( 'Y-m' ),
			'view'      => $args['view'],
			'weekStart' => $args['week_start'],
			'showTime'  => $args['show_time'],
			'showVenue' => $args['show_venue'],
			'showThumb' => $args['show_thumb'],
			'toggle'    => $args['toggle'],
			'tag'       => $args['tag'],
			'emptyText' => $args['empty_text'],
		);
		?>
		<div class="ade-cal ade-cal--<?php echo esc_attr( $args['view'] ); ?>" data-ade-cal="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<div class="ade-cal__inner">
				<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in ADE_Calendar. ?>
			</div>
			<p class="ade-sr-only ade-cal__status" role="status" aria-live="polite" aria-atomic="true"></p>
		</div>
		<?php
	}
}
