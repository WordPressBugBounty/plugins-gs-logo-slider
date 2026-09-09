<?php

namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor widget that exposes the whole shortcode builder inline.
 *
 * Unlike Elementor_Widget (which only picks a saved shortcode by ID), this
 * widget keeps every shortcode setting in its own Elementor controls, so
 * nothing is written to the shortcode table. Controls are native Elementor
 * fields; labels, options and defaults come from the shortcode builder.
 */
class Elementor_Builder_Widget extends \Elementor\Widget_Base {

	const INSTANCE_PREFIX = 'gslogo_el_';

	/**
	 * Cached builder translations for this request.
	 *
	 * @var array|null
	 */
	protected $translations = null;

	/**
	 * Cached builder default settings for this request.
	 *
	 * @var array|null
	 */
	protected $default_settings = null;

	/**
	 * Cached builder option lists for this request.
	 *
	 * @var array|null
	 */
	protected $default_options = null;

	public function get_name() {
		return 'gs-logo-slider-builder';
	}

	public function get_title() {
		return __( 'GS Logo Slider Builder', 'gslogo' );
	}

	public function get_icon() {
		return 'gs-logo-slider';
	}

	public function get_categories() {
		return [ 'gs-plugins', 'general' ];
	}

	public function get_keywords() {
		return [ 'logo', 'slider', 'carousel', 'grid', 'builder', 'gs' ];
	}

	protected function register_controls() {

		$this->register_general_controls();
		$this->register_style_controls();
		$this->register_query_controls();
		$this->register_visibility_controls();

	}

