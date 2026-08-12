<?php
/**
 * Divi 5 Integration – GS Logo Slider module.
 *
 * @package GSLOGO
 */

namespace GSLOGO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots the native Divi 5 Logo Slider module.
 */
class Integration_Divi {

	/**
	 * Singleton instance.
	 *
	 * @var Integration_Divi|null
	 */
	private static $_instance = null;

	/**
	 * Get singleton.
	 *
	 * @return Integration_Divi
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Divi 5 only — no legacy Divi 4 module registration.
		add_action( 'divi_module_library_modules_dependency_tree', [ $this, 'register_module' ] );
		add_action( 'divi_visual_builder_assets_before_enqueue_scripts', [ $this, 'enqueue_visual_builder_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets_in_builder' ] );
	}

	/**
	 * Whether Divi 5 builder APIs are available.
	 *
	 * @return bool
	 */
	public static function is_divi_5() {
		return function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled();
	}

	/**
	 * Register module into Divi 5 dependency tree.
	 *
	 * @param object $dependency_tree Divi dependency tree.
	 * @return void
	 */
	public function register_module( $dependency_tree ) {
		if ( ! interface_exists( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' ) ) {
			return;
		}

		require_once GSL_PLUGIN_DIR . 'includes/integrations/divi/LogoSliderModule.php';

		$dependency_tree->add_dependency( new \GSLOGO\Divi\LogoSliderModule() );
	}

	/**
	 * Register REST routes for Visual Builder preview.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// LogoSliderModule implements Divi 5 DependencyInterface — skip when
		// the framework is not loaded (e.g. Gutenberg page edit / Divi 4).
		if ( ! interface_exists( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' ) ) {
			return;
		}

		require_once GSL_PLUGIN_DIR . 'includes/integrations/divi/LogoSliderModule.php';
		require_once GSL_PLUGIN_DIR . 'includes/integrations/divi/LogoSliderController.php';

		\GSLOGO\Divi\LogoSliderController::register_routes();
	}

	/**
	 * Enqueue public CSS/JS inside Visual Builder so logo layouts preview correctly.
	 *
	 * @return void
	 */
	public function enqueue_public_assets_in_builder() {
		if ( ! function_exists( 'et_core_is_fb_enabled' ) || ! et_core_is_fb_enabled() ) {
			return;
		}

		if ( ! self::is_divi_5() ) {
			return;
		}

		plugin()->scripts->wp_enqueue_style_all( 'public' );
		plugin()->scripts->wp_enqueue_script_all( 'public' );

		gsLogoAssetGenerator()->enqueue_prefs_custom_css();
	}

	/**
	 * Enqueue Divi 5 Visual Builder package.
	 *
	 * @return void
	 */
	public function enqueue_visual_builder_assets() {
		if ( ! function_exists( 'et_core_is_fb_enabled' ) || ! et_core_is_fb_enabled() ) {
			return;
		}

		if ( ! self::is_divi_5() ) {
			return;
		}

		if ( ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' ) ) {
			return;
		}

		require_once GSL_PLUGIN_DIR . 'includes/integrations/divi/LogoSliderModule.php';
		require_once GSL_PLUGIN_DIR . 'includes/integrations/divi/LogoSliderController.php';

		$script_path = GSL_PLUGIN_DIR . 'includes/integrations/divi/visual-builder/build/gs-logo-divi.js';
		$script_uri  = GSL_PLUGIN_URI . 'includes/integrations/divi/visual-builder/build/gs-logo-divi.js';

		if ( ! file_exists( $script_path ) ) {
			return;
		}

		$shortcode_options = \GSLOGO\Divi\LogoSliderModule::get_shortcode_options();
		$default_id        = (string) \GSLOGO\Divi\LogoSliderModule::get_default_shortcode_id();
		$icon              = GSL_PLUGIN_URI . 'assets/img/icon.svg';

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'    => 'gs-logo-divi-vb',
				'version' => GSL_VERSION,
				'script'  => [
					'src'                => $script_uri,
					'deps'               => [
						'react',
						'jquery',
						'divi-module',
						'divi-module-library',
						'divi-vendor-wp-hooks',
						'divi-rest',
					],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
					'data_app_window'    => [
						'shortcodeOptions' => $shortcode_options,
						'defaultShortcode' => $default_id,
						'iconUrl'          => $icon,
						'restNamespace'    => \GSLOGO\Divi\LogoSliderController::NAMESPACE,
						'i18n'             => [
							'loading'     => __( 'Loading logo slider…', 'gslogo' ),
							'empty'       => __( 'Please select a GS Logo shortcode.', 'gslogo' ),
							'noShortcode' => __( 'No shortcodes found. Create one in GS Logo first.', 'gslogo' ),
						],
					],
				],
			]
		);
	}
}
