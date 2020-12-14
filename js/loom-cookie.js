(function($) {
  let script = {
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
          .on('click', '.eu-cookie-compliance-accept-all-button', function(e) {
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
    blockSomeScripts: function() {
      const originalCreateElement = document.createElement.bind(document);
      document.createElement = function(tagName, options) {
        // If this is not a script tag, bypass
        if (tagName.toLowerCase() !== 'script') {
          // Binding to document is essential
          return originalCreateElement(tagName, options);
        }

        const scriptElt = originalCreateElement(tagName, options);

        // Backup the original setAttribute function
        const originalSetAttribute = scriptElt.setAttribute.bind(scriptElt);

        // Define getters / setters to ensure that the script type is properly
        // set
        Object.defineProperties(scriptElt, {
          'src': {
            get: function() {
              return scriptElt.getAttribute('src');
            },
            set: function(value) {
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

      // Some scripts use navigator.sendBeacon to load further scripts.
      if (navigator.sendBeacon) {
        const originalSendBeacon = navigator.sendBeacon.bind(navigator);
        navigator.sendBeacon = function(url, data) {
          if (script.shouldBlockScript(url)) {
            return false;
          }

          return originalSendBeacon(url, data);
        };
      }

      // Some scripts use image elements to load further scripts.
      const originalImage = window.Image;
      // noinspection JSValidateTypes
      window.Image = function(width, height) {
        let img = new originalImage(width, height);

        const originalSetAttribute = img.setAttribute.bind(img);

        Object.defineProperties(img, {
          'src': {
            get: function() {
              return img.getAttribute('src');
            },
            set: function(value) {
              if (script.shouldBlockScript(value)) {
                value = '';
              }
              originalSetAttribute('src', value);
              return true;
            },
            configurable: true,
          },
        });

        return img;
      };
    },

    /**
     * Determines if a script source should be blocked client-side based on the
     * enabled categories.
     * @param src
     * @returns {boolean}
     */
    shouldBlockScript: function(src) {
      let disabledCategories = Object.keys(script.settings).filter(
        function(category) {
          return script.enabledCategories.indexOf(category) === -1;
        });

      // only block scripts of disabled categories
      for (let i in disabledCategories) {
        const category = disabledCategories[i];
        if (!script.settings[category].clientSideBlockedScripts) {
          continue;
        }

        if (typeof src == 'object' && src.toString) {
          src = src.toString();
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
    enableElements: function() {
      if (!script.enabledCategories.length) {
        // no scripts to reenable
        return;
      }

      for (let i in script.enabledCategories) {
        const category = script.enabledCategories[i];
        $('[data-loom-cookie-category="' + category + '"]').each(
          function(n, el) {
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

    modifyEUCookieComplianceFunctions: function() {
      // click on "Withdraw consent" -> show the banner again (no reset of the
      // settings)
      Drupal.eu_cookie_compliance.withdrawAction = function() {
        Drupal.eu_cookie_compliance.setStatus(0);
        Drupal.eu_cookie_compliance.setAcceptedCategories([]);
        let cookieName = (typeof drupalSettings.eu_cookie_compliance.cookie_name ===
          'undefined' || drupalSettings.eu_cookie_compliance.cookie_name ===
          '')
          ? 'cookie-agreed'
          : drupalSettings.eu_cookie_compliance.cookie_name;
        if (typeof $.removeCookie !== 'undefined' ||
          $.removeCookie(cookieName,
            {domain: drupalSettings.eu_cookie_compliance.domain}) == false) {
          $.cookie(cookieName, null, {
            path: '/',
            domain: drupalSettings.eu_cookie_compliance.domain,
          });
        }

        Drupal.eu_cookie_compliance.execute();

        script.enabledCategories.forEach(function(categoryId) {
          $('#sliding-popup input[id="cookie-category-' + categoryId + '"]')
            .prop('checked', 'checked');
        });
        $.cookie('cookie-agreed-categories',
          JSON.stringify(script.enabledCategories), {
            path: '/',
            domain: drupalSettings.eu_cookie_compliance.domain,
          });
        $.cookie('cookie-agreed', 2, {
          path: '/',
          domain: drupalSettings.eu_cookie_compliance.domain,
        });
      };

      // One-Click for reopening the banner
      Drupal.eu_cookie_compliance.toggleWithdrawBanner = function() {
        Drupal.eu_cookie_compliance.withdrawAction();
      };
    },

    showOverlays: function() {
      $('iframe[data-loom-cookie-category]').each(function(n, el) {
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
    reopenBanner: function() {
      Drupal.eu_cookie_compliance.withdrawAction();
    },
  };

  Drupal.behaviors.loom_cookie_filter_scripts = script;
})(jQuery);
