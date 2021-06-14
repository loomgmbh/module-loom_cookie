/**
 * @property {Object} window.loomCookieSettings
 * @property {Object} window.loomCookieSettingsECC
 */

(function() {
  let script = {
    init: function() {
      script.settings = window.loomCookieSettings;
      script.enabledCategories = script.getAcceptedCategories();
      script.disabledCategories = Object.keys(script.settings).filter(
        function(category) {
          return script.enabledCategories.indexOf(category) === -1;
        });

      script.blockSomeScripts();
    },

    settings: [],
    enabledCategories: [],
    disabledCategories: [],

    getAcceptedCategories: function() {
      let allCategories = window.loomCookieSettingsECC.cookie_categories;
      const cookieName = (typeof window.loomCookieSettingsECC.cookie_name ===
        'undefined' || window.loomCookieSettingsECC.cookie_name === '')
        ? 'cookie-agreed-categories'
        : window.loomCookieSettingsECC.cookie_name + '-categories';
      const cookies = document.cookie.split(';').map(function(c) {
        return c.trim().split('=').map(decodeURIComponent);
      }).reduce(function(a, b) {
        try {
          a[b[0]] = JSON.parse(b[1]);
        }
        catch (e) {
          a[b[0]] = b[1];
        }
        return a;
      }, {});
      let value = cookies[cookieName];
      let selectedCategories = [];

      if (value !== null && typeof value !== 'undefined') {
        // value = JSON.parse(value);
        selectedCategories = value;
      }

      if (window.loomCookieSettingsECC.fix_first_cookie_category &&
        selectedCategories.indexOf(allCategories[0]) == -1) {
        selectedCategories.push(allCategories[0]);
      }

      return selectedCategories;
    },

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
              let return_val = scriptElt.getAttribute('src');
              if (return_val == null) return_val = '';
              return return_val;
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
      // only block scripts of disabled categories
      for (let i in script.disabledCategories) {
        const category = script.disabledCategories[i];
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
  };

  script.init();
})();
