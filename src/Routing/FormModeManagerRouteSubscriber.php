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
        if (!isset($display_mode['_core'])) {
          // Edit routes.
          if ($route = $this->getFormModeManagerEditRoute($entity_type, $display_mode, $machine_name)) {
            $collection->add("entity." . $display_mode['id'], $route);
          }
          // Entity with bundle routes.
          if ($route = $this->getFormModeManagerAddRoute($entity_type, $display_mode, $machine_name)) {
            $collection->add("entity.add." . $machine_name, $route);
          }
          if ($entity_type_id == 'user' && $route = $this->getFormModeManagerUserAddRoute($entity_type, $display_mode, $machine_name)) {
            $collection->add("entity.add." . $machine_name, $route);
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
      $entity_type_id = $entity_type->id();
      $route = new Route($form_mode_manager);
      $route
        ->addDefaults([
          '_entity_form' => $form_display['id'],
          '_title' => t('Edit as @label', ['@label' => $form_display['label']])->render(),
        ])
        ->addRequirements([
          '_entity_access' => "$entity_type_id.update",
          '_permission' => "use $machine_name form mode with $entity_type_id entity"
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
   * @param string $form_display
   *   The operation name identifying the form variation (form_mode).
   * @param string $machine_name
   *   Machine name of form_display (form_mode) without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerAddRoute(EntityTypeInterface $entity_type, $form_display, $machine_name) {
    $entity_type_id = $entity_type->id();
    $route = new Route("/$entity_type_id/add/" . "{entity_bundle_id}" . "/{form_display}");
    $route
      ->addDefaults([
        '_entity_form' => $form_display['id'],
      ])
      ->addRequirements([
        '_entity_create_access' => "$entity_type_id",
        '_permission' => "use $machine_name form mode with $entity_type_id entity"
      ])
      ->setOptions([
        '_admin_route' => TRUE,
      ]);
    return $route;
  }

  /**
   * Gets entity add operation routes with specific form_display.
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
  protected function getFormModeManagerUserAddRoute(EntityTypeInterface $entity_type, $form_display, $machine_name) {
    $entity_type_id = $entity_type->id();
    $route = new Route("/$entity_type_id/register/{form_display}");
    $route
      ->addDefaults([
        '_entity_form' => $form_display['id'],
        '_title' => t('Create new account')->render()
      ])
      ->addRequirements([
        '_access_user_register' => 'TRUE',
        '_permission' => "use $machine_name form mode with $entity_type_id entity"
      ]);
    return $route;
  }

}
