<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Route controller factory specific for each entities using bundles.
 *
 * This Factory are specific to work with entities using bundles and need more,
 * specific code to implement it. The best example of that specific things are,
 * "type" keys or add pages to chooses what kind of entity we need to create.
 * This controller can be a good base to implement our custom things in our,
 * custom entities using bundles.
 */
class ComplexEntityFormModes extends AbstractEntityFormModesFactory {

  /**
   * {@inheritdoc}
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the entity types that can be added; however,
   *   if there is only one entity type defined for the site, the function
   *   will return a RedirectResponse to the entity add page for that one entity
   *   type.
   */
  public function addPage(RouteMatchInterface $route_match) {
    $entity_type_id = $route_match
      ->getRouteObject()
      ->getOption('_form_mode_manager_entity_type_id');
    $entity_bundle_name = $route_match
      ->getRouteObject()
      ->getOption('_form_mode_manager_bundle_entity_type_id');
    $form_mode_name = $route_match->getParameter('form_mode_name');
    $entity_routes_infos = $this->entityRoutingMap
      ->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id])
      ->getPluginDefinition();

    $entity_type_cache_tags = $this->entityTypeManager
      ->getDefinition($entity_bundle_name)
      ->getListCacheTags();

    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
      '#add_bundle_message' => $this->t('There is no @entity_type yet.', ['@entity_type' => $entity_type_id]),
      '#cache' => [
        'tags' => Cache::mergeTags($entity_type_cache_tags, $this->formModeManager->getListCacheTags()),
      ],
    ];

    $entity_type_definitions = $this->entityTypeManager
      ->getStorage($entity_bundle_name)
      ->loadMultiple();
    $entity_add_form = $entity_routes_infos['operations']['add_form'] . ".$form_mode_name";
    foreach ($entity_type_definitions as $bundle) {
      $bundle_name = $bundle->id();
      if ($access = $this->accessIsAllowed($entity_type_id, $bundle_name, $form_mode_name)) {
        $description = (method_exists($bundle, 'getDescription')) ? $bundle->getDescription() : '';
        $build['#bundles'][$bundle_name] = [
          'label' => $bundle->label(),
          'description' => $description,
          'add_link' => Link::createFromRoute($bundle->label(), $entity_add_form, [$entity_bundle_name => $bundle->id()]),
        ];
        $this->renderer->addCacheableDependency($build, $access);
      }
    }

    // Bypass the entity/add listing if only one content type is available.
    if (1 == count($build['#bundles'])) {
      $bundle = current($build['#bundles']);
      return $this->redirect($entity_add_form, $bundle['add_link']->getUrl()->getRouteParameters());
    }

    return $build;
  }

  /**
   * Evaluate if current user has access to this bundle AND form mode.
   *
   * @param string $entity_type_id
   *   The id of current entity.
   * @param string $bundle_name
   *   The name of current bundle need to access.
   * @param string $form_mode_name
   *   The form mode name to use.
   *
   * @return bool
   *   True if you can access to this entity type as given form mode.
   */
  public function accessIsAllowed($entity_type_id, $bundle_name, $form_mode_name) {
    $access = $this->entityTypeManager
      ->getAccessControlHandler($entity_type_id)
      ->createAccess($bundle_name, $this->account, [], TRUE);

    return $access->isAllowed() && $this->formModeManager->isActive($entity_type_id, $bundle_name, $form_mode_name);
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

    // If we can't retrieve the entity from the route match get load,
    // it by their storage with correct route bundle key.
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($route_entity_type_info['entity_type_id'])->create([
        $route_entity_type_info['entity_key'] => $route_entity_type_info['bundle'],
      ]);
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
    $bundle_entity_type_id = $route_match->getRouteObject()
      ->getOption('_form_mode_manager_bundle_entity_type_id');
    $entity = $route_match->getParameter($entity_type_id);

    if (empty($entity)) {
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
        'type' => $route_match->getParameter($bundle_entity_type_id),
      ]);
    }

    return $entity;
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
    $bundle_entity_type_id = $route->getOption('_form_mode_manager_bundle_entity_type_id');
    $form_mode = $this->formModeManager->getFormModeMachineName($route->getDefault('_entity_form'));
    $bundle = $route_match->getParameter($bundle_entity_type_id);
    $form_mode_definition = $this->formModeManager->getActiveDisplaysByBundle($entity_type_id, $bundle);
    $entity_type_key = $this->entityTypeManager
      ->getDefinition($entity_type_id)
      ->getKey('bundle');

    return [
      'bundle' => $bundle,
      'bundle_entity_type' => $bundle_entity_type_id,
      'entity_key' => $entity_type_key,
      'entity_type_id' => $entity_type_id,
      'form_mode' => isset($form_mode_definition[$entity_type_id][$form_mode]) ? $form_mode_definition[$entity_type_id][$form_mode] : NULL,
    ];
  }

}
