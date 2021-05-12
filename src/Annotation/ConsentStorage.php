<?php

namespace Drupal\loom_cookie\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a consent storage annotation object.
 *
 * Plugin Namespace: Plugin\ConsentStorage.
 *
 * For a working example, see
 * \Drupal\loom_cookie\Plugin\ConsentStorage\BasicConsentStorage/registerConsent
 *
 * @see hook_loom_cookie_consent_storage_info_alter()
 * @see \Drupal\loom_cookie\Plugin\ConsentStorageInterface
 * @see \Drupal\loom_cookie\Plugin\ConsentStorageBase
 * @see \Drupal\loom_cookie\Plugin\ConsentStorageManager
 * @see plugin_api
 *
 * @Annotation
 */
class ConsentStorage extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the consent storage.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the consent storage.
   *
   * This will be shown when adding or configuring this consent storage.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
