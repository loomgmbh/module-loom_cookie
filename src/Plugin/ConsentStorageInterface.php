<?php

namespace Drupal\loom_cookie\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for consent storages.
 *
 * @see \Drupal\loom_cookie\Plugin\ConsentStorageBase
 * @see \Drupal\loom_cookie\Plugin\ConsentStorageManager
 * @see \Drupal\loom_cookie\Plugin\ConsentStorageManagerInterface
 * @see plugin_api
 */
interface ConsentStorageInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the consent storage label.
   *
   * @return string
   *   The consent storage label.
   */
  public function label();

  /**
   * Returns the consent storage description.
   *
   * @return string
   *   The consent storage description.
   */
  public function description();

}
