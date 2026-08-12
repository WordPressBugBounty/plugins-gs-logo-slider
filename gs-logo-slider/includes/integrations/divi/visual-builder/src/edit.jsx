import React, { useEffect, useState } from 'react';
import {
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} from '@divi/module';
import { useFetch } from '@divi/rest';

/**
 * Module styles renderer.
 */
const ModuleStyles = ({
  attrs,
  elements,
  settings,
  mode,
  state,
  noStyleTag,
}) => (
  <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
    {elements.style({
      attrName: 'module',
      styleProps: {
        disabledOn: {
          disabledModuleVisibility: settings?.disabledModuleVisibility,
        },
      },
    })}
  </StyleContainer>
);

/**
 * Module script data renderer.
 */
const ModuleScriptData = ({ elements }) => (
  <React.Fragment>
    {elements.scriptData({
      attrName: 'module',
    })}
  </React.Fragment>
);

/**
 * Module classnames helper.
 */
const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(
    elementClassnames({
      attrs: attrs?.module?.decoration ?? {},
    }),
  );
};

/**
 * Re-run GS Logo frontend scripts after VB HTML updates.
 */
const reprocessLogoScripts = () => {
  if (typeof window.jQuery === 'undefined') {
    return;
  }

  let count = 0;
  const interval = setInterval(() => {
    window.jQuery(document).trigger('gslogo:scripts:reprocess');
    count += 1;
    if (count > 20) {
      clearInterval(interval);
    }
  }, 100);
};

/**
 * Extract a readable error message from Divi useFetch failures.
 */
const getFetchErrorMessage = async (error, fallback) => {
  if (!error) {
    return fallback;
  }

  if (typeof error === 'string') {
    return error;
  }

  if (error?.message && error.name !== 'AbortError') {
    return error.message;
  }

  // Divi rejects with the raw Response when !response.ok
  if (typeof error?.json === 'function') {
    try {
      const payload = await error.json();
      if (payload?.message) {
        return payload.message;
      }
    } catch (e) {
      // Ignore JSON parse errors.
    }
  }

  if (error?.status) {
    return `${fallback} (HTTP ${error.status})`;
  }

  return fallback;
};

/**
 * Visual Builder edit component.
 */
const LogoSliderEdit = (props) => {
  const {
    attrs,
    id,
    name,
    elements,
  } = props;

  const shortcodeId = String(
    attrs?.shortcode?.innerContent?.desktop?.value
      || window?.GsLogoDiviVbData?.defaultShortcode
      || '',
  );

  const i18n = window?.GsLogoDiviVbData?.i18n || {};
  const restNamespace = window?.GsLogoDiviVbData?.restNamespace || 'gs-logo/v1';

  const {
    fetch,
    response,
    isLoading,
  } = useFetch(null, false);

  const [errorMessage, setErrorMessage] = useState('');

  useEffect(() => {
    let cancelled = false;

    if (!shortcodeId) {
      setErrorMessage('');
      return undefined;
    }

    setErrorMessage('');

    fetch({
      restRoute: `/${restNamespace}/divi/render`,
      method: 'GET',
      data: {
        shortcode_id: shortcodeId,
      },
      // Avoid Divi rest-store returning a previously failed response.
      forceRequest: true,
    })
      .then((result) => {
        if (cancelled) {
          return;
        }

        setErrorMessage('');

        if (result?.html) {
          // Allow GS Logo scripts to bind after HTML injection.
          window.setTimeout(reprocessLogoScripts, 50);
        }
      })
      .catch(async (error) => {
        if (cancelled || error?.name === 'AbortError') {
          return;
        }

        const message = await getFetchErrorMessage(
          error,
          i18n.empty || 'Unable to load shortcode preview.',
        );

        setErrorMessage(message);
        // eslint-disable-next-line no-console
        console.error('[GS Logo Divi] preview failed', error);
      });

    return () => {
      cancelled = true;
    };
    // Intentionally omit `fetch` — useFetch returns a new function each render.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [shortcodeId, restNamespace]);

  const html = response?.html || '';
  const showEmpty = !shortcodeId;
  const showError = !isLoading && Boolean(errorMessage);
  const showNoHtml = !isLoading && shortcodeId && !html && !errorMessage;

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      name={name}
      moduleClassName="gs_logo_slider"
      scriptDataComponent={ModuleScriptData}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({
        attrName: 'module',
      })}
      <div className="et_pb_module_inner gs-logo-slider">
        {showEmpty && (
          <div className="gs-logo-divi-empty">
            {i18n.noShortcode || i18n.empty || 'Please select a GS Logo shortcode.'}
          </div>
        )}
        {isLoading && !showEmpty && (
          <div className="gs-logo-divi-loading">
            {i18n.loading || 'Loading logo slider…'}
          </div>
        )}
        {!isLoading && html && (
          <div
            className="gs-logo-divi-preview"
            dangerouslySetInnerHTML={{ __html: html }}
          />
        )}
        {showError && (
          <div className="gs-logo-divi-empty">
            {errorMessage}
          </div>
        )}
        {showNoHtml && (
          <div className="gs-logo-divi-empty">
            {i18n.empty || 'Please select a GS Logo shortcode.'}
          </div>
        )}
      </div>
    </ModuleContainer>
  );
};

export {
  LogoSliderEdit,
  ModuleStyles,
  ModuleScriptData,
  moduleClassnames,
};
