/**
 * Access to the data localized by Integration_Gutenberg_Builder.
 *
 * Everything the editor needs (defaults, select options, translations,
 * taxonomy settings, visibility maps) comes from the shortcode builder's own
 * PHP providers, so the block never keeps a second copy of the schema.
 */

export const blockData = window.gs_logo_builder_block || {};

export const BLOCK_NAME = 'gslogo/logo-builder';

export function translate( key, fallback ) {

	const translations = blockData.translations || {};

	if ( translations[ key ] ) return translations[ key ];

	return typeof fallback === 'string' ? fallback : key;
}

export function label( key ) {

	const labels = blockData.labels || {};

	return labels[ key ] || '';
}

function isProOption( option ) {
	return option.type === 'pro' || !! option.pro;
}

/**
 * Select options for a setting. Premium rows stay in the list and link to the pricing page.
 */
export function fieldOptions( settingKey ) {

	return rawOptions( settingKey ).map( function( option ) {

		const premiumLocked = isProOption( option ) && ! isProActive();

		return {
			label: premiumLocked ? option.label + ' - [PRO]' : option.label,
			text: option.label,
			value: String( option.value ),
			premiumLocked: premiumLocked
		};
	} );
}

/**
 * Whether a select value is locked behind Pro / a valid license.
 * Uses the same `pro` flag the shortcode builder stamps on options.
 */
export function isPremiumOption( settingKey, value ) {

	if ( isProActive() ) return false;

	return rawOptions( settingKey ).some( function( item ) {
		return String( item.value ) === String( value ) && isProOption( item );
	} );
}

export function premiumPageUrl() {
	return blockData.premium_url || 'https://www.gsplugins.com/product/gs-logo-slider/#pricing';
}

export function premiumAlertMessage() {

	// Pro installed but license not activated / invalid.
	if ( isProPluginActive() && ! isProLicenseValid() ) {
		return label( 'premium_license_alert' ) || 'Please activate your GS Logo Slider Pro license to use this feature.';
	}

	// Pro plugin not installed / not active.
	return label( 'premium_alert' ) || 'This is a premium feature. Please upgrade the plan.';
}

/**
 * Raw option list, keeping the original value types (term IDs stay numeric).
 */
export function rawOptions( settingKey ) {

	const options = blockData.options || {};

	return Array.isArray( options[ settingKey ] ) ? options[ settingKey ] : [];
}

export function settingDefaults() {
	return blockData.settings || {};
}

export function settingDefault( settingKey ) {

	const defaults = settingDefaults();

	return typeof defaults[ settingKey ] !== 'undefined' ? defaults[ settingKey ] : '';
}

export function taxonomySettings() {
	return blockData.taxonomy_settings || {};
}

export function isProPluginActive() {
	return !! blockData.is_pro_active;
}

export function isProLicenseValid() {
	return !! blockData.is_pro_license_valid;
}

/**
 * Full Pro access: plugin active and license valid.
 * Used to unlock premium fields in the inspector.
 */
export function isProActive() {
	return isProPluginActive() && isProLicenseValid();
}

export function instancePrefix() {
	return blockData.instance_prefix || 'gslogo_block_';
}

/**
 * The builder stores booleans as the strings 'on' / 'off'.
 */
export function isOn( value ) {
	return value === 'on' || value === true || value === 1 || value === '1';
}

export function toOnOff( checked ) {
	return checked ? 'on' : 'off';
}

export function isOneOf( value, allowed ) {
	return allowed.indexOf( value ) !== -1;
}
