/**
 * GS Logo Slider builder block.
 *
 * A dynamic block that keeps every shortcode builder setting in its own block
 * attributes, so no shortcode has to be saved first. The attribute schema is
 * derived from the same defaults the PHP side registers, which keeps the two in
 * sync automatically.
 */

import { BLOCK_NAME, label as uiLabel, settingDefaults } from './data';
import Edit from './edit';
import Icon from './icon';

const { registerBlockType } = wp.blocks;

/**
 * Same mapping rules as Integration_Gutenberg_Builder::get_attribute_schema().
 */
function buildAttributes() {

	const attributes = {
		blockId: { type: 'string', default: '' },
		align: { type: 'string', default: 'wide' }
	};

	const defaults = settingDefaults();

	Object.keys( defaults ).forEach( function( settingKey ) {

		const defaultValue = defaults[ settingKey ];

		if ( 'visibility_settings' === settingKey ) {
			attributes[ settingKey ] = { type: 'object', default: defaultValue };
			return;
		}

		if ( Array.isArray( defaultValue ) ) {
			attributes[ settingKey ] = { type: 'array', default: defaultValue };
			return;
		}

		if ( 'number' === typeof defaultValue ) {
			attributes[ settingKey ] = { type: 'number', default: defaultValue };
			return;
		}

		attributes[ settingKey ] = { type: 'string', default: String( defaultValue ) };
	} );

	return attributes;
}

registerBlockType( BLOCK_NAME, {

	title: uiLabel( 'block_title' ),
	description: uiLabel( 'block_description' ),
	icon: Icon,
	category: 'widgets',
	keywords: [ 'logo', 'slider', 'carousel', 'grid', 'builder', 'showcase' ],
	supports: {
		align: [ 'wide', 'full' ],
		html: false
	},
	attributes: buildAttributes(),
	edit: Edit

} );
