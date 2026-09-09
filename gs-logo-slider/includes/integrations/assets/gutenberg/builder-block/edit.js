/**
 * Block edit component.
 *
 * All settings live in the Inspector sidebar, grouped in the same four tabs the
 * shortcode builder uses. The canvas shows the real shortcode output, rendered
 * server side from the current attributes.
 */

import { BLOCK_NAME, instancePrefix, translate } from './data';

import GeneralPanels from './panels/general';
import StylePanels from './panels/style';
import QueryPanels from './panels/query';
import VisibilityPanels from './panels/visibility';

const React = window.React;

const { useEffect, useRef } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { TabPanel } = wp.components;
const ServerSideRender = wp.serverSideRender;

const TABS = [
	{ name: 'general', translationKey: 'general-settings', Panels: GeneralPanels },
	{ name: 'style', translationKey: 'style-settings', Panels: StylePanels },
	{ name: 'query', translationKey: 'query-settings', Panels: QueryPanels },
	{ name: 'visibility', translationKey: 'visibility-settings', Panels: VisibilityPanels }
].map( function( tab ) {
	return {
		name: tab.name,
		title: translate( tab.translationKey ),
		Panels: tab.Panels
	};
} );

/**
 * Which editor block currently owns a given instance key. Lets a duplicated
 * block notice that its copied key belongs to another block and claim a new one.
 */
const claimedInstanceKeys = new Map();

function createInstanceKey() {
	return instancePrefix() + Math.random().toString( 36 ).slice( 2 ) + Date.now().toString( 36 );
}

/**
 * WP 7.1 always iframes the post editor. Preview markup lives in that canvas
 * document, not in the parent admin document where editor scripts run.
 */
function getEditorCanvasDocument( node ) {

	if ( node && node.ownerDocument ) {
		return node.ownerDocument;
	}

	const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );

	if ( iframe && iframe.contentDocument ) {
		return iframe.contentDocument;
	}

	return document;
}

/**
 * Re-init logo widgets against the document that actually contains them.
 */
function reprocessLogoScripts( node ) {

	const canvasDocument = getEditorCanvasDocument( node );
	const canvasWindow = canvasDocument.defaultView || window;
	const jquery = canvasWindow.jQuery || window.jQuery;

	if ( ! jquery ) return;

	const areas = canvasDocument.querySelectorAll( '.gs_logo_area:not(.gs_logo__loaded)' );

	areas.forEach( function( area ) {
		jquery( area ).removeData( 'et-js-processed' );
	} );

	if ( typeof canvasWindow.gs_logo_init === 'function' ) {
		canvasWindow.gs_logo_init();
		return;
	}

	jquery( canvasDocument ).trigger( 'gslogo:scripts:reprocess' );
	jquery( document ).trigger( 'gslogo:scripts:reprocess' );
}

/**
 * The sliders and tickers are initialised by the public script. ServerSideRender
 * injects markup asynchronously into the iframed canvas, so keep nudging until
 * every preview area has been marked loaded.
 */
function useScriptReprocess( attributes, previewRef ) {

	useEffect( function() {

		let attempts = 0;

		const timer = window.setInterval( function() {

			reprocessLogoScripts( previewRef.current );

			attempts++;

			if ( attempts > 100 ) window.clearInterval( timer );

		}, 200 );

		return function() {
			window.clearInterval( timer );
		};

	}, [ attributes, previewRef ] );
}

/**
 * A stable, non numeric key per block instance. The render callback stores the
 * settings under this key so the AJAX filter and pagination handlers can resolve
 * them, and the generated CSS stays scoped to this instance.
 */
function useInstanceKey( attributes, setAttributes, clientId ) {

	useEffect( function() {

		const owner = claimedInstanceKeys.get( attributes.blockId );

		if ( ! attributes.blockId || ( owner && owner !== clientId ) ) {

			const instanceKey = createInstanceKey();

			claimedInstanceKeys.set( instanceKey, clientId );
			setAttributes( { blockId: instanceKey } );

			return;
		}

		claimedInstanceKeys.set( attributes.blockId, clientId );

	}, [ attributes.blockId, clientId ] );
}

export default function Edit( { attributes, setAttributes, clientId } ) {

	const previewRef = useRef( null );

	useInstanceKey( attributes, setAttributes, clientId );
	useScriptReprocess( attributes, previewRef );

	return (
		<React.Fragment>

			<InspectorControls>
				<TabPanel
					className="gslogo-builder-block--tabs"
					tabs={ TABS }
				>
					{ ( tab ) => (
						<tab.Panels attributes={ attributes } setAttributes={ setAttributes } />
					) }
				</TabPanel>
			</InspectorControls>

			<div className="gslogo-builder-block--preview" ref={ previewRef }>
				{ attributes.blockId ? (
					<ServerSideRender
						block={ BLOCK_NAME }
						attributes={ attributes }
						httpMethod="POST"
					/>
				) : null }
			</div>

		</React.Fragment>
	);
}
