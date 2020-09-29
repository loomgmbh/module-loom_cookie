(function($) {

  var script = {

    attach: function(context, settings) {
      if (!Drupal.eu_cookie_compliance) {
        return;
      }

      // apply Drupal behaviors to popup
      $(document).on('eu_cookie_compliance_popup_open', function() {
        Drupal.attachBehaviors($(document).find('#sliding-popup')[0]);
      });

      script.enabledCategories = Drupal.eu_cookie_compliance.getAcceptedCategories();

      if (context == document) {
        script.modifyEUCookieComplianceFunctions();

        if (!!settings.loom_cookie &&
          Object.keys(settings.loom_cookie).length) {
          script.settings = settings.loom_cookie;

          script.blockSomeScripts();
        }

        // Click on button "Accept all"
        // -> check all categories and click on "Save" button
        $(document)
          .on('click', '.eu-cookie-compliance-accept-all-button', (e) => {
            let $popupContent = $(e.target)
              .closest('.eu-cookie-compliance-content');
            $popupContent.find('[name="cookie-categories"]')
              .prop('checked', true);
            $popupContent.find('.eu-cookie-compliance-save-preferences-button')
              .click();
          });
      }

      script.enableElements();

      script.showOverlays();
    },

    settings: [],
    enabledCategories: [],
    disabledCategories: [],

    /**
     * Block scripts that could not be blocked server-side by monkey patching
     * the methods document.createElement and
     * HTMLScriptElement.prototype.setAttribute.
     * @see https://medium.com/snips-ai/how-to-block-third-party-scripts-with-a-few-lines-of-javascript-f0b08b9c4c0
     */
    blockSomeScripts: () => {
      const createElementBackup = document.createElement;
      document.createElement = (...args) => {
        // If this is not a script tag, bypass
        if (args[0].toLowerCase() !== 'script') {
          // Binding to document is essential
          return createElementBackup.bind(document)(...args);
        }

        const scriptElt = createElementBackup.bind(document)(...args);

        // Backup the original setAttribute function
        const originalSetAttribute = scriptElt.setAttribute.bind(scriptElt);

        // Define getters / setters to ensure that the script type is properly
        // set
        Object.defineProperties(scriptElt, {
          'src': {
            get() {
              return scriptElt.getAttribute('src');
            },
            set(value) {
              if (script.shouldBlockScript(value)) {
                value = '';
                originalSetAttribute('type', 'javascript/blocked');
              }
              originalSetAttribute('src', value);
              return true;
            },
            configurable: true,
          },
        });

        // Monkey patch the setAttribute function so that the setter is called
        // instead. Otherwise, setAttribute('type', 'whatever') will bypass our
        // custom descriptors!
        scriptElt.setAttribute = function(name, value) {
          if (name === 'src') {
            scriptElt[name] = value;
          }
          else {
            HTMLScriptElement.prototype.setAttribute.call(scriptElt, name,
              value);
          }
        };

        return scriptElt;
      };
    },

    /**
     * Determines if a script source should be blocked client-side based on the
     * enabled categories.
     * @param src
     * @returns {boolean}
     */
    shouldBlockScript: (src) => {
      let disabledCategories = Object.keys(script.settings).filter(
        category => script.enabledCategories.indexOf(category) === -1);

      // only block scripts of disabled categories
      for (const category of disabledCategories) {
        if (!script.settings[category].clientSideBlockedScripts) {
          continue;
        }

        if (src.match(
          new RegExp(script.settings[category].clientSideBlockedScripts))) {
          return true;
        }
      }

      return false;
    },

    /**
     * Enable elements that have been blocked server-side based on the
     * enabled categories.
     */
    enableElements: () => {
      if (!script.enabledCategories.length) {
        // no scripts to reenable
        return;
      }

      for (const category of script.enabledCategories) {
        $('[data-loom-cookie-category="' + category + '"]').each((n, el) => {
          let $el = $(el);

          switch ($el.attr('data-loom-cookie-type')) {
            case 'script-block':
              $el.html($el.attr('data-loom-cookie-content'));
              break;
            default:
              $el.attr('src', $el.attr('data-loom-cookie-src'));
          }

          $el.attr('data-loom-cookie-category', null);
          $el.attr('data-loom-cookie-type', null);
          $el.attr('data-loom-cookie-content', null);
          $el.attr('data-loom-cookie-src', null);
        });
      }
    },

    modifyEUCookieComplianceFunctions: () => {
      // click on "Withdraw consent" -> show the banner again (no reset of the
      // settings)
      Drupal.eu_cookie_compliance.withdrawAction = () => {
        Drupal.eu_cookie_compliance.setStatus(0);
        Drupal.eu_cookie_compliance.setAcceptedCategories([]);
        let cookieName = (typeof Drupal.settings.eu_cookie_compliance.cookie_name ===
          'undefined' || Drupal.settings.eu_cookie_compliance.cookie_name ===
          '')
          ? 'cookie-agreed'
          : Drupal.settings.eu_cookie_compliance.cookie_name;
        if (typeof $.removeCookie !== 'undefined' ||
          $.removeCookie(cookieName) == false) {
          $.cookie(cookieName, null, {path: '/'});
        }

        Drupal.eu_cookie_compliance.execute();

        script.enabledCategories.forEach(categoryId => {
          $('#sliding-popup input[id="cookie-category-' + categoryId + '"]')
            .prop('checked', 'checked');
        });
        $.cookie('cookie-agreed-categories',
          JSON.stringify(script.enabledCategories), {path: '/'});
        $.cookie('cookie-agreed', 2, {path: '/'});
      };

      // One-Click for reopening the banner
      Drupal.eu_cookie_compliance.toggleWithdrawBanner = () => {
        Drupal.eu_cookie_compliance.withdrawAction();
      };
    },

    showOverlays: () => {
      $('iframe[data-loom-cookie-category]').each((n, el) => {
        let $iframe = $(el);
        if ($iframe.closest('.loom-cookie-iframe-wrapper').length) {
          return;
        }
        $iframe.wrap('<div class="loom-cookie-iframe-wrapper">');
        let $wrapper = $iframe.parent();
        $wrapper.append(
          '<span class="loom-cookie-iframe-message">' +
          $iframe.attr('data-loom-cookie-message') + '</span>');
      });
    },

    /**
     * Open banner without resetting the selected categories.
     */
    reopenBanner: () => {
      Drupal.eu_cookie_compliance.withdrawAction();
    },
  };

  Drupal.behaviors.loom_cookie_filter_scripts = script;
})(jQuery);
