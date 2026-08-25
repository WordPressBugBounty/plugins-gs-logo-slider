<?php

namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visibility settings helpers for shortcode builder.
 */
trait Visibility_Settings {

	public function get_theme_visibility_fields() {

		$image_only = [
			'logo_image',
		];

		$image_title = [
			'logo_image',
			'logo_title',
		];

		$image_title_cat = [
			'logo_image',
			'logo_title',
			'logo_categories',
		];

		$image_title_content = [
			'logo_image',
			'logo_title',
			'logo_content',
		];

		$image_title_content_excerpt = [
			'logo_image',
			'logo_title',
			'logo_content',
			'logo_excerpt',
		];

		$image_title_cat_content_excerpt = [
			'logo_image',
			'logo_title',
			'logo_categories',
			'logo_content',
			'logo_excerpt',
		];

		return [

			'slider1'             => $image_title_cat,
			'ticker1'             => $image_title_cat,
			'vslider1'            => $image_title_cat,
			'slider_fullwidth'    => $image_title_cat,
			'center'              => $image_title_cat,
			'vwidth'              => $image_title_cat,
			'verticalcenter'      => $image_title_cat,
			'filter1'             => $image_title_cat,
			'filter2'             => $image_title_cat,
			'filter3'             => $image_title_cat,
			'filterlive1'         => $image_title_cat,
			'filterlive2'         => $image_title_cat,
			'filterlive3'         => $image_title_cat,
			'filter-select'       => $image_title_cat,

			'slider-2rows'        => $image_title,

			'slider2'             => $image_title_content,

			'table1'              => $image_title_content_excerpt,
			'table2'              => $image_title_content_excerpt,
			'table3'              => $image_title_content_excerpt,

			'grid1'               => $image_title_cat_content_excerpt,
			'list1'               => $image_title_cat_content_excerpt,
			'grid2'               => $image_title_cat_content_excerpt,
			'grid3'               => $image_title_cat_content_excerpt,
			'list2'               => $image_title_cat_content_excerpt,
			'list3'               => $image_title_cat_content_excerpt,
			'list4'               => $image_title_cat_content_excerpt,
			'filter4'             => $image_title_cat_content_excerpt,
			'verticalticker'      => $image_title_cat_content_excerpt,
			'verticaltickerdown'  => $image_title_cat_content_excerpt,

			'rounded-border'      => $image_only,
			'horizontal-scroll'   => $image_only,
			'3d-circular-slider'  => $image_only,
			'hexagon'             => $image_only,
		];
	}

	public function get_overlay_visibility_fields() {

		return [
			'logo_image',
			'logo_title',
			'logo_categories',
			'logo_website',
			'logo_about',
			'logo_vision',
			'logo_mission',
			'logo_email',
			'logo_address',
			'logo_established',
			'logo_employees',
			'logo_formation',
			'logo_client_since',
			'logo_funding_type',
			'logo_funding_source',
			'logo_social',
			'logo_gallery',
			'logo_video',
			'logo_map',
			'logo_pitch_deck',
			'logo_taxonomies',
		];
	}

	/**
	 * Fields each popup style template actually renders.
	 */
	public function get_popup_style_visibility_fields() {

		$overlay = $this->get_overlay_visibility_fields();

		return [
			'style-01' => $overlay,
			'style-02' => $overlay,
			'style-03' => $overlay,
			'style-04' => $overlay,
			'style-05' => $overlay,
		];
	}

	/**
	 * Fields each panel style template actually renders.
	 */
	public function get_panel_style_visibility_fields() {

		$overlay = $this->get_overlay_visibility_fields();

		return [
			'style-01' => $overlay,
			'style-02' => $overlay,
			'style-03' => $overlay,
			'style-04' => $overlay,
			'style-05' => $overlay,
		];
	}

