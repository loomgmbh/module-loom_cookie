<?php

namespace Drupal\loom_cookie\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Collects available consent storages.
 */
interface ConsentStorageManagerInterface extends PluginManagerInterface {

  /**
   * Get all available loom_cookie storage plugin instances.
   *
   * @param array $configuration
   *   Export configuration (aka export options).
   *
   * @return \Drupal\loom_cookie\Plugin\ConsentStorageInterface[]
   *   An array of all available loom_cookie consent plugin instances.
   */
  public function getInstances(array $configuration = []);

  /**
   * Get consent storage plugins as options.
   *
   * @return array
   *   An associative array of options keyed by plugin id.
   */
  public function getOptions();

}
