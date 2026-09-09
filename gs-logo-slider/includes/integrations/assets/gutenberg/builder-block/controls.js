/**
 * Native field wrappers.
 *
 * Every control is a thin wrapper around a core @wordpress/components field.
 * The wrappers exist so the builder's storage conventions stay intact:
 *  - booleans are kept as the strings 'on' / 'off'
 *  - a setting keeps the value type its PHP default declares
 *  - composite values keep their comma separated string format
 *
 * `premium` marks the fields the builder renders with its `sh-disabled` Pro
 * overlay, so the free version shows them read only with a notice.
 */

import {
	fieldOptions,
	isOn,
	isPremiumOption,
	isProActive,
	label as uiLabel,
	premiumAlertMessage,
	rawOptions,
	settingDefault,
	toOnOff
} from './data';

const React = window.React;

const {
	ToggleControl,
	SelectControl,
	TextControl,
	RangeControl,
	CheckboxControl,
	FormTokenField,
	BaseControl
} = wp.components;

const BoxControl = wp.components.__experimentalBoxControl;
const BorderControl = wp.components.__experimentalBorderControl;

const { PanelColorSettings } = wp.blockEditor;

/**
 * Keep the value type the block attribute schema declares, so the REST block
 * renderer does not reject the attribute and silently fall back to its default.
 */
function castToSettingType( settingKey, value ) {

	if ( typeof settingDefault( settingKey ) === 'number' ) {

		const numeric = Number( value );

		return Number.isNaN( numeric ) ? 0 : numeric;
	}

	return String( value );
}

function numericValue( settingKey, value ) {

	const numeric = Number( value );

	if ( ! Number.isNaN( numeric ) ) return numeric;

	return Number( settingDefault( settingKey ) ) || 0;
}

/**
 * Add the premium notice below a field, and stop interaction for controls that
 * have no `disabled` prop of their own.
 */
function withPremium( premium, control, lockInteraction ) {

	if ( ! premium ) return control;

	return (
		<div className={ lockInteraction ? 'gslogo-builder-block--locked' : '' }>
			{ control }
			<p className="gslogo-builder-block--premium">{ uiLabel( 'premium_notice' ) }</p>
		</div>
	);
}

export function ToggleField( { attributes, setAttributes, settingKey, label, help, premium } ) {

	return withPremium( premium, (
		<ToggleControl
			label={ label }
			help={ help }
			checked={ isOn( attributes[ settingKey ] ) }
			disabled={ !! premium }
			onChange={ ( checked ) => setAttributes( { [ settingKey ]: toOnOff( checked ) } ) }
		/>
	) );
}

export function SelectField( { attributes, setAttributes, settingKey, label, help, options, optionsKey, premium, onChange } ) {

	const lookupKey = optionsKey || settingKey;
	const list = options || fieldOptions( lookupKey );

	const handleChange = function( value ) {

		// Match the shortcode builder: refuse Pro options and tell the user why.
		if ( isPremiumOption( lookupKey, value ) && ! isProActive() ) {
			window.alert( premiumAlertMessage() );
			return;
		}

		if ( onChange ) {
			onChange( value );
			return;
		}

		setAttributes( { [ settingKey ]: value } );
	};

	// Native SelectControl cannot style individual options; render our own
	// <select> so premium rows stay clickable with a gray background.
	return withPremium( premium, (
		<BaseControl
			label={ label }
			help={ help }
			className="components-select-control gslogo-builder-block--select"
		>
			<select
				className="components-select-control__input"
				value={ String( attributes[ settingKey ] ) }
				disabled={ !! premium }
				onChange={ ( event ) => handleChange( event.target.value ) }
			>
				{ list.map( function( option ) {

					const premiumLocked = typeof option.premiumLocked !== 'undefined'
						? !! option.premiumLocked
						: ( isPremiumOption( lookupKey, option.value ) && ! isProActive() );

					return (
						<option
							key={ String( option.value ) }
							value={ String( option.value ) }
							className={ premiumLocked ? 'gslogo-builder-block--premium-option' : undefined }
						>
							{ option.label }
						</option>
					);
				} ) }
			</select>
		</BaseControl>
	) );
}