	public function get_visibility_field_translation_keys() {

		return [
			'logo_image'           => 'visibility-logo-image',
			'logo_title'           => 'visibility-logo-title',
			'logo_categories'      => 'visibility-logo-categories',
			'logo_content'         => 'visibility-logo-content',
			'logo_excerpt'         => 'visibility-logo-excerpt',
			'logo_website'         => 'visibility-logo-website',
			'logo_about'           => 'visibility-logo-about',
			'logo_vision'          => 'visibility-logo-vision',
			'logo_mission'         => 'visibility-logo-mission',
			'logo_email'           => 'visibility-logo-email',
			'logo_address'         => 'visibility-logo-address',
			'logo_established'     => 'visibility-logo-established',
			'logo_employees'       => 'visibility-logo-employees',
			'logo_formation'       => 'visibility-logo-formation',
			'logo_client_since'    => 'visibility-logo-client-since',
			'logo_funding_type'    => 'visibility-logo-funding-type',
			'logo_funding_source'  => 'visibility-logo-funding-source',
			'logo_social'          => 'visibility-logo-social',
			'logo_gallery'         => 'visibility-logo-gallery',
			'logo_video'           => 'visibility-logo-video',
			'logo_map'             => 'visibility-logo-map',
			'logo_pitch_deck'      => 'visibility-logo-pitch-deck',
			'logo_taxonomies'      => 'visibility-logo-taxonomies',
		];
	}

	public function get_visibility_legacy_key_map() {

		return [
			'logo_title'       => 'gs_l_title',
			'logo_categories'  => 'show_cat',
			'logo_content'     => 'gs_l_show_content',
			'logo_excerpt'     => 'gs_l_show_excerpt',
		];
	}

	public function get_visibility_device_defaults( $visible = true ) {

		$visible = wp_validate_boolean( $visible );

		return [
			'desktop'          => $visible,
			'tablet'           => $visible,
			'mobile_landscape' => $visible,
			'mobile'           => $visible,
		];
	}

	public function get_visibility_field_defaults( $field_key, $legacy_settings = [] ) {

		$translation_keys = $this->get_visibility_field_translation_keys();
		$legacy_map       = $this->get_visibility_legacy_key_map();
		$visible          = true;

		if ( isset( $legacy_map[ $field_key ] ) ) {
			$legacy_key = $legacy_map[ $field_key ];
			if ( isset( $legacy_settings[ $legacy_key ] ) ) {
				$visible = ( $legacy_settings[ $legacy_key ] === 'on' || $legacy_settings[ $legacy_key ] === true || $legacy_settings[ $legacy_key ] === 1 || $legacy_settings[ $legacy_key ] === '1' );
			}
		}

		$defaults                    = $this->get_visibility_device_defaults( $visible );
		$defaults['translation_key'] = isset( $translation_keys[ $field_key ] ) ? $translation_keys[ $field_key ] : $field_key;

		return $defaults;
	}

	public function build_visibility_group( $field_keys, $legacy_settings = [] ) {

		$group = [];

		foreach ( (array) $field_keys as $field_key ) {
			$group[ $field_key ] = $this->get_visibility_field_defaults( $field_key, $legacy_settings );
		}

		return $group;
	}

	public function get_visibility_defaults( $theme = '', $legacy_settings = [] ) {

		$theme_fields = $this->get_theme_visibility_fields();

		if ( empty( $theme ) || ! isset( $theme_fields[ $theme ] ) ) {
			$theme = 'slider1';
		}

		$overlay_fields = $this->get_overlay_visibility_fields();

		return [
			'initial' => $this->build_visibility_group( $theme_fields[ $theme ], $legacy_settings ),
			'popup'   => $this->build_visibility_group( $overlay_fields, [] ),
			'panel'   => $this->build_visibility_group( $overlay_fields, [] ),
		];
	}

