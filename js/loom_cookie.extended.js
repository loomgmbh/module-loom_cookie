(function($) {
  let script = {
    attach: function(context, settings) {
      if (!Drupal.loom_cookie) {
        return;
      }

      // apply Drupal behaviors to popup
      $(document).on('loom_cookie_popup_open', function() {
        Drupal.attachBehaviors($(document).find('#sliding-popup')[0]);
      });

      script.enabledCategories = Drupal.loom_cookie.getAcceptedCategories();

      if (context == document) {
        script.modifyEUCookieComplianceFunctions();

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

    enabledCategories: [],

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
        $('[data-loom-cookie-category*="' + category + '"]').each(
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
      Drupal.loom_cookie.withdrawAction = function() {
        Drupal.loom_cookie.setStatus(0);
        Drupal.loom_cookie.setAcceptedCategories([]);
        let cookieName = (typeof drupalSettings.loom_cookie.cookie_name ===
          'undefined' || drupalSettings.loom_cookie.cookie_name ===
          '')
          ? 'cookie-agreed'
          : drupalSettings.loom_cookie.cookie_name;
        if (typeof $.removeCookie !== 'undefined' ||
          $.removeCookie(cookieName,
            {domain: drupalSettings.loom_cookie.domain}) == false) {
          $.cookie(cookieName, null, {
            path: '/',
            domain: drupalSettings.loom_cookie.domain,
          });
        }

        Drupal.loom_cookie.execute();

        script.enabledCategories.forEach(function(categoryId) {
          $('#sliding-popup input[id="cookie-category-' + categoryId + '"]')
            .prop('checked', 'checked');
        });
        $.cookie('cookie-agreed-categories',
          JSON.stringify(script.enabledCategories), {
            path: '/',
            domain: drupalSettings.loom_cookie.domain,
          });
        $.cookie('cookie-agreed', 2, {
          path: '/',
          domain: drupalSettings.loom_cookie.domain,
        });
      };

      // One-Click for reopening the banner
      Drupal.loom_cookie.toggleWithdrawBanner = function() {
        Drupal.loom_cookie.withdrawAction();
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
      Drupal.loom_cookie.withdrawAction();
    },
  };

  Drupal.behaviors.loom_cookie_filter_scripts = script;
})(jQuery);
