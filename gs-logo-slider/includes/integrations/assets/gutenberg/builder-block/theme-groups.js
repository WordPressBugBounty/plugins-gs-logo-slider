/**
 * Theme grouping helpers.
 *
 * Direct port of the shortcode builder's theme predicates
 * (dev/shortcode/pages/shortcode.vue) so a field shows up in the block for
 * exactly the same themes as it does in the builder.
 */

import { isOn, isOneOf } from './data';

export function isListTicker( theme ) {
	return isOneOf( theme, [ 'verticalticker', 'verticaltickerdown' ] );
}

export function isTicker( theme ) {
	return isOneOf( theme, [ 'ticker1' ] ) || isListTicker( theme );
}

export function isGrid( theme ) {
	return isOneOf( theme, [ 'grid1', 'grid2', 'grid3', 'rounded-border' ] );
}

export function isList( theme ) {
	return isOneOf( theme, [ 'list1', 'list2', 'list3', 'list4' ] );
}

export function isTable( theme ) {
	return isOneOf( theme, [ 'table1', 'table2', 'table3' ] );
}

export function isVerticalCarousel( theme ) {
	return isOneOf( theme, [ 'vslider1' ] );
}

export function isHorizontalCarousel( theme ) {
	return isOneOf( theme, [ 'slider1', 'slider_fullwidth', 'center', 'vwidth', 'verticalcenter', 'slider-2rows' ] );
}

export function isCarousel( theme ) {
	return isVerticalCarousel( theme ) || isHorizontalCarousel( theme );
}

export function isVariableWidth( theme ) {
	return isOneOf( theme, [ 'vwidth' ] );
}

/**
 * Pagination settings only apply to grid / list themes, and are hidden when the
 * non AJAX filter is in charge of the query.
 */
export function isDisplayPaginationSettings( attributes ) {

	const theme = attributes.gs_l_theme;

	if ( ! isGrid( theme ) && ! isList( theme ) ) return false;

	if ( isOn( attributes.filter_enabled ) && attributes.gs_logo_filter_type === 'normal-filter' ) return false;

	return true;
}
