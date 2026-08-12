<?php
/**
 * Divi 5 Module: GS Logo Slider
 *
 * @package GSLOGO
 */

namespace GSLOGO\Divi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Native Divi 5 Logo Slider module.
 */
class LogoSliderModule implements DependencyInterface {

	/**
	 * Module block name.
	 *
	 * @var string
	 */
	const NAME = 'gslogo/logo-slider';

	/**
	 * Load / register the module.
	 *
	 * @return void
	 */
	public function load() {
		add_action( 'init', [ self::class, 'register_module' ] );
	}

	/**
	 * Register module with Divi 5.
	 *
	 * @return void
	 */
	public static function register_module() {
		$module_json_folder_path = GSL_PLUGIN_DIR . 'includes/integrations/divi/visual-builder/src';

		ModuleRegistration::register_module(
			$module_json_folder_path,
			[
				'render_callback' => [ self::class, 'render_callback' ],
			]
		);
	}

	/**
	 * Get shortcode ID from module attributes.
	 *
	 * @param array $attrs Module attributes.
	 * @return int
	 */
	public static function get_shortcode_id_from_attrs( array $attrs ) {
		$shortcode_id = $attrs['shortcode']['innerContent']['desktop']['value'] ?? '';

		if ( empty( $shortcode_id ) ) {
			$shortcode_id = self::get_default_shortcode_id();
		}

		return absint( $shortcode_id );
	}

	/**
	 * Default shortcode ID (first available).
	 *
	 * @return int|string
	 */
	public static function get_default_shortcode_id() {
		$shortcodes = \GSLOGO\get_shortcodes();

		if ( ! empty( $shortcodes ) && ! empty( $shortcodes[0]['id'] ) ) {
			return $shortcodes[0]['id'];
		}

		return '';
	}

	/**
	 * Shortcode options for select field (id => label).
	 *
	 * @return array
	 */
	public static function get_shortcode_options() {
		$shortcodes = \GSLOGO\get_shortcodes();
		$options    = [];

		if ( empty( $shortcodes ) ) {
			return $options;
		}

		foreach ( $shortcodes as $shortcode ) {
			$id    = (string) ( $shortcode['id'] ?? '' );
			$label = $shortcode['shortcode_name'] ?? $id;

			if ( '' === $id ) {
				continue;
			}

			$options[ $id ] = [
				'label' => $label,
			];
		}

		return $options;
	}

	/**
	 * Render shortcode HTML by ID.
	 *
	 * @param int $shortcode_id Shortcode ID.
	 * @return string
	 */
	public static function render_shortcode_html( $shortcode_id ) {
		$shortcode_id = absint( $shortcode_id );

		if ( ! $shortcode_id ) {
			return sprintf(
				'<div class="gs-logo-divi-empty">%s</div>',
				esc_html__( 'Please select a GS Logo shortcode.', 'gslogo' )
			);
		}

		return do_shortcode( sprintf( '[gslogo id="%u" /]', $shortcode_id ) );
	}

	/**
	 * Module classnames (VB + FE).
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public static function module_classnames( array $args ) {
		$classnames_instance = $args['classnamesInstance'];
		$attrs               = $args['attrs'] ?? [];

		$classnames_instance->add(
			ElementClassnames::classnames(
				[
					'attrs' => $attrs['module']['decoration'] ?? [],
				]
			)
		);
	}

	/**
	 * Module script data.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public static function module_script_data( array $args ) {
		$elements = $args['elements'];

		$elements->script_data(
			[
				'attrName' => 'module',
			]
		);
	}

	/**
	 * Module styles.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public static function module_styles( array $args ) {
		$elements = $args['elements'];
		$settings = $args['settings'] ?? [];

		Style::add(
			[
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => [
					$elements->style(
						[
							'attrName'   => 'module',
							'styleProps' => [
								'disabledOn' => [
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								],
							],
						]
					),
				],
			]
		);
	}

	/**
	 * Frontend render callback.
	 *
	 * @param array          $attrs    Block attributes.
	 * @param string         $content  Block content.
	 * @param WP_Block       $block    Parsed block.
	 * @param ModuleElements $elements Module elements helper.
	 * @return string
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		$shortcode_id   = self::get_shortcode_id_from_attrs( is_array( $attrs ) ? $attrs : [] );
		$shortcode_html = self::render_shortcode_html( $shortcode_id );

		$module_inner = HTMLUtility::render(
			[
				'tag'               => 'div',
				'attributes'        => [
					'class' => 'et_pb_module_inner gs-logo-slider',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $shortcode_html,
			]
		);

		$module_elements = $elements->style_components(
			[
				'attrName' => 'module',
			]
		);

		$parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render(
			[
				'orderIndex'          => $block->parsed_block['orderIndex'] ?? 0,
				'storeInstance'       => $block->parsed_block['storeInstance'] ?? null,
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $block->parsed_block['id'] ?? '',
				'name'                => $block->block_type->name,
				'moduleClassName'     => 'gs_logo_slider',
				'moduleCategory'      => $block->block_type->category,
				'classnamesFunction'  => [ self::class, 'module_classnames' ],
				'stylesComponent'     => [ self::class, 'module_styles' ],
				'scriptDataComponent' => [ self::class, 'module_script_data' ],
				'parentAttrs'         => $parent_attrs,
				'parentId'            => $parent->id ?? '',
				'parentName'          => $parent->blockName ?? '',
				'children'            => $module_elements . $module_inner,
			]
		);
	}
}
