<?php

namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Integration Class
class Integration_Elementor {

    private static $_instance = null;
    
    public static function get_instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
        
    }

    public function __construct() {

        add_action( 'elementor/widgets/register', [ $this, 'register_elementor_widget' ] );
        add_action( 'elementor/controls/register', [ $this, 'register_elementor_controls' ] );
        add_action( 'elementor/elements/categories_registered', [$this, 'add_elementor_widget_category'] );
        
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'print_elementor_editor_scripts' ] );
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_elementor_editor_scripts' ] );
        add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'print_elementor_editor_styles' ] );

        add_action( 'elementor/preview/enqueue_styles', [ $this, 'print_elementor_preview_styles' ] );
        add_action( 'elementor/preview/enqueue_scripts', [ $this, 'print_elementor_preview_scripts' ] );
        
    }

    public function register_elementor_controls( $controls_manager ) {

        require_once GSL_PLUGIN_DIR . 'includes/integrations/elementor-control-visibility-devices.php';

        $control = new Control_Visibility_Devices();

        if ( method_exists( $controls_manager, 'register' ) ) {
            $controls_manager->register( $control );
        } else {
            $controls_manager->register_control( $control->get_type(), $control );
        }

    }

    public function register_elementor_widget( $widgets_manager ) {

        load_elementor_widget_class();
        $widgets_manager->register( new Elementor_Widget() );

        if ( apply_filters( 'gs_logo_integration_elementor_builder', true ) ) {
            require_once GSL_PLUGIN_DIR . 'includes/integrations/elementor-builder-widget.php';
            $widgets_manager->register( new Elementor_Builder_Widget() );
        }

    }

    public function add_elementor_widget_category( $elements_manager ) {

        $elements_manager->add_category(
            'gs-plugins',
            [
                'title' => 'GS Plugins',
                'icon' => 'fa fa-plug',
            ]
        );
    
    }

    public function print_elementor_editor_scripts() {

        ?>
        <script>
            
            window.onload = function() {

                elementor.hooks.addAction( 'panel/open_editor/widget/gs-logo-slider', function( panel, model, view ) {

                    var $shortcode_field = jQuery('.elementor-control-gs_logo_slider_shortcode .elementor-control-input-wrapper select');
                    var $edit_link = jQuery('.elementor-control-gs_logo_slider_shortcode .gs-logo-slider-edit-link');
                    var shortcode_id = $shortcode_field.val();
                    var href = $edit_link.attr('href');
                    href = href.substring(0, href.indexOf('/shortcode/')+11);

                    $edit_link.attr( 'href', href + shortcode_id );

                    $shortcode_field.on('change', function() {
                        shortcode_id = jQuery(this).val();
                        $edit_link.attr( 'href', href + shortcode_id );
                    });

                });

            }

        </script>

        <?php

    }

    public function enqueue_elementor_editor_scripts() {

        $premium_notice = __( 'Available in the premium version.', 'gslogo' );

        wp_enqueue_script(
            'gs-logo-elementor-editor',
            GSL_PLUGIN_URI . 'includes/integrations/assets/elementor/elementor-editor.min.js',
            [ 'jquery' ],
            GSL_VERSION,
            true
        );

        wp_localize_script( 'gs-logo-elementor-editor', 'gsLogoElementorBuilder', [
            'is_pro_active'  => wp_validate_boolean( is_pro_active() ),
            'widget_name'    => 'gs-logo-slider-builder',
            'premium_suffix' => ' — ' . $premium_notice,
            'premium_notice' => $premium_notice,
            'premium_title'  => __( 'Premium Feature', 'gslogo' ),
        ] );

    }

    public function print_elementor_editor_styles() {

        $icon = GSL_PLUGIN_URI . 'assets/img/icon-colored.svg';
    
            ?>
    
            <style>

                body #elementor-controls .elementor-control-gs_logo_slider_shortcode .elementor-control-field-description {
                    font-size: 12px;
                    line-height: 1.8;
                }

                body #elementor-panel-elements-wrapper .icon .gs-logo-slider {
                    background: url('<?php echo esc_url_raw( $icon ); ?>') no-repeat center center;
                    background-size: contain;
                    height: 29px;
                    display: block;
                }

                body #elementor-controls .gs-logo-elementor--premium {
                    opacity: 0.75;
                    position: relative;
                    cursor: pointer;
                }

                body #elementor-controls .gs-logo-elementor--premium::after {
                    content: '';
                    position: absolute;
                    inset: 0;
                    z-index: 2;
                    cursor: pointer;
                }

                body .select2-results__option--disabled,
                body .select2-results__option[aria-disabled="true"] {
                    opacity: 0.55;
                    cursor: not-allowed;
                }

                body #elementor-controls .gs-logo-visibility-header-control,
                body #elementor-controls .elementor-control-type-gs_logo_visibility_devices {
                    padding-top: 6px;
                    padding-bottom: 6px;
                }

                body #elementor-controls .gs-logo-visibility-header-control {
                    padding-bottom: 8px;
                    margin-bottom: 2px;
                    border-bottom: 1px solid var(--e-a-border-color, #d5d8dc);
                }

                body #elementor-controls .gs-logo-visibility-devices {
                    display: grid;
                    grid-template-columns: minmax(72px, 1.3fr) repeat(4, minmax(0, 1fr));
                    align-items: center;
                    column-gap: 4px;
                }

                body #elementor-controls .gs-logo-visibility-devices__label {
                    font-size: 12px;
                    font-weight: 500;
                    line-height: 1.3;
                    color: var(--e-a-color-txt, #6d7882);
                    cursor: pointer;
                }

                body #elementor-controls .gs-logo-visibility-devices--header .gs-logo-visibility-devices__label {
                    font-size: 10px;
                    font-weight: 600;
                    color: var(--e-a-color-txt-muted, #a4afb7);
                    cursor: default;
                }

                body #elementor-controls .gs-logo-visibility-devices--header .gs-logo-visibility-devices__col {
                    font-size: 9px;
                    font-weight: 600;
                    line-height: 1.2;
                    text-align: center;
                    color: var(--e-a-color-txt-muted, #a4afb7);
                }

                body #elementor-controls .gs-logo-visibility-devices__item {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0;
                    cursor: pointer;
                }

                body #elementor-controls .gs-logo-visibility-devices__item input[type="checkbox"] {
                    margin: 0;
                    width: 15px;
                    height: 15px;
                    cursor: pointer;
                    accent-color: var(--e-a-color-primary, #93003c);
                }

                body #elementor-controls .gs-logo-visibility-devices__item .screen-reader-text {
                    position: absolute;
                    width: 1px;
                    height: 1px;
                    padding: 0;
                    margin: -1px;
                    overflow: hidden;
                    clip: rect(0, 0, 0, 0);
                    border: 0;
                }

            </style>
    
            <?php

    }

    public function print_elementor_preview_styles() {

        plugin()->scripts->wp_enqueue_style_all( 'public', ['gs-logo-divi-public'] );
        gsLogoAssetGenerator()->enqueue_prefs_custom_css();

        wp_add_inline_style( 'gs-logo-public', '
            .gs_logo_area {
                opacity: 1 !important;
                visibility: visible !important;
            }
        ' );

    }

    public function print_elementor_preview_scripts() {

        plugin()->scripts->wp_enqueue_script_all( 'public' );
        wp_enqueue_script(
            'gs-logo-elementor-preview',
            GSL_PLUGIN_URI . 'includes/integrations/assets/elementor/elementor-preview.min.js',
            [ 'jquery', 'gs-logo-public' ],
            GSL_VERSION,
            true
        );

    }

}

