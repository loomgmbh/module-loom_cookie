/**
 * @file
 * loom_cookie.main.js
 *
 * Defines the behavior of the loom cookie banner.
 *
 * Statuses:
 *  null: not yet agreed (or withdrawn), show popup
 *  0: Disagreed
 *  1: Agreed, show thank you banner
 *  2: Agreed
 */

(function ($, Drupal, drupalSettings) {

  'use strict';
  var euCookieComplianceBlockCookies;

  Drupal.behaviors.euCookieCompliancePopup = {
    attach: function (context) {
      $('body').once('eu-cookie-compliance').each(function () {
        // If configured, check JSON callback to determine if in EU.
        if (drupalSettings.loom_cookie.popup_eu_only_js) {
          if (Drupal.loom_cookie.showBanner()) {
            var url = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + 'loom-cookie-eu-check';
            var data = {};
            $.getJSON(url, data, function (data) {
              // If in the EU, show the compliance banner.
              if (data.in_eu) {
                Drupal.loom_cookie.execute();
              }

              // If not in EU, set an agreed cookie automatically.
              else {
                Drupal.loom_cookie.setStatus(2);
              }
            });
          }
        }

        // Otherwise, fallback to standard behavior which is to render the banner.
        else {
          Drupal.loom_cookie.execute();
        }
      });
    },
  };

  Drupal.loom_cookie = {};

  Drupal.loom_cookie.execute = function () {
    try {
      if (!drupalSettings.loom_cookie.popup_enabled) {
        return;
      }

      if (!Drupal.loom_cookie.cookiesEnabled()) {
        return;
      }

      var status = Drupal.loom_cookie.getCurrentStatus();
      if ((status === 0 && drupalSettings.loom_cookie.method === 'default') || status === null || (drupalSettings.loom_cookie.withdraw_enabled && drupalSettings.loom_cookie.withdraw_button_on_info_popup)) {
        if (!drupalSettings.loom_cookie.disagree_do_not_show_popup || status === null) {
          // Detect mobile here and use mobile_popup_html_info, if we have a mobile device.
          if (window.matchMedia('(max-width: ' + drupalSettings.loom_cookie.mobile_breakpoint + 'px)').matches && drupalSettings.loom_cookie.use_mobile_message) {
            Drupal.loom_cookie.createPopup(drupalSettings.loom_cookie.mobile_popup_html_info, (status !== null));
          } else {
            Drupal.loom_cookie.createPopup(drupalSettings.loom_cookie.popup_html_info, (status !== null));
          }
          Drupal.loom_cookie.initPopup();
        }
      }
      if (status === 2 && drupalSettings.loom_cookie.withdraw_enabled) {
        if (!drupalSettings.loom_cookie.withdraw_button_on_info_popup) {
          Drupal.loom_cookie.createWithdrawBanner(drupalSettings.loom_cookie.withdraw_markup);
        }
        Drupal.loom_cookie.attachWithdrawEvents();
      }
    }
    catch (e) {
    }
  };

  Drupal.loom_cookie.initPopup = function() {
    Drupal.loom_cookie.attachAgreeEvents();

    if (drupalSettings.loom_cookie.method === 'categories') {
      var categories_checked = [];

      if (Drupal.loom_cookie.getCurrentStatus() === null) {
        if (drupalSettings.loom_cookie.select_all_categories_by_default) {
          categories_checked = drupalSettings.loom_cookie.cookie_categories;
        }
      }
      else {
        categories_checked = Drupal.loom_cookie.getAcceptedCategories();
      }
      Drupal.loom_cookie.setPreferenceCheckboxes(categories_checked);
      Drupal.loom_cookie.attachSavePreferencesEvents();
    }

    if (drupalSettings.loom_cookie.withdraw_enabled && drupalSettings.loom_cookie.withdraw_button_on_info_popup) {
      Drupal.loom_cookie.attachWithdrawEvents();
      var currentStatus = Drupal.loom_cookie.getCurrentStatus();
      if (currentStatus === 1 || currentStatus === 2) {
        $('.eu-cookie-withdraw-button').show();
      }
    }
  }

  Drupal.loom_cookie.createWithdrawBanner = function (html) {
    var $html = $('<div></div>').html(html);
    var $banner = $('.eu-cookie-withdraw-banner', $html);
    $html.attr('id', 'sliding-popup');
    $html.addClass('eu-cookie-withdraw-wrapper');

    if (!drupalSettings.loom_cookie.popup_use_bare_css) {
      $banner.height(drupalSettings.loom_cookie.popup_height)
          .width(drupalSettings.loom_cookie.popup_width);
    }
    $html.hide();
    var height = 0;
    if (drupalSettings.loom_cookie.popup_position) {
      $html.prependTo('body');
      height = $html.outerHeight();

      $html.show()
          .addClass('sliding-popup-top')
          .addClass('clearfix')
        .css({ top: !drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top')) + height) : -1 * height });
      // For some reason, the tab outerHeight is -10 if we don't use a timeout
      // function to reveal the tab.
      setTimeout(function () {
        var height = $html.outerHeight();

        $html.animate({ top: !drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top')) + height) : -1 * height }, drupalSettings.loom_cookie.popup_delay, null, function () {
          $html.trigger('loom_cookie_popup_open');
        });
      }.bind($html), 0);
    } else {
      if (drupalSettings.loom_cookie.better_support_for_screen_readers) {
        $html.prependTo('body');
      } else {
        $html.appendTo('body');
      }
      height = $html.outerHeight();
      $html.show()
          .addClass('sliding-popup-bottom')
          .css({ bottom: -1 * height });
      // For some reason, the tab outerHeight is -10 if we don't use a timeout
      // function to reveal the tab.
      setTimeout(function () {
        var height = $html.outerHeight();

        $html.animate({ bottom: -1 * (height) }, drupalSettings.loom_cookie.popup_delay, null, function () {
          $html.trigger('loom_cookie_popup_open');
        });
      }.bind($html), 0);
    }
  };

  Drupal.loom_cookie.toggleWithdrawBanner = function () {
    var $wrapper = $('#sliding-popup');
    var $tab = $('.eu-cookie-withdraw-tab');
    var topBottom = (drupalSettings.loom_cookie.popup_position ? 'top' : 'bottom');
    var height = $wrapper.outerHeight();
    var $bannerIsShowing = drupalSettings.loom_cookie.popup_position ? parseInt($wrapper.css('top')) === (!drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top'))) : 0) : parseInt($wrapper.css('bottom')) === 0;
    if (drupalSettings.loom_cookie.popup_position) {
      if ($bannerIsShowing) {
        $wrapper.animate({ top: !drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top')) + height) : -1 * height}, drupalSettings.loom_cookie.popup_delay);
      }
      else {
        $wrapper.animate({ top: !drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top'))) : 0}, drupalSettings.loom_cookie.popup_delay);
      }
    }
    else {
      if ($bannerIsShowing) {
        $wrapper.animate({'bottom' : -1 * (height)}, drupalSettings.loom_cookie.popup_delay);
      }
      else {
        $wrapper.animate({'bottom' : 0}, drupalSettings.loom_cookie.popup_delay);
      }
    }
  };

  Drupal.loom_cookie.createPopup = function (html, closed) {
    // This fixes a problem with jQuery 1.9.
    var popup = $('<div></div>').html(html);
    popup.attr('id', 'sliding-popup');
    if (!drupalSettings.loom_cookie.popup_use_bare_css) {
      popup.height(drupalSettings.loom_cookie.popup_height)
          .width(drupalSettings.loom_cookie.popup_width);
    }

    popup.hide();
    var height = 0;
    if (drupalSettings.loom_cookie.popup_position) {
      popup.prependTo('body');
      height = popup.outerHeight();
      popup.show()
        .addClass('sliding-popup-top clearfix')
        .css({ top: -1 * height });
      if (closed !== true) {
        popup.animate({top: 0}, drupalSettings.loom_cookie.popup_delay, null, function () {
          popup.trigger('loom_cookie_popup_open');
        });
      }
    } else {
      if (drupalSettings.loom_cookie.better_support_for_screen_readers) {
        popup.prependTo('body');
      } else {
        popup.appendTo('body');
      }

      height = popup.outerHeight();
      popup.show()
        .addClass('sliding-popup-bottom')
        .css({bottom: -1 * height});
      if (closed !== true) {
        popup.animate({bottom: 0}, drupalSettings.loom_cookie.popup_delay, null, function () {
          popup.trigger('loom_cookie_popup_open');
        });
      }
    }
  };

  Drupal.loom_cookie.attachAgreeEvents = function () {
    var clickingConfirms = drupalSettings.loom_cookie.popup_clicking_confirmation;
    var scrollConfirms = drupalSettings.loom_cookie.popup_scrolling_confirmation;

    if (drupalSettings.loom_cookie.method === 'categories' && drupalSettings.loom_cookie.enable_save_preferences_button) {
        // The agree button becomes an agree to all categories button when the 'save preferences' button is present.
        $('.agree-button').click(Drupal.loom_cookie.acceptAllAction);
    }
    else {
        $('.agree-button').click(Drupal.loom_cookie.acceptAction);
    }
    $('.decline-button').click(Drupal.loom_cookie.declineAction);

    if (clickingConfirms) {
      $('a, input[type=submit], button[type=submit]').not('.popup-content *').bind('click.euCookieCompliance', Drupal.loom_cookie.acceptAction);
    }

    if (scrollConfirms) {
      var alreadyScrolled = false;
      var scrollHandler = function () {
        if (alreadyScrolled) {
          Drupal.loom_cookie.acceptAction();
          $(window).off('scroll', scrollHandler);
        } else {
          alreadyScrolled = true;
        }
      };

      $(window).bind('scroll', scrollHandler);
    }

    $('.find-more-button').not('.find-more-button-processed').addClass('find-more-button-processed').click(Drupal.loom_cookie.moreInfoAction);
  };

  Drupal.loom_cookie.attachSavePreferencesEvents = function () {
    $('.eu-cookie-compliance-save-preferences-button').click(Drupal.loom_cookie.savePreferencesAction);
  };

  Drupal.loom_cookie.attachHideEvents = function () {
    var clickingConfirms = drupalSettings.loom_cookie.popup_clicking_confirmation;
    $('.hide-popup-button').click(function () {
          Drupal.loom_cookie.changeStatus(2);
        }
    );
    if (clickingConfirms) {
      $('a, input[type=submit], button[type=submit]').unbind('click.euCookieCompliance');
    }

    $('.find-more-button').not('.find-more-button-processed').addClass('find-more-button-processed').click(Drupal.loom_cookie.moreInfoAction);
  };

  Drupal.loom_cookie.attachWithdrawEvents = function () {
    $('.eu-cookie-withdraw-button').click(Drupal.loom_cookie.withdrawAction);
    $('.eu-cookie-withdraw-tab').click(Drupal.loom_cookie.toggleWithdrawBanner);
  };

  Drupal.loom_cookie.acceptAction = function () {
    Drupal.loom_cookie.setStatus(1);
    var nextStatus = 2;

    if (!euCookieComplianceHasLoadedScripts && typeof euCookieComplianceLoadScripts === "function") {
      euCookieComplianceLoadScripts();
    }

    if (typeof euCookieComplianceBlockCookies !== 'undefined') {
      clearInterval(euCookieComplianceBlockCookies);
    }

    if (drupalSettings.loom_cookie.method === 'categories') {
      // Select Checked categories.
      var categories = $("#eu-cookie-compliance-categories input:checkbox:checked").map(function(){
        return $(this).val();
      }).get();
      Drupal.loom_cookie.setAcceptedCategories(categories);
      // Load scripts for all categories.
      Drupal.loom_cookie.loadCategoryScripts(categories);
    }

    Drupal.loom_cookie.changeStatus(nextStatus);
  };

  Drupal.loom_cookie.acceptAllAction = function () {
    var allCategories = drupalSettings.loom_cookie.cookie_categories;
    Drupal.loom_cookie.setPreferenceCheckboxes(allCategories);
    Drupal.loom_cookie.acceptAction();
  }

  Drupal.loom_cookie.savePreferencesAction = function () {
    var categories = $("#eu-cookie-compliance-categories input:checkbox:checked").map(function(){
      return $(this).val();
    }).get();
    Drupal.loom_cookie.setStatus(1);
    var nextStatus = 2;

    Drupal.loom_cookie.setAcceptedCategories(categories);
    if (!euCookieComplianceHasLoadedScripts && typeof euCookieComplianceLoadScripts === "function") {
      euCookieComplianceLoadScripts();
    }
    Drupal.loom_cookie.loadCategoryScripts(categories);
    Drupal.loom_cookie.changeStatus(nextStatus);
  };

  Drupal.loom_cookie.loadCategoryScripts = function(categories) {
    for (var cat in categories) {
      if (euCookieComplianceHasLoadedScriptsForCategory[cat] !== true && typeof euCookieComplianceLoadScripts === "function") {
        euCookieComplianceLoadScripts(categories[cat]);
        euCookieComplianceHasLoadedScriptsForCategory[cat] = true;
      }
    }
  }

  Drupal.loom_cookie.declineAction = function () {
    Drupal.loom_cookie.setStatus(0);
    var popup = $('#sliding-popup');
    if (popup.hasClass('sliding-popup-top')) {
      popup.animate({ top: !drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top')) + popup.outerHeight()) : popup.outerHeight() * -1 }, drupalSettings.loom_cookie.popup_delay, null, function () {
        popup.hide();
      }).trigger('loom_cookie_popup_close');
    }
    else {
      popup.animate({ bottom: popup.outerHeight() * -1 }, drupalSettings.loom_cookie.popup_delay, null, function () {
        popup.hide();
      }).trigger('loom_cookie_popup_close');
    }
  };

  Drupal.loom_cookie.withdrawAction = function () {
    Drupal.loom_cookie.setStatus(0);
    Drupal.loom_cookie.setAcceptedCategories([]);
    location.reload();
  };

  Drupal.loom_cookie.moreInfoAction = function () {
    if (drupalSettings.loom_cookie.disagree_do_not_show_popup) {
      Drupal.loom_cookie.setStatus(0);
      if (drupalSettings.loom_cookie.withdraw_enabled && drupalSettings.loom_cookie.withdraw_button_on_info_popup) {
        $('#sliding-popup .eu-cookie-compliance-banner').trigger('loom_cookie_popup_close').hide();
      }
      else {
        $('#sliding-popup').trigger('loom_cookie_popup_close').remove();
      }
    } else {
      if (drupalSettings.loom_cookie.popup_link_new_window) {
        window.open(drupalSettings.loom_cookie.popup_link);
      } else {
        window.location.href = drupalSettings.loom_cookie.popup_link;
      }
    }
  };

  Drupal.loom_cookie.getCurrentStatus = function () {
    var cookieName = (typeof drupalSettings.loom_cookie.cookie_name === 'undefined' || drupalSettings.loom_cookie.cookie_name === '') ? 'cookie-agreed' : drupalSettings.loom_cookie.cookie_name;
    var value = $.cookie(cookieName);
    value = parseInt(value);
    if (isNaN(value)) {
      value = null;
    }

    return value;
  };

  Drupal.loom_cookie.setPreferenceCheckboxes = function (categories) {
    for (var i in categories) {
      $("#eu-cookie-compliance-categories input:checkbox[value='" + categories[i] + "']").prop("checked", true);
    }
  }

  Drupal.loom_cookie.getAcceptedCategories = function () {
    var allCategories = drupalSettings.loom_cookie.cookie_categories;
    var cookieName = (typeof drupalSettings.loom_cookie.cookie_name === 'undefined' || drupalSettings.loom_cookie.cookie_name === '') ? 'cookie-agreed-categories' : drupalSettings.loom_cookie.cookie_name + '-categories';
    var value = $.cookie(cookieName);
    var selectedCategories = [];

    if (value !== null && typeof value !== 'undefined') {
      value = JSON.parse(value);
      selectedCategories = value;
    }

    if (Drupal.loom_cookie.fix_first_cookie_category && !$.inArray(allCategories[0], selectedCategories)) {
      selectedCategories.push(allCategories[0]);
    }

    return selectedCategories;
  };

  Drupal.loom_cookie.changeStatus = function (value) {
    var status = Drupal.loom_cookie.getCurrentStatus();
    var reloadPage = drupalSettings.loom_cookie.reload_page;
    if (status === value) {
      return;
    }

    if (drupalSettings.loom_cookie.popup_position) {
      $('.sliding-popup-top').animate({ top: !drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top')) + $('#sliding-popup').outerHeight()) : $('#sliding-popup').outerHeight() * -1 }, drupalSettings.loom_cookie.popup_delay, function () {
        if (value === 1 && status === null && !reloadPage) {
          $('.sliding-popup-top').not('.eu-cookie-withdraw-wrapper').html('').animate({ top: !drupalSettings.loom_cookie.fixed_top_position ? -(parseInt($('body').css('padding-top')) + parseInt($('body').css('margin-top'))) : 0 }, drupalSettings.loom_cookie.popup_delay);
          Drupal.loom_cookie.attachHideEvents();
        } else if (status === 1 && !(drupalSettings.loom_cookie.withdraw_enabled && drupalSettings.loom_cookie.withdraw_button_on_info_popup)) {
          $('.sliding-popup-top').not('.eu-cookie-withdraw-wrapper').trigger('loom_cookie_popup_close').remove();
        }
        Drupal.loom_cookie.showWithdrawBanner(value);
      });
    } else {
      $('.sliding-popup-bottom').animate({ bottom: $('#sliding-popup').outerHeight() * -1 }, drupalSettings.loom_cookie.popup_delay, function () {
        if (value === 1 && status === null && !reloadPage) {
          $('.sliding-popup-bottom').not('.eu-cookie-withdraw-wrapper').html('').animate({ bottom: 0 }, drupalSettings.loom_cookie.popup_delay);
          Drupal.loom_cookie.attachHideEvents();
        } else if (status === 1) {
          if (drupalSettings.loom_cookie.withdraw_enabled && drupalSettings.loom_cookie.withdraw_button_on_info_popup) {
            // Restore popup content.
            if (window.matchMedia('(max-width: ' + drupalSettings.loom_cookie.mobile_breakpoint + 'px)').matches && drupalSettings.loom_cookie.use_mobile_message) {
              $('.sliding-popup-bottom').not('.eu-cookie-withdraw-wrapper').html(drupalSettings.loom_cookie.mobile_popup_html_info);
            } else {
              $('.sliding-popup-bottom').not('.eu-cookie-withdraw-wrapper').html(drupalSettings.loom_cookie.popup_html_info);
            }
            Drupal.loom_cookie.initPopup();
          }
          else {
            $('.sliding-popup-bottom').not('.eu-cookie-withdraw-wrapper').trigger('loom_cookie_popup_close').remove();
          }
        }
        Drupal.loom_cookie.showWithdrawBanner(value);
      });
    }

    if (drupalSettings.loom_cookie.reload_page) {
      location.reload();
    }

    Drupal.loom_cookie.setStatus(value);
  };

  Drupal.loom_cookie.showWithdrawBanner = function (value) {
    if (value === 2 && drupalSettings.loom_cookie.withdraw_enabled) {
      if (!drupalSettings.loom_cookie.withdraw_button_on_info_popup) {
        Drupal.loom_cookie.createWithdrawBanner(drupalSettings.loom_cookie.withdraw_markup);
      }
      Drupal.loom_cookie.attachWithdrawEvents();
    }
  };

  Drupal.loom_cookie.setStatus = function (status) {
    var date = new Date();
    var domain = drupalSettings.loom_cookie.domain ? drupalSettings.loom_cookie.domain : '';
    var path = drupalSettings.loom_cookie.domain_all_sites ? '/' : drupalSettings.path.baseUrl;
    var cookieName = (typeof drupalSettings.loom_cookie.cookie_name === 'undefined' || drupalSettings.loom_cookie.cookie_name === '') ? 'cookie-agreed' : drupalSettings.loom_cookie.cookie_name;
    if (path.length > 1) {
      var pathEnd = path.length - 1;
      if (path.lastIndexOf('/') === pathEnd) {
        path = path.substring(0, pathEnd);
      }
    }

    var cookie_session = parseInt(drupalSettings.loom_cookie.cookie_session);
    if (cookie_session) {
      $.cookie(cookieName, status, { path: path, domain: domain });
    } else {
      var lifetime = parseInt(drupalSettings.loom_cookie.cookie_lifetime);
      date.setDate(date.getDate() + lifetime);
      $.cookie(cookieName, status, { expires: date, path: path, domain: domain });
    }
    $(document).trigger('loom_cookie.changeStatus', [status]);

    // Store consent if applicable.
    if (drupalSettings.loom_cookie.store_consent && status === 2) {
      var url = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + 'loom-cookie/store_consent/banner';
      $.post(url, {}, function (data) { });
    }
  };

  Drupal.loom_cookie.setAcceptedCategories = function (categories) {
    var date = new Date();
    var domain = drupalSettings.loom_cookie.domain ? drupalSettings.loom_cookie.domain : '';
    var path = drupalSettings.loom_cookie.domain_all_sites ? '/' : drupalSettings.path.baseUrl;
    var cookieName = (typeof drupalSettings.loom_cookie.cookie_name === 'undefined' || drupalSettings.loom_cookie.cookie_name === '') ? 'cookie-agreed-categories' : drupalSettings.loom_cookie.cookie_name + '-categories';
    if (path.length > 1) {
      var pathEnd = path.length - 1;
      if (path.lastIndexOf('/') === pathEnd) {
        path = path.substring(0, pathEnd);
      }
    }
    var categoriesString = JSON.stringify(categories);
    var cookie_session = parseInt(drupalSettings.loom_cookie.cookie_session);
    if (cookie_session) {
      $.cookie(cookieName, categoriesString, { path: path, domain: domain });
    } else {
      var lifetime = parseInt(drupalSettings.loom_cookie.cookie_lifetime);
      date.setDate(date.getDate() + lifetime);
      $.cookie(cookieName, categoriesString, { expires: date, path: path, domain: domain });
    }
    $(document).trigger('loom_cookie.changePreferences', [categories]);

    // TODO: Store categories with consent if applicable?
  };

  Drupal.loom_cookie.hasAgreed = function (category) {
    var status = Drupal.loom_cookie.getCurrentStatus();
    var agreed = (status === 1 || status === 2);

    if(category !== undefined && agreed) {
      agreed = Drupal.loom_cookie.hasAgreedWithCategory(category);
    }

    return agreed;
  };

  Drupal.loom_cookie.hasAgreedWithCategory = function(category) {
    var allCategories = drupalSettings.loom_cookie.cookie_categories;
    var agreedCategories = Drupal.loom_cookie.getAcceptedCategories();

    if (drupalSettings.loom_cookie.fix_first_cookie_category && category === allCategories[0]) {
      return true;
    }

    return $.inArray(category, agreedCategories) !== -1;
  };

  Drupal.loom_cookie.showBanner = function () {
    var showBanner = false;
    var status = Drupal.loom_cookie.getCurrentStatus();
    if ((status === 0 && drupalSettings.loom_cookie.method === 'default') || status === null) {
      if (!drupalSettings.loom_cookie.disagree_do_not_show_popup || status === null) {
        showBanner = true;
      }
    }

    return showBanner;
  };

  Drupal.loom_cookie.cookiesEnabled = function () {
    var cookieEnabled = (navigator.cookieEnabled);
    if (typeof navigator.cookieEnabled === 'undefined' && !cookieEnabled) {
      $.cookie('testcookie', 'testcookie', { expires: 100 });
      cookieEnabled = ($.cookie('testcookie').indexOf('testcookie') !== -1);
    }

    return (cookieEnabled);
  };

  Drupal.loom_cookie.isWhitelisted = function (cookieName) {
    // Skip the PHP session cookie.
    if (cookieName.indexOf('SESS') === 0 || cookieName.indexOf('SSESS') === 0) {
      return true;
    }
    // Split the white-listed cookies.
    var euCookieComplianceWhitelist = drupalSettings.loom_cookie.whitelisted_cookies.split(/\r\n|\n|\r/g);

    // Add the LOOM Cookie Compliance cookie.
    euCookieComplianceWhitelist.push((typeof drupalSettings.loom_cookie.cookie_name === 'undefined' || drupalSettings.loom_cookie.cookie_name === '') ? 'cookie-agreed' : drupalSettings.loom_cookie.cookie_name);
    euCookieComplianceWhitelist.push((typeof drupalSettings.loom_cookie.cookie_name === 'undefined' || drupalSettings.loom_cookie.cookie_name === '') ? 'cookie-agreed-categories' : drupalSettings.loom_cookie.cookie_name + '-categories');

    // Check if the cookie is white-listed.
    for (var item in euCookieComplianceWhitelist) {
      if (cookieName === euCookieComplianceWhitelist[item]) {
        return true;
      }
      // Handle cookie names that are prefixed with a category.
      if (drupalSettings.loom_cookie.method === 'categories') {
        var separatorPos = euCookieComplianceWhitelist[item].indexOf(":");
        if (separatorPos !== -1) {
          var category = euCookieComplianceWhitelist[item].substr(0, separatorPos);
          var wlCookieName = euCookieComplianceWhitelist[item].substr(separatorPos + 1);

          if (wlCookieName === cookieName && Drupal.loom_cookie.hasAgreedWithCategory(category)) {
            return true;
          }
        }
      }
    }

    return false;
  }

  /**
   * @todo: Legacy, remove if possible
   */
  Drupal.eu_cookie_compliance = Drupal.loom_cookie;

  // Load blocked scripts if the user has agreed to being tracked.
  var euCookieComplianceHasLoadedScripts = false;
  var euCookieComplianceHasLoadedScriptsForCategory = [];
  $(function () {
    if (Drupal.loom_cookie.hasAgreed()
        || (Drupal.loom_cookie.getCurrentStatus() === null && drupalSettings.loom_cookie.method !== 'opt_in' && drupalSettings.loom_cookie.method !== 'categories')
    ) {
      if (typeof euCookieComplianceLoadScripts === "function") {
        euCookieComplianceLoadScripts();
      }
      euCookieComplianceHasLoadedScripts = true;

      if (drupalSettings.loom_cookie.method === 'categories') {
        var acceptedCategories = Drupal.loom_cookie.getAcceptedCategories();
        Drupal.loom_cookie.loadCategoryScripts(acceptedCategories);
      }
    }
  });

  // Block cookies when the user hasn't agreed.
  if ((drupalSettings.loom_cookie.method === 'opt_in' && (Drupal.loom_cookie.getCurrentStatus() === null  || !Drupal.loom_cookie.hasAgreed()))
      || (drupalSettings.loom_cookie.method === 'opt_out' && !Drupal.loom_cookie.hasAgreed() && Drupal.loom_cookie.getCurrentStatus() !== null)
      || (drupalSettings.loom_cookie.method === 'categories')
  ) {
    var euCookieComplianceBlockCookies = setInterval(function () {
      // Load all cookies from jQuery.
      var cookies = $.cookie();

      // Check each cookie and try to remove it if it's not white-listed.
      for (var i in cookies) {
        var remove = true;
        var hostname = window.location.hostname;
        var cookieRemoved = false;
        var index = 0;

        remove = !Drupal.loom_cookie.isWhitelisted(i);

        // Remove the cookie if it's not white-listed.
        if (remove) {
          while (!cookieRemoved && hostname !== '') {
            // Attempt to remove.
            cookieRemoved = $.removeCookie(i, { domain: '.' + hostname, path: '/' });
            if (!cookieRemoved) {
              cookieRemoved = $.removeCookie(i, { domain: hostname, path: '/' });
            }

            index = hostname.indexOf('.');

            // We can be on a sub-domain, so keep checking the main domain as well.
            hostname = (index === -1) ? '' : hostname.substring(index + 1);
          }

          // Some jQuery Cookie versions don't remove cookies well.  Try again
          // using plain js.
          if (!cookieRemoved) {
            document.cookie = i + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/;';
          }
        }
      }
    }, 5000);
  }

})(jQuery, Drupal, drupalSettings);
