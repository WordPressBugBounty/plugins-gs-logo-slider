<?php
namespace GSLOGO;
use function GSLOGOPRO\is_plugin_loaded;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class Builder {

    private $option_name = 'gs_logo_slider_shortcode_prefs';
    private $taxonomy_option_name = 'gs_logo_slider_taxonomy_settings';

    public function __construct() {
        
        add_action( 'admin_menu', array( $this, 'register_sub_menu') );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts') );
        add_action( 'wp_enqueue_scripts', array( $this, 'preview_scripts') );

        add_action( 'wp_ajax_gslogo_create_shortcode', array($this, 'create_shortcode') );
        add_action( 'wp_ajax_gslogo_clone_shortcode', array($this, 'clone_shortcode') );
        add_action( 'wp_ajax_gslogo_get_shortcode', array($this, 'get_shortcode') );
        add_action( 'wp_ajax_gslogo_update_shortcode', array($this, 'update_shortcode') );
        add_action( 'wp_ajax_gslogo_delete_shortcodes', array($this, 'delete_shortcodes') );
        add_action( 'wp_ajax_gslogo_temp_save_shortcode_settings', array($this, 'temp_save_shortcode_settings') );
        add_action( 'wp_ajax_gslogo_get_shortcodes', array($this, 'get_shortcodes') );

        add_action( 'wp_ajax_gslogo_get_shortcode_pref', array($this, 'get_shortcode_pref') );
        add_action( 'wp_ajax_gslogo_save_shortcode_pref', array($this, 'save_shortcode_pref') );

        add_action( 'wp_ajax_gslogo_get_taxonomy_settings', array($this, 'get_taxonomy_settings') );
        add_action( 'wp_ajax_gslogo_save_taxonomy_settings', array($this, 'save_taxonomy_settings') );

        add_action( 'template_include', array($this, 'populate_shortcode_preview') );
        add_action( 'show_admin_bar', array($this, 'hide_admin_bar_from_preview') );

        return $this;
    }

    public static function is_gslogo_shortcode_preview() {
        return isset( $_REQUEST['gslogo_shortcode_preview'] ) && !empty($_REQUEST['gslogo_shortcode_preview']);
    }

    public function hide_admin_bar_from_preview( $visibility ) {
        if ( $this->is_gslogo_shortcode_preview() ) return false;
        return $visibility;
    }

    public function add_shortcode_body_class( $classes ) {
        if ( $this->is_gslogo_shortcode_preview() ) return array_merge( $classes, array( 'gslogo-shortcode-preview--page' ) );
        return $classes;
    }

    public function populate_shortcode_preview( $template ) {

        global $wp, $wp_query;
        
        if ( $this->is_gslogo_shortcode_preview() ) {

            // Create our fake post
            $post_id = 0;
            $post = new \stdClass();
            $post->ID = $post_id;
            $post->post_author = 1;
            $post->post_date = current_time( 'mysql' );
            $post->post_date_gmt = current_time( 'mysql', 1 );
            $post->post_title = __('Shortcode Preview', 'gslogo');
            $post->post_content = '[gslogo preview="yes" id="'. esc_attr( sanitize_key( $_REQUEST['gslogo_shortcode_preview'] ) ) .'"]';
            $post->post_status = 'publish';
            $post->comment_status = 'closed';
            $post->ping_status = 'closed';
            $post->post_name = 'fake-page-' . rand( 1, 99999 ); // append random number to avoid clash
            $post->post_type = 'page';
            $post->filter = 'raw'; // important!

            // Convert to WP_Post object
            $wp_post = new \WP_Post( $post );

            // Add the fake post to the cache
            wp_cache_add( $post_id, $wp_post, 'posts' );

            // Update the main query
            $wp_query->post = $wp_post;
            $wp_query->posts = array( $wp_post );
            $wp_query->queried_object = $wp_post;
            $wp_query->queried_object_id = $post_id;
            $wp_query->found_posts = 1;
            $wp_query->post_count = 1;
            $wp_query->max_num_pages = 1; 
            $wp_query->is_page = true;
            $wp_query->is_singular = true; 
            $wp_query->is_single = false; 
            $wp_query->is_attachment = false;
            $wp_query->is_archive = false; 
            $wp_query->is_category = false;
            $wp_query->is_tag = false; 
            $wp_query->is_tax = false;
            $wp_query->is_author = false;
            $wp_query->is_date = false;
            $wp_query->is_year = false;
            $wp_query->is_month = false;
            $wp_query->is_day = false;
            $wp_query->is_time = false;
            $wp_query->is_search = false;
            $wp_query->is_feed = false;
            $wp_query->is_comment_feed = false;
            $wp_query->is_trackback = false;
            $wp_query->is_home = false;
            $wp_query->is_embed = false;
            $wp_query->is_404 = false; 
            $wp_query->is_paged = false;
            $wp_query->is_admin = false; 
            $wp_query->is_preview = false; 
            $wp_query->is_robots = false; 
            $wp_query->is_posts_page = false;
            $wp_query->is_post_type_archive = false;


            // Update globals
            $GLOBALS['wp_query'] = $wp_query;
            $wp->register_globals();


            include GSL_PLUGIN_DIR . 'includes/shortcode-builder/preview.php';

            return;

        }

        return $template;

    }

    public function register_sub_menu() {

        add_submenu_page( 
            'edit.php?post_type=gs-logo-slider', 'Logo Shortcode', 'Logo Shortcode', 'manage_options', 'gs-logo-shortcode', array( $this, 'view' )
        );

        add_submenu_page( 
            'edit.php?post_type=gs-logo-slider', 'Install Demo', 'Install Demo', 'manage_options', 'gs-logo-shortcode#/demo-data', array( $this, 'view' )
        );

        do_action( 'gs_logo_register_sub_menu' );

    }

    public function view() {
        include_once GSL_PLUGIN_DIR . 'includes/shortcode-builder/page.php';
    }

    public function get_logo_categories() {

        if ( $this->get_tax_option('enable_category_tax') !== 'on' ) return [];

        $_terms = get_terms( 'logo-category' );

        $terms = [];

        foreach ( $_terms as $term ) {
            $terms[] = [
                'label' => $term->name,
                'value' => $term->slug
            ];
        }

        return $terms;
    }

    public function scripts( $hook ) {

        global $parent_file;

        if ( 'edit.php?post_type=gs-logo-slider' != $parent_file ) return;

        wp_register_style( 'gs-zmdi-fonts', GSL_PLUGIN_URI . 'assets/libs/material-design-iconic-font/css/material-design-iconic-font.min.css', '', GSL_VERSION, 'all' );

        if ( ! is_pro_active() || ! is_plugin_loaded() ) {
            wp_register_style( 'gs-logo-shortcode', GSL_PLUGIN_URI . 'assets/admin/css/gs-logo-shortcode.min.css', array('gs-zmdi-fonts'), GSL_VERSION, 'all' );
            wp_register_script( 'gs-logo-shortcode', GSL_PLUGIN_URI . 'assets/admin/js/gs-logo-shortcode.min.js', array('jquery'), GSL_VERSION, true );
        }

        do_action( 'gs_logo_register_scripts' );
        
        if( $hook === 'gs-logo-slider_page_gs-logo-shortcode' ){
            wp_enqueue_style( 'gs-logo-shortcode' );
            wp_enqueue_script( 'gs-logo-shortcode' );
        }
        
        wp_localize_script( 'gs-logo-shortcode', '_gslogo_data', $this->get_localized_data() );
        
    }

    public function get_localized_data() {

        $data = array(
            "nonce" => array(
                "create_shortcode" 		        => wp_create_nonce( "_gslogo_create_shortcode_gs_" ),
                "clone_shortcode" 		        => wp_create_nonce( "_gslogo_clone_shortcode_gs_" ),
                "update_shortcode" 	            => wp_create_nonce( "_gslogo_update_shortcode_gs_" ),
                "delete_shortcodes" 	        => wp_create_nonce( "_gslogo_delete_shortcodes_gs_" ),
                "temp_save_shortcode_settings" 	=> wp_create_nonce( "_gslogo_temp_save_shortcode_settings_gs_" ),
                "save_shortcode_pref" 	        => wp_create_nonce( "_gslogo_save_shortcode_pref_gs_" ),
                "save_taxonomy_settings" 	    => wp_create_nonce( "_gslogo_save_taxonomy_settings_gs_" ),
                "import_gslogo_demo" 	        => wp_create_nonce( "_gslogo_simport_gslogo_demo_gs_" ),
                "import_export"      	        => wp_create_nonce( "_gslogo_import_export_nonce_gs_" )
            ),
            "ajaxurl" => admin_url( "admin-ajax.php" ),
            "adminurl" => admin_url(),
            "siteurl" => home_url()
        );

        $data['shortcode_settings'] = $this->get_shortcode_default_settings();
        $data['shortcode_options']  = $this->get_shortcode_default_options();
        $data['translations']       = $this->get_translation_srtings();
        $data['preference']         = $this->get_shortcode_default_prefs();
        $data['preference_options'] = $this->get_shortcode_prefs_options();
        $data['taxonomy_default_settings']  = $this->get_taxonomy_default_settings();
        $data['taxonomy_settings']  = $this->get_taxonomy_settings();

        $data['demo_data'] = [
            'logo_data'      => wp_validate_boolean( get_option('gslogo_dummy_logo_data_created') ),
            'shortcode_data' => wp_validate_boolean( get_option('gslogo_dummy_shortcode_data_created') )
        ];

        $data['is_pro_active'] = wp_validate_boolean( is_pro_active() );

        return $data;
    }

    public function preview_scripts() {
        
        if ( ! $this->is_gslogo_shortcode_preview() ) return;

        wp_enqueue_style( 'gs-logo-shortcode-preview', GSL_PLUGIN_URI . 'assets/css/gs-logo-shortcode-preview.min.css', '', GSL_VERSION, 'all' );
        
    }

    public function get_wpdb() {

        global $wpdb;
        
        if ( wp_doing_ajax() ) $wpdb->show_errors = false;

        return $wpdb;

    }

    public function check_db_error() {

        $wpdb = $this->get_wpdb();

        if ( $wpdb->last_error === '') return false;

        return true;

    }

    public function validate_shortcode_settings( $shortcode_settings ) {
        $shortcode_settings = shortcode_atts( $this->get_shortcode_default_settings(), $shortcode_settings );

        $shortcode_settings['posts']              = intval( $shortcode_settings['posts'] );

        foreach ( $shortcode_settings as $key => $value ) {

            // If array → sanitize IDs
            if ( is_array( $value ) ) {
                $shortcode_settings[$key] = array_map( 'absint', $value );
                continue;
            }

            // Everything else is a string → sanitize as text
            $shortcode_settings[$key] = sanitize_text_field( $value );
        }
        
        return $shortcode_settings;
    }

    protected function get_shortcode_db_columns() {

        return array(
            'shortcode_name' => '%s',
            'shortcode_settings' => '%s',
            'created_at' => '%s',
            'updated_at' => '%s',
        );

    }

    public function _get_shortcode( $shortcode_id, $is_ajax = false ) {

        if ( empty($shortcode_id) ) {
            if ( $is_ajax ) wp_send_json_error( __('Shortcode ID missing', 'gslogo'), 400 );
            return false;
        }

        $wpdb = $this->get_wpdb();

        $shortcode = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gs_logo_slider WHERE id = %d LIMIT 1", absint($shortcode_id) ), ARRAY_A );

        if ( $shortcode ) {
            $shortcode["shortcode_settings"] = json_decode( $shortcode["shortcode_settings"], true );
            $shortcode["shortcode_settings"] = $this->validate_shortcode_settings( $shortcode["shortcode_settings"] );
            if ( $is_ajax ) wp_send_json_success( $shortcode );
            return $shortcode;
        }

        if ( $is_ajax ) wp_send_json_error( __('No shortcode found', 'gslogo'), 404 );

        return false;

    }

    public function _update_shortcode( $shortcode_id, $nonce, $fields, $is_ajax ) {

        if ( ! wp_verify_nonce( $nonce, '_gslogo_update_shortcode_gs_') ) {

            if ( $is_ajax ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );
            return false;

        }

        if ( empty($shortcode_id) ) {

            if ( $is_ajax ) wp_send_json_error( __('Shortcode ID missing', 'gslogo'), 400 );
            return false;

        }
    
        $_shortcode = $this->_get_shortcode( $shortcode_id, false );
    
        if ( empty($_shortcode) ) {
            if ( $is_ajax ) wp_send_json_error( __('No shortcode found to update', 'gslogo'), 404 );
            return false;
        }
    
        $shortcode_name = !empty( $fields['shortcode_name'] ) ? sanitize_text_field( $fields['shortcode_name'] ) : $_shortcode['shortcode_name'];
        $shortcode_settings  = !empty( $fields['shortcode_settings']) ? $fields['shortcode_settings'] : $_shortcode['shortcode_settings'];

        // Remove dummy indicator on update
        if ( isset($shortcode_settings['gslogo-demo_data']) ) unset($shortcode_settings['gslogo-demo_data']);
    
        $shortcode_settings = $this->validate_shortcode_settings( $shortcode_settings );
    
        $wpdb = $this->get_wpdb();
    
        $data = array(
            "shortcode_name" 	    => $shortcode_name,
            "shortcode_settings" 	=> json_encode($shortcode_settings),
            "updated_at" 		    => current_time( 'mysql')
        );
    
        $num_row_updated = $wpdb->update( "{$wpdb->prefix}gs_logo_slider" , $data, array( 'id' => absint( $shortcode_id ) ),  $this->get_shortcode_db_columns() );

        if ( $this->check_db_error() ) {
            if ( $is_ajax ) wp_send_json_error( sprintf( __( 'Database Error: %1$s', 'gslogo'), $wpdb->last_error), 500 );
            return false;
        }

        // Delete the shortcode cache
        wp_cache_delete( 'gs_logo_shortcodes', 'gs_logo_slider' );

        do_action( 'gs_logo_shortcode_updated', $num_row_updated );
        do_action( 'gsp_shortcode_updated', $num_row_updated );
    
        if ($is_ajax) wp_send_json_success( array(
            'message' => __('Shortcode updated', 'gslogo'),
            'shortcode_id' => $num_row_updated
        ));
    
        return $num_row_updated;

    }
    
    public function _get_shortcodes( $shortcode_ids = [], $is_ajax = false, $minimal = false ) {

        $wpdb = $this->get_wpdb();
        $fields = $minimal ? 'id, shortcode_name' : '*';

        if ( !empty($shortcode_ids) ) {

            $how_many = count($shortcode_ids);
            $placeholders = array_fill(0, $how_many, '%d');
            $format = implode(', ', $placeholders);
            $query = "SELECT {$fields} FROM {$wpdb->prefix}gs_logo_slider WHERE id IN($format)";
            
            $shortcodes = $wpdb->get_results( $wpdb->prepare($query, $shortcode_ids), ARRAY_A );

        } else {

            $shortcodes = wp_cache_get( 'gs_logo_shortcodes', 'gs_logo_slider' );

            if ( !empty($shortcodes) ) {
                if ( $is_ajax ) wp_send_json_success( $shortcodes );
                return $shortcodes;
            }

            $shortcodes = $wpdb->get_results( "SELECT {$fields} FROM {$wpdb->prefix}gs_logo_slider ORDER BY id DESC", ARRAY_A );

        }

        // check for database error
        if ( $this->check_db_error() ) wp_send_json_error(sprintf(__('Database Error: %s'), $wpdb->last_error));

        if ( empty($shortcode_ids) ) wp_cache_set( 'gs_logo_shortcodes', $shortcodes, 'gs_logo_slider', DAY_IN_SECONDS );

        if ( $is_ajax ) wp_send_json_success( $shortcodes );

        return $shortcodes;

    }

    public function create_shortcode() {

        // validate nonce && check permission
        if ( !check_admin_referer('_gslogo_create_shortcode_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

        $shortcode_settings  = !empty( $_POST['shortcode_settings'] ) ? $this->validate_shortcode_settings( $_POST['shortcode_settings'] ) : '';
        $shortcode_name  = !empty( $_POST['shortcode_name']) ? sanitize_text_field( $_POST['shortcode_name'] ) : __('Undefined', 'gslogo');

        if ( empty($shortcode_settings) || !is_array($shortcode_settings) ) {
            wp_send_json_error( __('Please configure the settings properly', 'gslogo'), 206 );
        }

        $wpdb = $this->get_wpdb();

        $data = array(
            "shortcode_name" => $shortcode_name,
            "shortcode_settings" => json_encode($shortcode_settings),
            "created_at" => current_time( 'mysql'),
            "updated_at" => current_time( 'mysql'),
        );

        $wpdb->insert( "{$wpdb->prefix}gs_logo_slider", $data, $this->get_shortcode_db_columns() );

        // check for database error
        if ( $this->check_db_error() ) wp_send_json_error( sprintf(__('Database Error: %s'), $wpdb->last_error), 500 );

        // Delete the shortcode cache
        wp_cache_delete( 'gs_logo_shortcodes', 'gs_logo_slider' );

        do_action( 'gs_logo_shortcode_created', $wpdb->insert_id );
        do_action( 'gsp_shortcode_created', $wpdb->insert_id );

        // send success response with inserted id
        wp_send_json_success( array(
            'message' => __('Shortcode created successfully', 'gslogo'),
            'shortcode_id' => $wpdb->insert_id
        ));
    }

    public function clone_shortcode() {

        // validate nonce && check permission
        if ( !check_admin_referer('_gslogo_clone_shortcode_gs_') || !current_user_can('manage_options') ) wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

        $clone_id  = !empty( $_POST['clone_id']) ? (int) $_POST['clone_id'] : '';

        if ( empty($clone_id) ) wp_send_json_error( __('Clone Id not provided', 'gslogo'), 400 );

        $clone_shortcode = $this->_get_shortcode( $clone_id, false );

        if ( empty($clone_shortcode) ) wp_send_json_error( __('Clone shortcode not found', 'gslogo'), 404 );


        $shortcode_settings  = $clone_shortcode['shortcode_settings'];
        $shortcode_name  = $clone_shortcode['shortcode_name'] .' '. __('- Cloned', 'gslogo');

        $shortcode_settings = $this->validate_shortcode_settings( $shortcode_settings );

        $wpdb = $this->get_wpdb();

        $data = array(
            "shortcode_name" => $shortcode_name,
            "shortcode_settings" => json_encode($shortcode_settings),
            "created_at" => current_time( 'mysql'),
            "updated_at" => current_time( 'mysql'),
        );

        $wpdb->insert( "{$wpdb->prefix}gs_logo_slider", $data, $this->get_shortcode_db_columns() );

        // check for database error
        if ( $this->check_db_error() ) wp_send_json_error( sprintf(__('Database Error: %s'), $wpdb->last_error), 500 );

        // Delete the shortcode cache
        wp_cache_delete( 'gs_logo_shortcodes', 'gs_logo_slider' );

        do_action( 'gs_logo_shortcode_created', $wpdb->insert_id );
        do_action( 'gsp_shortcode_created', $wpdb->insert_id );

        // Get the cloned shortcode
        $shotcode = $this->_get_shortcode( $wpdb->insert_id, false );

        // send success response with inserted id
        wp_send_json_success( array(
            'message' => __('Shortcode cloned successfully', 'gslogo'),
            'shortcode' => $shotcode,
        ));
    }

    public function get_shortcode() {

        $shortcode_id = !empty( $_GET['id']) ? absint( $_GET['id'] ) : null;

        $this->_get_shortcode( $shortcode_id, wp_doing_ajax() );

    }

    public function update_shortcode( $shortcode_id = null, $nonce = null ) {

        if ( ! $shortcode_id ) {
            $shortcode_id = !empty( $_POST['id']) ? (int) $_POST['id'] : null;
        }
        
        if ( ! $nonce ) {
            $nonce = $_POST['_wpnonce'] ?: null;
        }

        $this->_update_shortcode( $shortcode_id, $nonce, $_POST, true );

    }

    public function delete_shortcodes() {

        if ( !check_admin_referer('_gslogo_delete_shortcodes_gs_') || !current_user_can('manage_options') )
            wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );

        $ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : null;

        if ( empty( $ids ) ) {
            wp_send_json_error( __('No shortcode ids provided', 'gslogo'), 400 );
        }

        $wpdb = $this->get_wpdb();

        $count = count( $ids );
        $ids_format = implode( ', ', array_fill( 0, $count, '%d' ) );

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}gs_logo_slider WHERE ID IN($ids_format)", $ids ) );

        // Delete the shortcode cache
        wp_cache_delete( 'gs_logo_shortcodes', 'gs_logo_slider' );

        do_action( 'gs_logo_shortcode_deleted' );
        do_action( 'gsp_shortcode_deleted' );

        if ( $this->check_db_error() ) wp_send_json_error( sprintf(__('Database Error: %s'), $wpdb->last_error), 500 );

        $m = _n( "Shortcode has been deleted", "Shortcodes have been deleted", $count, 'gslogo' ) ;

        wp_send_json_success( ['message' => $m] );

    }

    public function get_shortcodes() {

        $this->_get_shortcodes( null, wp_doing_ajax() );

    }

    public function temp_save_shortcode_settings() {

        if ( !check_admin_referer('_gslogo_temp_save_shortcode_settings_gs_') || !current_user_can('manage_options') )
            wp_send_json_error( __('Unauthorised Request', 'gslogo'), 401 );
        
        $temp_key = isset( $_POST['temp_key'] ) ? sanitize_text_field( $_POST['temp_key'] ) : null;
        $shortcode_settings = isset( $_POST['shortcode_settings'] ) ? $this->validate_shortcode_settings( $_POST['shortcode_settings'] ) : null;

        if ( empty($temp_key) ) wp_send_json_error( __('No temp key provided', 'gslogo'), 400 );
        if ( empty($shortcode_settings) ) wp_send_json_error( __('No temp settings provided', 'gslogo'), 400 );

        delete_transient( $temp_key );
        set_transient( $temp_key, $shortcode_settings, DAY_IN_SECONDS ); // save the transient for 1 day

        wp_send_json_success([
            'message' => __('Temp data saved', 'gslogo'),
        ]);

    }

    public function get_translation_srtings() {
        return [

            'gs-l-theme' => __('Style & Theming', 'gslogo'),
            'gs-l-theme--placeholder' => __('Select Theme', 'gslogo'),
            'gs-l-theme--help' => __('Select preferred Style & Theme', 'gslogo'),

            'filter_enabled' => __('Enable Filter', 'gslogo'),
            'filter_enabled__details' => __('Enable filter for this theme, it may not available for certain theme', 'gslogo'),

            'filter_type' => __('Filter Type', 'gslogo'),
            'filter_type__details' => __('Select filter type', 'gslogo'),

            'gs_logo_pagination' => __('Enable Pagination', 'gslogo'),
            'gs_logo_pagination__details' => __('Enable paginations like number pagination, load more button, On scroll load etc.', 'gslogo'),

            'pagination_type' => __('Pagination Type', 'gslogo'),
            'pagination_type__details' => __('Select pagination type.', 'gslogo'),

            'initial_items'     => __('Initial Items', 'gslogo'),
            'initial_items__details'    => __('Set initial number of items that shows on page load (before users interaction)', 'gslogo'),

            'load_per_click' => __('Per Click', 'gslogo'),
            'load_per_click__details' => __('Load logos per button click', 'gslogo'),

            'logo_per_page' => __('Per Page', 'gslogo'),
            'logo_per_page__details' => __('Display logos per page', 'gslogo'),

            'per_load' => __('Per Load', 'gslogo'),
            'per_load__details' => __('Display logos per load', 'gslogo'),

            'load_button_text' => __('Button Text', 'gslogo'),
            'load_button_text__details' => __('Load more button text', 'gslogo'),
            
            'gs-l-s2-border-thickness' => __('Border Thickness', 'gslogo'),
            'gs-l-s2-border-thickness--help' => __('Select border thickness (in px).', 'gslogo'),
            
            'gs-l-s2-gradient-start' => __('Gradient Start Color', 'gslogo'),
            'gs-l-s2-gradient-start--help' => __('Select gradient start color (Make gradient start & end same if you want one color border).', 'gslogo'),
            
            'gs-l-s2-gradient-end' => __('Gradient End Color', 'gslogo'),
            'gs-l-s2-gradient-end--help' => __('Select gradient end color (Make gradient start & end same if you want one color border).', 'gslogo'),
            
            'gs-l-rb-border' => __('Border', 'gslogo'),
            'gs-l-rb-border--help' => __('Set border properties.', 'gslogo'),
            
            'gs-l-rb-border-radius' => __('Border Radius', 'gslogo'),
            'gs-l-rb-border-radius--help' => __('Select border radius (in px).', 'gslogo'),
            
            'gs-l-rb-hover-shadow-color' => __('Hover Shadow Color', 'gslogo'),
            'gs-l-rb-hover-shadow-color--help' => __('Select hover shadow color', 'gslogo'),
            
            'gs-l-rb-hover-shadow-control' => __('Shadow Control', 'gslogo'),
            'gs-l-rb-hover-shadow-control--help' => __('Set hover shadow properties', 'gslogo'),

            'enable_single_page' => __('Enable Single Pages', 'gslogo'),
            'enable_single_page-details' => __('Enable Single Pages for logos', 'gslogo'),
            
            'disable_lazy_load' => __('Disable Lazy Load', 'gslogo'),
            'disable_lazy_load-details' => __('Disable Lazy Load for logos', 'gslogo'),
            
            'lazy_load_class' => __('Lazy Load Class', 'gslogo'),
            'lazy_load_class-details' => __('Add class to disable lazy loading, multiple classes should be separated by space', 'gslogo'),

            'anchor_tag_rel' => __( 'Anchor Tag rel', 'gslogo' ),
            'anchor_tag_rel--details' => __( 'Select Anchor Tag rel attribute\'s value, to improve security and SEO, by default the value is dofollow.', 'gslogo' ),

            'image-size' => __('Image Size', 'gslogo'),
            'image-size--placeholder' => __('Select Size', 'gslogo'),
            'image-size--help' => __('Select the attachment size from the registered sources', 'gslogo'),

            'gs-l-link-logos' => __('Link Logos', 'gslogo'),
            'gs-l-link-logos--help' => __('Enable/Disable Linking of logos to their respective links', 'gslogo'),

            'custom-image-size' => __('Custom Image Size', 'gslogo'),
            'custom-image-size-width--placeholder' => __('Width', 'gslogo'),
            'custom-image-size-height--placeholder' => __('Height', 'gslogo'),
            'custom-image-size--help' => __('Set width and height of the logo image.', 'gslogo'),

            'gs-l-slide-speed' => __('Sliding Speed', 'gslogo'),
            'gs-l-slide-speed--help' => __('Set the speed in millisecond. Default 500 ms. To disable autoplay just set the speed 0', 'gslogo'),
            
            'gs-l-is-autop' => __('Autoplay', 'gslogo'),
            'gs-l-is-autop--help' => __('Enable/Disable Auto play to change the slides automatically after certain time. Default On', 'gslogo'),

            'gs-l-autop-pause' => __('Autoplay Delay', 'gslogo'),
            'gs-l-autop-pause--help' => __('You can adjust the time (in ms) between each slide. Default 4000 ms', 'gslogo'),

            'gs-l-inf-loop' => __('Infinite Loop', 'gslogo'),
            'gs-l-inf-loop--help' => __('If ON, clicking on "Next" while on the last slide will start over from first slide and vice-versa', 'gslogo'),

            'gs-l-slider-stop' => __('Pause on hover', 'gslogo'),
            'gs-l-slider-stop--help' => __('Autoplay will pause when mouse hovers over Logo. Default On', 'gslogo'),

            'gs-reverse-direction' => __('Reverse Direction', 'gslogo'),
            'gs-reverse-direction--help' => __('Reverse the direction of movement. Default Off', 'gslogo'),

            'gs-l-stp-tkr' => __('Pause on Hover', 'gslogo'),
            'gs-l-stp-tkr--help' => __('Ticker will pause when mouse hovers over slider. Note: this functionality does NOT work if using CSS transitions!. Default Off', 'gslogo'),
            
            'gs-l-ctrl' => __('Slider Navs', 'gslogo'),
            'gs-l-ctrl--help' => __('Next / Previous control for Logo slider. Default On Controls are not available when Ticker Mode is enabled', 'gslogo'),
            
            'gs-l-ctrl-pos' => __('Navs Position', 'gslogo'),
            'gs-l-ctrl-pos--placeholder' => __('Navs Position', 'gslogo'),
            'gs-l-ctrl-pos--help' => __('Position of Next / Previous control for Logo slider. Default Bottom', 'gslogo'),

            'gs-l-pagi' => __('Slider Dots', 'gslogo'),
            'gs-l-pagi--help' => __('Dots control for logo slider below the widget. Default Off', 'gslogo'),

            'gs-l-pagi-dynamic' => __('Dynamic Dots', 'gslogo'),
            'gs-l-pagi-dynamic--help' => __('Good to enable if you use for many slides. So it will keep only few dots visible at the same time. Default On', 'gslogo'),

            'gs-l-play-pause' => __('Pause on Hover', 'gslogo'),
            'gs-l-play-pause--help' => __('Pause on mouse hover over the logo slider. Default Off', 'gslogo'),

            'gs-l-title' => __('Logo Title', 'gslogo'),
            'gs-l-title--help' => __('Display Logo including / excluding Title. Default Off', 'gslogo'),

            'title-tag' => __('Title Tag', 'gslogo'),
            'title-tag--help' => __('Select logo title tag. Default H3.', 'gslogo'),

            'show-cat' => __('Logo Category', 'gslogo'),
            'show-cat--help' => __('Display Logo including / excluding Category. Default Off', 'gslogo'),

            'gs-l-show-content' => __('Logo Content', 'gslogo'),
            'gs-l-show-content--help' => __('Display Logo content in any number of words/characters. Default Off', 'gslogo'),

            'gs-l-content-limit' => __('Content Limit', 'gslogo'),
            'gs-l-content-limit--help' => __('Set content limit. Default 80 characters', 'gslogo'),

            'gs-l-show-excerpt' => __('Logo Excerpt', 'gslogo'),
            'gs-l-show-excerpt--help' => __('Display Logo excerpt in any number of words/characters. Default Off', 'gslogo'),

            'gs-l-excerpt-limit' => __('Excerpt Limit', 'gslogo'),
            'gs-l-excerpt-limit--help' => __('Set excerpt limit. Default 20 words', 'gslogo'),

            'gs-l-read-more-text' => __('Read More Text', 'gslogo'),

            'gs-l-tooltip' => __('Tooltip', 'gslogo'),
            'gs-l-tooltip--help' => __('Enable / disable Tooltip option.', 'gslogo'),

            'gs-l-tooltip-placement' => __('Tooltip Position', 'gslogo'),
            'gs-l-tooltip-placement--help' => __('Select tooltip position. Default top', 'gslogo'),

            'gs-l-tooltip-bgcolor' => __('Tooltip Background', 'gslogo'),
            'gs-l-tooltip-bgcolor--help' => __('Set tooltip background color. Default #ff5f6d, #ffc371', 'gslogo'),

            'gs-l-tooltip-textcolor' => __('Tooltip Text Color', 'gslogo'),
            'gs-l-tooltip-textcolor--help' => __('Set tooltip text color. Default #fff', 'gslogo'),

            'gs-l-all-filter' => __('All Filter', 'gslogo'),
            'gs-l-all-filter--help' => __('Show/hide All button for filter.', 'gslogo'),

            'gs-secondary-img'       => __( 'Secondary Image', 'gslogo' ),
            'gs-secondary-img--help' => __( 'Applicable for Slider 1 flip Image Style', 'gslogo' ),

            'image_filter'       => __( 'Image Filter', 'gslogo' ),
            'image_filter__help'       => __( 'Select default image filter', 'gslogo' ),

            'hover_image_filter' => __( 'Image Filter on Hover', 'gslogo' ),
            'hover_image_filter__help' => __( 'Select image filter for hover', 'gslogo' ),

            'gs-l-align' => __( 'Alignment', 'gslogo' ),
            'gs-l-align--help' => __( 'Horizonal Alignment of Grid Items', 'gslogo' ),

            'gs-l-margin' => __('Logo Space (px)', 'gslogo'),
            'gs-l-margin--help' => __('Increase / decrease Margin between each Logo. Default 10, max 50.', 'gslogo'),

            'gs-l-min-logo' => __('Desktop Logos', 'gslogo'),
            'gs-l-min-logo--help' => __('The minimum number of logos to be shown. Default 5, max 10. (Theme : Slider1,Fullwith slider,Center Mode, Ticker)', 'gslogo'),

            'gs-l-tab-logo' => __('Tablet Logos', 'gslogo'),
            'gs-l-tab-logo--help' => __('The minimum number of logos to be shown. Default 3, max 10. (Theme : Slider1,Fullwith slider,Center Mode,2 Rows Slider, Ticker)', 'gslogo'),

            'gs-l-mob-logo' => __('Mobile Logos', 'gslogo'),
            'gs-l-mob-logo--help' => __('The minimum number of logos to be shown. Default 2, max 10. (Theme : Slider1,Fullwith slider,Center Mode,2 Rows Slider, Ticker)', 'gslogo'),

            'gs-l-move-logo' => __('Move Logos', 'gslogo'),
            'gs-l-move-logo--help' => __('The number of logos to move on transition. Default 1, max 10.', 'gslogo'),

            'gs-logo-filter-name' => __('All Filter Name', 'gslogo'),
            'gs-logo-filter-name--placeholder' => __('All', 'gslogo'),
            'gs-logo-filter-name--help' => __('Replace preferred text instead of "All" for Filter Theme.', 'gslogo'),

            'gs-logo-filter-align' => __('Filter Name Align', 'gslogo'),
            'gs-logo-filter-align--placeholder' => __('Filters Align', 'gslogo'),
            'gs-logo-filter-align--help' => __('Filter Categories alignment for Filter Theme.', 'gslogo'),

            'gs-l-clkable' => __('Clickable Logos', 'gslogo'),
            'gs-l-clkable--help' => __('Specify target to open the Links, Default New Tab', 'gslogo'),

            'row_heading_image' => __( 'Image Heading', 'gslogo' ),
            'row_heading_image--placeholder' => __( 'Image', 'gslogo' ),

            'row_heading_name' => __( 'Name Heading', 'gslogo' ),
            'row_heading_name--placeholder' => __( 'Name', 'gslogo' ),

            'row_heading_desc' => __( 'Description Heading', 'gslogo' ),
            'row_heading_desc--placeholder' => __( 'Description', 'gslogo' ),

            'posts' => __('Logos', 'gslogo'),
            'posts--placeholder' => __('Logos', 'gslogo'),
            'posts--help' => __('Set max logo numbers you want to show, set -1 for all logos', 'gslogo'),

            'order' => __('Order', 'gslogo'),
            'order--placeholder' => __('Order', 'gslogo'),

            'order-by' => __('Order By', 'gslogo'),
            'order-by--placeholder' => __('Order By', 'gslogo'),

            'filter-order' => __('Filter Order', 'gslogo'),
            'filter-order-by' => __('Filter Order By', 'gslogo'),

            'install-demo-data' => __('Install Demo Data', 'gslogo'),
            'install-demo-data-description' => __('Quick start with GS Plugins by installing the demo data', 'gslogo'),

            'preference' => __('Preference', 'gslogo'),
            'save-preference' => __('Save Preference', 'gslogo'),

            'export-data' => __('Export Data', 'gslogo'),
            'export-data--description' => __('Export GS Logo Slider data to use in other sites', 'gslogo'),

            'import-data' => __('Import Data', 'gslogo'),
            'import-data--description' => __('Import GS Logo Slider data from other sites', 'gslogo'),

            'export-logo-data' => __('Export Logo Data', 'gslogo'),
            'export-shortcodes-data' => __('Export Shortcodes Data', 'gslogo'),
            'export-settings-data' => __('Export Settings Data', 'gslogo'),
            
            'custom-css' => __('Custom CSS', 'gslogo'),

            'shortcodes' => __('Shortcodes', 'gslogo'),
            'global-settings-for-gs-logo-slider' => __('Global Settings for GS Logo Slider', 'gslogo'),
            'all-shortcodes-for-gs-logo-slider' => __('All shortcodes for GS Logo Slider', 'gslogo'),
            'create-shortcode' => __('Create Shortcode', 'gslogo'),
            'create-new-shortcode' => __('Create New Shortcode', 'gslogo'),
            'shortcode' => __('Shortcode', 'gslogo'),
            'name' => __('Name', 'gslogo'),
            'action' => __('Action', 'gslogo'),
            'actions' => __('Actions', 'gslogo'),
            'edit' => __('Edit', 'gslogo'),
            'clone' => __('Clone', 'gslogo'),
            'delete' => __('Delete', 'gslogo'),
            'delete-all' => __('Delete All', 'gslogo'),
            'create-a-new-shortcode-and' => __('Create a new shortcode & save it to use globally in anywhere', 'gslogo'),
            'edit-shortcode' => __('Edit Shortcode', 'gslogo'),
            'general-settings' => __('General Settings', 'gslogo'),
            'style-settings' => __('Style Settings', 'gslogo'),
            'query-settings' => __('Query Settings', 'gslogo'),
            'shortcode-name' => __('Shortcode Name', 'gslogo'),
            'name-of-the-shortcode' => __('Shortcode Name', 'gslogo'),
            'save-shortcode' => __('Save Shortcode', 'gslogo'),
            'preview-shortcode' => __('Preview', 'gslogo'),

            'taxonomies-page'                   => __('Taxonomies', 'gslogo'),
            'taxonomies-page--des'              => __('Global settings for Taxonomies', 'gslogo'),

            'taxonomy_category'                 => __('Category', 'gslogo'),
            'taxonomy_tag'                      => __('Tag', 'gslogo'),
            'taxonomy_language'                 => __('Language', 'gslogo'),
            'taxonomy_location'                 => __('Location', 'gslogo'),
            'taxonomy_gender'                   => __('Gender', 'gslogo'),
            'taxonomy_specialty'                => __('Specialty', 'gslogo'),
            'taxonomy_extra_one'                => __('Extra One', 'gslogo'),
            'taxonomy_extra_two'                => __('Extra Two', 'gslogo'),
            'taxonomy_extra_three'              => __('Extra Three', 'gslogo'),
            'taxonomy_extra_four'               => __('Extra Four', 'gslogo'),
            'taxonomy_extra_five'               => __('Extra Five', 'gslogo'),

            // Extra Taxonomy Settings
            'enable_extra_tax'                  => __('Enable Taxonomy', 'gslogo'),
            'enable_extra_tax--details'         => __('Enable Taxonomy for logos', 'gslogo'),
            'extra_tax_label'                   => __('Taxonomy Label', 'gslogo'),
            'extra_tax_label--details'          => __('Set Taxonomy Label', 'gslogo'),
            'extra_tax_plural_label'            => __('Taxonomy Plural Label', 'gslogo'),
            'extra_tax_plural_label--details'   => __('Set Taxonomy Plural Label', 'gslogo'),
            'enable_extra_tax_archive'          => __('Enable Taxonomy Archive', 'gslogo'),
            'enable_extra_tax_archive--details' => __('Enable Taxonomy Archive', 'gslogo'),
            'extra_tax_archive_slug'            => __('Taxonomy Archive Slug', 'gslogo'),
            'extra_tax_archive_slug--details'   => __('Set Taxonomy Archive Slug', 'gslogo'),

            // Taxonomy include exclude
            'include-tax--details'              => __('Select specific terms to display specific logos', 'gslogo'),
            'exclude-tax--details'              => __('Select specific terms to exclude specific logos', 'gslogo'),

            'save-settings'                     => __('Save Settings', 'gslogo'),

        ];
        
    }

    public function get_shortcode_options_themes() {

        $free_themes = [
            [
                'label' => __( 'Slider 1', 'gslogo' ),
                'value' => 'slider1'
            ],
            [
                'label' => __( 'Grid - 1', 'gslogo' ),
                'value' => 'grid1'
            ],
            [
                'label' => __( 'List - 1', 'gslogo' ),
                'value' => 'list1'
            ],
            [
                'label' => __( 'Table - 1', 'gslogo' ),
                'value' => 'table1'
            ],
        ];

        $pro_themes = [
            [
                'label' => __( 'Slider 2', 'gslogo' ),
                'value' => 'slider2'
            ],
            [
                'label' => __( 'Ticker 1', 'gslogo' ),
                'value' => 'ticker1'
            ],
            [
                'label' => __( 'Grid - 2', 'gslogo' ),
                'value' => 'grid2'
            ],
            [
                'label' => __( 'Grid - 3', 'gslogo' ),
                'value' => 'grid3'
            ],
            [
                'label' => __( 'List - 2', 'gslogo' ),
                'value' => 'list2'
            ],
            [
                'label' => __( 'List - 3', 'gslogo' ),
                'value' => 'list3'
            ],
            [
                'label' => __( 'List - 4', 'gslogo' ),
                'value' => 'list4'
            ],
            [
                'label' => __( 'Table - 2', 'gslogo' ),
                'value' => 'table2'
            ],
            [
                'label' => __( 'Table - 3', 'gslogo' ),
                'value' => 'table3'
            ],
            [
                'label' => __( 'Vertical Slider', 'gslogo' ),
                'value' => 'vslider1'
            ],
            [
                'label' => __( 'Filter - 1', 'gslogo' ),
                'value' => 'filter1'
            ],
            [
                'label' => __( 'Filter - 2', 'gslogo' ),
                'value' => 'filter2'
            ],
            [
                'label' => __( 'Filter - 3', 'gslogo' ),
                'value' => 'filter3'
            ],
            [
                'label' => __( 'Filter - 4', 'gslogo' ),
                'value' => 'filter4'
            ],
            [
                'label' => __( 'Live Filter - 1', 'gslogo' ),
                'value' => 'filterlive1'
            ],
            [
                'label' => __( 'Live Filter - 2', 'gslogo' ),
                'value' => 'filterlive2'
            ],
            [
                'label' => __( 'Live Filter - 3', 'gslogo' ),
                'value' => 'filterlive3'
            ],
            [
                'label' => __( 'Filter - Selected Cats', 'gslogo' ),
                'value' => 'filter-select'
            ],
            [
                'label' => __( 'Full Width Slider', 'gslogo' ),
                'value' => 'slider_fullwidth'
            ],
            [
                'label' => __( 'Center Mode', 'gslogo' ),
                'value' => 'center'
            ],
            [
                'label' => __( 'Variable Width', 'gslogo' ),
                'value' => 'vwidth'
            ],
            [
                'label' => __( 'Vertical Center', 'gslogo' ),
                'value' => 'verticalcenter'
            ],
            [
                'label' => __( 'Vertical Ticker Up', 'gslogo' ),
                'value' => 'verticalticker'
            ],
            [
                'label' => __( 'Vertical Ticker Down', 'gslogo' ),
                'value' => 'verticaltickerdown'
            ],
            [
                'label' => __( '2 Rows Slider', 'gslogo' ),
                'value' => 'slider-2rows'
            ],
            [
                'label' => __( 'Rounded Border', 'gslogo' ),
                'value' => 'rounded-border'
            ],
            [
                'label' => __( 'Horizontal Scroll', 'gslogo' ),
                'value' => 'horizontal-scroll'
            ],
            [
                'label' => __( '3D Circular Slider', 'gslogo' ),
                'value' => '3d-circular-slider'
            ],
            [
                'label' => __( 'Hexagon', 'gslogo' ),
                'value' => 'hexagon'
            ]
        ];

        if ( ! is_pro_active() || ! is_plugin_loaded() || ! gs_logo_pro_is_valid() ) {
            $pro_themes = array_map( function( $item ) {
                $item['pro'] = true;
                return $item;
            }, $pro_themes);
        }

        return array_merge( $free_themes, $pro_themes );

    }

    public function get_pagination_types() {

        $free_pagination = [
            [
                'label' => __( 'Normal Pagination', 'gslogo' ),
                'value' => 'normal-pagination'
            ]
        ];

        $pro_pagination = [
            [
                'label' => __( 'AJAX Pagination', 'gslogo' ),
                'value' => 'ajax-pagination'
            ],
            [
                'label' => __( 'Load More Button', 'gslogo' ),
                'value' => 'load-more-button'
            ],
            [
                'label' => __( 'Load More On Scroll', 'gslogo' ),
                'value' => 'load-more-scroll'
            ]
        ];

        if ( ! is_pro_active() || ! is_plugin_loaded() || ! gs_logo_pro_is_valid() ) {
            $pro_pagination = array_map( function( $item ) {
                $item['pro'] = true;
                return $item;
            }, $pro_pagination);
        }

        return array_merge( $free_pagination, $pro_pagination );

    }

    public function get_shortcode_options_image_sizes() {

        $sizes = get_intermediate_image_sizes();

        if ( empty($sizes) ) return [];

        $_sizes = array_map( function($size) {
            $label = preg_replace('/_|-/', ' ', $size);
            return [
                'label' => ucwords($label),
                'value' => $size
            ];
        }, $sizes );

        $_sizes[] = [
            'label' => __('Set Custom Size', 'gslogo'),
            'value' => 'custom'
        ];

        return $_sizes;

    }

    public function get_logo_terms( $tax_name, $idsOnly = false ) {

        $taxonomies = get_taxonomies( [], 'names' );

        if ( ! in_array( $tax_name, $taxonomies, true ) ) {
            return [];
        }

        $_terms = get_terms([
            'taxonomy'   => $tax_name,
            'hide_empty' => false,
        ]);

        if ( is_wp_error($_terms) || empty($_terms) ) {
            return [];
        }
        
        if ( $idsOnly ) return wp_list_pluck( $_terms, 'term_id' );

        $terms = [];

        foreach ( $_terms as $term ) {
            $terms[] = [
                'label' => $term->name,
                'value' => $term->term_id
            ];
        }

        return $terms;

    }

    public function get_image_filter_effects() {

        $free_effects = [
            [
                'label' => __( 'None', 'gslogo' ),
                'value' => 'none'
            ]
        ];

        $pro_effects = [
            [
                'label' => __( 'Blur', 'gslogo' ),
                'value' => 'blur'
            ],
            [
                'label' => __( 'Brightness', 'gslogo' ),
                'value' => 'brightness'
            ],
            [
                'label' => __( 'Contrast', 'gslogo' ),
                'value' => 'contrast'
            ],
            [
                'label' => __( 'Grayscale', 'gslogo' ),
                'value' => 'grayscale'
            ],
            [
                'label' => __( 'Hue Rotate', 'gslogo' ),
                'value' => 'hue_rotate'
            ],
            [
                'label' => __( 'Invert', 'gslogo' ),
                'value' => 'invert'
            ],
            [
                'label' => __( 'Opacity', 'gslogo' ),
                'value' => 'opacity'
            ],
            [
                'label' => __( 'Saturate', 'gslogo' ),
                'value' => 'saturate'
            ],
            [
                'label' => __( 'Sepia', 'gslogo' ),
                'value' => 'sepia'
            ]
        ];

        if ( ! is_pro_active() || ! is_plugin_loaded() || ! gs_logo_pro_is_valid() ) {
            $pro_effects = array_map( function( $item ) {
                $item['pro'] = true;
                return $item;
            }, $pro_effects);
        }

        return array_merge( $free_effects, $pro_effects );

    }

    public function get_shortcode_default_options() {

        return [

            'gs_l_clkable' => [
                [
                    'label' => __( 'New Tab', 'gslogo' ),
                    'value' => '_blank'
                ],
                [
                    'label' => __( 'Same Window', 'gslogo' ),
                    'value' => '_self'
                ],
            ],

            'custom_image_size_crop' => [
                [
                    'label' => __( 'Hard Crop', 'gslogo' ),
                    'value' => 'hard-crop'
                ],
                [
                    'label' => __( 'Soft Crop', 'gslogo' ),
                    'value' => 'soft-crop'
                ],
            ],

            'gs_l_ctrl_pos' => [
                [
                    'label' => __( 'Bottom', 'gslogo' ),
                    'value' => 'bottom'
                ],
                [
                    'label' => __( 'Left Right', 'gslogo' ),
                    'value' => 'left-right'
                ],
                [
                    'label' => __( 'Left Right Outside', 'gslogo' ),
                    'value' => 'left-right-out'
                ],
            ],

            'title_tag' => [
                [
                    'label' => __( 'H1', 'gslogo' ),
                    'value' => 'h1'
                ],
                [
                    'label' => __( 'H2', 'gslogo' ),
                    'value' => 'h2'
                ],
                [
                    'label' => __( 'H3', 'gslogo' ),
                    'value' => 'h3'
                ],
                [
                    'label' => __( 'H4', 'gslogo' ),
                    'value' => 'h4'
                ],
                [
                    'label' => __( 'H5', 'gslogo' ),
                    'value' => 'h5'
                ],
                [
                    'label' => __( 'H6', 'gslogo' ),
                    'value' => 'h6'
                ],
                [
                    'label' => __( 'Span', 'gslogo' ),
                    'value' => 'span'
                ],
                [
                    'label' => __( 'Div', 'gslogo' ),
                    'value' => 'div'
                ],
                [
                    'label' => __( 'P', 'gslogo' ),
                    'value' => 'p'
                ],

            ],

            'gs_l_theme' => $this->get_shortcode_options_themes(),

            'gs_logo_filter_type' => [
                [
                    'label' => __( 'Normal Filter', 'gslogo' ),
                    'value' => 'normal-filter'
                ],
                [
                    'label' => __( 'Ajax Filter', 'gslogo' ),
                    'value' => 'ajax-filter'
                ]
            ],

            'pagination_type' => $this->get_pagination_types(),

            'gs_l_rb_border_type' => [
                [
                    'label' => __( 'Solid', 'gslogo' ),
                    'value' => 'solid'
                ],
                [
                    'label' => __( 'Dotted', 'gslogo' ),
                    'value' => 'dotted'
                ],
                [
                    'label' => __( 'Dashed', 'gslogo' ),
                    'value' => 'dashed'
                ],
                [
                    'label' => __( 'Double', 'gslogo' ),
                    'value' => 'double'
                ],
                [
                    'label' => __( 'Groove', 'gslogo' ),
                    'value' => 'groove'
                ],
                [
                    'label' => __( 'Ridge', 'gslogo' ),
                    'value' => 'ridge'
                ],
                [
                    'label' => __( 'Inset', 'gslogo' ),
                    'value' => 'inset'
                ],
                [
                    'label' => __( 'Outset', 'gslogo' ),
                    'value' => 'outset'
                ]
            ],

            'gs_l_tooltip_placement' => [
                [
                    'label' => __( 'Top', 'gslogo' ),
                    'value' => 'top'
                ],
                [
                    'label' => __( 'Bottom', 'gslogo' ),
                    'value' => 'bottom'
                ],
                [
                    'label' => __( 'Left', 'gslogo' ),
                    'value' => 'left'
                ],
                [
                    'label' => __( 'Right', 'gslogo' ),
                    'value' => 'right'
                ],
            ],

            'image_size' => $this->get_shortcode_options_image_sizes(),

            'image_filter' => $this->get_image_filter_effects(),

            'hover_image_filter' => $this->get_image_filter_effects(),

            'gs_l_align' => [
                [
                    'label' => __( 'Left', 'gslogo' ),
                    'value' => 'flex-start',
                ],
                [
                    'label' => __( 'Center', 'gslogo' ),
                    'value' => 'center',
                ],
                [
                    'label' => __( 'Right', 'gslogo' ),
                    'value' => 'flex-end',
                ]
            ],

            'gs_logo_filter_align' => [
                [
                    'label' => __( 'Center', 'gslogo' ),
                    'value' => 'center',
                ],
                [
                    'label' => __( 'Left', 'gslogo' ),
                    'value' => 'left',
                ],
                [
                    'label' => __( 'Right', 'gslogo' ),
                    'value' => 'right',
                ]
            ],

            'category'          => $this->get_logo_terms('logo-category'),
            'tag'               => $this->get_logo_terms('logo-tag'),
            'extra_one'         => $this->get_logo_terms('logo-extra-one'),
            'extra_two'         => $this->get_logo_terms('logo-extra-two'),
            'extra_three'       => $this->get_logo_terms('logo-extra-three'),
            'extra_four'        => $this->get_logo_terms('logo-extra-four'),
            'extra_five'        => $this->get_logo_terms('logo-extra-five'),

            'gs_l_content_limit_type' => [
                [
                    'label' => __( 'Characters', 'gslogo' ),
                    'value' => 'chars'
                ],
                [
                    'label' => __( 'Words', 'gslogo' ),
                    'value' => 'words'
                ],
            ],

            'gs_l_excerpt_limit_type' => [
                [
                    'label' => __( 'Characters', 'gslogo' ),
                    'value' => 'chars'
                ],
                [
                    'label' => __( 'Words', 'gslogo' ),
                    'value' => 'words'
                ],
            ],

            'orderby' => [
                [
                    'label' => __( 'Custom Order', 'gslogo' ),
                    'value' => 'menu_order'
                ],
                [
                    'label' => __( 'Logo ID', 'gslogo' ),
                    'value' => 'ID'
                ],
                [
                    'label' => __( 'Logo Name', 'gslogo' ),
                    'value' => 'title'
                ],
                [
                    'label' => __( 'Date', 'gslogo' ),
                    'value' => 'date'
                ],
                [
                    'label' => __( 'Random', 'gslogo' ),
                    'value' => 'rand'
                ],
            ],

            'order' => [
                [
                    'label' => __( 'DESC', 'gslogo' ),
                    'value' => 'DESC'
                ],
                [
                    'label' => __( 'ASC', 'gslogo' ),
                    'value' => 'ASC'
                ],
            ],

            'filter_orderby' => [
                [
                    'label' => __( 'Custom Order', 'gslogo' ),
                    'value' => 'term_order'
                ],
                [
                    'label' => __( 'Term ID', 'gslogo' ),
                    'value' => 'term_id'
                ],
                [
                    'label' => __( 'Term Name', 'gslogo' ),
                    'value' => 'name'
                ],
                [
                    'label' => __( 'Count', 'gslogo' ),
                    'value' => 'count'
                ],
                [
                    'label' => __( 'Random', 'gslogo' ),
                    'value' => 'rand'
                ]
            ]

        ];
        
    }

    public function get_shortcode_default_prefs() {
        return [
            'enable_single_page' => 'off',
            'disable_lazy_load' => 'off',
            'lazy_load_class' => 'skip-lazy',
            'anchor_tag_rel' => 'noopener',
            'gs_logo_slider_custom_css' => ''
        ];
    }

    public function get_shortcode_prefs_options() {
        return [
            'anchor_tag_rel' => [
                [
                    'label' => __( 'nofollow', 'gslogo' ),
                    'value' => 'nofollow'
                ],
                [
                    'label' => __( 'noopener', 'gslogo' ),
                    'value' => 'noopener'
                ],
                [
                    'label' => __( 'noreferrer', 'gslogo' ),
                    'value' => 'noreferrer'
                ],
                [
                    'label' => __( 'nofollow noopener', 'gslogo' ),
                    'value' => 'nofollow noopener'
                ],
                [
                    'label' => __( 'nofollow noreferrer', 'gslogo' ),
                    'value' => 'nofollow noreferrer'
                ],
                [
                    'label' => __( 'noopener noreferrer', 'gslogo' ),
                    'value' => 'noopener noreferrer'
                ],
                [
                    'label' => __( 'nofollow noopener noreferrer', 'gslogo' ),
                    'value' => 'nofollow noopener noreferrer'
                ],
            ]
        ];
    }

    public function get_taxonomy_default_settings() {

        return [

            // Category Taxonomy
            'enable_category_tax' => 'on',
            'category_tax_label' => __('Logo Category', 'gslogo'),
            'category_tax_plural_label' => __('Logo Categories', 'gslogo'),
            'enable_category_tax_archive' => 'on',
            'category_tax_archive_slug' => 'logo-category',

            // Tag Taxonomy
            'enable_tag_tax' => 'off',
            'tag_tax_label' => __('Logo Tag', 'gslogo'),
            'tag_tax_plural_label' => __('Logo Tags', 'gslogo'),
            'enable_tag_tax_archive' => 'on',
            'tag_tax_archive_slug' => 'logo-tag',

            // Extra One Taxonomy
            'enable_extra_one_tax' => 'off',
            'extra_one_tax_label' => __('Extra 1', 'gslogo'),
            'extra_one_tax_plural_label' => __('Extra 1', 'gslogo'),
            'enable_extra_one_tax_archive' => 'on',
            'extra_one_tax_archive_slug' => 'gs-logo-extra-one',

            // Extra Two Taxonomy
            'enable_extra_two_tax' => 'off',
            'extra_two_tax_label' => __('Extra 2', 'gslogo'),
            'extra_two_tax_plural_label' => __('Extra 2', 'gslogo'),
            'enable_extra_two_tax_archive' => 'off',
            'extra_two_tax_archive_slug' => 'gs-logo-extra-two',

            // Extra Three Taxonomy
            'enable_extra_three_tax' => 'off',
            'extra_three_tax_label' => __('Extra 3', 'gslogo'),
            'extra_three_tax_plural_label' => __('Extra 3', 'gslogo'),
            'enable_extra_three_tax_archive' => 'off',
            'extra_three_tax_archive_slug' => 'gs-logo-extra-three',

            // Extra Four Taxonomy
            'enable_extra_four_tax' => 'off',
            'extra_four_tax_label' => __('Extra 4', 'gslogo'),
            'extra_four_tax_plural_label' => __('Extra 4', 'gslogo'),
            'enable_extra_four_tax_archive' => 'off',
            'extra_four_tax_archive_slug' => 'gs-logo-extra-four',

            // Extra Five Taxonomy
            'enable_extra_five_tax' => 'off',
            'extra_five_tax_label' => __('Extra 5', 'gslogo'),
            'extra_five_tax_plural_label' => __('Extra 5', 'gslogo'),
            'enable_extra_five_tax_archive' => 'off',
            'extra_five_tax_archive_slug' => 'gs-logo-extra-five',

        ];

    }

    function get_shortcode_default_settings() {
        return [
            'posts' 	               => -1,
            'order'		               => 'DESC',
            'orderby'                  => 'date',
            'filter_order'             => 'ASC',
            'filter_orderby'           => 'name',
            'gs_l_title'               => 'on',
            'title_tag'                => 'h3',
            'gs_l_link_logos'          => 'on',
            'include_category'         => [],
            'include_tag'              => [],
            'include_extra_one'        => [],
            'include_extra_two'        => [],
            'include_extra_three'      => [],
            'include_extra_four'       => [],
            'include_extra_five'       => [],
            'exclude_category'         => [],
            'exclude_tag'              => [],
            'exclude_extra_one'        => [],
            'exclude_extra_two'        => [],
            'exclude_extra_three'      => [],
            'exclude_extra_four'       => [],
            'exclude_extra_five'       => [],
            'gs_l_ctrl'                => 'on',
            'gs_l_ctrl_pos'            => 'bottom',
            'gs_l_pagi'                => 'off',
            'gs_l_pagi_dynamic'        => 'on',
            'gs_l_play_pause'          => 'off',
            'gs_l_inf_loop'	           => 'on',
            'gs_l_slider_stop'         => 'on',
            'gs_l_tooltip' 	           => 'off',
            'gs_l_tooltip_placement'   => 'top',
            'gs_l_tooltip_bgcolor_one' => '#ff5f6d',
            'gs_l_tooltip_bgcolor_two' => '#ffc371',
            'gs_l_tooltip_textcolor'   => '#fff',
            'gs_l_all_filter'          => 'on',
            'gs_secondary_img'         => 'off',
            'gs_l_slide_speed'		   => 500,
            'gs_l_autop_pause'         => 2000,
            'gs_l_theme'		       => 'slider1',
            'filter_enabled'           => 'off',
            'gs_logo_filter_type'      => 'normal-filter',
            'gs_logo_pagination'       => 'off',
            'pagination_type'          => 'normal-pagination',
            'initial_items'            => 6,
            'logo_per_page'            => 6,
            'load_per_click'           => 3,
            'per_load'                 => 3,
            'load_button_text'         => __('Load More', 'gslogo'),
            'gs_l_s2_border_thickness' => '50',
            'gs_l_s2_gradient_start'   => '#003729',
            'gs_l_s2_gradient_end'     => '#1f9e74',
            'gs_l_rb_border'           => '1px,solid,#000000',
            'gs_l_rb_border_radius'    => '10,10,10,10',
            'gs_l_rb_hover_shadow_color' => '#1d202f',
            'gs_l_rb_hover_shadow_control' => '6,6,15,0',
            'image_size'               => 'medium',
            'custom_image_size_width'  => '',
            'custom_image_size_height' => '',
            'custom_image_size_crop'   => 'hard-crop',
            'gs_l_clkable'             => '_blank',
            'gs_l_is_autop'            => 'on',
            'gs_reverse_direction'     => 'off',
            'image_filter'             => 'none',
            'hover_image_filter'       => 'none',
            'gs_l_align'               => 'center',
            'gs_l_margin'              => 10,
            'gs_l_min_logo'            => 5,
            'gs_l_tab_logo'            => 3,
            'gs_l_mob_logo'            => 2,
            'gs_l_move_logo'           => 1,
            'gs_logo_filter_name'      => 'All',
            'gs_logo_filter_align'     => 'center',
            'show_cat'                 => 'off',
            'gs_l_show_content'        => 'off',
            'gs_l_content_limit_count' => 80,
            'gs_l_content_limit_type'  => 'chars',
            'gs_l_show_excerpt'        => 'off',
            'gs_l_excerpt_limit_count' => 20,
            'gs_l_excerpt_limit_type'  => 'words',
            'gs_l_read_more_text'      => __('Read More', 'gslogo'),
            'row_heading_image'        => 'Image',
            'row_heading_name'         => 'Name',
            'row_heading_desc'         => 'Description',
        ];
    }

    public function _save_shortcode_pref( $prefs, $is_ajax ) {

        $prefs = $this->validate_preference( $prefs );
        update_option( $this->option_name, $prefs, 'yes' );
        
        // Clean permalink flush
        delete_option( 'GS_Logo_Slider_plugin_permalinks_flushed' );
        do_action( 'gs_logo_preference_update' );
        do_action( 'gsp_preference_update' );
    
        if ( $is_ajax ) wp_send_json_success( __('Preference saved', 'gslogo') );
    }

    public function save_shortcode_pref( $nonce = null ) {

        check_ajax_referer( '_gslogo_save_shortcode_pref_gs_' );

        if ( empty($_POST['prefs']) ) {
            wp_send_json_error( __('No preference provided', 'gslogo'), 400 );
        }

        $this->_save_shortcode_pref( $_POST['prefs'], true );
    }

    public function get_tax_option( $option, $default = '' ) {
        $options = (array) get_option( $this->taxonomy_option_name, [] );
        $defaults = $this->get_taxonomy_default_settings();
        $options = array_merge($defaults, $options);

        if ( str_contains($option, '_label') && empty($options[$option]) ) {
            return $defaults[$option];
        }

        if ( isset($options[$option]) ) return $options[$option];
        return $default;
    }

    public function validate_taxonomy_settings( $settings ) {

        $defaults = $this->get_taxonomy_default_settings();

        if ( empty($settings) ) {
            $settings = $defaults;
        } else {
            foreach ( $settings as $setting_key => $setting_val ) {
                if ( str_contains($setting_key, '_label') && empty($setting_val) ) {
                    $settings[$setting_key] = $defaults[$setting_key];
                }
            }
        }
        
        return array_map( 'sanitize_text_field', $settings );
    }

    public function _get_taxonomy_settings( $is_ajax ) {

        $settings = (array) get_option( $this->taxonomy_option_name, [] );
        $settings = $this->validate_taxonomy_settings( $settings );

        if( ! is_gs_logo_pro_valid() ){
            $settings['enable_extra_one_tax'] = 'off';
            $settings['enable_extra_two_tax'] = 'off';
            $settings['enable_extra_three_tax'] = 'off';
            $settings['enable_extra_four_tax'] = 'off';
            $settings['enable_extra_five_tax'] = 'off';
        }

        if ( $is_ajax ) {
            wp_send_json_success( $settings );
        }

        return $settings;

    }

    public function get_taxonomy_settings() {
        return $this->_get_taxonomy_settings( wp_doing_ajax() );
    }

    public function _save_taxonomy_settings( $settings, $is_ajax ) {

        if ( empty($settings) ) $settings = [];

        $settings = $this->validate_taxonomy_settings( $settings );
        update_option( $this->taxonomy_option_name, $settings, true );
        
        // Clean permalink flush
        delete_option( 'GS_Logo_plugin_permalinks_flushed' );

        do_action( 'gs_logo_tax_settings_update' );
    
        if ( $is_ajax ) wp_send_json_success( __('Taxonomy settings saved', 'gslogo') );
    }

    public function save_taxonomy_settings() {

        check_ajax_referer( '_gslogo_save_taxonomy_settings_gs_' );
        
        if ( empty($_POST['tax_settings']) ) {
            wp_send_json_error( __('No settings provided', 'gslogo'), 400 );
        }

        $this->_save_taxonomy_settings( $_POST['tax_settings'], true );
    }

    public function validate_preference( $settings ) {

        $defaults = $this->get_shortcode_default_prefs();
        $settings = shortcode_atts( $defaults, $settings );

        $settings['enable_single_page']        = sanitize_text_field( $settings['enable_single_page'] );
        $settings['disable_lazy_load']         = sanitize_text_field( $settings['disable_lazy_load'] );
        $settings['lazy_load_class']           = sanitize_text_field( $settings['lazy_load_class'] );
        $settings['gs_logo_slider_custom_css'] = wp_strip_all_tags( $settings['gs_logo_slider_custom_css'] );

        return $settings;
    }

    /**
     * Returns option based on the option key.
     * 
     * @since  2.0.12
     * 
     * @param string $option  The option key.
     * @param string $default The default value incase doesn't get the actual value.
     * 
     * @return mixed option value.
     */
    public function get( $option, $default = '' ) {
        $options = $this->_get_shortcode_pref( false );
        if ( isset( $options[ $option ] ) ) {
            return $options[ $option ];
        }
        return $default;
    }

    public function _get_shortcode_pref( $is_ajax ) {
        $prefs = get_option( $this->option_name, [] );
        $prefs = $this->validate_preference( $prefs );
        if ( $is_ajax ) wp_send_json_success( $prefs );
        return $prefs;
    }

    public function get_shortcode_pref() {
        return $this->_get_shortcode_pref( wp_doing_ajax() );
    }

    static function maybe_create_shortcodes_table() {

        global $wpdb;

        $gs_logo_slider_db_version = '1.0';

        if ( get_option("{$wpdb->prefix}gs_logo_slider_db_version") == $gs_logo_slider_db_version ) return; // vail early
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gs_logo_slider (
            id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
            shortcode_name TEXT NOT NULL,
            shortcode_settings LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id)
        )".$wpdb->get_charset_collate().";";
            
        if ( get_option("{$wpdb->prefix}gs_logo_slider_db_version") < $gs_logo_slider_db_version ) {
            dbDelta( $sql );
        }

        update_option( "{$wpdb->prefix}gs_logo_slider_db_version", $gs_logo_slider_db_version );
        
    }

    public function create_dummy_shortcodes() {

        $request = wp_remote_get( GSL_PLUGIN_URI . 'includes/demo-data/shortcodes.json', array('sslverify' => false) );

        if ( is_wp_error($request) ) return false;

        $shortcodes = wp_remote_retrieve_body( $request );

        $shortcodes = json_decode( $shortcodes, true );

        $wpdb = $this->get_wpdb();

        if ( ! $shortcodes || ! count($shortcodes) ) return;

        foreach ( $shortcodes as $shortcode ) {

            $shortcode['shortcode_settings'] = json_decode( $shortcode['shortcode_settings'], true );
            $shortcode['shortcode_settings']['gslogo-demo_data'] = true;

            $data = array(
                "shortcode_name" => $shortcode['shortcode_name'],
                "shortcode_settings" => json_encode($shortcode['shortcode_settings']),
                "created_at" => current_time( 'mysql'),
                "updated_at" => current_time( 'mysql'),
            );

            $wpdb->insert( "{$wpdb->prefix}gs_logo_slider", $data, $this->get_shortcode_db_columns() );

        }

        wp_cache_delete( 'gs_logo_shortcodes' );

    }

    public function delete_dummy_shortcodes() {

        $wpdb = $this->get_wpdb();

        $string = 'gslogo-demo_data';

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}gs_logo_slider WHERE shortcode_settings like '%%s%'", $string ) );

        // Delete the shortcode cache
        wp_cache_delete( 'gs_logo_shortcodes', 'gs_logo_slider' );

    }

    public function maybe_upgrade_data( $old_version ){
        if ( version_compare( $old_version, '3.7.5' ) < 0 ) $this->upgrade_to_3_7_5();
        if ( version_compare( $old_version, '3.7.9' ) < 0 ) $this->upgrade_to_3_7_9();
    }

    public function upgrade_to_3_7_5() {

        $shortcodes = $this->_get_shortcodes( null, false );

        if ( ! $shortcodes || ! is_array( $shortcodes ) || ! count( $shortcodes ) ) return;

        foreach ( $shortcodes as $shortcode ) {

            $shortcode_id       = $shortcode['id'];
            $shortcode_settings = json_decode( $shortcode["shortcode_settings"], true );
            $gs_l_gray          = isset($shortcode_settings['gs_l_gray']) ? $shortcode_settings['gs_l_gray'] : '';

            if( 'default' === $gs_l_gray || '' === $gs_l_gray ){
                $shortcode_settings['image_filter'] = 'none';
                $shortcode_settings['hover_image_filter'] = 'none';
            } else if ( 'gray' === $gs_l_gray ){
                $shortcode_settings['image_filter'] = 'grayscale';
                $shortcode_settings['hover_image_filter'] = 'grayscale';
            } else if ( 'gray_to_def' === $gs_l_gray ){
                $shortcode_settings['image_filter'] = 'grayscale';
                $shortcode_settings['hover_image_filter'] = 'none';
            } else if ( 'def_to_gray' === $gs_l_gray ){
                $shortcode_settings['image_filter'] = 'none';
                $shortcode_settings['hover_image_filter'] = 'grayscale';
            } else {
                $shortcode_settings['image_filter'] = 'none';
                $shortcode_settings['hover_image_filter'] = 'none';
            }

            $shortcode_settings = $this->validate_shortcode_settings( $shortcode_settings );
    
            $wpdb = $this->get_wpdb();
        
            $data = array(
                "shortcode_name" 	    => $shortcode['shortcode_name'],
                "shortcode_settings" 	=> json_encode($shortcode_settings),
                "updated_at" 		    => current_time( 'mysql')
            );
        
            $wpdb->update( "{$wpdb->prefix}gs_logo_slider" , $data, array( 'id' => absint( $shortcode_id ) ),  $this->get_shortcode_db_columns() );
        }

    }

    public function upgrade_to_3_7_9(){

        $shortcodes = $this->_get_shortcodes( null, false );

        if ( ! $shortcodes || ! is_array( $shortcodes ) || ! count( $shortcodes ) ) return;

        foreach ( $shortcodes as $shortcode ) {

            $shortcode_id       = $shortcode['id'];
            $shortcode_settings = json_decode( $shortcode["shortcode_settings"], true );
            $logo_cat           = isset( $shortcode_settings['logo_cat'] ) ? $shortcode_settings['logo_cat'] : '';

            if( empty( $logo_cat ) ){
                continue;
            }

            $logo_cat = explode(',', $logo_cat );

            $cat_ids = get_term_ids_by_slugs( $logo_cat, 'logo-category' );

            // I will set data here
            $shortcode_settings['include_category'] = $cat_ids;

            $shortcode_settings = $this->validate_shortcode_settings( $shortcode_settings );
    
            $wpdb = $this->get_wpdb();
        
            $data = array(
                "shortcode_name" 	    => $shortcode['shortcode_name'],
                "shortcode_settings" 	=> json_encode($shortcode_settings),
                "updated_at" 		    => current_time( 'mysql')
            );
        
            $wpdb->update( "{$wpdb->prefix}gs_logo_slider" , $data, array( 'id' => absint( $shortcode_id ) ),  $this->get_shortcode_db_columns() );
        }
    }

}