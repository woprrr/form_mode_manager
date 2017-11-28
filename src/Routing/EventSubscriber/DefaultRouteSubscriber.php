<?php

namespace Drupal\form_mode_manager\Routing\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Drupal\form_mode_manager\EntityRoutingMapManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for form_mode_manager routes.
 */
class DefaultRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\form_mode_manager\EntityRoutingMapManager $plugin_routes_manager
   *   Plugin EntityRoutingMap to retrieve entity form operation routes.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, FormModeManagerInterface $form_mode_manager, EntityRoutingMapManager $plugin_routes_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->formModeManager = $form_mode_manager;
    $this->entityRoutingMap = $plugin_routes_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    foreach (array_keys($form_modes_definitions) as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $this->enhanceDefaultRoutes($collection, $entity_type->id());
    }
  }

  /**
   * Enhance existing entity operation routes (add_page, add_form, edit_form).
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  protected function enhanceDefaultRoutes(RouteCollection $collection, $entity_type_id) {
    $this->enhanceDefaultAddPageRoutes($collection, $entity_type_id);
    $this->enhanceDefaultAddRoutes($collection, $entity_type_id);
    $this->enhanceDefaultEditRoutes($collection, $entity_type_id);
  }

  /**
   * Enhance entity operation routes add_page.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  protected function enhanceDefaultAddPageRoutes(RouteCollection $collection, $entity_type_id) {
    $entity_add_page = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id])->getOperation('add_page');
    if ($entity_add_page && $route = $collection->get($entity_add_page)) {
      $collection->add($entity_add_page, $this->routeEnhancer($route, $entity_type_id));
    }
  }

  /**
   * Enhance entity operation routes add.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  protected function enhanceDefaultAddRoutes(RouteCollection $collection, $entity_type_id) {
    $entity_route_name = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id])->getOperation('add_form');
    if ($route = $collection->get($entity_route_name)) {
      $collection->add($entity_route_name, $this->routeEnhancer($route, $entity_type_id));
    }
  }

  /**
   * Enhance entity operation routes edit.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  protected function enhanceDefaultEditRoutes(RouteCollection $collection, $entity_type_id) {
    $entity_route_name = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id])->getOperation('edit_form');
    if ($route = $collection->get($entity_route_name)) {
      $collection->add($entity_route_name, $this->routeEnhancer($route, $entity_type_id));
    }
  }

  /**
   * Add required parameters on route basis.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object of entity.
   * @param string $entity_type_id
   *   The ID of the entity type.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route enhanced.
   */
  public function routeEnhancer(Route $route, $entity_type_id) {
    $route->setRequirement('_permission', "use $entity_type_id.default form mode");
    return $route;
  }

}
