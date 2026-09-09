(function($) {

	var config = window.gsLogoElementorBuilder || {};
	var alerting = false;
	var silentRevert = false;
	var hooked = false;
	var observer = null;

	function isProActive() {
		return !! config.is_pro_active;
	}

	function premiumSuffix() {
		return config.premium_suffix || '';
	}

	function isPremiumLabel( text ) {
		var suffix = premiumSuffix();

		if ( ! suffix ) return false;

		return String( text || '' ).indexOf( suffix ) !== -1;
	}

	function isBuilderWidgetModel( model ) {
		return model && model.get && model.get( 'widgetType' ) === config.widget_name;
	}

	function isEditingBuilderWidget() {
		try {
			var pageView = elementor.getPanelView().getCurrentPageView();
			return !!( pageView && pageView.model && isBuilderWidgetModel( pageView.model ) );
		} catch ( error ) {
			return false;
		}
	}

	function closeOpenSelect2() {
		$( '.select2-hidden-accessible' ).each( function() {
			var instance = $( this ).data( 'select2' );

			if ( instance && typeof instance.close === 'function' ) {
				instance.close();
			}
		} );
	}

	function showPremiumAlert() {
		if ( alerting || silentRevert ) return;

		alerting = true;
		closeOpenSelect2();

		// Defer until after the originating mouseup/click. Showing during that
		// event makes Elementor treat the leftover click as "outside" and hide
		// the dialog immediately.
		window.setTimeout( function() {
			var title = config.premium_title || '';
			var message = config.premium_notice || '';

			if ( window.elementorCommon && elementorCommon.dialogsManager ) {
				elementorCommon.dialogsManager.createWidget( 'alert', {
					id: 'gs-logo-elementor-premium-alert',
					headerMessage: title,
					message: message,
					onHide: function() {
						alerting = false;
					}
				} ).show();
				return;
			}

			window.alert( message );
			alerting = false;
		}, 150 );
	}

	function getFirstFreeValue( $select ) {
		var fallback = null;

		$select.find( 'option' ).each( function() {
			if ( isPremiumLabel( $( this ).text() ) ) return;

			fallback = this.value;
			return false;
		} );

		return fallback;
	}

	function applySetting( view, model, key, value ) {
		if ( ! key ) return;

		if ( view && typeof view.getContainer === 'function' && window.$e ) {
			var settings = {};
			settings[ key ] = value;
			$e.run( 'document/elements/settings', {
				container: view.getContainer(),
				settings: settings
			} );
			return;
		}

		if ( model && typeof model.setSetting === 'function' ) {
			model.setSetting( key, value );
		}
	}

	var activeView = null;
	var activeModel = null;

	function isSelect2( $select ) {
		return $select.hasClass( 'select2-hidden-accessible' ) || !! $select.data( 'select2' );
	}

	function lockSelectOptions( $panel ) {
		$panel.find( 'select' ).each( function() {
			var $select = $( this );
			var useDisabled = isSelect2( $select );

			$select.find( 'option' ).each( function() {
				var isPremium = isPremiumLabel( $( this ).text() );

				// Native <select> ignores clicks on disabled options, so the
				// alert never fires. Only Select2 options are disabled.
				this.disabled = useDisabled && isPremium;

				if ( useDisabled && isPremium ) {
					$( this ).attr( 'aria-disabled', 'true' );
				} else {
					$( this ).removeAttr( 'aria-disabled' );
				}
			} );
		} );
	}

	function handleNativePremiumChange( $select ) {
		var $selected = $select.find( 'option:selected' );

		if ( ! isPremiumLabel( $selected.text() ) ) return;

		var fallback = getFirstFreeValue( $select );

		if ( null === fallback ) return;

		$select.val( fallback );
		applySetting( activeView, activeModel, $select.data( 'setting' ), fallback );
		showPremiumAlert();
	}

	function revertPremiumSelections( view, model, $panel ) {
		silentRevert = true;

		$panel.find( 'select[data-setting]' ).each( function() {
			var $select = $( this );
			var selectedText = $select.find( 'option:selected' ).text();

			if ( ! isPremiumLabel( selectedText ) ) return;

			var fallback = getFirstFreeValue( $select );

			if ( null === fallback || String( fallback ) === String( $select.val() ) ) return;

			applySetting( view, model, $select.data( 'setting' ), fallback );
		} );

		silentRevert = false;
	}

	function markOpenSelect2Results() {
		if ( ! isEditingBuilderWidget() ) return;

		$( '.select2-results__option' ).each( function() {
			var $result = $( this );

			if ( ! isPremiumLabel( $result.text() ) ) return;

			$result.attr( 'aria-disabled', 'true' ).addClass( 'select2-results__option--disabled' );
		} );
	}

	function bindPanel( panel, model, view ) {
		var $panel = panel.$el;

		activeView = view;
		activeModel = model;

		if ( observer ) {
			observer.disconnect();
			observer = null;
		}

		function applyLocks() {
			lockSelectOptions( $panel );
		}

		applyLocks();
		revertPremiumSelections( view, model, $panel );
		applyLocks();

		observer = new MutationObserver( function() {
			window.clearTimeout( $panel.data( 'gsLogoLockTimer' ) );
			$panel.data( 'gsLogoLockTimer', window.setTimeout( applyLocks, 50 ) );
		} );

		observer.observe( $panel.get( 0 ), {
			childList: true,
			subtree: true
		} );

		$panel.off( '.gsLogoPremium' );

		$panel.on( 'select2:open.gsLogoPremium', 'select', function() {
			window.setTimeout( markOpenSelect2Results, 0 );
		} );

		$panel.on( 'select2:selecting.gsLogoPremium', 'select', function( event ) {
			var params = event.params || {};
			var data = ( params.args && params.args.data ) ? params.args.data : ( params.data || {} );

			if ( ! data.disabled && ! isPremiumLabel( data.text ) ) return;

			event.preventDefault();
			showPremiumAlert();
		} );

		$panel.on( 'change.gsLogoPremium', 'select', function() {
			var $select = $( this );

			if ( isSelect2( $select ) ) {
				var $selected = $select.find( 'option:selected' );

				if ( ! $selected.prop( 'disabled' ) && ! isPremiumLabel( $selected.text() ) ) return;

				var fallback = getFirstFreeValue( $select );

				if ( null === fallback ) return;

				$select.val( fallback );
				applySetting( view, model, $select.data( 'setting' ), fallback );
				showPremiumAlert();
				return;
			}

			handleNativePremiumChange( $select );
		} );

		$panel.on( 'click.gsLogoPremium', '.gs-logo-elementor--premium', function( event ) {
			event.preventDefault();
			event.stopPropagation();
			showPremiumAlert();
		} );
	}

	$( document ).on( 'select2:selecting', '#elementor-controls select', function( event ) {
		if ( isProActive() || ! isEditingBuilderWidget() ) return;

		var params = event.params || {};
		var data = ( params.args && params.args.data ) ? params.args.data : ( params.data || {} );

		if ( ! data.disabled && ! isPremiumLabel( data.text ) ) return;

		event.preventDefault();
		showPremiumAlert();
	} );

	$( document ).on( 'change', '#elementor-controls select', function() {
		if ( isProActive() || ! isEditingBuilderWidget() ) return;
		if ( isSelect2( $( this ) ) ) return;

		handleNativePremiumChange( $( this ) );
	} );

	$( document ).on( 'mousedown click', '.select2-results__option', function( event ) {
		if ( isProActive() || ! isEditingBuilderWidget() ) return;

		var $result = $( this );

		if ( $result.attr( 'aria-disabled' ) !== 'true' && ! $result.hasClass( 'select2-results__option--disabled' ) && ! isPremiumLabel( $result.text() ) ) {
			return;
		}

		event.preventDefault();
		event.stopImmediatePropagation();
		showPremiumAlert();
	} );

	function registerHook() {
		if ( isProActive() || hooked || ! config.widget_name || ! window.elementor || ! elementor.hooks ) return;

		hooked = true;
		elementor.hooks.addAction( 'panel/open_editor/widget/' + config.widget_name, bindPanel );
	}

	function registerVisibilityDevicesControl() {
		if ( ! window.elementor || ! elementor.modules || ! elementor.modules.controls || ! elementor.modules.controls.BaseData ) {
			return false;
		}

		if ( elementor.modules.controls.GsLogoVisibilityDevices ) {
			return true;
		}

		var VisibilityDevices = elementor.modules.controls.BaseData.extend( {
			onReady: function() {
				this.$el.off( '.gsLogoVisibility' );
				this.$el.on(
					'change.gsLogoVisibility',
					'input[type="checkbox"]',
					this.onCheckboxesChange.bind( this )
				);
				this.$el.on(
					'click.gsLogoVisibility',
					'.gs-logo-visibility-devices__label',
					this.onLabelToggle.bind( this )
				);
			},
			onLabelToggle: function() {
				var $boxes = this.$el.find( 'input[type="checkbox"]' );
				var checkAll = $boxes.filter( ':checked' ).length !== $boxes.length;

				$boxes.prop( 'checked', checkAll );
				this.onCheckboxesChange();
			},
			onCheckboxesChange: function() {
				var values = [];

				this.$el.find( 'input[type="checkbox"]:checked' ).each( function() {
					values.push( this.value );
				} );

				this.setValue( values );
			}
		} );

		elementor.modules.controls.GsLogoVisibilityDevices = VisibilityDevices;
		elementor.addControlView( 'gs_logo_visibility_devices', VisibilityDevices );

		return true;
	}

	$( window ).on( 'elementor:init', function() {
		registerVisibilityDevicesControl();
		registerHook();
	} );

	registerVisibilityDevicesControl();
	registerHook();

})( jQuery );
