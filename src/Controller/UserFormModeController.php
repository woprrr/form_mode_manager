<?php

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for specific User entity form mode support.
 *
 * @see \Drupal\form_mode_manager\Routing\RouteSubscriber
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalAction
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalTasks
 */
class UserFormModeController extends EntityFormModeBase implements ContainerInjectionInterface {

  /**
   * Provides the node submission form.
   *
   * @TODO Optimize function.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param array $form_mode_name
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   *
   * @return array
   *   A node submission form.
   */
  public function entityAdd(RouteMatchInterface $route_match) {
    // On check le context (une route de add sans entity dans la route ou un edit avec une entity dans la route).
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entityTypeManager()->getStorage($route_entity_type_info['entity_type_id'])->create([
        $route_entity_type_info['entity_key'] => $route_entity_type_info['bundle'],
      ]);
    }

    $form_mode_id = $this->getFormModeMachineName($route_match->getRouteObject()->getDefault('_entity_form'));
    $operation = empty($form_mode_id) ? 'default' : $form_mode_id;
    if ($entity instanceof EntityInterface) {
      return $this->entityFormBuilder()->getForm($entity, $operation);
    }

    throw new \Exception('Invalide entity passed or inexistant form mode');
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @TODO Refactor if/else part.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param array $form_mode
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(RouteMatchInterface $route_match) {
    // Check context of route (it's a add-form route if the route haven't any,
    // entity from routeMatch of edit-form if we have object from route.
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager()
        ->getStorage($route_entity_type_info['bundle_entity_type'])
        ->load($route_entity_type_info['bundle']);
    }
    else {
      /* @var \Drupal\Core\Entity\EntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager()
        ->getStorage($route_match->getRouteObject()->getOption('_form_mode_manager_bundle_entity_type_id'))
        ->load($entity->bundle());
    }

    // @TODO Refactor to found better.
    $form_mode_label = isset($route_entity_type_info) ? $route_entity_type_info['form_mode']['label'] : $route_match->getRouteObject()->getOption('parameters')['form_mode']['label'];
    return $this->t('Create @name as @form_mode_label', [
      '@name' => $bundle->get('name'),
      '@form_mode_label' => $form_mode_label,
    ]);
  }

}
