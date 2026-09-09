/**
 * Block icon.
 *
 * Same mark as the shortcode picker block. Kept free of SVG ids and filters
 * so both copies can sit in the inserter without clashing.
 */

const React = window.React;

export default function Icon() {

	return (
		<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<rect x="1" y="6" width="4" height="12" rx="1.5" fill="#FC9D7F" />
			<rect x="19" y="6" width="4" height="12" rx="1.5" fill="#EA8BF2" />
			<rect x="6.5" y="3.5" width="11" height="17" rx="2" fill="#6472EF" />
			<circle cx="12" cy="12" r="3" fill="#3F50EB" />
		</svg>
	);
}
