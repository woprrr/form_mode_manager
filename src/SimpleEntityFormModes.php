<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Route controller factory specific for each entities not using bundles.
 *
 * This Factory are specific to work with entities NOT USING bundles and,
 * need more specific code to implement it.
 */
class SimpleEntityFormModes extends AbstractEntityFormModesFactory {

  /**
   * {@inheritdoc}
   *
   * In simple entities without bundles we don't generate add_page routes.
   */
  public function addPage(RouteMatchInterface $route_match) {}

  /**
   * {@inheritdoc}
   */
  public function checkAccess(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    $route = $route_match->getRouteObject();
    $form_mode_id = $route->getDefault('_entity_form');
    $cache_tags = $this->formModeManager->getListCacheTags();

    // When we need to check access for actions links,
    // we don't have entity to load.
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      $entity_type_id = $route_entity_type_info['entity_type_id'];
    }

    $entity_type_id = isset($entity_type_id) ? $entity_type_id : $entity->getEntityTypeId();
    $operation = $this->getFormModeOperationName($this->formModeManager->getFormModeMachineName($form_mode_id));

    return AccessResult::allowedIf($this->formModeManager->isActive($entity_type_id, $entity_type_id, $operation))
      ->addCacheTags($cache_tags)
      ->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The entity object as determined from the passed-in route match.
   */
  public function getEntityTypeFromRouteMatch(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    $entity_type_id = $route->getOption('_form_mode_manager_entity_type_id');
    $form_mode = $this->formModeManager->getFormModeMachineName($route->getDefault('_entity_form'));
    $form_mode_definition = $this->formModeManager->getActiveDisplaysByBundle($entity_type_id, $entity_type_id);

    return [
      'entity_type_id' => $entity_type_id,
      'form_mode' => isset($form_mode_definition[$entity_type_id][$form_mode]) ? $form_mode_definition[$entity_type_id][$form_mode] : NULL,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity loaded form route_match.
   *
   * @throws \Exception
   *   If an invalid entity is retrieving from the route object.
   */
  public function getEntity(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($route_entity_type_info['entity_type_id'])->create();
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity object build with given route_match.
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $entity_type_id = $route_match->getRouteObject()
      ->getOption('_form_mode_manager_entity_type_id');
    $entity = $route_match->getParameter($entity_type_id);
    return $entity;
  }

}
