<?php

namespace GSLOGO;
use function GSLOGOPRO\is_plugin_loaded;

if ( ! defined( 'ABSPATH' ) ) exit;

class Shortcode {
	
	public function __construct() {
		add_shortcode( 'gslogo', [ $this, 'register_gslogo_shortcode_builder' ] );

		// Ajax Filter
		add_action('wp_ajax_gslogo_filter_logos', [ $this, 'filter_logos' ]);
		add_action('wp_ajax_nopriv_gslogo_filter_logos', [ $this, 'filter_logos' ]);

		// Load More Button and Infinite Scroll
		add_action('wp_ajax_gslogo_load_more_logos', [ $this, 'load_more_logos' ]);
		add_action('wp_ajax_nopriv_gslogo_load_more_logos', [ $this, 'load_more_logos' ]);

		// Ajax Pagination
		add_action('wp_ajax_gslogo_ajax_pagination', [ $this, 'ajax_pagination' ]);
		add_action('wp_ajax_nopriv_gslogo_ajax_pagination', [ $this, 'ajax_pagination' ]);
	}

	public function filter_logos(){
		if( ! check_ajax_referer('gslogo_user_action') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

		$shortcode_id = $_POST['shortcode_id'];
		$is_preview = is_numeric($shortcode_id) ? false : true;
		
		$filters = $_POST['filters'];
		$posts_per_page = (int) $_POST['posts_per_page'];
		
		$logos = $this->register_gslogo_shortcode_builder( array( 'id'=> $shortcode_id, 'preview' => $is_preview ), array( 'filters' => $filters, 'posts_per_page' => $posts_per_page, 'paged' => '' ) );

		$found_logos = $GLOBALS['gs_logo_loop']->found_posts;
		
		$pagination = get_ajax_pagination( $shortcode_id, $posts_per_page, 1 );

		wp_send_json_success(array( 'logos' => $logos, 'pagination' => $pagination, 'foundLogos' => $found_logos ), 200 );
		wp_die();
	}

	public function load_more_logos(){
		if( ! check_ajax_referer('gslogo_user_action') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

		$shortcode_id = $_POST['shortcodeId'];
		$is_preview = is_numeric($shortcode_id) ? false : true;

		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();
		$load_per_action = $_POST['loadPerAction'];
		$offset = $_POST['offset'];
		
		$logos = $this->register_gslogo_shortcode_builder( array( 'id'=> $shortcode_id, 'preview' => $is_preview ), array( 'filters' => $filters, 'load_per_action' => $load_per_action, 'offset' => $offset ) );

		$found_logos = $GLOBALS['gs_logo_loop']->found_posts;

		wp_send_json_success(array( 'logos' => $logos, 'foundLogos' => $found_logos ), 200 );
		wp_die();
	}

	public function ajax_pagination(){
		if( ! check_ajax_referer('gslogo_user_action') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

		$shortcode_id = $_POST['shortcode_id'];
		$is_preview = is_numeric($shortcode_id) ? false : true;

		$posts_per_page = (int) $_POST['posts_per_page'];
		$paged = $_POST['paged'];

		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();
		
		$logos = $this->register_gslogo_shortcode_builder( array( 'id'=> $shortcode_id, 'preview' => $is_preview ), array( 'filters' => $filters, 'paged' => $paged, 'posts_per_page' => $posts_per_page ) );

		$found_logos = $GLOBALS['gs_logo_loop']->found_posts;

		$pagination = get_ajax_pagination( $shortcode_id, $posts_per_page, $paged );

		wp_send_json_success(array( 'logos' => $logos, 'pagination' => $pagination, 'foundLogos' => $found_logos ), 200 );
		wp_die();
	}

	public function register_gslogo_shortcode_builder( $atts, $ajax_datas = array() ) {

		if ( empty($atts['id']) ) {
			return __( 'No shortcode ID found', 'gslogo' );
		}

		$atts['id'] = sanitize_text_field( $atts['id'] );
	
		$is_preview = ! empty($atts['preview']);
	
		$settings = $this->get_shortcode_settings( $atts['id'], $is_preview );
	
		if ( empty($settings) ) return sprintf( '<p style="color:#cf7e16;background:#fff5e8;padding:10px;font-size:16px;border:1px solid #f1d7b5;border-radius:4px;line-height:1.6;">GS Logo Slider: The shortcode with the ID of <strong>%s</strong> was not found.</p>', esc_html( $atts['id'] ) );
	
		// Cache the $settings from being changed
		$_settings = $settings;
	
		// By default force mode
		$force_asset_load = true;
	
		if ( ! $is_preview ) {
		
			// For Asset Generator
			$main_post_id = gsLogoAssetGenerator()->get_current_page_id();
	
			$asset_data = gsLogoAssetGenerator()->get_assets_data( $main_post_id );
	
			if ( empty($asset_data) ) {
				// Saved assets not found
				// Force load the assets for first time load
				// Generate the assets for later use
				gsLogoAssetGenerator()->generate( $main_post_id, $settings );
			} else {
				// Saved assets found
				// Stop force loading the assets
				// Leave the job for Asset Loader
				$force_asset_load = false;
			}
	
		}
	
		if ( isset($settings['image_size']) && $settings['image_size'] == 'custom' ) {
	
			if ( empty( $settings['custom_image_size_width'] ) || empty( $settings['custom_image_size_width'] ) || empty( $settings['custom_image_size_crop'] ) ) {
				$settings['image_size'] = 'full';
			}
	
		}

		$atts = $settings;

		$atts = change_key( $atts, 'gs_l_title', 'title' );
		$atts = change_key( $atts, 'gs_l_mode', 'mode' );
		$atts = change_key( $atts, 'gs_l_slide_speed', 'speed' );
		$atts = change_key( $atts, 'gs_l_inf_loop', 'inf_loop' );
		$atts = change_key( $atts, 'gs_l_theme', 'theme' );
		$atts = change_key( $atts, 'gs_l_tooltip', 'tooltip' );
		
		extract( $atts );

		$now = current_time( 'mysql' );
	
		$args = [
			'order'				=> $order,
			'orderby'			=> $orderby,
			'posts_per_page'	=> $posts,
		];

		if( is_pro_active() && is_gs_logo_pro_valid() ){
			$args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => 'gs_logo_expire_at',
					'value'   => $now,
					'compare' => '>',
					'type'    => 'DATETIME',
				],
				[
					'key'     => 'gs_logo_expire_at',
					'compare' => 'NOT EXISTS', // fallback in case some posts don’t have expiry set
				],
			];
		}

		/* ========== Tax include ========== */

		$is_include_exist = !empty($include_category) || !empty($include_tag) || !empty($include_extra_one) || !empty($include_extra_two) || !empty($include_extra_three) || !empty($include_extra_four) || !empty($include_extra_five);
	
		if( $is_include_exist ){
			$args['tax_query'] = [ 'relation' => 'OR' ];
		}

		if ( !empty($include_category) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-category',
					'field'    => 'term_id',
					'terms'    => $include_category,
					'operator' => 'IN'
				],
			];
	
		}
	
