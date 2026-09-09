/**
 * Style tab.
 *
 * Ported from the "style_settings" tab of dev/shortcode/pages/shortcode.vue.
 */

import { NumberField, SelectField } from '../controls';
import { isOneOf, label as uiLabel, translate } from '../data';
import { isCarousel, isList, isListTicker, isTable, isVariableWidth } from '../theme-groups';

const React = window.React;

const { PanelBody } = wp.components;

export default function StylePanels( { attributes, setAttributes } ) {

	const field = { attributes, setAttributes };

	const theme = attributes.gs_l_theme;

	// The per device logo counts only apply to the themes that lay logos out in
	// a track, so lists, tables and the variable width slider opt out.
	const showLogoCounts = ! isList( theme ) && ! isTable( theme ) && ! isVariableWidth( theme );

	return (
		<React.Fragment>

			<PanelBody title={ translate( 'image_filter' ) } initialOpen={ true }>

				<SelectField
					{ ...field }
					settingKey="image_filter"
					label={ translate( 'image_filter' ) }
					help={ translate( 'image_filter__help' ) }
				/>

				<SelectField
					{ ...field }
					settingKey="hover_image_filter"
					label={ translate( 'hover_image_filter' ) }
					help={ translate( 'hover_image_filter__help' ) }
				/>

			</PanelBody>

			<PanelBody title={ uiLabel( 'layout_panel' ) } initialOpen={ true }>

				{ isOneOf( theme, [ 'grid1', 'grid2', 'grid3' ] ) && (
					<SelectField
						{ ...field }
						settingKey="gs_l_align"
						label={ translate( 'gs-l-align' ) }
						help={ translate( 'gs-l-align--help' ) }
					/>
				) }

				{ ! isTable( theme ) && ! isListTicker( theme ) && (
					<NumberField
						{ ...field }
						settingKey="gs_l_margin"
						label={ translate( 'gs-l-margin' ) }
						help={ translate( 'gs-l-margin--help' ) }
						min={ 0 }
						max={ 50 }
					/>
				) }

				{ showLogoCounts && (
					<React.Fragment>
						<NumberField
							{ ...field }
							settingKey="gs_l_min_logo"
							label={ translate( 'gs-l-min-logo' ) }
							help={ translate( 'gs-l-min-logo--help' ) }
							min={ 1 }
							max={ 10 }
						/>
						<NumberField
							{ ...field }
							settingKey="gs_l_tab_logo"
							label={ translate( 'gs-l-tab-logo' ) }
							help={ translate( 'gs-l-tab-logo--help' ) }
							min={ 1 }
							max={ 10 }
						/>
						<NumberField
							{ ...field }
							settingKey="gs_l_mob_logo"
							label={ translate( 'gs-l-mob-logo' ) }
							help={ translate( 'gs-l-mob-logo--help' ) }
							min={ 1 }
							max={ 10 }
						/>
					</React.Fragment>
				) }

				{ isCarousel( theme ) && (
					<NumberField
						{ ...field }
						settingKey="gs_l_move_logo"
						label={ translate( 'gs-l-move-logo' ) }
						help={ translate( 'gs-l-move-logo--help' ) }
						min={ 1 }
						max={ 10 }
					/>
				) }

				<SelectField
					{ ...field }
					settingKey="gs_l_clkable"
					label={ translate( 'gs-l-clkable' ) }
					help={ translate( 'gs-l-clkable--help' ) }
				/>

			</PanelBody>

		</React.Fragment>
	);
}
