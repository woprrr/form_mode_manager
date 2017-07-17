<?php

namespace Drupal\form_mode_manager\Routing;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for form_mode_manager routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

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
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityDisplayRepositoryInterface $entity_display_repository, FormModeManagerInterface $form_mode_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    foreach ($form_modes_definitions as $entity_type_id => $form_modes) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ('user' !== $entity_type_id) {
        $this->addFormModesRoutes($collection, $entity_type, $form_modes);
        $this->enhanceDefaultRoutes($collection, $entity_type_id);
      }
      else {
        $this->addUserFormModesRoutes($collection, $entity_type, $form_modes);
        $this->enhanceUserDefaultRoutes($collection, $entity_type_id);
      }
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
  private function enhanceDefaultRoutes(RouteCollection $collection, $entity_type_id) {
    if ($route = $collection->get("$entity_type_id.add_page")) {
      $collection->add("$entity_type_id.add_page", $this->routeEnhancer($route, $entity_type_id));
    }

    if ($route = $collection->get("entity.$entity_type_id.edit_form")) {
      $collection->add("entity.$entity_type_id.edit_form", $this->routeEnhancer($route, $entity_type_id));
    }

    if ($route = $collection->get("entity.$entity_type_id.add_form")) {
      $collection->add("entity.$entity_type_id.add_form", $this->routeEnhancer($route, $entity_type_id));
    }
  }

  /**
   * Enhance existing User operation routes (add_page, add_form, edit_form).
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  private function enhanceUserDefaultRoutes(RouteCollection $collection, $entity_type_id) {
    if ($route = $collection->get("user.register")) {
      $collection->add("user.register", $this->routeEnhancer($route, $entity_type_id));
    }

    if ($route = $collection->get("entity.$entity_type_id.edit_form")) {
      $collection->add("entity.$entity_type_id.edit_form", $this->routeEnhancer($route, $entity_type_id));
    }

    if ($route = $collection->get("entity.$entity_type_id.add_form")) {
      $collection->add("form_mode_manager.user.add_page", $this->routeEnhancer($route, $entity_type_id));
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
  private function routeEnhancer(Route $route, $entity_type_id) {
    $route->setRequirement('_permission', "use $entity_type_id.default form mode");
    return $route;
  }

  /**
   * Generate all routes derivate to entity.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_modes
   *   All form-modes available for specified entity_type_id.
   */
  private function addFormModesRoutes(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_modes) {
    $entity_type_id = $entity_type->id();
    foreach ($form_modes as $form_mode_name => $form_mode) {
      if ($route = $this->getFormModeManagerAddRoute($collection, $entity_type, $form_mode)) {
        $collection->add("entity.$entity_type_id.add_form.$form_mode_name", $route);
      }

      if ($route = $this->getFormModeManagerEditRoute($collection, $entity_type, $form_mode)) {
        $collection->add("entity.$entity_type_id.edit_form.$form_mode_name", $route);
      }

      if ($route = $this->getFormModeManagerListPageRoute($entity_type, $form_mode)) {
        $collection->add("form_mode_manager.$entity_type_id.add_page.$form_mode_name", $route);
      }
    }
  }

  /**
   * Add routes by form-modes on each existing `user` routes.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_modes
   *   All form-modes available for specified entity_type_id.
   */
  private function addUserFormModesRoutes(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_modes) {
    foreach ($form_modes as $form_mode_name => $form_mode) {
      if ($route = $this->getFormModeManagerUserAddAdmin($collection, $entity_type, $form_mode)) {
        $collection->add("user.admin_create.$form_mode_name", $route);
      }

      if ($route = $this->getFormModeManagerUserRegister($collection, $entity_type, $form_mode)) {
        $collection->add("user.register.$form_mode_name", $route);
      }

      if ($route = $this->getFormModeManagerEditRoute($collection, $entity_type, $form_mode)) {
        $collection->add("entity.user.edit_form.$form_mode_name", $route);
      }

      if ($route = $this->getFormModeManagerListPageRoute($entity_type, $form_mode)) {
        $collection->add("form_mode_manager.user.add_page.$form_mode_name", $route);
      }
    }
  }

  /**
   * Generate route to Form Mode Manager `add-list` route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param array $form_mode
   *   An associative array represent a DisplayForm entity.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  private function getFormModeManagerListPageRoute(EntityTypeInterface $entity_type, array $form_mode) {
    $route = NULL;
    $entity_type_id = $entity_type->id();
    $has_active_mode = $this->formModeManager->hasActiveFormMode(
      $entity_type_id,
      $this->formModeManager->getFormModeMachineName($form_mode['id'])
    );

    if ($has_active_mode) {
      $route = new Route("/$entity_type_id/add-list/{$this->formModeManager->getFormModeMachineName($form_mode['id'])}");
      $route
        ->addDefaults([
          '_controller' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::addPage',
          '_title' => $this->t('Add @entity_type', ['@entity_type' => $entity_type->getLabel()])
            ->render(),
          'entity_type' => $entity_type,
          'form_mode_name' => $this->formModeManager->getFormModeMachineName($form_mode['id']),
        ])
        ->addRequirements([
          '_permission' => "use {$form_mode['id']} form mode",
        ])
        ->setOptions([
          '_admin_route' => TRUE,
        ]);
    }

    return $route;
  }

  /**
   * Get the Form Mode Manager `add-form` route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   The form mode info.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerAddRoute(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    $has_active_mode = $this->formModeManager->hasActiveFormMode(
      $entity_type->id(),
      $this->formModeManager->getFormModeMachineName($form_mode['id'])
    );

    if ($has_active_mode && $entity_add_route = $collection->get($this->getAddCollectionRouteName($entity_type))) {
      return $this->setRoutes($entity_add_route, $entity_type, $form_mode);
    }
    return NULL;
  }

  /**
   * Get the Form Mode Manager `admin-create` route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   The form mode info.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerUserAddAdmin(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    if ($entity_add_admin_route = $collection->get('user.admin_create')) {
      $route = $this->setRoutes($entity_add_admin_route, $entity_type, $form_mode);
      $route
        ->setDefault('_controller', '\Drupal\form_mode_manager\Controller\UserFormModeController::entityAdd')
        ->setDefault('_title_callback', '\Drupal\form_mode_manager\Controller\UserFormModeController::addPageTitle');

      return $route;
    }
    return NULL;
  }

  /**
   * Get the Form Mode Manager `user-register` route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   The form mode info.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerUserRegister(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    if ($entity_add_register_route = $collection->get('user.register')) {
      $route = $this->setRoutes($entity_add_register_route, $entity_type, $form_mode);
      $route
        ->setDefault('_controller', '\Drupal\form_mode_manager\Controller\UserFormModeController::entityAdd')
        ->setDefault('_title_callback', '\Drupal\form_mode_manager\Controller\UserFormModeController::addPageTitle');

      return $route;
    }
    return NULL;
  }

  /**
   * Get the Form Mode Manager `edit-form` route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   The form mode info.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerEditRoute(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    $has_active_mode = $this->formModeManager->hasActiveFormMode(
      $entity_type->id(),
      $this->formModeManager->getFormModeMachineName($form_mode['id'])
    );

    $entity_edit_route = $collection->get("entity.{$entity_type->id()}.edit_form");
    if ($has_active_mode && !empty($entity_edit_route)) {
      $route = $this->setRoutes($entity_edit_route, $entity_type, $form_mode);
      $this->userEditEnhancements($route, $entity_type->id());
      return $route;
    }

    return NULL;
  }

  /**
   * Set a specific callback for Edit context of User entity.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object of entity.
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  private function userEditEnhancements(Route $route, $entity_type_id) {
    if ('user' !== $entity_type_id) {
      return;
    }
    $route->setDefault('_title_callback', '\Drupal\form_mode_manager\Controller\UserFormModeController::editPageTitle');
  }

  /**
   * Set Form Mode Manager routes based on parent entity routes.
   *
   * @param \Symfony\Component\Routing\Route $parent_route
   *   The route object of entity.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   The form mode info.
   *
   * @return \Symfony\Component\Routing\Route
   *   Form Mode Manager route to be added on entity collection.
   */
  private function setRoutes(Route $parent_route, EntityTypeInterface $entity_type, array $form_mode) {
    $op = 'create';
    if (preg_match_all('/^.*?\/edit[^$]*/', $parent_route->getPath(), $matches, PREG_SET_ORDER, 0)) {
      $op = 'edit';
    }

    $route_defaults = array_merge($parent_route->getDefaults(), $this->getFormModeManagerDefaults($form_mode, $op));
    $roue_options = array_merge($parent_route->getOptions(), $this->getFormModeManagerOptions($form_mode, $entity_type));
    $route_requirements = array_merge($parent_route->getRequirements(), $this->getFormModeManagerRequirements($form_mode, $entity_type));

    $route = new Route("{$parent_route->getPath()}/{$this->formModeManager->getFormModeMachineName($form_mode['id'])}");
    $route
      ->addDefaults($route_defaults)
      ->setOptions($roue_options)
      ->addRequirements($route_requirements);

    return $route;
  }

  /**
   * Calculate all routes of each compatible entities.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return string
   *   Route name of `add-form` action of parent entity route.
   */
  private function getAddCollectionRouteName(EntityTypeInterface $entity_type) {
    switch ($entity_type->id()) {
      case 'node':
        $collection_route_name = $entity_type->id() . '.add';
        break;

      case 'block_content':
        $collection_route_name = $entity_type->id() . '.add_form';
        break;

      case 'contact':
        $collection_route_name = $entity_type->id() . '.form_add';
        break;

      case 'user':
        $collection_route_name = $entity_type->id() . '.register';
        break;

      default:
        $collection_route_name = "entity.{$entity_type->id()}.add_form";
        break;
    }

    return $collection_route_name;
  }

  /**
   * Get defaults parameters nedeed to build Form Mode Manager routes.
   *
   * @param array $form_mode
   *   The form mode info.
   * @param string $operation
   *   Operation context (create or edit).
   *
   * @return array
   *   Array contain defaults routes parameters.
   */
  private function getFormModeManagerDefaults(array $form_mode, $operation) {
    $properties = [
      '_entity_form' => $form_mode['id'],
      '_controller' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::entityAdd',
      '_title_callback' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::addPageTitle',
    ];

    if ('edit' === $operation) {
      $properties['_title_callback'] = '\Drupal\form_mode_manager\Controller\EntityFormModeController::editPageTitle';
    }

    return $properties;
  }

  /**
   * Get options parameters nedeed to build Form Mode Manager routes.
   *
   * @param array $form_mode
   *   The form mode info.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return array
   *   Array contain options routes parameters.
   */
  private function getFormModeManagerOptions(array $form_mode, EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $entity_type_bundle_id = $entity_type->getBundleEntityType();
    $bundle_entity_type_id = !empty($entity_type_bundle_id) ? $entity_type_bundle_id : $entity_type_id;

    return [
      '_form_mode_manager_entity_type_id' => $entity_type_id,
      '_form_mode_manager_bundle_entity_type_id' => $bundle_entity_type_id,
      'parameters' => [
        $entity_type_id => [
          'type' => "entity:$entity_type_id",
        ],
        'form_mode' => $form_mode,
      ],
    ];
  }

  /**
   * Get options requirements nedeed to build Form Mode Manager routes.
   *
   * @return array
   *   Array contain requirements routes parameters.
   */
  private function getFormModeManagerRequirements(array $form_mode, EntityTypeInterface $entity_type) {
    return [
      '_permission' => "use {$form_mode['id']} form mode",
      '_custom_access' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::checkAccess',
    ];
  }

}
