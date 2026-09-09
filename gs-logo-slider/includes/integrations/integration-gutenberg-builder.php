<?php

namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gutenberg block that exposes the whole shortcode builder inline.
 *
 * Unlike Integration_Gutenberg (which only picks a saved shortcode by ID), this
 * block keeps every shortcode setting in its own block attributes, so nothing is
 * written to the shortcode table.
 */
class Integration_Gutenberg_Builder {

    const BLOCK_NAME = 'gslogo/logo-builder';

    const SCRIPT_HANDLE = 'gs-logo-builder-block';

    /**
     * Prefix of the per instance key used for CSS scoping and AJAX lookups.
     */
    const INSTANCE_PREFIX = 'gslogo_block_';

    private static $_instance = null;

    public static function get_instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        add_action( 'init', [ $this, 'register_block' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
        // WP 7.1 always iframes the canvas; editor_assets stay in the parent
        // document, so public CSS/JS for the preview must use this hook.
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
    }

    public function register_block() {

        wp_register_style(
            self::SCRIPT_HANDLE . '-editor',
            false,
            [],
            GSL_VERSION
        );

        wp_add_inline_style( self::SCRIPT_HANDLE . '-editor', $this->get_inspector_css() );

        wp_register_script(
            self::SCRIPT_HANDLE,
            GSL_PLUGIN_URI . '/includes/integrations/assets/gutenberg/gutenberg-builder-block.min.js',
            [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ],
            GSL_VERSION,
            true
        );

        wp_localize_script( self::SCRIPT_HANDLE, 'gs_logo_builder_block', $this->get_localized_data() );

        // block.json supplies the title, icon and description that WordPress.org
        // shows on the plugin page; attributes stay PHP-driven so they cannot
        // drift from the shortcode builder defaults.
        register_block_type( GSL_PLUGIN_DIR . 'includes/integrations/assets/gutenberg/builder-block', [
            'editor_script'   => self::SCRIPT_HANDLE,
            'attributes'      => $this->get_block_attributes_schema(),
            'render_callback' => [ $this, 'render_block' ],
        ] );

    }

    /**
     * Inspector UI styles belong on the parent admin document (sidebar), not
     * only in the iframed canvas.
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_style( self::SCRIPT_HANDLE . '-editor' );
    }

    /**
     * Load the public slider assets into the iframed editor canvas so the
     * ServerSideRender preview can be revealed and initialised.
     */
    public function enqueue_block_assets() {

        if ( ! is_admin() ) {
            return;
        }

        plugin()->scripts->wp_enqueue_style_all( 'public', [ 'gs-logo-divi-public' ] );
        plugin()->scripts->wp_enqueue_script_all( 'public' );

        // Markup ships with opacity:0 until .gs_logo__loaded is added. Force it
        // visible in the editor so a missed reprocess still leaves a usable preview.
        wp_add_inline_style( 'gs-logo-public', $this->get_canvas_preview_css() );

    }

    /**
     * Block attribute schema derived from the shortcode builder defaults, so the
     * block and the builder can never drift apart.
     */
    public function get_block_attributes_schema() {

        $attributes = [
            'blockId' => [
                'type'    => 'string',
                'default' => ''
            ],
            'align' => [
                'type'    => 'string',
                'default' => 'wide'
            ]
        ];

        foreach ( plugin()->builder->get_shortcode_default_settings() as $setting_key => $default_value ) {
            $attributes[ $setting_key ] = $this->get_attribute_schema( $setting_key, $default_value );
        }

        return $attributes;
    }

    /**
     * Map a single builder default to a Gutenberg attribute definition.
     */
    protected function get_attribute_schema( $setting_key, $default_value ) {

        // Nested device visibility structure: initial / popup / panel groups.
        if ( 'visibility_settings' === $setting_key ) {
            return [
                'type'    => 'object',
                'default' => $default_value
            ];
        }

        // Taxonomy include / exclude lists.
        if ( is_array( $default_value ) ) {
            return [
                'type'    => 'array',
                'default' => $default_value
            ];
        }

        if ( is_int( $default_value ) || is_float( $default_value ) ) {
            return [
                'type'    => 'number',
                'default' => $default_value
            ];
        }

        return [
            'type'    => 'string',
            'default' => (string) $default_value
        ];
    }

    /**
     * Render the block through the regular shortcode pipeline.
     */
    public function render_block( $block_attributes ) {

        $settings = plugin()->builder->validate_shortcode_settings( (array) $block_attributes );
        $settings = $this->sanitize_premium_select_values( $settings );

        $instance_key = $this->get_instance_key( $block_attributes );

        // The AJAX filter / load more / pagination handlers resolve a non numeric
        // shortcode id through a transient, so refresh it on every render.
        set_transient( $instance_key, $settings, DAY_IN_SECONDS );

        return plugin()->shortcode->register_gslogo_shortcode_builder([
            'id'       => $instance_key,
            'settings' => $settings
        ]);
    }

    /**
     * Snap Pro-only select values back to a free option when the Pro plugin is
     * inactive or the license is invalid, so the preview never loads missing scripts.
     */
    protected function sanitize_premium_select_values( $settings ) {

        $options = plugin()->builder->get_shortcode_default_options();

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

            if ( ! isset( $settings[ $setting_key ] ) || empty( $options[ $setting_key ] ) || ! is_array( $options[ $setting_key ] ) ) {
                continue;
            }

            $is_premium = false;
            $free_value = null;

            foreach ( $options[ $setting_key ] as $item ) {

                if ( ! isset( $item['value'] ) ) {
                    continue;
                }

                if ( null === $free_value && empty( $item['pro'] ) ) {
                    $free_value = $item['value'];
                }

                if ( (string) $item['value'] === (string) $settings[ $setting_key ] && ! empty( $item['pro'] ) ) {
                    $is_premium = true;
                }
            }

            if ( $is_premium && null !== $free_value ) {
                $settings[ $setting_key ] = $free_value;
            }
        }

        return $settings;
    }

