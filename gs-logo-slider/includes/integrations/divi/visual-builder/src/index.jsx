import React from 'react';
import { addAction, addFilter } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';

import metadata from './module.json';
import { LogoSliderEdit } from './edit';

/**
 * Localized builder data (from PackageBuildManager data_app_window).
 */
const getBuilderData = () => window?.GsLogoDiviVbData || {};

/**
 * Inject shortcode select options into module metadata before registration.
 */
const prepareMetadata = (baseMetadata) => {
  const data = getBuilderData();
  const prepared = JSON.parse(JSON.stringify(baseMetadata));
  const options = data.shortcodeOptions || {};
  const defaultShortcode = data.defaultShortcode || '';

  if (
    prepared?.attributes?.shortcode?.settings?.innerContent?.item?.component?.props
  ) {
    prepared.attributes.shortcode.settings.innerContent.item.component.props.options = options;
  }

  if (defaultShortcode && prepared?.attributes?.shortcode?.default?.innerContent?.desktop) {
    prepared.attributes.shortcode.default.innerContent.desktop.value = defaultShortcode;
  }

  return prepared;
};

/**
 * Custom module icon (inline SVG paths — reliable in Divi icon library).
 *
 * Divi's module picker forces viewBox="0 0 16 16", so artwork must live in
 * that coordinate space (brand SVG is 50×50 and would only show a clipped slice).
 */
const registerModuleIcon = () => {
  addFilter('divi.iconLibrary.icon.map', 'gslogo.logoSlider', (icons) => {
    const icon = {
      name: 'gslogo/module-logo',
      viewBox: '0 0 16 16',
      component: () => (
        // Scale brand logo glyph (50×50) into Divi's 16×16 module-icon viewport.
        <g transform="translate(8 8) scale(0.32) translate(-25 -25)">
          <circle cx="25" cy="25" r="25" fill="#E9E9FA" />
          <circle cx="41.393" cy="6.148" r="1.639" fill="#9A8AFC" />
          <circle cx="43" cy="43" r="1" fill="#F99C7F" />
          <circle cx="4.098" cy="44.673" r="0.82" fill="#72F2EB" />
          <circle cx="8.402" cy="43.648" r="2.254" fill="#FDDB8C" />
          <circle cx="7.788" cy="4.508" r="0.82" fill="#9A8AFC" />
          <circle cx="4.102" cy="5.333" r="1.644" fill="#FE7086" />
          <rect x="7" y="15.455" width="14.905" height="18.631" rx="3" fill="#FC9D7F" />
          <circle cx="14.365" cy="25.275" r="3.683" fill="#F07867" />
          <rect x="29.095" y="15.455" width="14.905" height="18.631" rx="3" fill="#EA8BF2" />
          <circle cx="36.46" cy="25.275" r="3.683" fill="#DC79E4" />
          <rect x="15.593" y="13" width="19.422" height="24.277" rx="3" fill="#6472EF" />
          <circle cx="24.799" cy="24.661" r="4.296" fill="#3F50EB" />
        </g>
      ),
    };

    return {
      ...icons,
      [icon.name]: icon,
    };
  });
};

registerModuleIcon();

const preparedMetadata = prepareMetadata(metadata);

const logoSliderModule = {
  metadata: preparedMetadata,
  renderers: {
    edit: LogoSliderEdit,
  },
  placeholderContent: {
    shortcode: {
      innerContent: {
        desktop: {
          value: getBuilderData().defaultShortcode || '',
        },
      },
    },
  },
};

/**
 * Register module with Divi module library.
 * Call immediately if the store already exists (script loaded after the action).
 */
const registerLogoSliderModule = () => {
  const { metadata: moduleMetadata, ...moduleDefinition } = logoSliderModule;
  registerModule(moduleMetadata, moduleDefinition);
};

addAction(
  'divi.moduleLibrary.registerModuleLibraryStore.after',
  'gslogo.logoSlider',
  registerLogoSliderModule,
);

// Fallback when this script loads after the store-init action already fired.
try {
  if (window?.divi?.moduleLibrary?.select?.('divi/module-library')) {
    registerLogoSliderModule();
  }
} catch (e) {
  // Store not ready yet — addAction handler will run instead.
}