export function TextField( { attributes, setAttributes, settingKey, label, help, placeholder, premium } ) {

	return withPremium( premium, (
		<TextControl
			label={ label }
			help={ help }
			placeholder={ placeholder }
			value={ attributes[ settingKey ] }
			disabled={ !! premium }
			onChange={ ( value ) => setAttributes( { [ settingKey ]: castToSettingType( settingKey, value ) } ) }
		/>
	) );
}

export function NumberField( { attributes, setAttributes, settingKey, label, help, min, max, premium } ) {

	return withPremium( premium, (
		<TextControl
			type="number"
			label={ label }
			help={ help }
			min={ min }
			max={ max }
			value={ attributes[ settingKey ] }
			disabled={ !! premium }
			onChange={ ( value ) => setAttributes( {
				[ settingKey ]: castToSettingType( settingKey, '' === value ? settingDefault( settingKey ) : value )
			} ) }
		/>
	) );
}

export function SliderField( { attributes, setAttributes, settingKey, label, help, min, max, step, premium } ) {

	return withPremium( premium, (
		<RangeControl
			label={ label }
			help={ help }
			min={ min }
			max={ max }
			step={ step }
			value={ numericValue( settingKey, attributes[ settingKey ] ) }
			disabled={ !! premium }
			onChange={ ( value ) => setAttributes( { [ settingKey ]: castToSettingType( settingKey, value ) } ) }
		/>
	) );
}

/**
 * Colors get their own panel, which is how core blocks expose them.
 * `colors` is a list of { settingKey, label }.
 */
export function ColorFieldsPanel( { attributes, setAttributes, title, initialOpen, colors } ) {

	const colorSettings = colors.map( function( color ) {
		return {
			label: color.label,
			value: attributes[ color.settingKey ],
			onChange: ( value ) => setAttributes( {
				[ color.settingKey ]: value || settingDefault( color.settingKey )
			} )
		};
	} );

	return (
		<PanelColorSettings
			title={ title }
			initialOpen={ !! initialOpen }
			colorSettings={ colorSettings }
		/>
	);
}

/**
 * Taxonomy include / exclude lists. Tokens are term names, the stored value is
 * a list of term IDs because validate_shortcode_settings() runs absint() on it.
 */
export function TermsField( { attributes, setAttributes, settingKey, optionsKey, label, help } ) {

	const terms = rawOptions( optionsKey );
	const selected = Array.isArray( attributes[ settingKey ] ) ? attributes[ settingKey ] : [];

	const termLabel = ( termId ) => {

		const term = terms.find( ( item ) => String( item.value ) === String( termId ) );

		return term ? term.label : String( termId );
	};

	const termId = ( token ) => {

		const term = terms.find( ( item ) => item.label === token );

		return term ? term.value : null;
	};

	return (
		<BaseControl help={ help }>
			<FormTokenField
				label={ label }
				value={ selected.map( termLabel ) }
				suggestions={ terms.map( ( term ) => term.label ) }
				__experimentalExpandOnFocus
				__experimentalShowHowTo={ false }
				onChange={ ( tokens ) => setAttributes( {
					[ settingKey ]: tokens.map( termId ).filter( ( id ) => null !== id )
				} ) }
			/>
		</BaseControl>
	);
}

function splitQuad( value, fallback ) {

	const parts = String( value || fallback ).split( ',' );

	return [ 0, 1, 2, 3 ].map( ( index ) => {

		const part = parseFloat( String( parts[ index ] ).trim() );

		return Number.isNaN( part ) ? 0 : part;
	} );
}

/**
 * Four unitless numbers stored as "a,b,c,d". The asset generator appends px.
 */
export function QuadField( { attributes, setAttributes, settingKey, label, help, partLabels, min, max, premium } ) {

	const values = splitQuad( attributes[ settingKey ], settingDefault( settingKey ) );

	const updatePart = ( index, raw ) => {

		const next = values.slice();

		next[ index ] = '' === raw ? 0 : parseFloat( raw ) || 0;

		setAttributes( { [ settingKey ]: next.join( ',' ) } );
	};

	return withPremium( premium, (
		<BaseControl label={ label } help={ help }>
			<div className="gslogo-builder-block--inline">
				{ partLabels.map( ( partLabel, index ) => (
					<TextControl
						key={ partLabel }
						type="number"
						label={ partLabel }
						min={ min }
						max={ max }
						value={ values[ index ] }
						disabled={ !! premium }
						onChange={ ( raw ) => updatePart( index, raw ) }
					/>
				) ) }
			</div>
		</BaseControl>
	) );
}