    /**
     * Stable, non numeric key for this block instance.
     */
    protected function get_instance_key( $block_attributes ) {

        $block_id = ! empty( $block_attributes['blockId'] ) ? sanitize_key( $block_attributes['blockId'] ) : '';

        if ( empty( $block_id ) ) {
            $block_id = self::INSTANCE_PREFIX . md5( maybe_serialize( $block_attributes ) );
        }

        // A numeric key would be treated as a saved shortcode id.
        if ( is_numeric( $block_id ) ) {
            $block_id = self::INSTANCE_PREFIX . $block_id;
        }

        return $block_id;
    }

    /**
     * Everything the editor UI needs, reusing the builder's own data providers.
     */
    public function get_localized_data() {

        $builder = plugin()->builder;

        return [
            'settings'                     => $builder->get_shortcode_default_settings(),
            'options'                      => $builder->get_shortcode_default_options(),
            'translations'                 => $builder->get_translation_srtings(),
            'taxonomy_settings'            => $builder->_get_taxonomy_settings( false ),
            'theme_visibility_fields'      => $builder->get_theme_visibility_fields(),
            'overlay_visibility_fields'    => $builder->get_overlay_visibility_fields(),
            'popup_style_visibility_fields' => $builder->get_popup_style_visibility_fields(),
            'panel_style_visibility_fields' => $builder->get_panel_style_visibility_fields(),
            'visibility_legacy_key_map'    => $builder->get_visibility_legacy_key_map(),
            'visibility_translation_keys'  => $builder->get_visibility_field_translation_keys(),
            'is_pro_active'                => wp_validate_boolean( is_pro_active() ),
            'is_pro_license_valid'         => wp_validate_boolean( is_gs_logo_pro_valid() ),
            'instance_prefix'              => self::INSTANCE_PREFIX,
            'labels'                       => [
                'block_title'       => __( 'GS Logo Slider Builder', 'gslogo' ),
                'block_description' => __( 'Build a logo slider, grid, list, or table with all layout and style options.', 'gslogo' ),
                'premium_notice'    => __( 'Available in the premium version.', 'gslogo' ),
                'premium_alert'     => __( 'This is a premium feature. Please upgrade the plan.', 'gslogo' ),
                'premium_license_alert' => __( 'Please activate your GS Logo Slider Pro license to use this feature.', 'gslogo' ),
                'include_terms'     => __( 'Include', 'gslogo' ),
                'exclude_terms'     => __( 'Exclude', 'gslogo' ),
                'filter_panel'      => __( 'Filter & Pagination', 'gslogo' ),
                'slider_panel'      => __( 'Slider & Autoplay', 'gslogo' ),
                'content_panel'     => __( 'Title & Content', 'gslogo' ),
                'layout_panel'      => __( 'Layout', 'gslogo' ),
                'table_headings'    => __( 'Table Headings', 'gslogo' ),
                'hexagon_colors'    => __( 'Hexagon Gradient', 'gslogo' ),
                'border_colors'     => __( 'Border Shadow', 'gslogo' ),
                'tooltip_colors'    => __( 'Tooltip Colors', 'gslogo' ),
                'limit_type'        => __( 'Limit By', 'gslogo' ),
                'custom_width'      => __( 'Width', 'gslogo' ),
                'custom_height'     => __( 'Height', 'gslogo' ),
                'border_thickness'  => __( 'Thickness', 'gslogo' ),
                'border_type'       => __( 'Type', 'gslogo' ),
                'border_color'      => __( 'Color', 'gslogo' ),
                'shadow_x'          => __( 'Offset X', 'gslogo' ),
                'shadow_y'          => __( 'Offset Y', 'gslogo' ),
                'shadow_blur'       => __( 'Blur', 'gslogo' ),
                'shadow_spread'     => __( 'Spread', 'gslogo' ),
                'radius_top'        => __( 'Top', 'gslogo' ),
                'radius_right'      => __( 'Right', 'gslogo' ),
                'radius_bottom'     => __( 'Bottom', 'gslogo' ),
                'radius_left'       => __( 'Left', 'gslogo' ),
            ]
        ];
    }

