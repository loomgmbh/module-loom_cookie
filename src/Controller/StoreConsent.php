<?php

namespace Drupal\loom_cookie\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for JS call that stores consent.
 */
class StoreConsent extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function store($target) {
    // Get list of all plugins.
    $consent_storages = \Drupal::service('plugin.manager.loom_cookie.consent_storage');
    // Get the currently active plugin.
    $consent_storage_method = \Drupal::configFactory()
      ->get('loom_cookie.settings')
      ->get('consent_storage_method');
    // If we're not going to log consent, return NULL.
    if (!$consent_storage_method || $consent_storage_method == 'do_not_store') {
      return new JsonResponse(NULL);
    }

    // Get plugin.
    /* @var \Drupal\loom_cookie\Plugin\ConsentStorageInterface $consent_storage */
    $consent_storage = $consent_storages->createInstance($consent_storage_method);
    // Register consent.
    $result = $consent_storage->registerConsent($target);
    // Return value.
    return new JsonResponse($result);
  }

}