		if ( !empty($include_tag) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-tag',
					'field'    => 'term_id',
					'terms'    => $include_tag,
					'operator' => 'IN'
				],
			];
	
		}
	
		if ( !empty($include_extra_one) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-one',
					'field'    => 'term_id',
					'terms'    => $include_extra_one,
					'operator' => 'IN'
				],
			];
	
		}
	
		if ( !empty($include_extra_two) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-two',
					'field'    => 'term_id',
					'terms'    => $include_extra_two,
					'operator' => 'IN'
				],
			];
	
		}
	
		if ( !empty($include_extra_three) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-three',
					'field'    => 'term_id',
					'terms'    => $include_extra_three,
					'operator' => 'IN'
				],
			];
	
		}
	
		if ( !empty($include_extra_four) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-four',
					'field'    => 'term_id',
					'terms'    => $include_extra_four,
					'operator' => 'IN'
				],
			];
	
		}
	
		if ( !empty($include_extra_five) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-five',
					'field'    => 'term_id',
					'terms'    => $include_extra_five,
					'operator' => 'IN'
				],
			];
	
		}


		/* ========== Tax exclude ========== */

		if ( !empty($exclude_category) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-category',
					'field'    => 'term_id',
					'terms'    => $exclude_category,
					'operator' => 'NOT IN'
				],
			];
	
		}
	
		if ( !empty($exclude_tag) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-tag',
					'field'    => 'term_id',
					'terms'    => $exclude_tag,
					'operator' => 'NOT IN'
				],
			];
	
		}
	
		if ( !empty($exclude_extra_one) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-one',
					'field'    => 'term_id',
					'terms'    => $exclude_extra_one,
					'operator' => 'NOT IN'
				],
			];
	
		}
	
		if ( !empty($exclude_extra_two) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-two',
					'field'    => 'term_id',
					'terms'    => $exclude_extra_two,
					'operator' => 'NOT IN'
				],
			];
	
		}
	
		if ( !empty($exclude_extra_three) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-three',
					'field'    => 'term_id',
					'terms'    => $exclude_extra_three,
					'operator' => 'NOT IN'
				],
			];
	
		}
	
		if ( !empty($exclude_extra_four) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-four',
					'field'    => 'term_id',
					'terms'    => $exclude_extra_four,
					'operator' => 'NOT IN'
				],
			];
	
		}
	
		if ( !empty($exclude_extra_five) ) {
	
			$args['tax_query'][] = [
				[
					'taxonomy' => 'logo-extra-five',
					'field'    => 'term_id',
					'terms'    => $exclude_extra_five,
					'operator' => 'NOT IN'
				],
			];
	
		}


		// Handle Pagination while Filter is off
		if ( 'off' === $filter_enabled ) {

			// Filter off & Pagination off
			if ( 'off' === $gs_logo_pagination ) {
				$args['posts_per_page'] = (int) $posts;

			// Filter off & Pagination on
			} elseif ( 'on' === $gs_logo_pagination ) {

				// AJAX Call
				if ( wp_doing_ajax() ) {

					if ( 'ajax-pagination' === $pagination_type ) {
						$args["paged"] = (int) $ajax_datas['paged'];
						$args['posts_per_page'] = (int) $ajax_datas['posts_per_page'];

					} elseif ( in_array( $pagination_type, ['load-more-button', 'load-more-scroll'], true ) ) {
						$args['posts_per_page'] = (int) $ajax_datas['load_per_action'];
						$args['offset'] = (int) $ajax_datas['offset'];
					}

				// Initial Page Load
				} else {

					if ( 'normal-pagination' === $pagination_type ) {
						$args['posts_per_page'] = (int) $logo_per_page;

						$shortcode_id = $id;
						$paged_var = 'paged' . $shortcode_id;
						$paged = max( 1, $_GET[$paged_var] ?? 1 );
						$args["paged"] = $paged;

					} elseif( 'ajax-pagination' === $pagination_type ){
						$args['posts_per_page'] = (int) $logo_per_page;
					} elseif ( in_array( $pagination_type, ['load-more-button', 'load-more-scroll'], true ) ) {
						$args['posts_per_page'] = (int) $initial_items;
					}
				}
			}
		}

		// FILTER ON
		elseif ( 'on' === $filter_enabled ) {

			// Filter On & Normal Filter
			if ( 'normal-filter' === $gs_logo_filter_type ) {
				$args['posts_per_page'] = (int) $posts;

			// Filter On & Ajax Filter
			} elseif ( 'ajax-filter' === $gs_logo_filter_type ) {

				// Filter on & Pagination off
				if ( 'off' === $gs_logo_pagination ) {

					// All filter btn off & initial load & grid theme (Retrieve from first category)
					if( $gs_l_all_filter === 'off' && empty($ajax_datas['filters']) && is_grid_theme( $theme ) ){

						$logo_first_category = get_terms( array(
							'taxonomy'   => 'logo-category',
							'hide_empty' => false,
							'fields'     => 'ids',
							'number'     => 1, // only first
						) );

						$logo_first_category_id = !empty( $logo_first_category ) ? $logo_first_category[0] : 0;

						$args['tax_query'] = [
							[
								'taxonomy' => 'logo-category',
								'field'    => 'term_id',
								'terms'    => $logo_first_category_id
							]
						];
					}

					$args['posts_per_page'] = (int) $posts;

				// Filter on & Pagination on
				} elseif ( 'on' === $gs_logo_pagination ) {

					// AJAX Call
					if ( wp_doing_ajax() ) {

						if ( 'ajax-pagination' === $pagination_type || 'normal-pagination' === $pagination_type ) {
							$args["paged"] = (int) $ajax_datas['paged'];
							$args['posts_per_page'] = (int) $ajax_datas['posts_per_page'];

						} elseif ( in_array( $pagination_type, ['load-more-button', 'load-more-scroll'], true ) && ! empty($ajax_datas['load_per_action']) ) {
							$args['posts_per_page'] = (int) $ajax_datas['load_per_action'];
							$args['offset'] = (int) $ajax_datas['offset'];
						}

					// Initial Load
					} else {
						if ( 'ajax-pagination' === $pagination_type || 'normal-pagination' === $pagination_type ) {
							$args['posts_per_page'] = (int) $logo_per_page;

						} elseif ( in_array( $pagination_type, ['load-more-button', 'load-more-scroll'], true ) ) {
							$args['posts_per_page'] = (int) $initial_items;
						}

						// All filter btn off & initial load & grid theme (Retrieve from first category)
						if( $gs_l_all_filter === 'off' && empty($ajax_datas['filters']) && is_grid_theme( $theme ) ){

							$logo_first_category = get_terms( array(
								'taxonomy'   => 'logo-category',
								'hide_empty' => false,
								'fields'     => 'ids',
								'number'     => 1, // only first
							) );

							$logo_first_category_id = !empty( $logo_first_category ) ? $logo_first_category[0] : 0;

							$args['tax_query'] = [
								[
									'taxonomy' => 'logo-category',
									'field'    => 'term_id',
									'terms'    => $logo_first_category_id
								]
							];
						}
					}
				}
			}
		}

		// Handle Filter Pagination connection on Filter Call
		if( ! empty($ajax_datas['filters']) ){

			if( wp_doing_ajax() ){

				if( 'on' === $gs_logo_pagination && empty($ajax_datas['load_per_action']) ){
					if ( in_array( $pagination_type, ['load-more-button', 'load-more-scroll'], true ) ) {
						$args['posts_per_page'] = (int) $initial_items;
					}
				}

				$filters = $ajax_datas['filters'];
								
				if( ! empty($filters['category']) && '' !== $filters['category'] ) {
					// Search through category
					$args['tax_query'][] = [
						'taxonomy' => 'logo-category',
						'field'    => 'slug',
						'terms'    => $filters['category']
					];
				}

			} else{
				if ( in_array( $pagination_type, ['load-more-button', 'load-more-scroll'], true ) ) {
					$args['posts_per_page'] = (int) $initial_items;
				}
			}

		}

		if( ! is_grid_theme( $theme ) && ! is_list_theme( $theme ) ){
			$args['posts_per_page'] = $posts;
		}

		if( ! is_pro_active() ){
			$args['posts_per_page'] = $posts;
		}
	
		$GLOBALS['gs_logo_loop'] = get_gs_logo_query( $args );
	
		$id = empty($id) ? uniqid() : sanitize_key( $id );
	
		if ( $theme == '2rows' ) $theme = 'slider-2rows';
		
		$classes = [
			"gs_logo_area",
			"gs_logo_area_$id",
			$theme
		];

		$img_effect_class = '';

		if ( is_pro_active() && is_gs_logo_pro_valid() ) {
			$img_effect_class = "gs-logo--img-efect_$image_filter gs-logo--img-hover-efect_$hover_image_filter";
		}

		if( 'ajax-pagination' === $pagination_type || 'normal-pagination' === $pagination_type ){
			$data_options['logo_per_page'] = $logo_per_page;
		} elseif( 'load-more-button' === $pagination_type ){
			$data_options['load_per_click'] = $load_per_click;
			$data_options['initial_items'] = $initial_items;
		} elseif( 'load-more-scroll' === $pagination_type ){
			$data_options['per_load'] = $per_load;
			$data_options['initial_items'] = $initial_items;
		}

		$sort_mode = apply_filters( 'gs_logo_isotope_sort_mode', 'name', $id ); // name | original-order
	
		ob_start();

		?>
	
		<div id="<?php echo 'gs_logo_area_' . esc_attr( $id ); ?>" data-sort="<?php echo esc_attr($sort_mode); ?>" data-shortcode-id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?> <?php echo esc_attr($img_effect_class); ?>" data-options='<?php echo json_encode($data_options); ?>' style="opacity: 0; visibility: hidden;">
			<div class="gs_logo_area--inner">

				<!-- Category Filters - New (Global) -->
				<?php
					if( is_grid_theme( $theme ) && is_pro_active() ){
						include Template_Loader::locate_template( 'partials/gs-logo-cat-filters-2.php' );
					}
				?>
	
				<?php
					do_action( 'gs_logo_template_before__loaded', $theme );
	
					if ( $theme == 'slider1' ) {
						include Template_Loader::locate_template( 'gs-logo-theme-slider-1.php' );
					} else if ( $theme == 'grid1' ) {
						include Template_Loader::locate_template( 'gs-logo-theme-grid-1.php' );
					} else if ( $theme == 'list1' ) {
						include Template_Loader::locate_template( 'gs-logo-theme-list-1.php' );
					} else if ( $theme == 'table1' ) {
						include Template_Loader::locate_template( 'gs-logo-theme-table-1.php' );
					} else if ( ! is_pro_active() || ! is_plugin_loaded() ) {
						printf('<div class="gs-logo-template-upgrade"><p>%s</p></div>', __('Please upgrade to pro version to use this template', 'gslogo'));
					}
	
					do_action( 'gs_logo_template_after__loaded', $theme,  $atts );
					
					wp_reset_postdata();
				?>

				<!-- Pagination -->
				<?php
					if( ( is_grid_theme( $theme ) || is_list_theme( $theme ) ) && is_pro_active() ){
						include Template_Loader::locate_template( 'partials/gs-logo-layout-pagination.php' );
					}
				?>

			</div>
		</div>
	
		<?php
	
		if ( plugin()->integrations->is_builder_preview() || $force_asset_load ) {
	
			gsLogoAssetGenerator()->force_enqueue_assets( $_settings );
			wp_add_inline_script( 'gs-logo-public', "jQuery(document).trigger( 'gslogo:scripts:reprocess' );jQuery(function() { jQuery(document).trigger( 'gslogo:scripts:reprocess' ) })" );

			// Shortcode Custom CSS
			$css = gsLogoAssetGenerator()->get_shortcode_custom_css( $settings );
			if ( !empty($css) ) printf( "<style>%s</style>" , minimize_css_simple($css) );
			
			// Prefs Custom CSS
			$css = gsLogoAssetGenerator()->get_prefs_custom_css();
			if ( !empty($css) ) printf( "<style>%s</style>" , minimize_css_simple($css) );
	
		}
	
		$settings = null; // Free up the memory
	
		return ob_get_clean();
	
	}

	public function get_shortcode_settings($id, $is_preview = false) {

		$default_settings = array_merge( ['id' => $id, 'is_preview' => $is_preview], plugin()->builder->get_shortcode_default_settings() );
	
		if ( $is_preview ) {
			$preview_settings = plugin()->builder->validate_shortcode_settings( get_transient($id) );
			return shortcode_atts( $default_settings, $preview_settings );
		}
	
		$shortcode = plugin()->builder->_get_shortcode($id);

		if ( empty($shortcode) ) return false;

		return shortcode_atts( $default_settings, (array) $shortcode['shortcode_settings'] );
		
	}
}
