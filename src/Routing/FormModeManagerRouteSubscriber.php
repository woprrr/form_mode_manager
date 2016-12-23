<?php

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
      unset($display_modes['register']);
      foreach ($display_modes as $form_mode_name => $form_mode) {
        switch ($entity_type_id) {
          case 'file':
            break;

          case 'user':
            // Unregister add route.
            if ($add_route = $this->getFormModeManagerUserAddRoute($entity_type, $form_mode, $form_mode_name)) {
              $collection->add($form_mode['id'], $add_route);
            }

            // Admin add routes.
            if ($admin_route = $this->getFormModeManagerUserAddAdmin($entity_type, $form_mode, $form_mode_name)) {
              $collection->add('admin.' . $form_mode['id'], $admin_route);
            }

            // Edit routes.
            if ($edit_route = $this->getFormModeManagerEditRoute($entity_type, $form_mode, $form_mode_name)) {
              $collection->add("entity." . $form_mode['id'], $edit_route);
            }
            break;
          case 'taxonomy_term':
            if ($add_route = $this->getFormModeManagerTaxonomyAddRoute($entity_type, $form_mode, $form_mode_name)) {
              $collection->add("entity.add." . $form_mode['id'], $add_route);
            }

            if ($list_route = $this->getFormModeManagerListPage($collection, $entity_type, $form_mode, $form_mode_name)) {
              // Add entity type list page specific to form_modes.
              $collection->add("form_mode_manager.{$form_mode['id']}.add_page", $list_route);
            }

            // Edit routes.
            if ($edit_route = $this->getFormModeManagerEditRoute($entity_type, $form_mode, $form_mode_name)) {
              $collection->add("entity." . $form_mode['id'], $edit_route);
            }

            $overview_route = $collection->get('entity.taxonomy_vocabulary.overview_form');
            $overview_route->setDefault('_form', 'Drupal\form_mode_manager\Form\FormModeManagerOverviewTerms');
            break;
          default:
            if ($add_route = $this->getFormModeManagerAddRoute($collection, $entity_type, $form_mode, $form_mode_name)) {
              $collection->add("entity.add." . $form_mode['id'], $add_route);
            }

            if ($list_route = $this->getFormModeManagerListPage($collection, $entity_type, $form_mode, $form_mode_name)) {
              // Add entity type list page specific to form_modes.
              $collection->add("form_mode_manager.{$form_mode['id']}.add_page", $list_route);
            }

            // Edit routes.
            if ($edit_route = $this->getFormModeManagerEditRoute($entity_type, $form_mode, $form_mode_name)) {
              $collection->add("entity." . $form_mode['id'], $edit_route);
            }
            break;
        }
      }
    }
  }

  /**
   * Gets entity edit route with specific form mode.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple possibly dynamic entity types.
   * @param array $form_mode
   *   The operation name identifying the form variation (form_mode).
   * @param string $form_mode_name
   *   Machine name of form_mode without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerEditRoute(EntityTypeInterface $entity_type, $form_mode, $form_mode_name) {
    if ($form_mode_manager_path = $entity_type->getLinkTemplate($form_mode_name)) {
      $entity_id = $entity_type->id();
      $route = new Route($form_mode_manager_path);

      $route
        ->addDefaults([
          '_entity_form' => $form_mode['id'],
          '_title' => t('Edit as @label', ['@label' => $form_mode['label']])->render(),
        ])
        ->addRequirements([
          '_entity_access' => "$entity_id.update",
          '_permission' => "use {$form_mode['id']} form mode",
        ])
        ->setOptions([
          '_admin_route' => TRUE,
          'form_mode_name' => ['type' => $form_mode_name],
        ]);

      return $route;
    }
    return NULL;
  }


  /**
   * Gets entity edit route with specific form mode.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple possibly dynamic entity types.
   * @param array $form_mode
   *   The operation name identifying the form variation (form_mode).
   * @param string $form_mode_name
   *   Machine name of form_mode without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerTaxonomyAddRoute(EntityTypeInterface $entity_type, $form_mode, $form_mode_name) {
    if ($form_mode_manager_path = $entity_type->getLinkTemplate($form_mode_name)) {
      $route = new Route("/admin/structure/taxonomy/manage/{entity_bundle_id}/add/{form_mode_name}");
      $this->setFormModeManagerDefaultsRouteOptions($route, $entity_type, $form_mode['id']);
      $route->setRequirement('_entity_create_access', 'taxonomy_term:{entity_bundle_id}');
      return $route;
    }
    return NULL;
  }

  /**
   * Gets entity add operation routes with specific form mode.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_mode
   *   The operation name identifying the form variation (form_mode).
   * @param string $form_mode_name
   *   Machine name of form_mode without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|false
   *   The generated route, if available.
   */
  protected function getFormModeManagerAddRoute(RouteCollection $collection, EntityTypeInterface $entity_type, $form_mode, $form_mode_name) {
    if ($entity_type->getFormClass($form_mode_name)) {
      $entity_type_id = $entity_type->id();
      $route = new Route("/$entity_type_id/add/" . "{entity_bundle_id}" . "/{form_mode_name}");
      $this->setFormModeManagerDefaultsRouteOptions($route, $entity_type, $form_mode['id']);

      // Add feature to restrict access on default form mode.
      switch ($entity_type_id) {
        case 'node':
        case 'block_content':
          // node.add route alter.
          $collection_route_name = $entity_type_id . '.add';

          // Special case entity.add_form entities are not standard.
          if (in_array($entity_type_id, ['block_content', 'taxonomy_term'])) {
            $collection_route_name = $entity_type_id . '.add_form';
          }

          $route_add = $collection->get($collection_route_name);
          $route_add->addDefaults(['entity_type' => $entity_type]);
          $route_add->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          // node.add_page route alter.
          $route_list = $collection->get("$entity_type_id.add_page");
          $route_list->addDefaults(['entity_type' => $entity_type]);
          $route_list->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          // entity.node.edit_form route alter.
          $route_edit_form = $collection->get("entity.$entity_type_id.edit_form");
          $route_edit_form->addDefaults(['entity_type' => $entity_type]);
          $route_edit_form->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          break;

        case 'user':
          $route_add = $collection->get("user.register");
          $route_add->addDefaults(['entity_type' => $entity_type]);
          $route_add->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');
          break;
      }
      return $route;
    }
    return FALSE;
  }

  /**
   * Set specific options of NODE routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   * @param string $access
   *   The access routes options for entities.
   */
  protected function setNodeRouteOptions(Route $route, $access) {
    $route->setRequirement('_node_add_access', $access)
      ->setOptions([
        '_node_operation_route' => TRUE,
        'parameters' => [
          'node_type' => [
            'with_config_overrides' => TRUE,
          ],
        ],
      ]);
  }

  /**
   * Set cross entities options into routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   * @param string $access
   *   The access routes options for entities.
   */
  protected function setFormModeManagerRouteOptions(Route $route, $access) {
    $route->setRequirement('_entity_create_access', $access)
      ->setOption('_admin_route', TRUE);
  }

  /**
   * Set default routes option onto Form Mode Manager routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   A Route instance.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param string $form_mode_name
   *   The machine name of current form mode.
   */
  protected function setFormModeManagerDefaultsRouteOptions(Route $route, EntityTypeInterface $entity_type, $form_mode_name) {
    $access = (!empty($entity_type->getBundleEntityType())) ? "{$entity_type->id()}:{entity_bundle_id}" : $entity_type->id();

    $route
      ->addDefaults([
        '_entity_form' => $form_mode_name,
        '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::entityAdd',
        '_title_callback' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPageTitle',
        'entity_type' => $entity_type,
      ])
      ->setRequirement('_custom_access', '\Drupal\form_mode_manager\Controller\FormModeManagerController::checkAccess');

    if ('node' === $entity_type->id()) {
      $this->setNodeRouteOptions($route, $access);
    }
    else {
      $this->setFormModeManagerRouteOptions($route, $access);
    }

    $parameters = $route->getOption('parameters') ?: [];
    $parameters += ['form_mode_name' => ['type' => $form_mode_name]];

    $route->setOption('parameters', $parameters);
  }

  /**
   * Gets entity create user routes with specific form mode.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_mode
   *   The operation name identifying the form variation (form_mode).
   * @param string $form_mode_name
   *   Machine name of form_mode without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|false
   *   The generated route, if available.
   */
  protected function getFormModeManagerUserAddRoute(EntityTypeInterface $entity_type, $form_mode, $form_mode_name) {
    $route = new Route("/{$entity_type->id()}/register/{form_mode_name}");
    $route
      ->addDefaults([
        '_entity_form' => $form_mode['id'],
        '_title' => t('Create new account')->render(),
      ])
      ->addRequirements([
        '_access_user_register' => 'TRUE',
        '_permission' => "use {$form_mode['id']} form mode",
      ])
      ->setOptions([
        'parameters' => [
          'form_mode_name' => ['type' => $form_mode_name],
        ],
      ]);
    return $route;
  }

  /**
   * Gets entity create user routes with specific form mode.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_mode
   *   The operation name identifying the form variation (form_mode).
   * @param string $form_mode_name
   *   Machine name of form_mode without entity prefixed.
   *
   * @return \Symfony\Component\Routing\Route|false
   *   The generated route, if available.
   */
  protected function getFormModeManagerUserAddAdmin(EntityTypeInterface $entity_type, $form_mode, $form_mode_name) {
    $route = new Route("/admin/people/create/{form_mode_name}");
    $route
      ->addDefaults([
        '_entity_form' => $form_mode['id'],
        '_title' => t('Add user as @label', ['@label' => $form_mode['label']])->render(),
      ])
      ->addRequirements([
        '_permission' => "use {$form_mode['id']} form mode",
      ])
      ->setOptions([
        'parameters' => [
          'form_mode_name' => ['type' => $form_mode_name],
        ],
      ]);
    return $route;
  }

  /**
   * Gets entity add operation routes with specific form mode.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_mode
   *   The operation array of form variation (form_mode).
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerListPage($collection, EntityTypeInterface $entity_type, $form_mode, $form_mode_name) {
    $entity_id = $entity_type->id();
    $route = new Route("/$entity_id/add-list/{form_mode_name}");
    if ('user' === $entity_id) {
      $route = $collection->get("user.admin_create");
      $route->setPath("{$route->getPath()}/{form_mode_name}");
    }
    $route
      ->addDefaults([
        '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPage',
        '_title' => t('Add @entity_type', ['@entity_type' => $entity_type->getLabel()])->render(),
        'entity_type' => $entity_type,
      ])
      ->addRequirements([
        '_permission' => "use {$form_mode['id']} form mode",
      ])
      ->setOptions([
        '_admin_route' => TRUE,
        'form_mode_name' => ['type' => $form_mode_name],
      ]);

    return $route;
  }

}
