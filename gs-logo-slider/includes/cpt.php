<?php 
namespace GSLOGO;

if ( ! defined( 'ABSPATH' ) ) exit;

class Cpt {

	public function __construct() {
		add_action( 'init', [ $this, 'GS_Logo_Slider' ] );
		add_action( 'init', [ $this, 'register_taxonomies' ], 0 );
		add_action( 'after_setup_theme', [ $this, 'gs_logo_theme_support' ] );
	}

	function GS_Logo_Slider() {

		$labels = array(
			'name'               	=> _x( 'GS Logos', 'gslogo' ),
			'singular_name'      	=> _x( 'GS Logo', 'gslogo' ),
			'menu_name'          	=> _x( 'GS Logos', 'admin menu', 'gslogo' ),
			'name_admin_bar'     	=> _x( 'GS Logo Slider', 'add new on admin bar', 'gslogo' ),
			'add_new'            	=> _x( 'Add New Logo', 'logo', 'gslogo' ),
			'add_new_item'       	=> __( 'Add New Logo', 'gslogo' ),
			'new_item'           	=> __( 'New Logo', 'gslogo' ),
			'edit_item'          	=> __( 'Edit Logo', 'gslogo' ),
			'view_item'          	=> __( 'View Logo', 'gslogo' ),
			'all_items'          	=> __( 'All Logos', 'gslogo' ),
			'search_items'       	=> __( 'Search Logos', 'gslogo' ),
			'parent_item_colon'  	=> __( 'Parent Logos:', 'gslogo' ),
			'not_found'          	=> __( 'No logos found.', 'gslogo' ),
			'not_found_in_trash' 	=> __( 'No logos found in Trash.', 'gslogo' ),
			'featured_image'     	=> __( 'Add Logo', 'gslogo' ),
			'set_featured_image'    => __( 'Add New Logo', 'gslogo' ),
			'remove_featured_image' => __( 'Remove This Logo', 'gslogo' ),
			'use_featured_image'    => __( 'Use This Logo', 'gslogo' ),
		);
	
		$args = array(
			'labels'             	=> $labels,
			'show_ui'            	=> true,
			'exclude_from_search' 	=> true,
			'public'            	=> true,
			'publicly_queryable' 	=> false,
			'rewrite'            	=> false,
			'has_archive'       	=> false,
			'hierarchical'       	=> false,
			'show_in_rest'       	=> true,
			'menu_position'      	=> GSL_MENU_POSITION,
			'capability_type'    	=> 'post',
			'menu_icon'          	=> GSL_PLUGIN_URI . 'assets/img/icon.svg',
			'supports'           	=> array( 'title', 'editor', 'thumbnail', 'excerpt')
		);

		if ( plugin()->builder->get( 'enable_single_page' ) == 'on' ) {
			$args['publicly_queryable'] = true;
			$args['rewrite'] = [ 'slug' => 'gs-logo-slider' ];
		}
	
		register_post_type( 'gs-logo-slider', $args );
	}

	public function register_taxonomies() {
		$this->category();
		$this->tag();

		if( is_gs_logo_pro_valid() ){
			$this->extra_one();
			$this->extra_two();
			$this->extra_three();
			$this->extra_four();
			$this->extra_five();
		}
	}
	
