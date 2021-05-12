<?php

namespace Drupal\loom_cookie\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for JS call that checks if the visitor is in the EU.
 */
class CheckIfEuCountryJsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $data = _loom_cookie_user_in_eu();

    // Allow other modules to alter the geo IP matching logic.
    \Drupal::moduleHandler()->alter('loom_cookie_geoip_match', $data);

    return new JsonResponse($data, 200, ['Cache-Control' => 'private']);
  }

}
