<?php

namespace Drupal\form_mode_manager;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages EntityRoutingMap plugins.
 */
class EntityRoutingMapManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a new EntityRoutingMapManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityRoutingMap', $namespaces, $module_handler, 'Drupal\form_mode_manager\EntityRoutingMapInterface', 'Drupal\form_mode_manager\Annotation\EntityRoutingMap');
    $this->alterInfo('entity_routing_map_info');
    $this->setCacheBackend($cache_backend, 'form_mode_manager_routes_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'generic';
  }

}
