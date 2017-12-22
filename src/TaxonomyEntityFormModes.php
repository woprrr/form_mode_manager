<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Route controller factory specific for Taxonomy Term entities.
 *
 * This Factory are herited from "ComplexEntityFormModes" because this entity,
 * implement some specific things proper to Taxonomy term like 'type' => 'vid'.
 */
class TaxonomyEntityFormModes extends ComplexEntityFormModes {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $entity_type_id = $route_match->getRouteObject()
      ->getOption('_form_mode_manager_entity_type_id');
    $bundle_entity_type_id = $route_match->getRouteObject()
      ->getOption('_form_mode_manager_bundle_entity_type_id');
    $entity = $route_match->getParameter($entity_type_id);

    if (empty($entity)) {
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
        'vid' => $route_match->getParameter($bundle_entity_type_id),
      ]);
    }

    return $entity;
  }

}
