<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates Menus Links informations.
 *
 * This class contains primarily bridged hooks for menu links like :
 * links.actions, links.contextual, links.task, links.menu plugins.
 */
class MenuLinksInfo implements ContainerInjectionInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * The Routes Manager Plugin.
   *
   * @var \Drupal\form_mode_manager\EntityRoutingMapManager
   */
  protected $entityRoutingMap;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\form_mode_manager\EntityRoutingMapManager $plugin_routes_manager
   *   Plugin EntityRoutingMap to retrieve entity form operation routes.
   */
  public function __construct(FormModeManagerInterface $form_mode_manager, EntityRoutingMapManager $plugin_routes_manager) {
    $this->formModeManager = $form_mode_manager;
    $this->entityRoutingMap = $plugin_routes_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_mode.manager'),
      $container->get('plugin.manager.entity_routing_map')
    );
  }

  /**
   * Hide default entity contextualLinks.
   *
   * This is an alter hook bridge.
   *
   * We need to hide default contextual links to manage permission of,
   * "Edit as default" contextual links.
   *
   * @param array $links
   *   An associative array containing contextual links for the given $group,
   *   as described above. The array keys are used to build CSS class names for
   *   contextual links and must therefore be unique for each set of contextual
   *   links.
   * @param string $group
   *   The group of contextual links being rendered.
   * @param array $route_parameters
   *   The route parameters passed to each route_name of the contextual links.
   *   For example :.
   *
   * @code
   *   array('entityTypeId' => $entity->id())
   * @endcode
   *
   * @see hook_contextual_links_alter()
   */
  public function contextualLinksAlter(array &$links, $group, array $route_parameters) {
    $available_entity_types = array_keys($this->formModeManager->getAllFormModesDefinitions());
    foreach ($available_entity_types as $entity_type_id) {
      /** @var \Drupal\form_mode_manager\EntityRoutingMapBase $entity_handler_mapping */
      $entity_handler_mapping = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id]);
      $contextual_links_edit_id = $entity_handler_mapping->getContextualLink('edit');
      if ($contextual_links_edit_id && isset($links[$contextual_links_edit_id])) {
        unset($links[$contextual_links_edit_id]);
      }
    }
  }

}
