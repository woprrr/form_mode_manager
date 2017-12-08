<?php

namespace Drupal\form_mode_manager\Routing\EventSubscriber;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Drupal\form_mode_manager\EntityRoutingMapManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for form_mode_manager routes.
 */
class FormModesSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

  /**
   * The Regex pattern to contextualize process by route path.
   *
   * @var string
   */
  const ROUTE_PATH_CONTEXT_REGEX = '/^.*?\/edit[^$]*/';

  /**
   * The Regex pattern to contextualize process by route path.
   *
   * @var string
   */
  const FORM_MODE_DEFAULT_CONTROLLER = '\Drupal\form_mode_manager\Controller\EntityFormModeController';

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
   * The Routes Manager Plugin.
   *
   * @var \Drupal\form_mode_manager\EntityRoutingMapManager
   */
  protected $entityRoutingMap;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\form_mode_manager\EntityRoutingMapManager $plugin_routes_manager
   *   Plugin EntityRoutingMap to retrieve entity form operation routes.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityDisplayRepositoryInterface $entity_display_repository, FormModeManagerInterface $form_mode_manager, EntityRoutingMapManager $plugin_routes_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->formModeManager = $form_mode_manager;
    $this->entityRoutingMap = $plugin_routes_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    unset($form_modes_definitions['user']);
    foreach ($form_modes_definitions as $entity_type_id => $form_modes) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $this->addFormModesRoutes($collection, $entity_type, $form_modes);
    }
  }

  /**
   * Generate all operations routes for entities.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_modes
   *   All form-modes available for specified entity_type_id.
   */
  protected function addFormModesRoutes(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_modes) {
    foreach ($form_modes as $form_mode) {
      $this->setFormModeCollection($collection, $entity_type, $form_mode, 'admin_add');
      $this->setFormModeCollection($collection, $entity_type, $form_mode, 'add_form');
      $this->setFormModeCollection($collection, $entity_type, $form_mode, 'edit_form');
      $this->setAddPageCollection($collection, $entity_type, $form_mode);
    }
  }

  /**
   * Create a route per form_mode for given operation form.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   A form-mode for specified entity_type_id.
   * @param string $operation
   *   The entity operation name.
   */
  public function setFormModeCollection(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode, $operation) {
    /** @var \Drupal\form_mode_manager\EntityRoutingMapBase $entity_operation_mapping */
    $entity_operation_mapping = $this->entityRoutingMap->createInstance($entity_type->id(), ['entityTypeId' => $entity_type->id()]);
    if ($entity_operation_mapping->getOperation($operation) && $route = $this->getFormModeRoute($collection, $entity_type, $form_mode, $operation)) {
      $form_mode_name = $this->formModeManager->getFormModeMachineName($form_mode['id']);
      $collection->add($entity_operation_mapping->getOperation($operation) . ".$form_mode_name", $route);
    }
  }

  /**
   * Set one add_page route per form_mode for compatible entities.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   A form-mode for specified entity_type_id.
   *
   * @todo Make this configurable.
   */
  public function setAddPageCollection(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode) {
    $form_mode_name = $this->formModeManager->getFormModeMachineName($form_mode['id']);
    if ($route = $this->getFormModeListPageRoute($entity_type, $form_mode)) {
      $collection->add("form_mode_manager.{$entity_type->id()}.add_page.$form_mode_name", $route);
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
  public function getFormModeListPageRoute(EntityTypeInterface $entity_type, array $form_mode) {
    $route = NULL;
    $entity_type_id = $entity_type->id();
    $entity_type_bundle_id = $entity_type->getBundleEntityType();
    $bundle_entity_type_id = !empty($entity_type_bundle_id) ? $entity_type_bundle_id : $entity_type_id;
    $has_active_mode = $this->formModeManager->hasActiveFormMode(
      $entity_type_id,
      $this->formModeManager->getFormModeMachineName($form_mode['id'])
    );

    if ($has_active_mode) {
      $route = new Route("/$entity_type_id/add-list/{$this->formModeManager->getFormModeMachineName($form_mode['id'])}");
      $route
        ->addDefaults([
          '_controller' => static::FORM_MODE_DEFAULT_CONTROLLER . '::addPage',
          '_title' => $this->t('Add @entity_type', ['@entity_type' => $entity_type->getLabel()])
            ->render(),
          'form_mode_name' => $this->formModeManager->getFormModeMachineName($form_mode['id']),
        ])
        ->addRequirements([
          '_permission' => "use {$form_mode['id']} form mode",
        ])
        ->setOptions([
          '_form_mode_manager_entity_type_id' => $entity_type_id,
          '_form_mode_manager_bundle_entity_type_id' => $bundle_entity_type_id,
          '_admin_route' => TRUE,
        ]);
      $this->userListEnhancements($route, $entity_type->id());
    }

    return $route;
  }

  /**
   * Get the Form Mode Manager route for given operation.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to retrieve parent entity routes.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $form_mode
   *   The form mode info.
   * @param string $operation
   *   The entity operation name.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  public function getFormModeRoute(RouteCollection $collection, EntityTypeInterface $entity_type, array $form_mode, $operation) {
    $entity_type_id = $entity_type->id();
    $has_active_mode = $this->formModeManager->hasActiveFormMode(
      $entity_type_id,
      $this->formModeManager->getFormModeMachineName($form_mode['id'])
    );

    /** @var \Drupal\form_mode_manager\EntityRoutingMapBase $entity_operation_mapping */
    $entity_operation_mapping = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id]);
    if ($has_active_mode && $entity_edit_route = $collection->get($entity_operation_mapping->getOperation($operation))) {
      return $this->setRoutes($entity_edit_route, $entity_type, $form_mode);
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
  public function userListEnhancements(Route $route, $entity_type_id) {
    if ('user' !== $entity_type_id) {
      return;
    }

    $route
      ->setDefault('_controller', '\Drupal\form_mode_manager\Controller\UserFormModeController::entityAdd')
      ->setDefault('_title_callback', '\Drupal\form_mode_manager\Controller\UserFormModeController::addPageTitle');
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
    $route_defaults = array_merge($parent_route->getDefaults(), $this->getFormModeRouteDefaults($parent_route, $form_mode));
    $roue_options = array_merge($parent_route->getOptions(), $this->getFormModeRouteOptions($form_mode, $entity_type));
    $route_requirements = array_merge($parent_route->getRequirements(), $this->getFormModeRouteRequirements($form_mode));

    $route = new Route("{$parent_route->getPath()}/{$this->formModeManager->getFormModeMachineName($form_mode['id'])}");
    $route
      ->addDefaults($route_defaults)
      ->setOptions($roue_options)
      ->addRequirements($route_requirements);

    return $route;
  }

  /**
   * Evaluate if current context is edit.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object of entity.
   *
   * @return bool
   *   True if current route context is edit or False if not.
   */
  public static function isEditRoute(Route $route) {
    return (bool) preg_match_all(self::ROUTE_PATH_CONTEXT_REGEX, $route->getPath(), $matches, PREG_SET_ORDER, 0);
  }

  /**
   * Get defaults parameters nedeed to build Form Mode Manager routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object of entity.
   * @param array $form_mode
   *   The form mode info.
   *
   * @return array
   *   Array contain defaults routes parameters.
   */
  protected function getFormModeRouteDefaults(Route $route, array $form_mode) {
    $route_parameters = [
      '_entity_form' => $form_mode['id'],
      '_controller' => static::FORM_MODE_DEFAULT_CONTROLLER . '::entityAdd',
      '_title_callback' => static::FORM_MODE_DEFAULT_CONTROLLER . '::addPageTitle',
    ];

    if (static::isEditRoute($route)) {
      $route_parameters['_title_callback'] = static::FORM_MODE_DEFAULT_CONTROLLER . '::editPageTitle';
      $route_parameters['_controller'] = static::FORM_MODE_DEFAULT_CONTROLLER . '::entityEdit';
    }

    return $route_parameters;
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
  protected function getFormModeRouteOptions(array $form_mode, EntityTypeInterface $entity_type) {
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
   * @param array $form_mode
   *   The form mode info.
   *
   * @return array
   *   Array contain requirements routes parameters.
   */
  protected function getFormModeRouteRequirements(array $form_mode) {
    return [
      '_permission' => "use {$form_mode['id']} form mode",
      '_custom_access' => static::FORM_MODE_DEFAULT_CONTROLLER . '::checkAccess',
    ];
  }

}
