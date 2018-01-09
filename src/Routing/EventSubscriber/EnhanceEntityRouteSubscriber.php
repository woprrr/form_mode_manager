<?php

namespace Drupal\form_mode_manager\Routing\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Drupal\form_mode_manager\EntityRoutingMapManager;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route event and enhance existing routes.
 *
 * To provide a more flexible system as possible we need to add some,
 * parameters dynamically onto each entities using form modes to,
 * applies our logic. In the current case Form Mode Manager provide,
 * ability to add more access granularity to `Default` entity routes.
 */
class EnhanceEntityRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityDefinition;

  /**
   * The Routes Manager Plugin.
   *
   * @var \Drupal\form_mode_manager\EntityRoutingMapBase
   */
  protected $entityRoutingDefinition;

  /**
   * The Routes Manager Plugin.
   *
   * @var \Drupal\form_mode_manager\EntityRoutingMapManager
   */
  protected $entityRoutingMap;

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
   * The route collection to add routes.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

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
   * Add form mode manager requirements to add more access granularity.
   *
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $entity_type_ids = array_keys($this->formModeManager->getAllFormModesDefinitions());
    $this->routeCollection = $collection;
    foreach ($entity_type_ids as $entity_type_id) {
      $this->entityDefinition = $this->entityTypeManager->getDefinition($entity_type_id);
      $this->entityRoutingDefinition = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id]);
      $this->enhanceDefaultEntityRoute('add_form');
      $this->enhanceDefaultEntityRoute('edit_form');

      // This operation doesn't exist for unbundled entities.
      if (!empty($this->entityDefinition->getKey('bundle'))) {
        $this->enhanceDefaultEntityRoute('add_page');
      }
    }
  }

  /**
   * Enhance existing route for given operation name.
   *
   * @param string $operation_name
   *   The entity operation name.
   */
  public function enhanceDefaultEntityRoute($operation_name) {
    $entity_add_page = $this->entityRoutingDefinition->getOperation($operation_name);
    if ($entity_add_page && $route = $this->routeCollection->get($entity_add_page)) {
      $route->setRequirement('_permission', "use {$this->entityDefinition->id()}.default form mode");
      $route->setOption('form_mode_theme', NULL);
      $this->routeCollection->add($entity_add_page, $route);
    }
  }

}