    /**
     * Inspector sidebar styles (parent admin document).
     */
    public function get_inspector_css() {

        ob_start(); ?>

        /* Keep the four builder tabs inside the narrow inspector width. */
        .gslogo-builder-block--tabs {
            max-width: 100%;
            overflow-x: hidden;
        }

        .gslogo-builder-block--tabs .components-tab-panel__tabs {
            display: flex !important;
            flex-wrap: wrap;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow-x: hidden;
            border-bottom: 1px solid #e0e0e0;
        }

        .gslogo-builder-block--tabs .components-tab-panel__tabs-item,
        .gslogo-builder-block--tabs .components-tab-panel__tabs .components-button {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            max-width: 100%;
            justify-content: center;
            padding: 8px 2px !important;
            font-size: 11px !important;
            line-height: 1.25;
            white-space: normal !important;
            text-align: center;
            height: auto !important;
        }

        .gslogo-builder-block--tabs .components-tab-panel__tab-content {
            max-width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        /* Wide native controls (border / box) must not stretch the sidebar. */
        .gslogo-builder-block--tabs .components-panel__body,
        .gslogo-builder-block--tabs .components-base-control,
        .gslogo-builder-block--tabs .components-input-control,
        .gslogo-builder-block--tabs .components-border-control,
        .gslogo-builder-block--tabs .components-border-box-control,
        .gslogo-builder-block--tabs .components-box-control,
        .gslogo-builder-block--tabs .components-unit-control,
        .gslogo-builder-block--tabs .components-select-control {
            max-width: 100%;
            box-sizing: border-box;
        }

        .gslogo-builder-block--tabs .components-border-control__inner-wrapper,
        .gslogo-builder-block--tabs .components-border-box-control__wrapper,
        .gslogo-builder-block--tabs .components-border-box-control__header,
        .gslogo-builder-block--tabs .components-flex,
        .gslogo-builder-block--tabs .components-h-stack,
        .gslogo-builder-block--tabs .components-v-stack {
            max-width: 100%;
            min-width: 0 !important;
            flex-wrap: wrap;
        }

        .gslogo-builder-block--tabs .components-border-control {
            width: 100%;
        }

        /* Custom selects must fill the inspector, not grow to option label length. */
        .gslogo-builder-block--tabs .components-select-control,
        .gslogo-builder-block--tabs .components-select-control .components-base-control__field {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .gslogo-builder-block--tabs .components-select-control select,
        .gslogo-builder-block--tabs select.components-select-control__input {
            display: block;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .gslogo-builder-block--premium {
            margin: -8px 0 16px;
            font-size: 12px;
            color: #b26b00;
        }

        /* Premium select choices when Pro / license is locked (still clickable). */
        .gslogo-builder-block--tabs select option.gslogo-builder-block--premium-option {
            background-color: #e2e4e7;
            color: #757575;
        }

        .gslogo-builder-block--locked > *:first-child {
            pointer-events: none;
            opacity: 0.6;
        }

        .gslogo-builder-block--devices {
            display: flex;
            flex-wrap: wrap;
            gap: 0 16px;
        }

        .gslogo-builder-block--devices .components-base-control {
            margin-bottom: 4px;
        }

        .gslogo-builder-block--group {
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .gslogo-builder-block--group:last-child {
            border-bottom: 0;
        }

        .gslogo-builder-block--group-title {
            margin: 0 0 8px;
            font-weight: 600;
        }

        .gslogo-builder-block--inline {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-width: 100%;
        }

        .gslogo-builder-block--inline > * {
            flex: 1 1 0;
            min-width: 0;
        }

        <?php return ob_get_clean();
    }

    /**
     * Canvas styles for the iframed editor preview.
     */
    public function get_canvas_preview_css() {

        return '
            .gs_logo_area {
                opacity: 1 !important;
                visibility: visible !important;
            }

            /* WP core: .wp-block img:not([draggable]) { pointer-events: none; } */
            .wp-block .gs_logo_area img {
                pointer-events: auto !important;
            }
        ';
    }

}
