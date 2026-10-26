<?php

namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Integration_Gutenberg {

	const BLOCK_NAME = 'gslogo/shortcodes';

	const SCRIPT_HANDLE = 'gs-logo-block';

	private static $_instance = null;

	private $shortcode_list = null;
        
    public static function get_instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;        
    }

    public function __construct() {
        add_action( 'init', [ $this, 'load_block_script' ] );
        // WP 7.1 always iframes the post editor; assets enqueued here reach the
        // canvas. Keep a no-op editor_assets hook name for older call sites.
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_editor_assets' ] );
    }

    public function enqueue_block_editor_assets() {

        if ( ! is_admin() ) {
            return;
        }

        plugin()->scripts->wp_enqueue_style_all( 'public', ['gs-logo-divi-public'] );
        plugin()->scripts->wp_enqueue_script_all( 'public' );

        // The post editor canvas is an iframe. Styles attached to wp-block-editor
        // stay in the parent document, so the toolbar CSS has to be printed here.
        wp_register_style( 'gs-logo-block-canvas', false, [], GSL_VERSION );
        wp_enqueue_style( 'gs-logo-block-canvas' );
        wp_add_inline_style( 'gs-logo-block-canvas', $this->get_block_css() );

        wp_localize_script( self::SCRIPT_HANDLE, 'gs_logo_slider_block', $this->get_editor_data() );

        wp_add_inline_style( 'gs-logo-public', '
            .gs_logo_area {
                opacity: 1 !important;
                visibility: visible !important;
            }

            /* WP core: .wp-block img:not([draggable]) { pointer-events: none; } */
            .wp-block .gs_logo_area img {
                pointer-events: auto !important;
            }
        ' );
    }

    public function load_block_script() {

        gsLogoAssetGenerator()->enqueue_builder_preview_assets();

        wp_register_script(
            self::SCRIPT_HANDLE,
            GSL_PLUGIN_URI . '/includes/integrations/assets/gutenberg/gutenberg-widget.min.js',
            [ 'wp-blocks', 'wp-editor', 'wp-i18n', 'wp-server-side-render' ],
            GSL_VERSION
        );

        // block.json supplies the title, icon and description that WordPress.org
        // shows on the plugin page; attributes and render stay in PHP.
        register_block_type( GSL_PLUGIN_DIR . 'includes/integrations/assets/gutenberg/shortcodes', [
            'editor_script'   => self::SCRIPT_HANDLE,
            'attributes'      => [
                'shortcode' => [
                    'type'    => 'string',
                    'default' => $this->get_default_item()
                ],
                'align' => [
                    'type'    => 'string',
                    'default' => 'wide'
                ]
            ],
            'render_callback' => [ $this, 'shortcodes_dynamic_render_callback' ]
        ] );

    }

    public function shortcodes_dynamic_render_callback( $block_attributes ) {
        $shortcode_id = ( ! empty($block_attributes) && ! empty($block_attributes['shortcode']) ) ? absint( $block_attributes['shortcode'] ) : $this->get_default_item();
        return do_shortcode( sprintf( '[gslogo id="%u"]', esc_attr($shortcode_id) ) );
    }

    public function get_block_css() {

        return '
            .editor-styles-wrapper .gslogo--toolbar,
            .gslogo--toolbar {
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                gap: 14px;
                margin: 0 0 20px;
                padding: 16px;
                border: 1px solid #dcdcde;
                border-radius: 8px;
                background: #f6f7f7;
            }

            .editor-styles-wrapper .gslogo--toolbar-field,
            .gslogo--toolbar-field {
                display: flex;
                flex-direction: column;
                gap: 6px;
                min-width: 0;
                margin: 0;
            }

            .editor-styles-wrapper .gslogo--toolbar-label,
            .gslogo--toolbar-label {
                display: block;
                margin: 0;
                color: #1d2327;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.4;
            }

            .editor-styles-wrapper .gslogo--toolbar select.gslogo--toolbar-select,
            .gslogo--toolbar select.gslogo--toolbar-select {
                box-sizing: border-box;
                display: block;
                width: 100%;
                max-width: 100%;
                height: auto;
                min-height: 40px;
                margin: 0;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                background: #fff;
                color: #1d2327;
                font-size: 14px;
                line-height: 1.4;
                box-shadow: none;
            }

            .editor-styles-wrapper .gslogo--toolbar-actions,
            .gslogo--toolbar-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin: 0;
            }

            .editor-styles-wrapper .gslogo--toolbar a.gslogo--toolbar-action,
            .gslogo--toolbar a.gslogo--toolbar-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
                min-height: 32px;
                margin: 0;
                padding: 6px 12px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                background: #fff;
                color: #1d2327;
                font-size: 13px;
                font-weight: 500;
                line-height: 1.2;
                text-decoration: none;
                cursor: pointer;
            }

            .editor-styles-wrapper .gslogo--toolbar a.gslogo--toolbar-action:hover,
            .editor-styles-wrapper .gslogo--toolbar a.gslogo--toolbar-action:focus,
            .gslogo--toolbar a.gslogo--toolbar-action:hover,
            .gslogo--toolbar a.gslogo--toolbar-action:focus {
                border-color: #2271b1;
                color: #2271b1;
                text-decoration: none;
                outline: none;
                box-shadow: 0 0 0 1px #2271b1;
            }

            .editor-styles-wrapper .gslogo--toolbar a.gslogo--toolbar-action-primary,
            .gslogo--toolbar a.gslogo--toolbar-action-primary {
                border-color: #2271b1;
                background: #2271b1;
                color: #fff;
            }

            .editor-styles-wrapper .gslogo--toolbar a.gslogo--toolbar-action-primary:hover,
            .editor-styles-wrapper .gslogo--toolbar a.gslogo--toolbar-action-primary:focus,
            .gslogo--toolbar a.gslogo--toolbar-action-primary:hover,
            .gslogo--toolbar a.gslogo--toolbar-action-primary:focus {
                border-color: #135e96;
                background: #135e96;
                color: #fff;
                box-shadow: none;
            }

            .editor-styles-wrapper .gslogo--toolbar p.gslogo--toolbar-empty,
            .gslogo--toolbar p.gslogo--toolbar-empty {
                margin: 0;
                color: #646970;
                font-size: 13px;
                line-height: 1.5;
            }

            .editor-styles-wrapper .wp-block h3.gs_logo_title {
                font-size: 16px;
                font-weight: 400;
                margin: 20px 0 0;
            }
        ';
    }

    protected function get_editor_data() {

        return array(
            'block_title'             => __( 'GS Logo Shortcodes', 'gslogo' ),
            'block_description'       => __( 'Insert a saved GS Logo shortcode to display a logo slider, grid, or gallery.', 'gslogo' ),
            'select_shortcode'        => __( 'Select Shortcode', 'gslogo' ),
            'empty_text'              => __( 'No shortcodes found yet. Create one, then come back and select it.', 'gslogo' ),
            'edit_description_text'   => __( 'Edit this shortcode', 'gslogo' ),
            'edit_link_text'          => __( 'Edit', 'gslogo' ),
            'create_description_text' => __( 'Create new shortcode', 'gslogo' ),
            'create_link_text'        => __( 'Create', 'gslogo' ),
            'edit_link'               => admin_url( 'edit.php?post_type=gs-logo-slider&page=gs-logo-shortcode#/shortcode/' ),
            'create_link'             => admin_url( 'edit.php?post_type=gs-logo-slider&page=gs-logo-shortcode#/shortcode' ),
            'shortcodes'              => $this->get_shortcode_list( true ),
        );
    }

    protected function get_shortcode_list( $with_designs = false ) {

        if ( ! $with_designs && null !== $this->shortcode_list ) {
            return $this->shortcode_list;
        }

        $rows = $this->get_shortcode_rows( $with_designs );
        $theme_labels = $with_designs ? $this->get_theme_labels() : array();
        $fallback_name = __( 'Undefined', 'gslogo' );
        $list = array();

        foreach ( $rows as $row ) {

            if ( empty( $row['id'] ) ) {
                continue;
            }

            $id = absint( $row['id'] );
            $name = isset( $row['shortcode_name'] ) ? sanitize_text_field( $row['shortcode_name'] ) : '';

            if ( '' === $name || $fallback_name === $name ) {
                $theme_label = $this->get_row_theme_label( $row, $theme_labels );
                $name = $theme_label ? sprintf( '%s (#%d)', $theme_label, $id ) : ( '#' . $id );
            }

            $list[] = array(
                'id'             => (string) $id,
                'shortcode_name' => $name,
            );
        }

        if ( ! $with_designs ) {
            $this->shortcode_list = $list;
        }

        return $list;
    }

    /**
     * Saved shortcodes. The editor list also loads settings so a row saved
     * without a name can still be labeled by its design.
     */
    protected function get_shortcode_rows( $with_designs = false ) {

        $rows = plugin()->builder->_get_shortcodes( null, false, true );

        if ( empty( $rows ) || ! is_array( $rows ) ) {
            return array();
        }

        if ( ! $with_designs ) {
            return $rows;
        }

        $first = reset( $rows );

        if ( is_array( $first ) && isset( $first['shortcode_settings'] ) ) {
            return $rows;
        }

        $ids = array_filter( wp_list_pluck( $rows, 'id' ) );

        if ( empty( $ids ) ) {
            return $rows;
        }

        $full_rows = plugin()->builder->_get_shortcodes( $ids, false, false );

        if ( empty( $full_rows ) || ! is_array( $full_rows ) ) {
            return $rows;
        }

        $rows_by_id = array();

        foreach ( $full_rows as $row ) {
            $rows_by_id[ (string) $row['id'] ] = $row;
        }

        $ordered = array();

        foreach ( $rows as $row ) {
            $key = (string) $row['id'];
            if ( isset( $rows_by_id[ $key ] ) ) {
                $ordered[] = $rows_by_id[ $key ];
            }
        }

        return $ordered ? $ordered : $rows;
    }

    protected function get_theme_labels() {

        $labels = array();

        foreach ( plugin()->builder->get_shortcode_options_themes() as $theme ) {

            if ( empty( $theme['value'] ) || empty( $theme['label'] ) ) {
                continue;
            }

            $labels[ $theme['value'] ] = $theme['label'];
        }

        return $labels;
    }

    protected function get_row_theme_label( $row, $theme_labels ) {

        if ( empty( $row['shortcode_settings'] ) || empty( $theme_labels ) ) {
            return '';
        }

        $settings = json_decode( $row['shortcode_settings'], true );

        if ( ! is_array( $settings ) || empty( $settings['gs_l_theme'] ) ) {
            return '';
        }

        $theme = sanitize_key( $settings['gs_l_theme'] );

        return isset( $theme_labels[ $theme ] ) ? $theme_labels[ $theme ] : '';
    }

    protected function get_default_item() {

        $shortcodes = $this->get_shortcode_list();

        if ( ! empty( $shortcodes ) ) {
            return $shortcodes[0]['id'];
        }

        return '';

    }

}