/**
 * Border radius. Uses the native box control when the WordPress version ships
 * it, and falls back to four plain number inputs otherwise.
 */
export function BoxField( { attributes, setAttributes, settingKey, label, help, partLabels, min, max, premium } ) {

	if ( ! BoxControl ) {
		return (
			<QuadField
				attributes={ attributes }
				setAttributes={ setAttributes }
				settingKey={ settingKey }
				label={ label }
				help={ help }
				partLabels={ partLabels }
				min={ min }
				max={ max }
				premium={ premium }
			/>
		);
	}

	const sides = splitQuad( attributes[ settingKey ], settingDefault( settingKey ) );

	const toNumber = ( side ) => {

		const parsed = parseFloat( String( side ).replace( /[^0-9.-]/g, '' ) );

		return Number.isNaN( parsed ) ? 0 : parsed;
	};

	return withPremium( premium, (
		<BaseControl help={ help }>
			<BoxControl
				label={ label }
				units={ [ { value: 'px', label: 'px', default: 0 } ] }
				values={ {
					top: sides[ 0 ] + 'px',
					right: sides[ 1 ] + 'px',
					bottom: sides[ 2 ] + 'px',
					left: sides[ 3 ] + 'px'
				} }
				onChange={ ( next ) => setAttributes( {
					[ settingKey ]: [
						toNumber( next.top ),
						toNumber( next.right ),
						toNumber( next.bottom ),
						toNumber( next.left )
					].join( ',' )
				} ) }
			/>
		</BaseControl>
	), true );
}

/**
 * Composite border stored as "1px,solid,#000000". The asset generator turns the
 * commas into spaces, so the thickness has to keep its unit.
 */
export function BorderField( { attributes, setAttributes, settingKey, label, help, premium } ) {

	const parts = String( attributes[ settingKey ] || settingDefault( settingKey ) ).split( ',' );

	const width = ( parts[ 0 ] || '1px' ).trim();
	const style = ( parts[ 1 ] || 'solid' ).trim();
	const color = ( parts[ 2 ] || '#000000' ).trim();

	const save = ( nextWidth, nextStyle, nextColor ) => setAttributes( {
		[ settingKey ]: [ nextWidth, nextStyle, nextColor ].join( ',' )
	} );

	if ( BorderControl ) {
		return withPremium( premium, (
			<BaseControl help={ help }>
				<BorderControl
					label={ label }
					colors={ [] }
					enableStyle
					value={ { width: width, style: style, color: color } }
					onChange={ ( next ) => save(
						next && next.width ? next.width : '1px',
						next && next.style ? next.style : 'solid',
						next && next.color ? next.color : '#000000'
					) }
				/>
			</BaseControl>
		), true );
	}

	return withPremium( premium, (
		<BaseControl label={ label } help={ help }>
			<div className="gslogo-builder-block--inline">
				<TextControl
					type="number"
					label={ uiLabel( 'border_thickness' ) }
					min={ 0 }
					value={ parseFloat( width ) || 0 }
					disabled={ !! premium }
					onChange={ ( raw ) => save( ( parseFloat( raw ) || 0 ) + 'px', style, color ) }
				/>
				<SelectControl
					label={ uiLabel( 'border_type' ) }
					value={ style }
					options={ fieldOptions( 'gs_l_rb_border_type' ) }
					disabled={ !! premium }
					onChange={ ( value ) => save( width, value, color ) }
				/>
				<BaseControl label={ uiLabel( 'border_color' ) }>
					<input
						type="color"
						value={ color }
						disabled={ !! premium }
						onChange={ ( event ) => save( width, style, event.target.value ) }
					/>
				</BaseControl>
			</div>
		</BaseControl>
	) );
}

export function DeviceCheckbox( { label, checked, onChange } ) {

	return (
		<CheckboxControl
			label={ label }
			checked={ !! checked }
			onChange={ onChange }
		/>
	);
}
