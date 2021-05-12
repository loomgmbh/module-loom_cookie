<?php

namespace Drupal\loom_cookie\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class CheckIfEuCountryJs {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    if (\Drupal::config('loom_cookie.settings')->get('eu_only_js')) {
      $routes['loom_cookie.check_if_eu_country_js'] = new Route(
        '/loom-cookie-eu-check',
        [
          '_controller' => '\Drupal\loom_cookie\Controller\CheckIfEuCountryJsController::content',
        ],
        [
          '_permission' => 'access content',
        ]
      );
    }
    return $routes;
  }

}
