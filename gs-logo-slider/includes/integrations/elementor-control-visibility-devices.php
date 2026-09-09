<?php

namespace GSLOGO;

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Compact per-device checkbox row for visibility fields.
 *
 * Stores an array of enabled device keys, e.g. ['desktop', 'tablet'].
 */
class Control_Visibility_Devices extends \Elementor\Base_Data_Control {

	public function get_type() {
		return 'gs_logo_visibility_devices';
	}

	public function get_default_value() {
		return [];
	}

	protected function get_default_settings() {
		return [
			'label_block' => true,
			'show_label'  => false,
			'devices'     => [],
			'separator'   => 'none',
		];
	}

	public function content_template() {
		?>
		<div class="gs-logo-visibility-devices">
			<#
			var saved = data.controlValue;
			if ( Array.isArray( saved ) ) {
				// Keep as-is.
			} else if ( saved && typeof saved === 'object' ) {
				saved = _.keys( saved ).filter( function( key ) {
					return saved[ key ] && saved[ key ] !== 'off' && saved[ key ] !== 'false';
				} );
			} else {
				saved = [];
			}
			#>
			<span class="gs-logo-visibility-devices__label">{{{ data.label }}}</span>
			<# _.each( data.devices, function( deviceLabel, deviceKey ) { #>
				<label class="gs-logo-visibility-devices__item" title="{{ deviceLabel }}">
					<input type="checkbox" value="{{ deviceKey }}" <# if ( -1 !== saved.indexOf( deviceKey ) ) { #>checked<# } #> />
					<span class="screen-reader-text">{{{ deviceLabel }}}</span>
				</label>
			<# } ); #>
		</div>
		<?php
	}

}
