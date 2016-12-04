<?php

/**
 * @file
 * Contains \Drupal\form_mode_manager\Routing\FormModeManagerRouteSubscriber.
 */

namespace Drupal\form_mode_manager\Routing;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for form_mode_manager routes.
 */
class FormModeManagerRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityTypeManager = $entity_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityDisplayRepository->getAllFormModes() as $entity_type_id => $display_modes) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      foreach ($display_modes as $machine_name => $display_mode) {
        if ($machine_name != 'register') {
          if ($route = $this->getFormModeManagerListPage($entity_type, $display_mode, $machine_name)) {
            // Add entity type list page specific to form_modes.
            $collection->add("form_mode_manager.$machine_name.add_page", $route);
          }
          // Edit routes.
          if ($route = $this->getFormModeManagerEditRoute($entity_type, $display_mode, $machine_name)) {
            $collection->add("entity." . $display_mode['id'], $route);
          }
          /*
           * @TODO Add compatibility with multistep file form,
           * the add operation works, but when user go at,
           * file/add/{file_type}/{form_display} we have only,
           * second part or file form (first step is upload of file).
           * We need to have a special case for the route path like,
           * /file/add/{form_display} to work with file.
           */
          if ($entity_type_id != 'file' && $route = $this->getFormModeManagerAddRoute($entity_type, $display_mode, $machine_name)) {
            $collection->add("entity.add." . $machine_name, $route);
          }
          // Specific case with user entity (add operation).
          if ($entity_type_id == 'user' && $route = $this->getFormModeManagerUserAddRoute($entity_type, $display_mode, $machine_name)) {
            $collection->add($display_mode['id'], $route);
          }
        }
      }
    }
  }

  /**
   * Gets entity edit route with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   * @param string $form_display
   *   The operation name identifying the form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerEditRoute(EntityTypeInterface $entity_type, $form_display, $machine_name) {
    if ($form_mode_manager = $entity_type->getLinkTemplate($machine_name)) {
      $route = new Route($form_mode_manager);
      $entity_id = $entity_type->id();
      $route
        ->addDefaults([
          '_entity_form' => $form_display['id'],
          '_title' => t('Edit as @label', ['@label' => $form_display['label']])->render(),
        ])
        ->addRequirements([
          '_entity_access' => "$entity_id.update",
          '_permission' => "use {$form_display['id']} form mode"
        ])
        ->setOptions([
          '_admin_route' => TRUE
        ]);
      return $route;
    }
    return NULL;
  }

  /**
   * Gets entity add operation routes with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   * @param array $form_display
   *   The operation array of form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerAddRoute(EntityTypeInterface $entity_type, $form_display, $machine_name) {
    if ($entity_type->getFormClass($machine_name)) {
      $entity_id = $entity_type->id();
      $route = new Route("/$entity_id/add/" . "{entity_bundle_id}" . "/{form_display}");
      // Check if that entity have bundles to check access by bundle or by entity type.
      $access = (!empty($entity_type->getBundleEntityType())) ? "{$entity_type->id()}:{entity_bundle_id}" : $entity_type->id();
      $route
        ->addDefaults([
          '_entity_form' => $form_display['id'],
          '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::entityAdd',
          '_title_callback' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPageTitle',
          'entity_type' => $entity_type
        ])
        ->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
      if ($entity_id == 'node') {
        $route->setRequirement('_node_add_access', $access)
          ->setOptions([
            '_node_operation_route' => TRUE,
            'parameters' => [
              'entity_bundle_id' => [
                'with_config_overrides' => TRUE
              ]
            ]
          ]);
      }
      else {
        $route->setRequirement('_entity_create_access', $access)
          ->setOption('_admin_route', TRUE);
      }
      return $route;
    }
    return NULL;
  }

  /**
   * Gets entity create user routes with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   * @param array $form_display
   *   The operation array of form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerUserAddRoute(EntityTypeInterface $entity_type, $form_display, $machine_name) {
    if ($entity_type->id() === 'user') {
      $route = new Route("/{$entity_type->id()}/register/{form_display}");
      $route
        ->addDefaults([
          '_entity_form' => $form_display['id'],
          '_title' => t('Create new account')->render()
        ])
        ->addRequirements([
          '_access_user_register' => 'TRUE',
          '_permission' => "use {$form_display['id']} form mode"
        ]);
      return $route;
    }
    return NULL;
  }

  /**
   * Gets entity add operation routes with specific form_display.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   * @param array $form_display
   *   The operation array of form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerListPage(EntityTypeInterface $entity_type, $form_display, $machine_name) {
    $entity_id = $entity_type->id();
    $route = new Route("/$entity_id/add-$machine_name");
    $route
      ->addDefaults([
        '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPage',
        '_title' => t('Add @entity_type', ['@entity_type' => $entity_type->getLabel()])->render(),
        'entity_type' => $entity_type,
        'form_display' => $machine_name
      ])
      ->addRequirements([
        '_permission' => "use {$form_display['id']} form mode"
      ])
      ->setOptions([
        '_admin_route' => TRUE,
      ]);
    return $route;
  }

}
