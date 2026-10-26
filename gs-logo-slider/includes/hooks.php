<?php
namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Hooks {

    public function __construct() {
        add_action( 'admin_init', [ $this, 'maybe_redirect' ] );
        add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
        add_action( 'plugins_loaded', [ $this, 'plugin_loaded' ] );
        add_action( 'admin_menu', [ $this, 'register_sub_menu'] );
        add_action( 'admin_menu', [ $this, 'add_submenu_separators' ], 999 );
        add_action( 'admin_head', [ $this, 'print_submenu_separator_css' ] );
        add_action( 'in_admin_header', [ $this, 'disable_admin_notices' ], PHP_INT_MAX );
        add_filter( 'jetpack_content_options_featured_image_exclude_cpt', [$this, 'jetpack__featured_image_exclude_cpt']);
        add_filter( 'use_block_editor_for_post_type', [$this, 'disable_gutenberg'], 10, 2 );
        add_filter( 'get_terms_orderby', array( $this, 'gs_logo_order_terms' ), 10, 3 );
        add_filter( 'single_template', [ $this, 'gs_logo_single_template' ], -1 );
    }


    /**
     * Alter the taxonomy query on backend to order terms as desired.
     */
    public function gs_logo_order_terms( $orderby, $args, $taxonomies ) {

        // Only affect your taxonomy
        if ( ! in_array( 'logo-category', (array) $taxonomies, true ) ) {
            return $orderby;
        }

        $choice = $args['orderby'] ?? '';

        switch ( $choice ) {
            case 'term_order':
                // Your custom column that you added earlier
                return 't.term_order'; // no ASC or DESC here

            case 'term_id':
                return 't.term_id';

            case 'name':
                return 't.name';

            case 'count':
                // count lives on term_taxonomy
                return 'tt.count';

            case 'rand':
                return 'RAND()';

            // Let WordPress handle built-ins you did not remap
            default:
                return $orderby;
        }
    }

    public function gs_logo_single_template($single_logo_template) {
        global $post;

        if ($post->post_type == 'gs-logo-slider') {
            $single_logo_template = Template_Loader::locate_template('gs-logo-template-single.php');
        }

        return $single_logo_template;
    }

    function disable_gutenberg( $current_status, $post_type ) {
        if ( $post_type === 'gs-logo-slider' ) return false;
        return $current_status;
    }

    function jetpack__featured_image_exclude_cpt( $excluded_post_types ) {
        return array_merge( $excluded_post_types, ['gs-logo-slider'] );
    }

    /**
     * Redirect to options page
     *
     * @since v1.0.0
     */
    public function maybe_redirect() {

        if ( get_option('gslogo_activation_redirect', false) ) {

            delete_option('gslogo_activation_redirect');

            if ( !isset($_GET['activate-multi']) ) {
                wp_redirect( Welcome::get_page_url() );
            }
        }
    }

    public function plugin_row_meta( $meta_fields, $file ) {
  
        if ( strpos($file, basename(__FILE__)) === false ) return $meta_fields;
        
        echo "<style>.gslogo-rate-stars { display: inline-block; color: #ffb900; position: relative; top: 3px; }.gslogo-rate-stars svg{ fill:#ffb900; } .gslogo-rate-stars svg:hover{ fill:#ffb900 } .gslogo-rate-stars svg:hover ~ svg{ fill:none; } </style>";

        $plugin_rate   = "https://wordpress.org/support/plugin/gs-logo-slider/reviews/?rate=5#new-post";
        $plugin_filter = "https://wordpress.org/support/plugin/gs-logo-slider/reviews/?filter=5";
        $svg_xmlns     = "https://www.w3.org/2000/svg";
        $svg_icon      = '';

        for ( $i = 0; $i < 5; $i++ ) {
            $svg_icon .= "<svg xmlns='" . esc_url( $svg_xmlns ) . "' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>";
        }

        // Set icon for thumbsup.
        $meta_fields[] = '<a href="' . esc_url( $plugin_filter ) . '" target="_blank"><span class="dashicons dashicons-thumbs-up"></span>' . __( 'Vote!', 'gscs' ) . '</a>';

        // Set icon for 5-star reviews. v1.1.22
        $meta_fields[] = "<a href='" . esc_url( $plugin_rate ) . "' target='_blank' title='" . esc_html__( 'Rate', 'gscs' ) . "'><i class='gslogo-rate-stars'>" . $svg_icon . "</i></a>";

        return $meta_fields;

    }

    public function plugin_loaded() {
        plugin()->builder->maybe_create_shortcodes_table();
        gs_update_plugin_version();
    }

    public function register_sub_menu() {
        add_submenu_page(
            'edit.php?post_type=gs-logo-slider', 'Taxonomies', 'Taxonomies', 'publish_pages', 'gs-logo-shortcode#/taxonomies', array( plugin()->builder, 'view' )
        );
    }

    public function add_submenu_separators() {
        global $submenu;

        $parent = 'edit.php?post_type=gs-logo-slider';

        if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
            return;
        }

        $items          = $submenu[ $parent ];
        $last_tax_index = null;
        $import_index   = null;
        $license_index  = null;
        $pro_index      = null;

        foreach ( $items as $index => $item ) {
            $slug = isset( $item[2] ) ? (string) $item[2] : '';

            if ( str_contains( $slug, 'edit-tags.php?taxonomy=' ) ) {
                $last_tax_index = $index;
            }

            if ( str_contains( $slug, 'gs-logo-shortcode#/import-export' ) ) {
                $import_index = $index;
            }

            if ( 'gs-logo-license' === $slug ) {
                $license_index = $index;
            }

            if ( 'gs-logo-plugins-premium' === $slug ) {
                $pro_index = $index;
            }
        }

        $move_license = null !== $license_index && null !== $pro_index;
        $updated      = [];

        foreach ( $items as $index => $item ) {
            if ( $move_license && $index === $license_index ) {
                continue;
            }

            if ( $move_license && $index === $pro_index ) {
                $updated[] = $items[ $license_index ];
            }

            $updated[] = $item;

            if ( $index === $last_tax_index && $this->has_visible_submenu_item_after( $items, $index ) ) {
                $updated[] = $this->get_submenu_separator( 'gs-logo-menu-separator-tax' );
            }

            if ( $index === $import_index && $this->has_visible_submenu_item_after( $items, $index ) ) {
                $updated[] = $this->get_submenu_separator( 'gs-logo-menu-separator-import' );
            }
        }

        $submenu[ $parent ] = $updated;
    }

    private function get_submenu_separator( $slug ) {
        return [
            '',
            'read',
            $slug,
            '',
            'gs-submenu-separator',
        ];
    }

    private function has_visible_submenu_item_after( $items, $index ) {
        $passed = false;

        foreach ( $items as $item_index => $item ) {
            if ( ! $passed ) {
                if ( $item_index === $index ) {
                    $passed = true;
                }
                continue;
            }

            $cap = isset( $item[1] ) ? $item[1] : '';

            if ( $cap && current_user_can( $cap ) ) {
                return true;
            }
        }

        return false;
    }

    public function print_submenu_separator_css() {
        echo '<style>'
            . '#adminmenu .menu-icon-gs-logo-slider .wp-submenu li.gs-submenu-separator{height:0;margin:6px 0;padding:0;border-top:1px solid #3c434a;font-size:0;line-height:0;pointer-events:none;}'
            . '#adminmenu .menu-icon-gs-logo-slider .wp-submenu li.gs-submenu-separator a{display:none;}'
            . '#adminmenu .menu-icon-gs-logo-slider .wp-submenu a[href*="gs-logo-shortcode#/go-pro"]{color:#c4b5fd;font-weight:600;}'
            . '#adminmenu .menu-icon-gs-logo-slider .wp-submenu .gs-logo-go-pro{display:inline-flex;gap:6px;}'
            . '#adminmenu .menu-icon-gs-logo-slider .wp-submenu .gs-logo-go-pro:before{content:"";width:14px;height:14px;background:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'%3E%3Cpath fill=\'%23f5c542\' d=\'M1.7 11.7h12.6l-1.2-6.3-3.1 2.5L8 3.4 6 7.9 2.9 5.4l-1.2 6.3z\'/%3E%3Cpath fill=\'%23f5c542\' d=\'M1.5 11.8h13v1.6c0 .3-.3.6-.6.6H2.1c-.3 0-.6-.3-.6-.6v-1.6z\'/%3E%3Ccircle fill=\'%23ffe08a\' cx=\'8\' cy=\'3.4\' r=\'1\'/%3E%3C/svg%3E") center/contain no-repeat;filter:drop-shadow(0 0 3px rgba(245,197,66,.55));}'
            . '#adminmenu .menu-icon-gs-logo-slider .wp-submenu a:hover .gs-logo-go-pro,#adminmenu .menu-icon-gs-logo-slider .wp-submenu .current .gs-logo-go-pro{color:#e9d5ff;}'
            . '@media (prefers-reduced-motion:no-preference){#adminmenu .menu-icon-gs-logo-slider .wp-submenu .gs-logo-go-pro:before{animation:gs-logo-go-pro-pulse 2.2s ease-in-out infinite;}}'
            . '@keyframes gs-logo-go-pro-pulse{0%,100%{transform:scale(1);filter:drop-shadow(0 0 2px rgba(245,197,66,.35))}50%{transform:scale(1.12);filter:drop-shadow(0 0 5px rgba(245,197,66,.8))}}'
            . '</style>';
    }

    public function disable_admin_notices() {

        global $parent_file;
    
        if ( $parent_file != 'edit.php?post_type=gs-logo-slider' ) return;
        
        remove_all_actions( 'network_admin_notices' );
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );
    
    }

}