	/**
	 * General tab of the shortcode builder: theme, filter, image, linking,
	 * slider, title/content, tooltip, table headings.
	 */
	protected function register_general_controls() {

		$grid_themes               = $this->grid_themes();
		$list_themes               = $this->list_themes();
		$table_themes              = $this->table_themes();
		$carousel_themes           = $this->carousel_themes();
		$horizontal_carousel       = $this->horizontal_carousel_themes();
		$ticker_themes             = $this->ticker_themes();
		$list_ticker_themes        = $this->list_ticker_themes();
		$carousel_or_ticker        = array_values( array_unique( array_merge( $carousel_themes, $ticker_themes ) ) );
		$carousel_except_vwidth    = array_values( array_diff( $carousel_themes, [ 'vwidth' ] ) );
		$pagination_themes         = array_values( array_merge( $grid_themes, $list_themes ) );
		$title_themes              = $this->get_themes_for_visibility_field( 'logo_title' );
		$content_themes            = $this->get_themes_for_visibility_field( 'logo_content' );
		$excerpt_themes            = $this->get_themes_for_visibility_field( 'logo_excerpt' );

		$this->start_controls_section(
			'section_theme',
			[
				'label' => $this->trans( 'gs-l-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'gs_l_theme',
			[
				'label'       => $this->trans( 'gs-l-theme' ),
				'description' => $this->trans( 'gs-l-theme--help' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'gs_l_theme' ),
				'default'     => $this->setting_default( 'gs_l_theme' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'gs_l_s2_border_thickness',
			$this->premium_control_args( [
				'label'       => $this->trans( 'gs-l-s2-border-thickness' ),
				'description' => $this->trans( 'gs-l-s2-border-thickness--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => $this->setting_default( 'gs_l_s2_border_thickness' ),
				'condition'   => [
					'gs_l_theme' => 'hexagon',
				],
			] )
		);

		$this->add_control(
			'gs_l_s2_gradient_start',
			$this->premium_control_args( [
				'label'     => $this->trans( 'gs-l-s2-gradient-start' ),
				'description' => $this->trans( 'gs-l-s2-gradient-start--help' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => $this->setting_default( 'gs_l_s2_gradient_start' ),
				'condition' => [
					'gs_l_theme' => 'hexagon',
				],
			] )
		);

		$this->add_control(
			'gs_l_s2_gradient_end',
			$this->premium_control_args( [
				'label'     => $this->trans( 'gs-l-s2-gradient-end' ),
				'description' => $this->trans( 'gs-l-s2-gradient-end--help' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => $this->setting_default( 'gs_l_s2_gradient_end' ),
				'condition' => [
					'gs_l_theme' => 'hexagon',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_border_width',
			$this->premium_control_args( [
				'label'       => __( 'Thickness', 'gslogo' ),
				'description' => $this->trans( 'gs-l-rb-border--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => 1,
				'condition'   => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_border_style',
			$this->premium_control_args( [
				'label'     => __( 'Type', 'gslogo' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => $this->get_select_options( 'gs_l_rb_border_type' ),
				'default'   => 'solid',
				'condition' => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_border_color',
			$this->premium_control_args( [
				'label'     => __( 'Color', 'gslogo' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#000000',
				'condition' => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_border_radius',
			$this->premium_control_args( [
				'label'       => $this->trans( 'gs-l-rb-border-radius' ),
				'description' => $this->trans( 'gs-l-rb-border-radius--help' ),
				'type'        => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units'  => [ 'px' ],
				'default'     => [
					'top'      => 10,
					'right'    => 10,
					'bottom'   => 10,
					'left'     => 10,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'condition'   => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_hover_shadow_color',
			$this->premium_control_args( [
				'label'       => $this->trans( 'gs-l-rb-hover-shadow-color' ),
				'description' => $this->trans( 'gs-l-rb-hover-shadow-color--help' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'default'     => $this->setting_default( 'gs_l_rb_hover_shadow_color' ),
				'condition'   => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_hover_shadow_heading',
			[
				'label'     => $this->trans( 'gs-l-rb-hover-shadow-control' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'gs_l_theme' => 'rounded-border',
				],
			]
		);

		$this->add_control(
			'gs_l_rb_hover_shadow_x',
			$this->premium_control_args( [
				'label'     => __( 'Offset X', 'gslogo' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 200,
				'default'   => 6,
				'condition' => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_hover_shadow_y',
			$this->premium_control_args( [
				'label'     => __( 'Offset Y', 'gslogo' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 200,
				'default'   => 6,
				'condition' => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_hover_shadow_blur',
			$this->premium_control_args( [
				'label'     => __( 'Blur', 'gslogo' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 200,
				'default'   => 15,
				'condition' => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->add_control(
			'gs_l_rb_hover_shadow_spread',
			$this->premium_control_args( [
				'label'       => __( 'Spread', 'gslogo' ),
				'description' => $this->trans( 'gs-l-rb-hover-shadow-control--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 200,
				'default'     => 0,
				'condition'   => [
					'gs_l_theme' => 'rounded-border',
				],
			] )
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_filter_pagination',
			[
				'label'     => __( 'Filter & Pagination', 'gslogo' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => [
					'gs_l_theme' => $pagination_themes,
				],
			]
		);

		$this->add_on_off_switcher(
			'filter_enabled',
			$this->premium_control_args( [
				'label'       => $this->trans( 'filter_enabled' ),
				'description' => $this->trans( 'filter_enabled__details' ),
				'condition'   => [
					'gs_l_theme' => $grid_themes,
				],
			] )
		);

		$this->add_control(
			'gs_logo_filter_type',
			$this->premium_control_args( [
				'label'       => $this->trans( 'filter_type' ),
				'description' => $this->trans( 'filter_type__details' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'gs_logo_filter_type' ),
				'default'     => $this->setting_default( 'gs_logo_filter_type' ),
				'condition'   => [
					'gs_l_theme'     => $grid_themes,
					'filter_enabled' => 'on',
				],
			] )
		);

		$this->add_on_off_switcher(
			'gs_logo_pagination',
			$this->premium_control_args( [
				'label'       => $this->trans( 'gs_logo_pagination' ),
				'description' => $this->trans( 'gs_logo_pagination__details' ),
				'conditions'  => $this->get_pagination_display_conditions( $pagination_themes ),
			] )
		);

		$this->add_control(
			'pagination_type',
			$this->premium_control_args( [
				'label'       => $this->trans( 'pagination_type' ),
				'description' => $this->trans( 'pagination_type__details' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'pagination_type' ),
				'default'     => $this->setting_default( 'pagination_type' ),
				'conditions'  => $this->get_pagination_enabled_conditions( $pagination_themes ),
			] )
		);

		$this->add_control(
			'initial_items',
			$this->premium_control_args( [
				'label'       => $this->trans( 'initial_items' ),
				'description' => $this->trans( 'initial_items__details' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => $this->setting_default( 'initial_items' ),
				'conditions'  => $this->get_pagination_type_conditions( $pagination_themes, [ 'load-more-button', 'load-more-scroll' ] ),
			] )
		);

		$this->add_control(
			'logo_per_page',
			$this->premium_control_args( [
				'label'       => $this->trans( 'logo_per_page' ),
				'description' => $this->trans( 'logo_per_page__details' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => $this->setting_default( 'logo_per_page' ),
				'conditions'  => $this->get_pagination_type_conditions( $pagination_themes, [ 'normal-pagination', 'ajax-pagination' ] ),
			] )
		);

		$this->add_control(
			'load_per_click',
			$this->premium_control_args( [
				'label'       => $this->trans( 'load_per_click' ),
				'description' => $this->trans( 'load_per_click__details' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => $this->setting_default( 'load_per_click' ),
				'conditions'  => $this->get_pagination_type_conditions( $pagination_themes, [ 'load-more-button' ] ),
			] )
		);

		$this->add_control(
			'per_load',
			$this->premium_control_args( [
				'label'       => $this->trans( 'per_load' ),
				'description' => $this->trans( 'per_load__details' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => $this->setting_default( 'per_load' ),
				'conditions'  => $this->get_pagination_type_conditions( $pagination_themes, [ 'load-more-scroll' ] ),
			] )
		);

		$this->add_control(
			'load_button_text',
			[
				'label'       => $this->trans( 'load_button_text' ),
				'description' => $this->trans( 'load_button_text__details' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $this->setting_default( 'load_button_text' ),
				'conditions'  => $this->get_pagination_type_conditions( $pagination_themes, [ 'load-more-button' ] ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_image_size',
			[
				'label' => $this->trans( 'image-size' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'       => $this->trans( 'image-size' ),
				'description' => $this->trans( 'image-size--help' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_select_options( 'image_size' ),
				'default'     => $this->setting_default( 'image_size' ),
			]
		);

		$this->add_control(
			'custom_image_size_width',
			$this->premium_control_args( [
				'label'     => __( 'Width', 'gslogo' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => $this->setting_default( 'custom_image_size_width' ),
				'condition' => [
					'image_size' => 'custom',
				],
			] )
		);

		$this->add_control(
			'custom_image_size_height',
			$this->premium_control_args( [
				'label'     => __( 'Height', 'gslogo' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => $this->setting_default( 'custom_image_size_height' ),
				'condition' => [
					'image_size' => 'custom',
				],
			] )
		);

		$this->add_control(
			'custom_image_size_crop',
			$this->premium_control_args( [
				'label'       => $this->trans( 'custom-image-size' ),
				'description' => $this->trans( 'custom-image-size--help' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_select_options( 'custom_image_size_crop' ),
				'default'     => $this->setting_default( 'custom_image_size_crop' ),
				'condition'   => [
					'image_size' => 'custom',
				],
			] )
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_link_logos',
			[
				'label' => $this->trans( 'gs-l-link-logos' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_on_off_switcher( 'gs_l_link_logos', [
			'label'       => $this->trans( 'gs-l-link-logos' ),
			'description' => $this->trans( 'gs-l-link-logos--help' ),
		] );

		$this->add_control(
			'gs_logo_link_type',
			[
				'label'       => $this->trans( 'gs_logo_link_type' ),
				'description' => $this->trans( 'gs_logo_link_type__details' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'gs_logo_link_type' ),
				'default'     => $this->setting_default( 'gs_logo_link_type' ),
				'label_block' => true,
				'condition'   => [
					'gs_l_link_logos' => 'on',
				],
			]
		);

		$this->add_control(
			'popup_style',
			[
				'label'       => $this->trans( 'popup_style' ),
				'description' => $this->trans( 'popup_style__details' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'popup_style' ),
				'default'     => $this->setting_default( 'popup_style' ),
				'condition'   => [
					'gs_l_link_logos'   => 'on',
					'gs_logo_link_type' => 'popup',
				],
			]
		);

		$this->add_control(
			'panel_style',
			[
				'label'       => $this->trans( 'panel_style' ),
				'description' => $this->trans( 'panel_style__details' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'panel_style' ),
				'default'     => $this->setting_default( 'panel_style' ),
				'condition'   => [
					'gs_l_link_logos'   => 'on',
					'gs_logo_link_type' => 'panel',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_slider',
			[
				'label'     => __( 'Slider & Autoplay', 'gslogo' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => [
					'gs_l_theme' => $carousel_or_ticker,
				],
			]
		);

		$this->add_control(
			'gs_l_slide_speed',
			[
				'label'       => $this->trans( 'gs-l-slide-speed' ),
				'description' => $this->trans( 'gs-l-slide-speed--help' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'ms' ],
				'range'       => [
					'ms' => [
						'min'  => 0,
						'max'  => 10000,
						'step' => 50,
					],
				],
				'default'     => [
					'unit' => 'ms',
					'size' => (int) $this->setting_default( 'gs_l_slide_speed' ),
				],
			]
		);

		$this->add_on_off_switcher( 'gs_l_is_autop', [
			'label'       => $this->trans( 'gs-l-is-autop' ),
			'description' => $this->trans( 'gs-l-is-autop--help' ),
			'condition'   => [
				'gs_l_theme' => $carousel_themes,
			],
		] );

		$this->add_control(
			'gs_l_autop_pause',
			[
				'label'       => $this->trans( 'gs-l-autop-pause' ),
				'description' => $this->trans( 'gs-l-autop-pause--help' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'ms' ],
				'range'       => [
					'ms' => [
						'min'  => 0,
						'max'  => 10000,
						'step' => 50,
					],
				],
				'default'     => [
					'unit' => 'ms',
					'size' => (int) $this->setting_default( 'gs_l_autop_pause' ),
				],
				'conditions'  => [
					'relation' => 'or',
					'terms'    => [
						[
							'relation' => 'and',
							'terms'    => [
								[
									'name'     => 'gs_l_theme',
									'operator' => 'in',
									'value'    => $carousel_themes,
								],
								[
									'name'     => 'gs_l_is_autop',
									'operator' => '===',
									'value'    => 'on',
								],
							],
						],
						[
							'name'     => 'gs_l_theme',
							'operator' => 'in',
							'value'    => $list_ticker_themes,
						],
					],
				],
			]
		);

		$this->add_on_off_switcher( 'gs_l_slider_stop', [
			'label'       => $this->trans( 'gs-l-slider-stop' ),
			'description' => $this->trans( 'gs-l-slider-stop--help' ),
			'conditions'  => [
				'relation' => 'or',
				'terms'    => [
					[
						'relation' => 'and',
						'terms'    => [
							[
								'name'     => 'gs_l_theme',
								'operator' => 'in',
								'value'    => $carousel_themes,
							],
							[
								'name'     => 'gs_l_is_autop',
								'operator' => '===',
								'value'    => 'on',
							],
						],
					],
					[
						'name'     => 'gs_l_theme',
						'operator' => 'in',
						'value'    => $ticker_themes,
					],
				],
			],
		] );

		$this->add_on_off_switcher( 'gs_l_inf_loop', [
			'label'       => $this->trans( 'gs-l-inf-loop' ),
			'description' => $this->trans( 'gs-l-inf-loop--help' ),
			'condition'   => [
				'gs_l_theme' => $carousel_except_vwidth,
			],
		] );

		$this->add_on_off_switcher(
			'gs_reverse_direction',
			$this->premium_control_args( [
				'label'       => $this->trans( 'gs-reverse-direction' ),
				'description' => $this->trans( 'gs-reverse-direction--help' ),
				'conditions'  => [
					'relation' => 'or',
					'terms'    => [
						[
							'relation' => 'and',
							'terms'    => [
								[
									'name'     => 'gs_l_theme',
									'operator' => 'in',
									'value'    => $carousel_themes,
								],
								[
									'name'     => 'gs_l_is_autop',
									'operator' => '===',
									'value'    => 'on',
								],
							],
						],
						[
							'name'     => 'gs_l_theme',
							'operator' => 'in',
							'value'    => $ticker_themes,
						],
					],
				],
			] )
		);

		$this->add_on_off_switcher( 'gs_l_ctrl', [
			'label'       => $this->trans( 'gs-l-ctrl' ),
			'description' => $this->trans( 'gs-l-ctrl--help' ),
			'condition'   => [
				'gs_l_theme' => $horizontal_carousel,
			],
		] );

		$this->add_control(
			'gs_l_ctrl_pos',
			[
				'label'       => $this->trans( 'gs-l-ctrl-pos' ),
				'description' => $this->trans( 'gs-l-ctrl-pos--help' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_select_options( 'gs_l_ctrl_pos' ),
				'default'     => $this->setting_default( 'gs_l_ctrl_pos' ),
				'condition'   => [
					'gs_l_theme' => $horizontal_carousel,
					'gs_l_ctrl'  => 'on',
				],
			]
		);

		$this->add_on_off_switcher( 'gs_l_pagi', [
			'label'       => $this->trans( 'gs-l-pagi' ),
			'description' => $this->trans( 'gs-l-pagi--help' ),
			'condition'   => [
				'gs_l_theme' => $horizontal_carousel,
			],
		] );

		$this->add_on_off_switcher( 'gs_l_pagi_dynamic', [
			'label'       => $this->trans( 'gs-l-pagi-dynamic' ),
			'description' => $this->trans( 'gs-l-pagi-dynamic--help' ),
			'condition'   => [
				'gs_l_theme' => $horizontal_carousel,
				'gs_l_pagi'  => 'on',
			],
		] );

		$this->add_on_off_switcher( 'gs_l_play_pause', [
			'label'       => $this->trans( 'gs-l-play-pause' ),
			'description' => $this->trans( 'gs-l-play-pause--help' ),
			'condition'   => [
				'gs_l_theme' => $horizontal_carousel,
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title_content',
			[
				'label'      => __( 'Title & Content', 'gslogo' ),
				'tab'        => \Elementor\Controls_Manager::TAB_CONTENT,
				'conditions' => [
					'relation' => 'or',
					'terms'    => [
						[
							'name'     => 'gs_l_theme',
							'operator' => 'in',
							'value'    => $title_themes,
						],
						[
							'name'     => 'gs_l_theme',
							'operator' => 'in',
							'value'    => $content_themes,
						],
						[
							'name'     => 'gs_l_theme',
							'operator' => 'in',
							'value'    => $excerpt_themes,
						],
					],
				],
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'       => $this->trans( 'title-tag' ),
				'description' => $this->trans( 'title-tag--help' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_select_options( 'title_tag' ),
				'default'     => $this->setting_default( 'title_tag' ),
				'conditions'  => $this->get_visibility_field_on_conditions( 'initial', 'logo_title', $title_themes ),
			]
		);

		$this->add_control(
			'gs_l_content_limit_count',
			$this->premium_control_args( [
				'label'       => $this->trans( 'gs-l-content-limit' ),
				'description' => $this->trans( 'gs-l-content-limit--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => $this->setting_default( 'gs_l_content_limit_count' ),
				'conditions'  => $this->get_visibility_field_on_conditions( 'initial', 'logo_content', $content_themes ),
			] )
		);

		$this->add_control(
			'gs_l_content_limit_type',
			$this->premium_control_args( [
				'label'      => __( 'Limit By', 'gslogo' ),
				'type'       => \Elementor\Controls_Manager::SELECT,
				'options'    => $this->get_select_options( 'gs_l_content_limit_type' ),
				'default'    => $this->setting_default( 'gs_l_content_limit_type' ),
				'conditions' => $this->get_visibility_field_on_conditions( 'initial', 'logo_content', $content_themes ),
			] )
		);

		$this->add_control(
			'gs_l_excerpt_limit_count',
			$this->premium_control_args( [
				'label'       => $this->trans( 'gs-l-excerpt-limit' ),
				'description' => $this->trans( 'gs-l-excerpt-limit--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => $this->setting_default( 'gs_l_excerpt_limit_count' ),
				'conditions'  => $this->get_visibility_field_on_conditions( 'initial', 'logo_excerpt', $excerpt_themes ),
			] )
		);

		$this->add_control(
			'gs_l_excerpt_limit_type',
			$this->premium_control_args( [
				'label'      => __( 'Limit By', 'gslogo' ),
				'type'       => \Elementor\Controls_Manager::SELECT,
				'options'    => $this->get_select_options( 'gs_l_excerpt_limit_type' ),
				'default'    => $this->setting_default( 'gs_l_excerpt_limit_type' ),
				'conditions' => $this->get_visibility_field_on_conditions( 'initial', 'logo_excerpt', $excerpt_themes ),
			] )
		);

		$this->add_control(
			'gs_l_read_more_text',
			$this->premium_control_args( [
				'label'      => $this->trans( 'gs-l-read-more-text' ),
				'type'       => \Elementor\Controls_Manager::TEXT,
				'default'    => $this->setting_default( 'gs_l_read_more_text' ),
				'conditions' => [
					'relation' => 'or',
					'terms'    => [
						$this->get_visibility_field_on_condition_term( 'initial', 'logo_content', $content_themes ),
						$this->get_visibility_field_on_condition_term( 'initial', 'logo_excerpt', $excerpt_themes ),
					],
				],
			] )
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_tooltip',
			[
				'label' => $this->trans( 'gs-l-tooltip' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_on_off_switcher( 'gs_l_tooltip', [
			'label'       => $this->trans( 'gs-l-tooltip' ),
			'description' => $this->trans( 'gs-l-tooltip--help' ),
		] );

		$this->add_control(
			'gs_l_tooltip_placement',
			[
				'label'       => $this->trans( 'gs-l-tooltip-placement' ),
				'description' => $this->trans( 'gs-l-tooltip-placement--help' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_select_options( 'gs_l_tooltip_placement' ),
				'default'     => $this->setting_default( 'gs_l_tooltip_placement' ),
				'condition'   => [
					'gs_l_tooltip' => 'on',
				],
			]
		);

		$this->add_control(
			'gs_l_tooltip_bgcolor_one',
			[
				'label'       => $this->trans( 'gs-l-tooltip-bgcolor' ),
				'description' => $this->trans( 'gs-l-tooltip-bgcolor--help' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'default'     => $this->setting_default( 'gs_l_tooltip_bgcolor_one' ),
				'condition'   => [
					'gs_l_tooltip' => 'on',
				],
			]
		);

		$this->add_control(
			'gs_l_tooltip_bgcolor_two',
			[
				'label'     => $this->trans( 'gs-l-tooltip-bgcolor' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => $this->setting_default( 'gs_l_tooltip_bgcolor_two' ),
				'condition' => [
					'gs_l_tooltip' => 'on',
				],
			]
		);

		$this->add_control(
			'gs_l_tooltip_textcolor',
			[
				'label'       => $this->trans( 'gs-l-tooltip-textcolor' ),
				'description' => $this->trans( 'gs-l-tooltip-textcolor--help' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'default'     => $this->setting_default( 'gs_l_tooltip_textcolor' ),
				'condition'   => [
					'gs_l_tooltip' => 'on',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_table_headings',
			[
				'label'     => __( 'Table Headings', 'gslogo' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => [
					'gs_l_theme' => $table_themes,
				],
			]
		);

		$this->add_control(
			'row_heading_image',
			[
				'label'       => $this->trans( 'row_heading_image' ),
				'placeholder' => $this->trans( 'row_heading_image--placeholder' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $this->setting_default( 'row_heading_image' ),
			]
		);

		$this->add_control(
			'row_heading_name',
			[
				'label'       => $this->trans( 'row_heading_name' ),
				'placeholder' => $this->trans( 'row_heading_name--placeholder' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $this->setting_default( 'row_heading_name' ),
			]
		);

		$this->add_control(
			'row_heading_desc',
			[
				'label'       => $this->trans( 'row_heading_desc' ),
				'placeholder' => $this->trans( 'row_heading_desc--placeholder' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $this->setting_default( 'row_heading_desc' ),
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Style tab of the shortcode builder.
	 */
	protected function register_style_controls() {

		$grid_align_themes   = [ 'grid1', 'grid2', 'grid3' ];
		$table_themes        = $this->table_themes();
		$list_themes         = $this->list_themes();
		$list_ticker_themes  = $this->list_ticker_themes();
		$carousel_themes     = $this->carousel_themes();
		$logo_count_hide     = array_values( array_merge( $list_themes, $table_themes, [ 'vwidth' ] ) );
		$margin_hide         = array_values( array_merge( $table_themes, $list_ticker_themes ) );

		$this->start_controls_section(
			'section_image_filter',
			[
				'label' => $this->trans( 'image_filter' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'image_filter',
			[
				'label'       => $this->trans( 'image_filter' ),
				'description' => $this->trans( 'image_filter__help' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'image_filter' ),
				'default'     => $this->setting_default( 'image_filter' ),
			]
		);

		$this->add_control(
			'hover_image_filter',
			[
				'label'       => $this->trans( 'hover_image_filter' ),
				'description' => $this->trans( 'hover_image_filter__help' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_select_options( 'hover_image_filter' ),
				'default'     => $this->setting_default( 'hover_image_filter' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'Layout', 'gslogo' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'gs_l_align',
			[
				'label'       => $this->trans( 'gs-l-align' ),
				'description' => $this->trans( 'gs-l-align--help' ),
				'type'        => \Elementor\Controls_Manager::CHOOSE,
				'options'     => [
					'flex-start' => [
						'title' => __( 'Left', 'gslogo' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => __( 'Center', 'gslogo' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => __( 'Right', 'gslogo' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'     => $this->setting_default( 'gs_l_align' ),
				'toggle'      => false,
				'condition'   => [
					'gs_l_theme' => $grid_align_themes,
				],
			]
		);

		$this->add_control(
			'gs_l_margin',
			[
				'label'       => $this->trans( 'gs-l-margin' ),
				'description' => $this->trans( 'gs-l-margin--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 50,
				'default'     => $this->setting_default( 'gs_l_margin' ),
				'condition'   => [
					'gs_l_theme!' => $margin_hide,
				],
			]
		);

		$this->add_control(
			'gs_l_min_logo',
			[
				'label'       => $this->trans( 'gs-l-min-logo' ),
				'description' => $this->trans( 'gs-l-min-logo--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 10,
				'default'     => $this->setting_default( 'gs_l_min_logo' ),
				'condition'   => [
					'gs_l_theme!' => $logo_count_hide,
				],
			]
		);

		$this->add_control(
			'gs_l_tab_logo',
			[
				'label'       => $this->trans( 'gs-l-tab-logo' ),
				'description' => $this->trans( 'gs-l-tab-logo--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 10,
				'default'     => $this->setting_default( 'gs_l_tab_logo' ),
				'condition'   => [
					'gs_l_theme!' => $logo_count_hide,
				],
			]
		);

		$this->add_control(
			'gs_l_mob_logo',
			[
				'label'       => $this->trans( 'gs-l-mob-logo' ),
				'description' => $this->trans( 'gs-l-mob-logo--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 10,
				'default'     => $this->setting_default( 'gs_l_mob_logo' ),
				'condition'   => [
					'gs_l_theme!' => $logo_count_hide,
				],
			]
		);

		$this->add_control(
			'gs_l_move_logo',
			[
				'label'       => $this->trans( 'gs-l-move-logo' ),
				'description' => $this->trans( 'gs-l-move-logo--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 10,
				'default'     => $this->setting_default( 'gs_l_move_logo' ),
				'condition'   => [
					'gs_l_theme' => $carousel_themes,
				],
			]
		);

		$this->add_control(
			'gs_l_clkable',
			[
				'label'       => $this->trans( 'gs-l-clkable' ),
				'description' => $this->trans( 'gs-l-clkable--help' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_select_options( 'gs_l_clkable' ),
				'default'     => $this->setting_default( 'gs_l_clkable' ),
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Query tab of the shortcode builder.
	 */
	protected function register_query_controls() {

		$this->start_controls_section(
			'section_query',
			[
				'label' => $this->trans( 'query-settings' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'posts',
			[
				'label'       => $this->trans( 'posts' ),
				'description' => $this->trans( 'posts--help' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => -1,
				'default'     => $this->setting_default( 'posts' ),
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => $this->trans( 'order' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_select_options( 'order' ),
				'default' => $this->setting_default( 'order' ),
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => $this->trans( 'order-by' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_select_options( 'orderby' ),
				'default' => $this->setting_default( 'orderby' ),
			]
		);

		$this->end_controls_section();

		$taxonomies = $this->get_enabled_taxonomies();

		if ( ! empty( $taxonomies ) ) {

			$this->start_controls_section(
				'section_include_terms',
				[
					'label' => __( 'Include', 'gslogo' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			foreach ( $taxonomies as $taxonomy ) {
				$this->add_control(
					'include_' . $taxonomy['suffix'],
					[
						'label'       => $taxonomy['label'],
						'description' => $this->trans( 'include-tax--details' ),
						'type'        => \Elementor\Controls_Manager::SELECT2,
						'multiple'    => true,
						'label_block' => true,
						'options'     => $this->get_select_options( $taxonomy['options_key'] ),
						'default'     => [],
					]
				);
			}

			$this->end_controls_section();

			$this->start_controls_section(
				'section_exclude_terms',
				[
					'label' => __( 'Exclude', 'gslogo' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);

			foreach ( $taxonomies as $taxonomy ) {
				$this->add_control(
					'exclude_' . $taxonomy['suffix'],
					[
						'label'       => $taxonomy['label'],
						'description' => $this->trans( 'exclude-tax--details' ),
						'type'        => \Elementor\Controls_Manager::SELECT2,
						'multiple'    => true,
						'label_block' => true,
						'options'     => $this->get_select_options( $taxonomy['options_key'] ),
						'default'     => [],
					]
				);
			}

			$this->end_controls_section();

		}

	}

	/**
	 * Visibility tab of the shortcode builder: one checkbox row per field,
	 * four devices as columns, split into initial / popup / panel groups.
	 */
	protected function register_visibility_controls() {

		$devices = $this->get_visibility_devices();

		$this->start_controls_section(
			'section_visibility_initial',
			[
				'label' => $this->trans( 'visibility-initial-view' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->register_visibility_devices_header( 'initial' );

		foreach ( $this->get_all_initial_visibility_fields() as $field_key ) {
			$this->register_visibility_field_controls( 'initial', $field_key, $devices, [
				'gs_l_theme' => $this->get_themes_for_visibility_field( $field_key ),
			] );
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_visibility_popup',
			[
				'label'     => $this->trans( 'visibility-popup' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => [
					'gs_l_link_logos'   => 'on',
					'gs_logo_link_type' => 'popup',
				],
			]
		);

		$this->register_visibility_devices_header( 'popup' );

		foreach ( plugin()->builder->get_overlay_visibility_fields() as $field_key ) {
			$this->register_visibility_field_controls( 'popup', $field_key, $devices, [
				'gs_l_link_logos'   => 'on',
				'gs_logo_link_type' => 'popup',
			] );
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_visibility_panel',
			[
				'label'     => $this->trans( 'visibility-panel' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => [
					'gs_l_link_logos'   => 'on',
					'gs_logo_link_type' => 'panel',
				],
			]
		);

		$this->register_visibility_devices_header( 'panel' );

		foreach ( plugin()->builder->get_overlay_visibility_fields() as $field_key ) {
			$this->register_visibility_field_controls( 'panel', $field_key, $devices, [
				'gs_l_link_logos'   => 'on',
				'gs_logo_link_type' => 'panel',
			] );
		}

		$this->end_controls_section();

	}

	/**
	 * Column headers that sit above the per-field checkbox rows.
	 *
	 * @param string $group     initial|popup|panel
	 * @param array  $condition Optional Elementor condition array.
	 */
	protected function register_visibility_devices_header( $group, $condition = [] ) {

		$args = [
			'type'            => \Elementor\Controls_Manager::RAW_HTML,
			'raw'             => $this->get_visibility_devices_header_html(),
			'content_classes' => 'gs-logo-visibility-header',
			'classes'         => 'gs-logo-visibility-header-control',
			'separator'       => 'none',
		];

		if ( $condition ) {
			$args['condition'] = $condition;
		}

		$this->add_control( "visibility_{$group}_devices_header", $args );

	}

	/**
	 * One checkbox row for a visibility field (label + four device columns).
	 *
	 * @param string $group     initial|popup|panel
	 * @param string $field_key Visibility field key.
	 * @param array  $devices   Device key => label map.
	 * @param array  $condition Elementor condition array.
	 */
	protected function register_visibility_field_controls( $group, $field_key, $devices, $condition ) {

		$defaults         = plugin()->builder->get_visibility_field_defaults( $field_key, $this->get_default_settings() );
		$translation_keys = plugin()->builder->get_visibility_field_translation_keys();
		$field_label      = isset( $translation_keys[ $field_key ] ) ? $this->trans( $translation_keys[ $field_key ], $field_key ) : $field_key;
		$enabled          = [];

		foreach ( array_keys( $devices ) as $device_key ) {
			if ( ! empty( $defaults[ $device_key ] ) ) {
				$enabled[] = $device_key;
			}
		}

		$this->add_control(
			"visibility_{$group}_{$field_key}",
			[
				'label'     => $field_label,
				'type'      => 'gs_logo_visibility_devices',
				'devices'   => $devices,
				'default'   => $enabled,
				'classes'   => 'gs-logo-visibility-field',
				'separator' => 'none',
				'condition' => $condition,
			]
		);

	}

	protected function render() {

		$settings = $this->prepare_shortcode_settings();
		$instance_key = $this->get_instance_key();

		set_transient( $instance_key, $settings, DAY_IN_SECONDS );

		echo plugin()->shortcode->register_gslogo_shortcode_builder( [
			'id'       => $instance_key,
			'settings' => $settings,
		] );

	}

	/**
	 * Map Elementor control values back to the shortcode builder's storage
	 * conventions, then run the same validator the builder uses.
	 *
	 * @return array
	 */
	protected function prepare_shortcode_settings() {

		$widget_settings = $this->get_settings_for_display();
		$defaults        = $this->get_default_settings();
		$settings        = [];

		foreach ( $defaults as $setting_key => $default_value ) {

			if ( 'visibility_settings' === $setting_key ) {
				continue;
			}

			if ( ! array_key_exists( $setting_key, $widget_settings ) ) {
				$settings[ $setting_key ] = $default_value;
				continue;
			}

			$settings[ $setting_key ] = $widget_settings[ $setting_key ];
		}

		$settings['gs_l_slide_speed']  = $this->extract_slider_size( $widget_settings, 'gs_l_slide_speed', $defaults['gs_l_slide_speed'] );
		$settings['gs_l_autop_pause']  = $this->extract_slider_size( $widget_settings, 'gs_l_autop_pause', $defaults['gs_l_autop_pause'] );
		$settings['gs_l_rb_border']    = $this->compose_border_value( $widget_settings, $defaults['gs_l_rb_border'] );
		$settings['gs_l_rb_border_radius'] = $this->compose_dimensions_value( $widget_settings, 'gs_l_rb_border_radius', $defaults['gs_l_rb_border_radius'] );
		$settings['gs_l_rb_hover_shadow_control'] = $this->compose_shadow_value( $widget_settings, $defaults['gs_l_rb_hover_shadow_control'] );

		foreach ( $defaults as $setting_key => $default_value ) {

			if ( 'visibility_settings' === $setting_key || ! array_key_exists( $setting_key, $settings ) ) {
				continue;
			}

			if ( 'on' === $default_value || 'off' === $default_value ) {
				$settings[ $setting_key ] = $this->to_on_off( $settings[ $setting_key ] );
			}

			if ( is_array( $default_value ) ) {
				$settings[ $setting_key ] = $this->to_id_array( $settings[ $setting_key ] );
			}
		}

		$settings['visibility_settings'] = $this->build_visibility_settings( $widget_settings );
		$settings = $this->sanitize_premium_select_values( $settings );

		return plugin()->builder->validate_shortcode_settings( $settings );
	}

	/**
	 * Snap premium-only select values back to a free option so the preview
	 * never tries to render a Pro template on the free plugin.
	 *
	 * @param array $settings Mapped shortcode settings.
	 * @return array
	 */
	protected function sanitize_premium_select_values( $settings ) {

		if ( is_pro_active() ) {
			return $settings;
		}

		$select_keys = [
			'gs_l_theme',
			'pagination_type',
			'gs_logo_link_type',
			'popup_style',
			'panel_style',
			'image_filter',
			'hover_image_filter',
		];

		foreach ( $select_keys as $setting_key ) {
			if ( ! isset( $settings[ $setting_key ] ) ) {
				continue;
			}

			if ( ! $this->is_premium_option_value( $setting_key, $settings[ $setting_key ] ) ) {
				continue;
			}

			$settings[ $setting_key ] = $this->get_first_free_option_value(
				$setting_key,
				$this->setting_default( $setting_key )
			);
		}

		return $settings;
	}

	protected function is_premium_option_value( $options_key, $value ) {

		if ( null === $this->default_options ) {
			$this->default_options = plugin()->builder->get_shortcode_default_options();
		}

		if ( empty( $this->default_options[ $options_key ] ) || ! is_array( $this->default_options[ $options_key ] ) ) {
			return false;
		}

		foreach ( $this->default_options[ $options_key ] as $item ) {
			if ( ! isset( $item['value'] ) ) {
				continue;
			}

			if ( (string) $item['value'] !== (string) $value ) {
				continue;
			}

			return ! empty( $item['pro'] );
		}

		return false;
	}

	protected function get_first_free_option_value( $options_key, $fallback ) {

		if ( null === $this->default_options ) {
			$this->default_options = plugin()->builder->get_shortcode_default_options();
		}

		if ( empty( $this->default_options[ $options_key ] ) || ! is_array( $this->default_options[ $options_key ] ) ) {
			return $fallback;
		}

		foreach ( $this->default_options[ $options_key ] as $item ) {
			if ( isset( $item['value'] ) && empty( $item['pro'] ) ) {
				return $item['value'];
			}
		}

		return $fallback;
	}

	/**
	 * Rebuild the nested visibility_settings object from per-field checkbox rows.
	 *
	 * @param array $widget_settings Elementor settings.
	 * @return array
	 */
	protected function build_visibility_settings( $widget_settings ) {

		$devices = array_keys( $this->get_visibility_devices() );
		$result  = [
			'initial' => [],
			'popup'   => [],
			'panel'   => [],
		];

		$groups = [
			'initial' => $this->get_all_initial_visibility_fields(),
			'popup'   => plugin()->builder->get_overlay_visibility_fields(),
			'panel'   => plugin()->builder->get_overlay_visibility_fields(),
		];

		foreach ( $groups as $group => $field_keys ) {
			foreach ( $field_keys as $field_key ) {
				$enabled = $this->get_enabled_devices_from_widget( $widget_settings, $group, $field_key, $devices );
				$field   = [];
				foreach ( $devices as $device ) {
					$field[ $device ] = in_array( $device, $enabled, true );
				}
				$result[ $group ][ $field_key ] = $field;
			}
		}

		return $result;
	}

	/**
	 * Enabled device keys for one visibility field.
	 *
	 * Prefers the checkbox-row array; falls back to the older per-device switchers.
	 *
	 * @param array  $widget_settings Elementor settings.
	 * @param string $group           initial|popup|panel
	 * @param string $field_key       Visibility field key.
	 * @param array  $devices         Device keys.
	 * @return array
	 */
	protected function get_enabled_devices_from_widget( $widget_settings, $group, $field_key, $devices ) {

		$control_name = "visibility_{$group}_{$field_key}";
		$saved        = isset( $widget_settings[ $control_name ] ) ? $widget_settings[ $control_name ] : null;

		if ( is_array( $saved ) ) {
			$is_list = [] === $saved || array_keys( $saved ) === range( 0, count( $saved ) - 1 );

			if ( $is_list ) {
				return array_values( array_intersect( array_map( 'strval', $saved ), $devices ) );
			}

			$enabled = [];
			foreach ( $devices as $device ) {
				$value = isset( $saved[ $device ] ) ? $saved[ $device ] : null;
				if ( ! empty( $value ) && 'off' !== $value && false !== $value ) {
					$enabled[] = $device;
				}
			}
			return $enabled;
		}

		$enabled = [];
		foreach ( $devices as $device ) {
			$legacy = "visibility_{$group}_{$field_key}_{$device}";
			if ( isset( $widget_settings[ $legacy ] ) && 'on' === $widget_settings[ $legacy ] ) {
				$enabled[] = $device;
			}
		}

		return $enabled;
	}

	/**
	 * Stable, non-numeric key for this widget instance.
	 *
	 * @return string
	 */
	protected function get_instance_key() {

		$widget_id = $this->get_id();

		if ( empty( $widget_id ) ) {
			$widget_id = md5( maybe_serialize( $this->get_settings_for_display() ) );
		}

		return self::INSTANCE_PREFIX . sanitize_key( $widget_id );
	}

	public static function get_premium_notice() {
		return __( 'Available in the premium version.', 'gslogo' );
	}

	public static function get_premium_option_suffix() {
		return ' — ' . self::get_premium_notice();
	}

	protected function add_on_off_switcher( $name, $args = [] ) {

		$default = $this->setting_default( $name );

		$control = array_merge( [
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', 'gslogo' ),
			'label_off'    => __( 'Off', 'gslogo' ),
			'return_value' => 'on',
			'default'      => 'on' === $default ? 'on' : '',
		], $args );

		$this->add_control( $name, $control );
	}

	/**
	 * Append the premium notice to a control when Pro is not active.
	 *
	 * @param array $args Control arguments.
	 * @return array
	 */
	protected function premium_control_args( $args = [] ) {

		if ( is_pro_active() ) {
			return $args;
		}

		$notice = self::get_premium_notice();

		$args['description'] = ! empty( $args['description'] )
			? $args['description'] . '<br>' . $notice
			: $notice;

		$args['classes'] = ! empty( $args['classes'] )
			? $args['classes'] . ' gs-logo-elementor--premium'
			: 'gs-logo-elementor--premium';

		return $args;
	}

	/**
	 * Convert a builder option list (label/value) to Elementor's value=>label map.
	 *
	 * @param string $options_key Key in get_shortcode_default_options().
	 * @return array
	 */
	protected function get_select_options( $options_key ) {

		if ( null === $this->default_options ) {
			$this->default_options = plugin()->builder->get_shortcode_default_options();
		}

		$list = isset( $this->default_options[ $options_key ] ) && is_array( $this->default_options[ $options_key ] ) ? $this->default_options[ $options_key ] : [];
		$result      = [];
		$suffix      = self::get_premium_option_suffix();

		foreach ( $list as $item ) {
			if ( ! isset( $item['value'] ) ) {
				continue;
			}
			$label = isset( $item['label'] ) ? $item['label'] : $item['value'];
			if ( ! empty( $item['pro'] ) ) {
				$label .= $suffix;
			}
			$result[ (string) $item['value'] ] = $label;
		}

		return $result;
	}

	protected function trans( $key, $fallback = '' ) {

		if ( null === $this->translations ) {
			$this->translations = plugin()->builder->get_translation_srtings();
		}

		if ( isset( $this->translations[ $key ] ) ) {
			return $this->translations[ $key ];
		}

		return '' !== $fallback ? $fallback : $key;
	}

	protected function get_default_settings() {

		if ( null === $this->default_settings ) {
			$this->default_settings = plugin()->builder->get_shortcode_default_settings();
		}

		return $this->default_settings;
	}

	protected function setting_default( $key ) {

		$defaults = $this->get_default_settings();

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	protected function to_on_off( $value ) {
		return ( 'on' === $value || true === $value || 1 === $value || '1' === $value ) ? 'on' : 'off';
	}

	protected function to_id_array( $value ) {

		if ( ! is_array( $value ) ) {
			return empty( $value ) ? [] : [ absint( $value ) ];
		}

		return array_values( array_filter( array_map( 'absint', $value ) ) );
	}

	protected function extract_slider_size( $widget_settings, $key, $fallback ) {

		if ( empty( $widget_settings[ $key ] ) ) {
			return $fallback;
		}

		if ( is_array( $widget_settings[ $key ] ) && isset( $widget_settings[ $key ]['size'] ) ) {
			return $widget_settings[ $key ]['size'];
		}

		return $widget_settings[ $key ];
	}

	protected function compose_border_value( $widget_settings, $fallback ) {

		$parts = explode( ',', $fallback );

		$width = isset( $widget_settings['gs_l_rb_border_width'] ) && '' !== $widget_settings['gs_l_rb_border_width']
			? rtrim( $widget_settings['gs_l_rb_border_width'] ) . 'px'
			: ( isset( $parts[0] ) ? trim( $parts[0] ) : '1px' );

		$style = ! empty( $widget_settings['gs_l_rb_border_style'] )
			? $widget_settings['gs_l_rb_border_style']
			: ( isset( $parts[1] ) ? trim( $parts[1] ) : 'solid' );

		$color = ! empty( $widget_settings['gs_l_rb_border_color'] )
			? $widget_settings['gs_l_rb_border_color']
			: ( isset( $parts[2] ) ? trim( $parts[2] ) : '#000000' );

		return implode( ',', [ $width, $style, $color ] );
	}

	protected function compose_dimensions_value( $widget_settings, $key, $fallback ) {

		if ( empty( $widget_settings[ $key ] ) || ! is_array( $widget_settings[ $key ] ) ) {
			return $fallback;
		}

		$dimensions = $widget_settings[ $key ];

		return implode( ',', [
			isset( $dimensions['top'] ) ? (int) $dimensions['top'] : 0,
			isset( $dimensions['right'] ) ? (int) $dimensions['right'] : 0,
			isset( $dimensions['bottom'] ) ? (int) $dimensions['bottom'] : 0,
			isset( $dimensions['left'] ) ? (int) $dimensions['left'] : 0,
		] );
	}

	protected function compose_shadow_value( $widget_settings, $fallback ) {

		$parts = array_map( 'trim', explode( ',', $fallback ) );

		$x      = isset( $widget_settings['gs_l_rb_hover_shadow_x'] ) ? $widget_settings['gs_l_rb_hover_shadow_x'] : ( isset( $parts[0] ) ? $parts[0] : 6 );
		$y      = isset( $widget_settings['gs_l_rb_hover_shadow_y'] ) ? $widget_settings['gs_l_rb_hover_shadow_y'] : ( isset( $parts[1] ) ? $parts[1] : 6 );
		$blur   = isset( $widget_settings['gs_l_rb_hover_shadow_blur'] ) ? $widget_settings['gs_l_rb_hover_shadow_blur'] : ( isset( $parts[2] ) ? $parts[2] : 15 );
		$spread = isset( $widget_settings['gs_l_rb_hover_shadow_spread'] ) ? $widget_settings['gs_l_rb_hover_shadow_spread'] : ( isset( $parts[3] ) ? $parts[3] : 0 );

		return implode( ',', [ (int) $x, (int) $y, (int) $blur, (int) $spread ] );
	}

	protected function grid_themes() {
		return [ 'grid1', 'grid2', 'grid3', 'rounded-border' ];
	}

	protected function list_themes() {
		return [ 'list1', 'list2', 'list3', 'list4' ];
	}

	protected function table_themes() {
		return [ 'table1', 'table2', 'table3' ];
	}

	protected function list_ticker_themes() {
		return [ 'verticalticker', 'verticaltickerdown' ];
	}

	protected function ticker_themes() {
		return array_merge( [ 'ticker1' ], $this->list_ticker_themes() );
	}

	protected function vertical_carousel_themes() {
		return [ 'vslider1' ];
	}

	protected function horizontal_carousel_themes() {
		return [ 'slider1', 'slider_fullwidth', 'center', 'vwidth', 'verticalcenter', 'slider-2rows' ];
	}

	protected function carousel_themes() {
		return array_merge( $this->vertical_carousel_themes(), $this->horizontal_carousel_themes() );
	}

	protected function get_visibility_devices() {
		return [
			'desktop'          => $this->trans( 'visibility-desktop' ),
			'tablet'           => $this->trans( 'visibility-tablet' ),
			'mobile_landscape' => $this->trans( 'visibility-large-mobile' ),
			'mobile'           => $this->trans( 'visibility-mobile' ),
		];
	}

	protected function get_visibility_devices_header_html() {

		$html  = '<div class="gs-logo-visibility-devices gs-logo-visibility-devices--header">';
		$html .= '<span class="gs-logo-visibility-devices__label">' . esc_html( $this->trans( 'visibility-field' ) ) . '</span>';

		foreach ( $this->get_visibility_devices() as $label ) {
			$html .= '<span class="gs-logo-visibility-devices__col">' . esc_html( $label ) . '</span>';
		}

		$html .= '</div>';

		return $html;
	}

	protected function get_all_initial_visibility_fields() {

		$unique = [];

		foreach ( plugin()->builder->get_theme_visibility_fields() as $fields ) {
			foreach ( (array) $fields as $field_key ) {
				$unique[ $field_key ] = true;
			}
		}

		return array_keys( $unique );
	}

	protected function get_themes_for_visibility_field( $field_key ) {

		$themes = [];

		foreach ( plugin()->builder->get_theme_visibility_fields() as $theme => $fields ) {
			if ( in_array( $field_key, (array) $fields, true ) ) {
				$themes[] = $theme;
			}
		}

		return $themes;
	}

	protected function get_enabled_taxonomies() {

		$tax_settings = plugin()->builder->_get_taxonomy_settings( false );

		$taxonomies = [
			[ 'suffix' => 'category', 'enable_key' => 'enable_category_tax', 'label_key' => 'category_tax_label', 'options_key' => 'category' ],
			[ 'suffix' => 'tag', 'enable_key' => 'enable_tag_tax', 'label_key' => 'tag_tax_label', 'options_key' => 'tag' ],
			[ 'suffix' => 'extra_one', 'enable_key' => 'enable_extra_one_tax', 'label_key' => 'extra_one_tax_label', 'options_key' => 'extra_one' ],
			[ 'suffix' => 'extra_two', 'enable_key' => 'enable_extra_two_tax', 'label_key' => 'extra_two_tax_label', 'options_key' => 'extra_two' ],
			[ 'suffix' => 'extra_three', 'enable_key' => 'enable_extra_three_tax', 'label_key' => 'extra_three_tax_label', 'options_key' => 'extra_three' ],
			[ 'suffix' => 'extra_four', 'enable_key' => 'enable_extra_four_tax', 'label_key' => 'extra_four_tax_label', 'options_key' => 'extra_four' ],
			[ 'suffix' => 'extra_five', 'enable_key' => 'enable_extra_five_tax', 'label_key' => 'extra_five_tax_label', 'options_key' => 'extra_five' ],
		];

		$enabled = [];

		foreach ( $taxonomies as $taxonomy ) {
			if ( empty( $tax_settings[ $taxonomy['enable_key'] ] ) || 'on' !== $tax_settings[ $taxonomy['enable_key'] ] ) {
				continue;
			}

			$taxonomy['label'] = ! empty( $tax_settings[ $taxonomy['label_key'] ] ) ? $tax_settings[ $taxonomy['label_key'] ] : $taxonomy['suffix'];
			$enabled[]         = $taxonomy;
		}

		return $enabled;
	}

	/**
	 * Pagination settings only apply to grid/list themes, and are hidden when
	 * the non-AJAX filter is in charge of the query.
	 *
	 * @param array $pagination_themes Grid + list theme slugs.
	 * @return array
	 */
	protected function get_pagination_display_conditions( $pagination_themes ) {

		return [
			'relation' => 'and',
			'terms'    => [
				[
					'name'     => 'gs_l_theme',
					'operator' => 'in',
					'value'    => $pagination_themes,
				],
				[
					'relation' => 'or',
					'terms'    => [
						[
							'name'     => 'filter_enabled',
							'operator' => '!==',
							'value'    => 'on',
						],
						[
							'name'     => 'gs_logo_filter_type',
							'operator' => '!==',
							'value'    => 'normal-filter',
						],
					],
				],
			],
		];
	}

	protected function get_pagination_enabled_conditions( $pagination_themes ) {

		$conditions = $this->get_pagination_display_conditions( $pagination_themes );

		$conditions['terms'][] = [
			'name'     => 'gs_logo_pagination',
			'operator' => '===',
			'value'    => 'on',
		];

		return $conditions;
	}

	protected function get_pagination_type_conditions( $pagination_themes, $types ) {

		$conditions = $this->get_pagination_enabled_conditions( $pagination_themes );

		$conditions['terms'][] = [
			'name'     => 'pagination_type',
			'operator' => 'in',
			'value'    => $types,
		];

		return $conditions;
	}

	/**
	 * Show a control only while the matching visibility field is on for at
	 * least one device, matching the shortcode builder.
	 *
	 * @param string $group     initial|popup|panel
	 * @param string $field_key Visibility field key.
	 * @param array  $themes    Themes that support the field.
	 * @return array
	 */
	protected function get_visibility_field_on_conditions( $group, $field_key, $themes ) {
		return $this->get_visibility_field_on_condition_term( $group, $field_key, $themes );
	}

	protected function get_visibility_field_on_condition_term( $group, $field_key, $themes ) {

		$control_name = "visibility_{$group}_{$field_key}";
		$device_terms = [];

		foreach ( array_keys( $this->get_visibility_devices() ) as $device ) {
			$device_terms[] = [
				'name'     => $control_name,
				'operator' => 'contains',
				'value'    => $device,
			];
		}

		return [
			'relation' => 'and',
			'terms'    => [
				[
					'name'     => 'gs_l_theme',
					'operator' => 'in',
					'value'    => $themes,
				],
				[
					'relation' => 'or',
					'terms'    => $device_terms,
				],
			],
		];
	}

}
