/**
 * General tab.
 *
 * Field list, order and conditional visibility are ported from the
 * "general_settings" tab of dev/shortcode/pages/shortcode.vue.
 */

import {
	BorderField,
	BoxField,
	ColorFieldsPanel,
	NumberField,
	QuadField,
	SelectField,
	SliderField,
	TextField,
	ToggleField
} from '../controls';

import {
	isOn,
	isOneOf,
	isProActive,
	label as uiLabel,
	translate
} from '../data';

import {
	isCarousel,
	isDisplayPaginationSettings,
	isGrid,
	isListTicker,
	isTable,
	isTicker,
	isVariableWidth,
	isVerticalCarousel
} from '../theme-groups';

import { ensureInitialVisibilityFields, themeHasVisibilityField } from '../visibility';

const React = window.React;

const { PanelBody } = wp.components;

export default function GeneralPanels( { attributes, setAttributes } ) {

	const field = { attributes, setAttributes };
	const premium = ! isProActive();

	const theme = attributes.gs_l_theme;
	const paginationOn = isOn( attributes.gs_logo_pagination ) && isDisplayPaginationSettings( attributes );

	// The title, content and excerpt limits only make sense while the matching
	// part is both supported by the theme and switched on.
	const showTitleTag = themeHasVisibilityField( theme, 'logo_title' ) && isOn( attributes.gs_l_title );
	const showContentLimit = themeHasVisibilityField( theme, 'logo_content' ) && isOn( attributes.gs_l_show_content );
	const showExcerptLimit = themeHasVisibilityField( theme, 'logo_excerpt' ) && isOn( attributes.gs_l_show_excerpt );

	/**
	 * Switching theme brings in a different set of visibility fields, so seed the
	 * missing ones the same way the builder does.
	 */
	const changeTheme = ( nextTheme ) => setAttributes( Object.assign(
		{ gs_l_theme: nextTheme },
		ensureInitialVisibilityFields( attributes, nextTheme )
	) );

	return (
		<React.Fragment>

			<PanelBody title={ translate( 'gs-l-theme' ) } initialOpen={ true }>

				<SelectField
					{ ...field }
					settingKey="gs_l_theme"
					label={ translate( 'gs-l-theme' ) }
					help={ translate( 'gs-l-theme--help' ) }
					onChange={ changeTheme }
				/>

				{ 'hexagon' === theme && (
					<NumberField
						{ ...field }
						settingKey="gs_l_s2_border_thickness"
						label={ translate( 'gs-l-s2-border-thickness' ) }
						help={ translate( 'gs-l-s2-border-thickness--help' ) }
						min={ 0 }
						premium={ premium }
					/>
				) }

				{ 'rounded-border' === theme && (
					<React.Fragment>
						<BorderField
							{ ...field }
							settingKey="gs_l_rb_border"
							label={ translate( 'gs-l-rb-border' ) }
							help={ translate( 'gs-l-rb-border--help' ) }
							premium={ premium }
						/>
						<BoxField
							{ ...field }
							settingKey="gs_l_rb_border_radius"
							label={ translate( 'gs-l-rb-border-radius' ) }
							help={ translate( 'gs-l-rb-border-radius--help' ) }
							partLabels={ [
								uiLabel( 'radius_top' ),
								uiLabel( 'radius_right' ),
								uiLabel( 'radius_bottom' ),
								uiLabel( 'radius_left' )
							] }
							min={ 0 }
							max={ 100 }
							premium={ premium }
						/>
						<QuadField
							{ ...field }
							settingKey="gs_l_rb_hover_shadow_control"
							label={ translate( 'gs-l-rb-hover-shadow-control' ) }
							help={ translate( 'gs-l-rb-hover-shadow-control--help' ) }
							partLabels={ [
								uiLabel( 'shadow_x' ),
								uiLabel( 'shadow_y' ),
								uiLabel( 'shadow_blur' ),
								uiLabel( 'shadow_spread' )
							] }
							min={ 0 }
							max={ 200 }
							premium={ premium }
						/>
					</React.Fragment>
				) }

			</PanelBody>

			{ 'hexagon' === theme && (
				<ColorFieldsPanel
					{ ...field }
					title={ uiLabel( 'hexagon_colors' ) }
					colors={ [
						{ settingKey: 'gs_l_s2_gradient_start', label: translate( 'gs-l-s2-gradient-start' ) },
						{ settingKey: 'gs_l_s2_gradient_end', label: translate( 'gs-l-s2-gradient-end' ) }
					] }
				/>
			) }

			{ 'rounded-border' === theme && (
				<ColorFieldsPanel
					{ ...field }
					title={ uiLabel( 'border_colors' ) }
					colors={ [
						{ settingKey: 'gs_l_rb_hover_shadow_color', label: translate( 'gs-l-rb-hover-shadow-color' ) }
					] }
				/>
			) }

			{ ( isGrid( theme ) || isDisplayPaginationSettings( attributes ) ) && (
				<PanelBody title={ uiLabel( 'filter_panel' ) } initialOpen={ false }>

					{ isGrid( theme ) && (
						<ToggleField
							{ ...field }
							settingKey="filter_enabled"
							label={ translate( 'filter_enabled' ) }
							help={ translate( 'filter_enabled__details' ) }
							premium={ premium }
						/>
					) }

					{ isGrid( theme ) && isOn( attributes.filter_enabled ) && (
						<SelectField
							{ ...field }
							settingKey="gs_logo_filter_type"
							label={ translate( 'filter_type' ) }
							help={ translate( 'filter_type__details' ) }
							premium={ premium }
						/>
					) }

					{ isDisplayPaginationSettings( attributes ) && (
						<ToggleField
							{ ...field }
							settingKey="gs_logo_pagination"
							label={ translate( 'gs_logo_pagination' ) }
							help={ translate( 'gs_logo_pagination__details' ) }
							premium={ premium }
						/>
					) }

					{ paginationOn && (
						<SelectField
							{ ...field }
							settingKey="pagination_type"
							label={ translate( 'pagination_type' ) }
							help={ translate( 'pagination_type__details' ) }
							premium={ premium }
						/>
					) }

					{ paginationOn && isOneOf( attributes.pagination_type, [ 'load-more-button', 'load-more-scroll' ] ) && (
						<NumberField
							{ ...field }
							settingKey="initial_items"
							label={ translate( 'initial_items' ) }
							help={ translate( 'initial_items__details' ) }
							min={ 1 }
							premium={ premium }
						/>
					) }

					{ paginationOn && isOneOf( attributes.pagination_type, [ 'normal-pagination', 'ajax-pagination' ] ) && (
						<NumberField
							{ ...field }
							settingKey="logo_per_page"
							label={ translate( 'logo_per_page' ) }
							help={ translate( 'logo_per_page__details' ) }
							min={ 1 }
							premium={ premium }
						/>
					) }

					{ paginationOn && 'load-more-button' === attributes.pagination_type && (
						<NumberField
							{ ...field }
							settingKey="load_per_click"
							label={ translate( 'load_per_click' ) }
							help={ translate( 'load_per_click__details' ) }
							min={ 1 }
							premium={ premium }
						/>
					) }

					{ paginationOn && 'load-more-scroll' === attributes.pagination_type && (
						<NumberField
							{ ...field }
							settingKey="per_load"
							label={ translate( 'per_load' ) }
							help={ translate( 'per_load__details' ) }
							min={ 1 }
							premium={ premium }
						/>
					) }

					{ paginationOn && 'load-more-button' === attributes.pagination_type && (
						<TextField
							{ ...field }
							settingKey="load_button_text"
							label={ translate( 'load_button_text' ) }
							help={ translate( 'load_button_text__details' ) }
						/>
					) }

				</PanelBody>
			) }

			<PanelBody title={ translate( 'image-size' ) } initialOpen={ false }>

				<SelectField
					{ ...field }
					settingKey="image_size"
					label={ translate( 'image-size' ) }
					help={ translate( 'image-size--help' ) }
				/>

				{ 'custom' === attributes.image_size && (
					<React.Fragment>
						<TextField
							{ ...field }
							settingKey="custom_image_size_width"
							label={ uiLabel( 'custom_width' ) }
							premium={ premium }
						/>
						<TextField
							{ ...field }
							settingKey="custom_image_size_height"
							label={ uiLabel( 'custom_height' ) }
							premium={ premium }
						/>
						<SelectField
							{ ...field }
							settingKey="custom_image_size_crop"
							label={ translate( 'custom-image-size' ) }
							help={ translate( 'custom-image-size--help' ) }
							premium={ premium }
						/>
					</React.Fragment>
				) }

			</PanelBody>

			<PanelBody title={ translate( 'gs-l-link-logos' ) } initialOpen={ false }>

				<ToggleField
					{ ...field }
					settingKey="gs_l_link_logos"
					label={ translate( 'gs-l-link-logos' ) }
					help={ translate( 'gs-l-link-logos--help' ) }
				/>

				{ isOn( attributes.gs_l_link_logos ) && (
					<SelectField
						{ ...field }
						settingKey="gs_logo_link_type"
						label={ translate( 'gs_logo_link_type' ) }
						help={ translate( 'gs_logo_link_type__details' ) }
					/>
				) }

				{ isOn( attributes.gs_l_link_logos ) && 'popup' === attributes.gs_logo_link_type && (
					<SelectField
						{ ...field }
						settingKey="popup_style"
						label={ translate( 'popup_style' ) }
						help={ translate( 'popup_style__details' ) }
					/>
				) }

				{ isOn( attributes.gs_l_link_logos ) && 'panel' === attributes.gs_logo_link_type && (
					<SelectField
						{ ...field }
						settingKey="panel_style"
						label={ translate( 'panel_style' ) }
						help={ translate( 'panel_style__details' ) }
					/>
				) }

			</PanelBody>

			{ ( isCarousel( theme ) || isTicker( theme ) ) && (
				<PanelBody title={ uiLabel( 'slider_panel' ) } initialOpen={ false }>

					<SliderField
						{ ...field }
						settingKey="gs_l_slide_speed"
						label={ translate( 'gs-l-slide-speed' ) }
						help={ translate( 'gs-l-slide-speed--help' ) }
						min={ 0 }
						max={ 10000 }
						step={ 50 }
					/>

					{ isCarousel( theme ) && (
						<ToggleField
							{ ...field }
							settingKey="gs_l_is_autop"
							label={ translate( 'gs-l-is-autop' ) }
							help={ translate( 'gs-l-is-autop--help' ) }
						/>
					) }

					{ ( ( isCarousel( theme ) && isOn( attributes.gs_l_is_autop ) ) || isListTicker( theme ) ) && (
						<SliderField
							{ ...field }
							settingKey="gs_l_autop_pause"
							label={ translate( 'gs-l-autop-pause' ) }
							help={ translate( 'gs-l-autop-pause--help' ) }
							min={ 0 }
							max={ 10000 }
							step={ 50 }
						/>
					) }

					{ ( ( isCarousel( theme ) && isOn( attributes.gs_l_is_autop ) ) || isTicker( theme ) ) && (
						<ToggleField
							{ ...field }
							settingKey="gs_l_slider_stop"
							label={ translate( 'gs-l-slider-stop' ) }
							help={ translate( 'gs-l-slider-stop--help' ) }
						/>
					) }

					{ isCarousel( theme ) && ! isVariableWidth( theme ) && (
						<ToggleField
							{ ...field }
							settingKey="gs_l_inf_loop"
							label={ translate( 'gs-l-inf-loop' ) }
							help={ translate( 'gs-l-inf-loop--help' ) }
						/>
					) }

					{ ( ( isCarousel( theme ) && isOn( attributes.gs_l_is_autop ) ) || isTicker( theme ) ) && (
						<ToggleField
							{ ...field }
							settingKey="gs_reverse_direction"
							label={ translate( 'gs-reverse-direction' ) }
							help={ translate( 'gs-reverse-direction--help' ) }
							premium={ premium }
						/>
					) }

					{ isCarousel( theme ) && ! isVerticalCarousel( theme ) && (
						<React.Fragment>

							<ToggleField
								{ ...field }
								settingKey="gs_l_ctrl"
								label={ translate( 'gs-l-ctrl' ) }
								help={ translate( 'gs-l-ctrl--help' ) }
							/>

							{ isOn( attributes.gs_l_ctrl ) && (
								<SelectField
									{ ...field }
									settingKey="gs_l_ctrl_pos"
									label={ translate( 'gs-l-ctrl-pos' ) }
									help={ translate( 'gs-l-ctrl-pos--help' ) }
								/>
							) }

							<ToggleField
								{ ...field }
								settingKey="gs_l_pagi"
								label={ translate( 'gs-l-pagi' ) }
								help={ translate( 'gs-l-pagi--help' ) }
							/>

							{ isOn( attributes.gs_l_pagi ) && (
								<ToggleField
									{ ...field }
									settingKey="gs_l_pagi_dynamic"
									label={ translate( 'gs-l-pagi-dynamic' ) }
									help={ translate( 'gs-l-pagi-dynamic--help' ) }
								/>
							) }

							<ToggleField
								{ ...field }
								settingKey="gs_l_play_pause"
								label={ translate( 'gs-l-play-pause' ) }
								help={ translate( 'gs-l-play-pause--help' ) }
							/>

						</React.Fragment>
					) }

				</PanelBody>
			) }

			{ ( showTitleTag || showContentLimit || showExcerptLimit ) && (
				<PanelBody title={ uiLabel( 'content_panel' ) } initialOpen={ false }>

					{ showTitleTag && (
						<SelectField
							{ ...field }
							settingKey="title_tag"
							label={ translate( 'title-tag' ) }
							help={ translate( 'title-tag--help' ) }
						/>
					) }

					{ showContentLimit && (
						<React.Fragment>
							<NumberField
								{ ...field }
								settingKey="gs_l_content_limit_count"
								label={ translate( 'gs-l-content-limit' ) }
								help={ translate( 'gs-l-content-limit--help' ) }
								min={ 1 }
								premium={ premium }
							/>
							<SelectField
								{ ...field }
								settingKey="gs_l_content_limit_type"
								label={ uiLabel( 'limit_type' ) }
								premium={ premium }
							/>
						</React.Fragment>
					) }

					{ showExcerptLimit && (
						<React.Fragment>
							<NumberField
								{ ...field }
								settingKey="gs_l_excerpt_limit_count"
								label={ translate( 'gs-l-excerpt-limit' ) }
								help={ translate( 'gs-l-excerpt-limit--help' ) }
								min={ 1 }
								premium={ premium }
							/>
							<SelectField
								{ ...field }
								settingKey="gs_l_excerpt_limit_type"
								label={ uiLabel( 'limit_type' ) }
								premium={ premium }
							/>
						</React.Fragment>
					) }

					{ ( showContentLimit || showExcerptLimit ) && (
						<TextField
							{ ...field }
							settingKey="gs_l_read_more_text"
							label={ translate( 'gs-l-read-more-text' ) }
							premium={ premium }
						/>
					) }

				</PanelBody>
			) }

			<PanelBody title={ translate( 'gs-l-tooltip' ) } initialOpen={ false }>

				<ToggleField
					{ ...field }
					settingKey="gs_l_tooltip"
					label={ translate( 'gs-l-tooltip' ) }
					help={ translate( 'gs-l-tooltip--help' ) }
				/>

				{ isOn( attributes.gs_l_tooltip ) && (
					<React.Fragment>
						<SelectField
							{ ...field }
							settingKey="gs_l_tooltip_placement"
							label={ translate( 'gs-l-tooltip-placement' ) }
							help={ translate( 'gs-l-tooltip-placement--help' ) }
						/>
						<ColorFieldsPanel
							{ ...field }
							title={ uiLabel( 'tooltip_colors' ) }
							initialOpen={ true }
							colors={ [
								{ settingKey: 'gs_l_tooltip_bgcolor_one', label: translate( 'gs-l-tooltip-bgcolor' ) },
								{ settingKey: 'gs_l_tooltip_bgcolor_two', label: translate( 'gs-l-tooltip-bgcolor' ) },
								{ settingKey: 'gs_l_tooltip_textcolor', label: translate( 'gs-l-tooltip-textcolor' ) }
							] }
						/>
					</React.Fragment>
				) }

			</PanelBody>

			{ isTable( theme ) && (
				<PanelBody title={ uiLabel( 'table_headings' ) } initialOpen={ false }>

					<TextField
						{ ...field }
						settingKey="row_heading_image"
						label={ translate( 'row_heading_image' ) }
						placeholder={ translate( 'row_heading_image--placeholder' ) }
					/>

					<TextField
						{ ...field }
						settingKey="row_heading_name"
						label={ translate( 'row_heading_name' ) }
						placeholder={ translate( 'row_heading_name--placeholder' ) }
					/>

					<TextField
						{ ...field }
						settingKey="row_heading_desc"
						label={ translate( 'row_heading_desc' ) }
						placeholder={ translate( 'row_heading_desc--placeholder' ) }
					/>

				</PanelBody>
			) }

		</React.Fragment>
	);
}
