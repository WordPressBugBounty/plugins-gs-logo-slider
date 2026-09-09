/**
 * Query tab.
 *
 * Ported from the "query_settings" tab of dev/shortcode/pages/shortcode.vue,
 * including the include / exclude split. Which taxonomies show up follows the
 * global taxonomy settings, exactly like the builder.
 */

import { NumberField, SelectField, TermsField } from '../controls';
import { label as uiLabel, taxonomySettings, translate } from '../data';

const React = window.React;

const { PanelBody } = wp.components;

const TAXONOMIES = [
	{ optionsKey: 'category', enableKey: 'enable_category_tax', labelKey: 'category_tax_label' },
	{ optionsKey: 'tag', enableKey: 'enable_tag_tax', labelKey: 'tag_tax_label' },
	{ optionsKey: 'extra_one', enableKey: 'enable_extra_one_tax', labelKey: 'extra_one_tax_label' },
	{ optionsKey: 'extra_two', enableKey: 'enable_extra_two_tax', labelKey: 'extra_two_tax_label' },
	{ optionsKey: 'extra_three', enableKey: 'enable_extra_three_tax', labelKey: 'extra_three_tax_label' },
	{ optionsKey: 'extra_four', enableKey: 'enable_extra_four_tax', labelKey: 'extra_four_tax_label' },
	{ optionsKey: 'extra_five', enableKey: 'enable_extra_five_tax', labelKey: 'extra_five_tax_label' }
];

function enabledTaxonomies() {

	const settings = taxonomySettings();

	return TAXONOMIES.filter( ( taxonomy ) => 'on' === settings[ taxonomy.enableKey ] ).map( ( taxonomy ) => {
		return Object.assign( {}, taxonomy, {
			label: settings[ taxonomy.labelKey ] || taxonomy.optionsKey
		} );
	} );
}

export default function QueryPanels( { attributes, setAttributes } ) {

	const field = { attributes, setAttributes };

	const taxonomies = enabledTaxonomies();

	return (
		<React.Fragment>

			<PanelBody title={ translate( 'query-settings' ) } initialOpen={ true }>

				<NumberField
					{ ...field }
					settingKey="posts"
					label={ translate( 'posts' ) }
					help={ translate( 'posts--help' ) }
				/>

				<SelectField
					{ ...field }
					settingKey="order"
					label={ translate( 'order' ) }
				/>

				<SelectField
					{ ...field }
					settingKey="orderby"
					label={ translate( 'order-by' ) }
				/>

			</PanelBody>

			{ taxonomies.length > 0 && (
				<React.Fragment>

					<PanelBody title={ uiLabel( 'include_terms' ) } initialOpen={ false }>
						{ taxonomies.map( ( taxonomy ) => (
							<TermsField
								key={ 'include_' + taxonomy.optionsKey }
								{ ...field }
								settingKey={ 'include_' + taxonomy.optionsKey }
								optionsKey={ taxonomy.optionsKey }
								label={ taxonomy.label }
								help={ translate( 'include-tax--details' ) }
							/>
						) ) }
					</PanelBody>

					<PanelBody title={ uiLabel( 'exclude_terms' ) } initialOpen={ false }>
						{ taxonomies.map( ( taxonomy ) => (
							<TermsField
								key={ 'exclude_' + taxonomy.optionsKey }
								{ ...field }
								settingKey={ 'exclude_' + taxonomy.optionsKey }
								optionsKey={ taxonomy.optionsKey }
								label={ taxonomy.label }
								help={ translate( 'exclude-tax--details' ) }
							/>
						) ) }
					</PanelBody>

				</React.Fragment>
			) }

		</React.Fragment>
	);
}