function load_elementor_widget_class() {

    // Elementor Widget Class
    class Elementor_Widget extends \Elementor\Widget_Base {

        public function get_name() {
            return 'gs-logo-slider';
        }

        public function get_title() {
            return __( 'GS Logo Slider', 'gslogo' );
        }

        public function get_icon() {
            return 'gs-logo-slider';
        }

        public function get_categories() {
            return [ 'gs-plugins', 'general' ];
        }

        protected function register_controls() {

            $this->start_controls_section(
                'content_section',
                [
                    'label' => __( 'Content', 'gslogo' ),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'gs_logo_slider_shortcode',
                [
                    'label' => __( 'Logo Shortcode', 'gslogo' ),
                    'description' => $this->get_field_description(),
                    'label_block' => true,
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'options' => $this->get_shortcode_list(),
                    'value' => $this->get_default_item()
                ]
            );

            $this->end_controls_section();

        }

        protected function get_field_description() {
            
            $eidt_link = sprintf( '%s: <a class="gs-logo-slider-edit-link" href="%s" target="_blank">%s</a>',
                __('Edit this shortcode', 'gslogo'),
                admin_url( "edit.php?post_type=gs-logo-slider&page=gs-logo-shortcode#/shortcode/" ),
                __('Edit', 'gslogo')
            );

            $create_link = sprintf( '%s: <a class="gs-logo-slider-create-link" href="%s" target="_blank">%s</a>',
                __('Create new shortcode', 'gslogo'),
                admin_url( 'edit.php?post_type=gs-logo-slider&page=gs-logo-shortcode#/shortcode' ),
                __('Craete', 'gslogo')
            );

            return implode( '<br />', [$eidt_link, $create_link] );

        }

        protected function get_shortcode_list() {

            $shortcodes = get_shortcodes();
    
            if ( !empty($shortcodes) ) {
                return wp_list_pluck( $shortcodes, 'shortcode_name', 'id' );
            }
            
            return [];

        }

        protected function get_default_item() {

            $shortcodes = get_shortcodes();
    
            if ( !empty($shortcodes) ) {
                return $shortcodes[0]['id'];
            }

            return '';

        }

        protected function render() {

            $shortcode_id = $this->get_settings_for_display( 'gs_logo_slider_shortcode' );
    
            if ( empty($shortcode_id) ) $shortcode_id = $this->get_default_item();

            if ( empty($shortcode_id) ) return;
            
            echo do_shortcode( "[gslogo id={$shortcode_id}]" );
        }

    }

}