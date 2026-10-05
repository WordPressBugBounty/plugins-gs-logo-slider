/**
 * Visibility tab.
 *
 * Compact field grid: device icons in the header, one checkbox per breakpoint.
 * Clicking the field name toggles every device, matching the YouTube builder.
 */

import { isOn, translate } from '../data';
import {
	VISIBILITY_DEVICES,
	getInitialVisibilityFieldKeys,
	getPanelVisibilityFieldKeys,
	getPopupVisibilityFieldKeys,
	getVisibilityField,
	toggleVisibilityRow,
	updateVisibilityField,
	visibilityFieldLabel
} from '../visibility';

const React = window.React;

const { PanelBody } = wp.components;

const DEVICE_ICONS = {
	desktop: (
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect x="2" y="3" width="20" height="13" rx="2" stroke="currentColor" strokeWidth="2" />
			<path d="M8 21h8M12 16v5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
		</svg>
	),
	tablet: (
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect x="5" y="2" width="14" height="20" rx="2" stroke="currentColor" strokeWidth="2" />
			<circle cx="12" cy="18.5" r="1" fill="currentColor" />
		</svg>
	),
	mobile_landscape: (
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect x="2" y="7" width="20" height="10" rx="2" stroke="currentColor" strokeWidth="2" />
			<circle cx="4.5" cy="12" r="1" fill="currentColor" />
		</svg>
	),
	mobile: (
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect x="7" y="2" width="10" height="20" rx="2" stroke="currentColor" strokeWidth="2" />
			<circle cx="12" cy="18.5" r="1" fill="currentColor" />
		</svg>
	)
};

function VisibilityGrid( { attributes, setAttributes, group, fieldKeys } ) {

	if ( ! fieldKeys.length ) {
		return null;
	}

	return (
		<div className="gslogo-builder-block--visibility">

			<div className="gslogo-builder-block--visibility-row gslogo-builder-block--visibility-head">
				<span className="gslogo-builder-block--visibility-label">{ translate( 'visibility-field' ) }</span>
				{ VISIBILITY_DEVICES.map( function( device ) {
					return (
						<span
							key={ device.key }
							className="gslogo-builder-block--visibility-device"
							title={ translate( device.translationKey ) }
						>
							{ DEVICE_ICONS[ device.key ] }
						</span>
					);
				} ) }
			</div>

			{ fieldKeys.map( function( fieldKey ) {

				const fieldValue = getVisibilityField( attributes, group, fieldKey );
				const fieldLabel = visibilityFieldLabel( fieldKey );

				return (
					<div className="gslogo-builder-block--visibility-row" key={ fieldKey }>

						<button
							type="button"
							className="gslogo-builder-block--visibility-label"
							onClick={ () => setAttributes( toggleVisibilityRow( attributes, group, fieldKey ) ) }
						>
							{ fieldLabel }
						</button>

						{ VISIBILITY_DEVICES.map( function( device ) {
							const deviceLabel = translate( device.translationKey );

							return (
								<label className="gslogo-builder-block--visibility-check" key={ device.key }>
									<input
										type="checkbox"
										checked={ !! fieldValue[ device.key ] }
										aria-label={ fieldLabel + ' ' + deviceLabel }
										onChange={ ( event ) => setAttributes(
											updateVisibilityField( attributes, group, fieldKey, device.key, event.target.checked )
										) }
									/>
									<span />
								</label>
							);
						} ) }

					</div>
				);
			} ) }

		</div>
	);
}

export default function VisibilityPanels( { attributes, setAttributes } ) {

	const group = { attributes, setAttributes };

	const linkingEnabled = isOn( attributes.gs_l_link_logos );

	return (
		<React.Fragment>

			<PanelBody title={ translate( 'visibility-initial-view' ) } initialOpen={ true }>
				<VisibilityGrid
					{ ...group }
					group="initial"
					fieldKeys={ getInitialVisibilityFieldKeys( attributes.gs_l_theme ) }
				/>
			</PanelBody>

			{ linkingEnabled && 'popup' === attributes.gs_logo_link_type && (
				<PanelBody title={ translate( 'visibility-popup' ) } initialOpen={ false }>
					<VisibilityGrid
						{ ...group }
						group="popup"
						fieldKeys={ getPopupVisibilityFieldKeys( attributes.popup_style ) }
					/>
				</PanelBody>
			) }

			{ linkingEnabled && 'panel' === attributes.gs_logo_link_type && (
				<PanelBody title={ translate( 'visibility-panel' ) } initialOpen={ false }>
					<VisibilityGrid
						{ ...group }
						group="panel"
						fieldKeys={ getPanelVisibilityFieldKeys( attributes.panel_style ) }
					/>
				</PanelBody>
			) }

		</React.Fragment>
	);
}
