<?php

namespace Drupal\loom_cookie\Plugin\ConsentStorage;

use Drupal;
use Drupal\loom_cookie\Plugin\ConsentStorageBase;

/**
 * Provides a database storage for cookie consents.
 *
 * @ConsentStorage(
 *   id = "basic",
 *   name = @Translation("Basic storage"),
 *   description = @Translation("Basic storage")
 * )
 */
class BasicConsentStorage extends ConsentStorageBase {

  /**
   * {@inheritdoc}
   */
  public function registerConsent($consent_type) {
    $revision_id = $this->getCurrentPolicyNodeRevision();
    $timestamp = time();
    $ip_address = Drupal::request()->getClientIp();
    $uid = Drupal::currentUser()->id();
    $categories = !empty($_COOKIE['cookie-agreed-categories'])
      ? $_COOKIE['cookie-agreed-categories'] : '[]';

    $consent = [
      'type' => $consent_type,
      'categories' => json_decode($categories),
    ];

    Drupal::database()->insert('loom_cookie_basic_consent')->fields(
      [
        'uid' => $uid,
        'ip_address' => $ip_address,
        'timestamp' => $timestamp,
        'revision_id' => $revision_id ? $revision_id : 0,
        'consent_type' => json_encode($consent),
      ]
    )->execute();
    return TRUE;
  }

}
