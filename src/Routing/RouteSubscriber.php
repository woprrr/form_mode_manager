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
   * @var \Drupal\form_mode_manager\FormModeManager
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
      /* @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      if ($list_task = $this->getFormModeManagerLinksTask($entity_type)) {
        $collection->add("entity.$entity_type_id.form_modes_links_task", $list_task);
      }

      if ('user' !== $entity_type_id) {
        $this->addFormModesRoutes($collection, $entity_type, $form_modes);
      } else {
        $this->addUserFormModesRoutes($collection, $entity_type, $form_modes);
      }
    }
  }

  /**
   * Add all routes to manage contents with FormModes.
   */
  private function addFormModesRoutes(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_modes) {
    $entity_type_id = $entity_type->id();
    foreach ($form_modes as $form_mode_name => $form_mode) {
      if ($route = $this->getFormModeManagerAddRoute($collection, $entity_type, $form_mode)) {
        $collection->add("entity.$entity_type_id.add_form_$form_mode_name", $route);
      }

      if ($route = $this->getFormModeManagerEditRoute($collection, $entity_type, $form_mode)) {
        $collection->add("entity.$entity_type_id.edit_form_$form_mode_name", $route);
      }

      if ($route = $this->getFormModeManagerListPageRoute($entity_type, $form_mode)) {
        $collection->add("form_mode_manager.{$form_mode['id']}.add_page", $route);
      }
    }
  }

  /**
   * Add Users routes to manage contents with FormModes.
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
        $collection->add("entity.user.edit_form_$form_mode_name", $route);
      }
    }
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
  private function getFormModeManagerListPageRoute(EntityTypeInterface $entity_type, array $form_mode) {
    $entity_type_id = $entity_type->id();
    /** @var \Symfony\Component\Routing\Route $route */
    $route = (new Route("/$entity_type_id/add-list/{form_mode_name}"))
      ->addDefaults([
        '_controller' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::addPage',
        '_title' => $this->t('Add @entity_type', ['@entity_type' => $entity_type->getLabel()])
          ->render(),
        'entity_type' => $entity_type,
      ])
      ->addRequirements([
        '_permission' => "use {$form_mode['id']} form mode",
      ])
      ->setOptions([
        '_admin_route' => TRUE,
        'form_mode_name' => ['type' => $this->formModeManager->getFormModeMachineName($form_mode['id'])],
      ]);

    return $route;
  }

  /**
   * Format Add_form context routes.
   */
  protected function getFormModeManagerAddRoute(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    if ($entity_add_route = $collection->get($this->getAddCollectionRouteName($entity_type))) {
      return $this->setRoutes($entity_add_route, $entity_type, $form_mode);
    }
  }

  /**
   * Gets entity create user routes with specific form mode.
   */
  protected function getFormModeManagerUserAddAdmin(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    if ($entity_add_admin_route = $collection->get('user.admin_create')) {
      $route = $this->setRoutes($entity_add_admin_route, $entity_type, $form_mode);
      $route->setDefaults([
        '_controller' => '\Drupal\form_mode_manager\Controller\UserFormModeController::entityAdd',
        '_title_callback' => '\Drupal\form_mode_manager\Controller\UserFormModeController::addPageTitle',
      ]);
      return $route;
    }
  }

  /**
   * Gets entity create user routes with specific form mode.
   */
  protected function getFormModeManagerUserRegister(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    if ($entity_add_register_route = $collection->get('user.register')) {
      $route = $this->setRoutes($entity_add_register_route, $entity_type, $form_mode);
      $route->setDefaults([
        '_controller' => '\Drupal\form_mode_manager\Controller\UserFormModeController::entityAdd',
        '_title_callback' => '\Drupal\form_mode_manager\Controller\UserFormModeController::addPageTitle',
      ]);
      return $route;
    }
  }

  /**
   * Format edit_form context routes.
   */
  protected function getFormModeManagerEditRoute(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    if ($entity_edit_route = $collection->get("entity.{$entity_type->id()}.edit_form")) {
      $route = $this->setRoutes($entity_edit_route, $entity_type, $form_mode);
      $route->setDefaults([
        '_controller' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::entityAdd',
        '_title_callback' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::addPageTitle',
        $entity_type->getBundleEntityType() => "{{$entity_type->getBundleEntityType()}}",
      ]);
      return $route;
    }
  }

  private function setRoutes(Route $parent_route, EntityTypeInterface $entity_type, array $form_mode) {
    $route_defaults = array_merge($parent_route->getDefaults(), $this->getFormModeManagerDefaults($form_mode));
    $roue_options = array_merge($parent_route->getOptions(), $this->getFormModeManagerOptions($form_mode, $entity_type));
    $route_requirements = array_merge($parent_route->getRequirements(), $this->getFormModeManagerRequirements());

    // Define new route for form-modes derivations based,
    // on parent entity definition.
    $route = (new Route("{$parent_route->getPath()}/{$this->formModeManager->getFormModeMachineName($form_mode['id'])}"))
      ->addDefaults($route_defaults)
      ->setOptions($roue_options)
      ->addRequirements($route_requirements);

    return $route;
  }

  /**
   * Calculate all routes of each compatible entities.
   */
  private function getAddCollectionRouteName(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    switch ($entity_type_id) {
      case 'node':
        $collection_route_name = $entity_type_id . '.add';
        break;

      case 'block_content':
        $collection_route_name = $entity_type_id . '.add_form';
        break;

      case 'contact':
        $collection_route_name = $entity_type_id . '.form_add';
        break;

      case 'user':
        $collection_route_name = $entity_type_id . '.register';
        break;

      default:
        $collection_route_name = "entity.$entity_type_id.add_form";
        break;
    }

    return $collection_route_name;
  }

  /**
   * Gets the entity load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  private function getFormModeManagerLinksTask(EntityTypeInterface $entity_type) {
    if ($form_mode_list = $entity_type->getLinkTemplate('form-modes-links-task')) {
      $entity_type_id = $entity_type->id();
      $route = (new Route($form_mode_list))
        ->addDefaults([
          '_controller' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::entityAdd',
          '_title' => 'Form modes load',
          $entity_type->getBundleEntityType() => "{{$entity_type->getBundleEntityType()}}",
        ])
        ->addRequirements([
          '_permission' => 'access devel information',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_form_mode_manager_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * Format defaults of routes required to formModeManager.
   */
  private function getFormModeManagerDefaults(array $form_mode) {
    return [
      '_entity_form' => $form_mode['id'],
      '_controller' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::entityAdd',
      '_title_callback' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::addPageTitle',
    ];
  }

  /**
   * Format Options of routes required to formModeManager.
   */
  private function getFormModeManagerOptions(array $form_mode, EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $entity_type_bundle_id = $entity_type->getBundleEntityType();
    return [
      '_form_mode_manager_entity_type_id' => $entity_type_id,
      '_form_mode_manager_bundle_entity_type_id' => !empty($entity_type_bundle_id) ? $entity_type_bundle_id : $entity_type_id,
      'parameters' => [
        $entity_type_id => ['type' => "entity:$entity_type_id"],
        'form_mode' => $form_mode,
      ],
    ];
  }

  /**
   * Format requirements of routes required to formModeManager.
   */
  private function getFormModeManagerRequirements() {
    return [
      '_custom_access' => '\Drupal\form_mode_manager\Controller\EntityFormModeController::checkAccess',
    ];
  }

}