	function category() {

		if ( plugin()->builder->get_tax_option('enable_category_tax') !== 'on' ) return;
		
		$plural = plugin()->builder->get_tax_option('category_tax_plural_label');
		$singular = plugin()->builder->get_tax_option('category_tax_label');
	
		if( ! taxonomy_exists( 'logo-category' ) ) {

			$labels = array(
				'name'                       => $plural,
				'singular_name'              => $singular,
				'all_items'                  => sprintf( __('All %s'), $plural ),
				'parent_item'                => sprintf( __('Parent %s'), $singular ),
				'parent_item_colon'          => sprintf( __('Parent %s'), $singular ),
				'new_item_name'              => sprintf( __('New %s'), $singular ),
				'add_new_item'               => sprintf( __('Add New %s'), $singular ),
				'edit_item'                  => sprintf( __('Edit %s'), $singular ),
				'update_item'                => sprintf( __('Update %s'), $singular ),
				'separate_items_with_commas' => sprintf( __('Separate %s with commas'), $plural ),
				'search_items'               => sprintf( __('Search %s'), $plural ),
				'add_or_remove_items'        => sprintf( __('Add or remove %s'), $plural ),
				'choose_from_most_used'      => sprintf( __('Choose from the most used %s'), $plural ),
				'not_found'                  => __( 'Not Found', 'gslogo' ),
			);

			$rewrite = array(
				'slug'                       => plugin()->builder->get_tax_option('category_tax_archive_slug', 'logo-category'),
				'with_front'                 => true,
				'hierarchical'               => false,
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => false,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_tagcloud'              => false,
			    'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'logo-category', array( 'gs-logo-slider' ), $args );
		}
	
	}

	public function tag() {

		if ( plugin()->builder->get_tax_option('enable_tag_tax') !== 'on' ) return;
		
		$plural = plugin()->builder->get_tax_option('tag_tax_plural_label');
		$singular = plugin()->builder->get_tax_option('tag_tax_label');
	
		if( ! taxonomy_exists( 'logo-tag' ) ) {

			$labels = array(
				'name'                       => $plural,
				'singular_name'              => $singular,
				'all_items'                  => sprintf( __('All %s'), $plural ),
				'parent_item'                => sprintf( __('Parent %s'), $singular ),
				'parent_item_colon'          => sprintf( __('Parent %s'), $singular ),
				'new_item_name'              => sprintf( __('New %s'), $singular ),
				'add_new_item'               => sprintf( __('Add New %s'), $singular ),
				'edit_item'                  => sprintf( __('Edit %s'), $singular ),
				'update_item'                => sprintf( __('Update %s'), $singular ),
				'separate_items_with_commas' => sprintf( __('Separate %s with commas'), $plural ),
				'search_items'               => sprintf( __('Search %s'), $plural ),
				'add_or_remove_items'        => sprintf( __('Add or remove %s'), $plural ),
				'choose_from_most_used'      => sprintf( __('Choose from the most used %s'), $plural ),
				'not_found'                  => __( 'Not Found', 'gslogo' ),
			);

			$rewrite = array(
				'slug'                       => plugin()->builder->get_tax_option('tag_tax_archive_slug', 'logo-tag'),
				'with_front'                 => true,
				'hierarchical'               => false,
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => false,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_tagcloud'              => false,
			    'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'logo-tag', array( 'gs-logo-slider' ), $args );
		}
	}

	public function extra_one() {

		if ( plugin()->builder->get_tax_option('enable_extra_one_tax') !== 'on' ) return;
		
		$plural = plugin()->builder->get_tax_option('extra_one_tax_plural_label');
		$singular = plugin()->builder->get_tax_option('extra_one_tax_label');
	
		if( ! taxonomy_exists( 'logo-extra-one' ) ) {

			$labels = array(
				'name'                       => $plural,
				'singular_name'              => $singular,
				'all_items'                  => sprintf( __('All %s'), $plural ),
				'parent_item'                => sprintf( __('Parent %s'), $singular ),
				'parent_item_colon'          => sprintf( __('Parent %s'), $singular ),
				'new_item_name'              => sprintf( __('New %s'), $singular ),
				'add_new_item'               => sprintf( __('Add New %s'), $singular ),
				'edit_item'                  => sprintf( __('Edit %s'), $singular ),
				'update_item'                => sprintf( __('Update %s'), $singular ),
				'separate_items_with_commas' => sprintf( __('Separate %s with commas'), $plural ),
				'search_items'               => sprintf( __('Search %s'), $plural ),
				'add_or_remove_items'        => sprintf( __('Add or remove %s'), $plural ),
				'choose_from_most_used'      => sprintf( __('Choose from the most used %s'), $plural ),
				'not_found'                  => __( 'Not Found', 'gslogo' ),
			);

			$rewrite = array(
				'slug'                       => plugin()->builder->get_tax_option('extra_one_tax_archive_slug', 'logo-extra-one'),
				'with_front'                 => true,
				'hierarchical'               => false,
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => false,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_tagcloud'              => false,
			    'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'logo-extra-one', array( 'gs-logo-slider' ), $args );
		}
	}

	public function extra_two() {

		if ( plugin()->builder->get_tax_option('enable_extra_two_tax') !== 'on' ) return;
		
		$plural = plugin()->builder->get_tax_option('extra_two_tax_plural_label');
		$singular = plugin()->builder->get_tax_option('extra_two_tax_label');
	
		if( ! taxonomy_exists( 'logo-extra-two' ) ) {

			$labels = array(
				'name'                       => $plural,
				'singular_name'              => $singular,
				'all_items'                  => sprintf( __('All %s'), $plural ),
				'parent_item'                => sprintf( __('Parent %s'), $singular ),
				'parent_item_colon'          => sprintf( __('Parent %s'), $singular ),
				'new_item_name'              => sprintf( __('New %s'), $singular ),
				'add_new_item'               => sprintf( __('Add New %s'), $singular ),
				'edit_item'                  => sprintf( __('Edit %s'), $singular ),
				'update_item'                => sprintf( __('Update %s'), $singular ),
				'separate_items_with_commas' => sprintf( __('Separate %s with commas'), $plural ),
				'search_items'               => sprintf( __('Search %s'), $plural ),
				'add_or_remove_items'        => sprintf( __('Add or remove %s'), $plural ),
				'choose_from_most_used'      => sprintf( __('Choose from the most used %s'), $plural ),
				'not_found'                  => __( 'Not Found', 'gslogo' ),
			);

			$rewrite = array(
				'slug'                       => plugin()->builder->get_tax_option('extra_two_tax_archive_slug', 'logo-extra-two'),
				'with_front'                 => true,
				'hierarchical'               => false,
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => false,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_tagcloud'              => false,
			    'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'logo-extra-two', array( 'gs-logo-slider' ), $args );
		}
	}

	public function extra_three() {

		if ( plugin()->builder->get_tax_option('enable_extra_three_tax') !== 'on' ) return;
		
		$plural = plugin()->builder->get_tax_option('extra_three_tax_plural_label');
		$singular = plugin()->builder->get_tax_option('extra_three_tax_label');
	
		if( ! taxonomy_exists( 'logo-extra-three' ) ) {

			$labels = array(
				'name'                       => $plural,
				'singular_name'              => $singular,
				'all_items'                  => sprintf( __('All %s'), $plural ),
				'parent_item'                => sprintf( __('Parent %s'), $singular ),
				'parent_item_colon'          => sprintf( __('Parent %s'), $singular ),
				'new_item_name'              => sprintf( __('New %s'), $singular ),
				'add_new_item'               => sprintf( __('Add New %s'), $singular ),
				'edit_item'                  => sprintf( __('Edit %s'), $singular ),
				'update_item'                => sprintf( __('Update %s'), $singular ),
				'separate_items_with_commas' => sprintf( __('Separate %s with commas'), $plural ),
				'search_items'               => sprintf( __('Search %s'), $plural ),
				'add_or_remove_items'        => sprintf( __('Add or remove %s'), $plural ),
				'choose_from_most_used'      => sprintf( __('Choose from the most used %s'), $plural ),
				'not_found'                  => __( 'Not Found', 'gslogo' ),
			);

			$rewrite = array(
				'slug'                       => plugin()->builder->get_tax_option('extra_three_tax_archive_slug', 'logo-extra-three'),
				'with_front'                 => true,
				'hierarchical'               => false,
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => false,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_tagcloud'              => false,
			    'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'logo-extra-three', array( 'gs-logo-slider' ), $args );
		}
	}

	public function extra_four() {

		if ( plugin()->builder->get_tax_option('enable_extra_four_tax') !== 'on' ) return;
		
		$plural = plugin()->builder->get_tax_option('extra_four_tax_plural_label');
		$singular = plugin()->builder->get_tax_option('extra_four_tax_label');
	
		if( ! taxonomy_exists( 'logo-extra-four' ) ) {

			$labels = array(
				'name'                       => $plural,
				'singular_name'              => $singular,
				'all_items'                  => sprintf( __('All %s'), $plural ),
				'parent_item'                => sprintf( __('Parent %s'), $singular ),
				'parent_item_colon'          => sprintf( __('Parent %s'), $singular ),
				'new_item_name'              => sprintf( __('New %s'), $singular ),
				'add_new_item'               => sprintf( __('Add New %s'), $singular ),
				'edit_item'                  => sprintf( __('Edit %s'), $singular ),
				'update_item'                => sprintf( __('Update %s'), $singular ),
				'separate_items_with_commas' => sprintf( __('Separate %s with commas'), $plural ),
				'search_items'               => sprintf( __('Search %s'), $plural ),
				'add_or_remove_items'        => sprintf( __('Add or remove %s'), $plural ),
				'choose_from_most_used'      => sprintf( __('Choose from the most used %s'), $plural ),
				'not_found'                  => __( 'Not Found', 'gslogo' ),
			);

			$rewrite = array(
				'slug'                       => plugin()->builder->get_tax_option('extra_four_tax_archive_slug', 'logo-extra-four'),
				'with_front'                 => true,
				'hierarchical'               => false,
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => false,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_tagcloud'              => false,
			    'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'logo-extra-four', array( 'gs-logo-slider' ), $args );
		}
	}

	public function extra_five() {

		if ( plugin()->builder->get_tax_option('enable_extra_five_tax') !== 'on' ) return;
		
		$plural = plugin()->builder->get_tax_option('extra_five_tax_plural_label');
		$singular = plugin()->builder->get_tax_option('extra_five_tax_label');
	
		if( ! taxonomy_exists( 'logo-extra-five' ) ) {

			$labels = array(
				'name'                       => $plural,
				'singular_name'              => $singular,
				'all_items'                  => sprintf( __('All %s'), $plural ),
				'parent_item'                => sprintf( __('Parent %s'), $singular ),
				'parent_item_colon'          => sprintf( __('Parent %s'), $singular ),
				'new_item_name'              => sprintf( __('New %s'), $singular ),
				'add_new_item'               => sprintf( __('Add New %s'), $singular ),
				'edit_item'                  => sprintf( __('Edit %s'), $singular ),
				'update_item'                => sprintf( __('Update %s'), $singular ),
				'separate_items_with_commas' => sprintf( __('Separate %s with commas'), $plural ),
				'search_items'               => sprintf( __('Search %s'), $plural ),
				'add_or_remove_items'        => sprintf( __('Add or remove %s'), $plural ),
				'choose_from_most_used'      => sprintf( __('Choose from the most used %s'), $plural ),
				'not_found'                  => __( 'Not Found', 'gslogo' ),
			);

			$rewrite = array(
				'slug'                       => plugin()->builder->get_tax_option('extra_five_tax_archive_slug', 'logo-extra-five'),
				'with_front'                 => true,
				'hierarchical'               => false,
			);

			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => false,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_tagcloud'              => false,
			    'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'logo-extra-five', array( 'gs-logo-slider' ), $args );
		}
	}
	
	function gs_logo_theme_support()  {
		// Add theme support for Featured Images
		add_theme_support( 'post-thumbnails', array( 'gs-logo-slider' ) );
		add_theme_support( 'post-thumbnails', array( 'post' ) ); // Add it for posts
		add_theme_support( 'post-thumbnails', array( 'page' ) ); // Add it for pages
		add_theme_support( 'post-thumbnails', array( 'product' ) ); // Add it for products
		add_theme_support( 'post-thumbnails');
		// Add Shortcode support in text widget
		add_filter('widget_text', 'do_shortcode'); 
	}

}
