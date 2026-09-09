/**
 * Visibility tab.
 *
 * Ported from the "visibility_settings" tab of dev/shortcode/pages/shortcode.vue:
 * one row per field, four device checkboxes per row, split into the initial
 * view plus the popup and panel overlays.
 */

import { DeviceCheckbox } from '../controls';
import { isOn, translate } from '../data';

import {
	VISIBILITY_DEVICES,
	getInitialVisibilityFieldKeys,
	getPanelVisibilityFieldKeys,
	getPopupVisibilityFieldKeys,
	getVisibilityField,
	updateVisibilityField,
	visibilityFieldLabel
} from '../visibility';

const React = window.React;

const { PanelBody } = wp.components;

function VisibilityGroup( { attributes, setAttributes, group, fieldKeys } ) {

	if ( ! fieldKeys.length ) return null;

	return (
		<React.Fragment>
			{ fieldKeys.map( function( fieldKey ) {

				const fieldValue = getVisibilityField( attributes, group, fieldKey );

				return (
					<div className="gslogo-builder-block--group" key={ group + '_' + fieldKey }>

						<p className="gslogo-builder-block--group-title">{ visibilityFieldLabel( fieldKey ) }</p>

						<div className="gslogo-builder-block--devices">
							{ VISIBILITY_DEVICES.map( ( device ) => (
								<DeviceCheckbox
									key={ device.key }
									label={ translate( device.translationKey ) }
									checked={ fieldValue[ device.key ] }
									onChange={ ( checked ) => setAttributes(
										updateVisibilityField( attributes, group, fieldKey, device.key, checked )
									) }
								/>
							) ) }
						</div>

					</div>
				);
			} ) }
		</React.Fragment>
	);
}

export default function VisibilityPanels( { attributes, setAttributes } ) {

	const group = { attributes, setAttributes };

	const linkingEnabled = isOn( attributes.gs_l_link_logos );

	return (
		<React.Fragment>

			<PanelBody title={ translate( 'visibility-initial-view' ) } initialOpen={ true }>
				<VisibilityGroup
					{ ...group }
					group="initial"
					fieldKeys={ getInitialVisibilityFieldKeys( attributes.gs_l_theme ) }
				/>
			</PanelBody>

			{ linkingEnabled && 'popup' === attributes.gs_logo_link_type && (
				<PanelBody title={ translate( 'visibility-popup' ) } initialOpen={ false }>
					<VisibilityGroup
						{ ...group }
						group="popup"
						fieldKeys={ getPopupVisibilityFieldKeys( attributes.popup_style ) }
					/>
				</PanelBody>
			) }

			{ linkingEnabled && 'panel' === attributes.gs_logo_link_type && (
				<PanelBody title={ translate( 'visibility-panel' ) } initialOpen={ false }>
					<VisibilityGroup
						{ ...group }
						group="panel"
						fieldKeys={ getPanelVisibilityFieldKeys( attributes.panel_style ) }
					/>
				</PanelBody>
			) }

		</React.Fragment>
	);
}
