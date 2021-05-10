<?php

namespace Drupal\loom_cookie\Plugin\ConsentStorage;

use Drupal;
use Drupal\loom_cookie_compliance\Plugin\ConsentStorageBase;

/**
 * Provides a database storage for cookie consents.
 *
 * @ConsentStorage(
 *   id = "loom_cookie",
 *   name = @Translation("Loom cookie consent storage"),
 *   description = @Translation("Loom cookie consent storage")
 * )
 */
class LoomCookieConsentStorage extends ConsentStorageBase {

  /**
   * {@inheritdoc}
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
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

    Drupal::database()->insert('loom_cookie_compliance_basic_consent')->fields(
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
