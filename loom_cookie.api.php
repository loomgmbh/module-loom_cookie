<?php

/**
 * @file
 * Hooks specific to the LOOM Cookie module.
 */

/**
 * @addtogroup hooks
 * @{
 * Hooks that extend the LOOM Cookie module.
 */

/**
 * Alter the geo_ip_match variable.
 *
 * @param bool &$geoip_match
 *   Whether to show the cookie banner.
 */
function hook_loom_cookie_geoip_match_alter(&$geoip_match) {
  $geoip_match = FALSE;
}

/**
 * Alter hook to provide advanced logic for hiding the banner.
 *
 * @param bool $show_popup
 *   Whether to show the banner.
 */
function hook_loom_cookie_show_popup_alter(&$show_popup) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node && $node->type === 'my_type') {
    $show_popup = FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