	public function validate_visibility_group( $settings, $field_defaults ) {

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$validated = [];

		$existing_visible = false;
		foreach ( $settings as $existing_field ) {
			if ( ! is_array( $existing_field ) ) {
				continue;
			}
			if ( ! empty( $existing_field['desktop'] ) || ! empty( $existing_field['tablet'] )
				|| ! empty( $existing_field['mobile_landscape'] ) || ! empty( $existing_field['mobile'] ) ) {
				$existing_visible = true;
				break;
			}
		}
		$seed_new_fields_visible = empty( $settings ) || $existing_visible;

		foreach ( $field_defaults as $field_key => $defaults ) {
			$field        = isset( $settings[ $field_key ] ) && is_array( $settings[ $field_key ] ) ? $settings[ $field_key ] : [];
			$is_new_field = ! isset( $settings[ $field_key ] ) || ! is_array( $settings[ $field_key ] );

			if ( isset( $field['translation_key'] ) ) {
				unset( $field['translation_key'] );
			}

			if ( $is_new_field && ! $seed_new_fields_visible ) {
				$defaults = array_merge( $defaults, $this->get_visibility_device_defaults( false ) );
			}

			$field = shortcode_atts( $defaults, $field );

			$field['desktop']          = wp_validate_boolean( $field['desktop'] );
			$field['tablet']           = wp_validate_boolean( $field['tablet'] );
			$field['mobile_landscape'] = wp_validate_boolean( $field['mobile_landscape'] );
			$field['mobile']           = wp_validate_boolean( $field['mobile'] );
			$field['translation_key']  = $defaults['translation_key'];

			$validated[ $field_key ] = $field;
		}

		// Keep stored keys not in current defaults (e.g. after theme switch).
		foreach ( $settings as $field_key => $field ) {
			if ( isset( $validated[ $field_key ] ) || ! is_array( $field ) ) {
				continue;
			}

			$defaults = $this->get_visibility_field_defaults( $field_key, [] );

			if ( isset( $field['translation_key'] ) ) {
				unset( $field['translation_key'] );
			}

			$field = shortcode_atts( $defaults, $field );

			$field['desktop']          = wp_validate_boolean( $field['desktop'] );
			$field['tablet']           = wp_validate_boolean( $field['tablet'] );
			$field['mobile_landscape'] = wp_validate_boolean( $field['mobile_landscape'] );
			$field['mobile']           = wp_validate_boolean( $field['mobile'] );
			$field['translation_key']  = $defaults['translation_key'];

			$validated[ $field_key ] = $field;
		}

		return $validated;
	}

	public function validate_visibility_settings( $settings, $theme = '', $legacy_settings = [] ) {

		$defaults = $this->get_visibility_defaults( $theme, $legacy_settings );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return [
			'initial' => $this->validate_visibility_group( isset( $settings['initial'] ) ? $settings['initial'] : [], $defaults['initial'] ),
			'popup'   => $this->validate_visibility_group( isset( $settings['popup'] ) ? $settings['popup'] : [], $defaults['popup'] ),
			'panel'   => $this->validate_visibility_group( isset( $settings['panel'] ) ? $settings['panel'] : [], $defaults['panel'] ),
		];
	}

	public function is_visibility_field_on( $field ) {

		if ( ! is_array( $field ) ) {
			return false;
		}

		return ! empty( $field['desktop'] ) || ! empty( $field['tablet'] ) || ! empty( $field['mobile_landscape'] ) || ! empty( $field['mobile'] );
	}

	public function sync_legacy_visibility_keys( $shortcode_settings ) {

		$legacy_map = $this->get_visibility_legacy_key_map();
		$initial    = isset( $shortcode_settings['visibility_settings']['initial'] ) ? $shortcode_settings['visibility_settings']['initial'] : [];

		foreach ( $legacy_map as $field_key => $legacy_key ) {
			if ( ! isset( $initial[ $field_key ] ) ) {
				continue;
			}

			$shortcode_settings[ $legacy_key ] = $this->is_visibility_field_on( $initial[ $field_key ] ) ? 'on' : 'off';
		}

		return $shortcode_settings;
	}
}
