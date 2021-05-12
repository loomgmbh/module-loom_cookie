<?php

namespace Drupal\loom_cookie\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;

/**
 * Provides an ConsentStorage plugin manager.
 *
 * @see \Drupal\loom_cookie\Annotation\ConsentStorage
 * @see \Drupal\loom_cookie\Plugin\ConsentStorageInterface
 * @see plugin_api
 */
class ConsentStorageManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a ConsentStorageManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ConsentStorage',
      $namespaces,
      $module_handler,
      'Drupal\loom_cookie\Plugin\ConsentStorageInterface',
      'Drupal\loom_cookie\Annotation\ConsentStorage'
    );
    $this->alterInfo('consent_storage_info');
    $this->setCacheBackend($cache_backend, 'consent_storage_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'basic';
  }

}
