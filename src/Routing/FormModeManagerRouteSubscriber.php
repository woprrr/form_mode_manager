<?php

namespace Drupal\form_mode_manager\Routing;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\form_mode_manager\FormModeManagerInterface;
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
   * Gets the entity load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormModeManagerListFormModes(EntityTypeInterface $entity_type) {
    if ($form_mode_list = $entity_type->getLinkTemplate('form-modes-list')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($form_mode_list);

      // Voir pour définir une permission et afficher une task "default".
      $route
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
   * {@inheritdoc}
   *
   * @TODO Refactor continue in this part. Edit / add context are Ok now. We can refactor After this API change...
   */
  protected function alterRoutes(RouteCollection $collection) {
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    foreach ($form_modes_definitions as $entity_type_id => $form_modes) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      // Génération de la liste des form-modes par task.
      if ($list_task = $this->getFormModeManagerListFormModes($entity_type)) {
        // Add entity type list page specific to form_modes.
        $collection->add("entity.$entity_type_id.form_modes_list", $list_task);
      }
      foreach ($form_modes as $form_mode_name => $form_mode) {
        if ($entity_type->getFormClass($form_mode_name)) {
          $entity_type_id = $entity_type->id();
          $collection_route_name = "entity.$entity_type_id.add_form";
          if ('node' === $entity_type_id) {
            $collection_route_name = $entity_type_id . '.add';
          }

          if ('block_content' === $entity_type_id) {
            $collection_route_name = $entity_type_id . '.add_form';
          }

          if ('contact' === $entity_type_id) {
            $collection_route_name = $entity_type_id . '.form_add';
          }

          if ('user' === $entity_type_id) {
            $collection_route_name = $entity_type_id . '.register';
          }

          if ($entity_add_route = $collection->get($collection_route_name)) {
            $add_route = new Route("{$entity_add_route->getPath()}/$form_mode_name");
            $route_defaults = $entity_add_route->getDefaults();
            $route_defaults['_entity_form'] = $form_mode['id'];
            $route_defaults['_controller'] = '\Drupal\form_mode_manager\Controller\EntityFormModeController::entityAdd';
            $route_defaults['_title_callback'] = '\Drupal\form_mode_manager\Controller\EntityFormModeController::addPageTitle';
            $add_route->addDefaults($route_defaults);

            $roue_options = $entity_add_route->getOptions();
            $roue_options['_form_mode_manager_entity_type_id'] = $entity_type->id();
            $roue_options['_form_mode_manager_bundle_entity_type_id'] = $entity_type->getBundleEntityType();
            $roue_options['parameters'] = [
              $entity_type_id => ['type' => "entity:$entity_type_id"],
              'form_mode' => $form_mode,
            ];

            $add_route->setOptions($roue_options);

            $route_requirements = $entity_add_route->getRequirements();
            $route_requirements['_custom_access'] = '\Drupal\form_mode_manager\Controller\EntityFormModeController::checkAccess';
            $add_route->addRequirements($route_requirements);

            // Normalize the path with common pattern `entity.entity_type_id.action.entity_form`
            $collection->add("entity.$entity_type_id.add_form_$form_mode_name", $add_route);
          }
        }

        if ($list_route = $this->getFormModeManagerListPage($collection, $entity_type, $form_mode, $form_mode_name)) {
          // Add entity type list page specific to form_modes.
          $collection->add("form_mode_manager.{$form_mode['id']}.add_page", $list_route);
        }

        // Edit routes.
        if ($entity_edit_route = $collection->get("entity.$entity_type_id.edit_form")) {
          $edit_route = new Route("{$entity_edit_route->getPath()}/$form_mode_name");
          $route_defaults = $entity_edit_route->getDefaults();
          $route_defaults['_entity_form'] = $form_mode['id'];
          $route_defaults['_controller'] = '\Drupal\form_mode_manager\Controller\EntityFormModeController::entityAdd';
          $route_defaults['_title_callback'] = '\Drupal\form_mode_manager\Controller\EntityFormModeController::addPageTitle';
          $route_defaults[$entity_type->getBundleEntityType()] = "{{$entity_type->getBundleEntityType()}}";
          $edit_route->addDefaults($route_defaults);

          $roue_options = $entity_edit_route->getOptions();
          $roue_options['_form_mode_manager_entity_type_id'] = $entity_type->id();
          $roue_options['_form_mode_manager_bundle_entity_type_id'] = $entity_type->getBundleEntityType();
          $roue_options['parameters'] = [
            $entity_type_id => ['type' => "entity:$entity_type_id"],
            'form_mode' => $form_mode,
          ];

          $edit_route->setOptions($roue_options);

          $route_requirements = $entity_edit_route->getRequirements();
          $route_requirements['_custom_access'] = '\Drupal\form_mode_manager\Controller\EntityFormModeController::checkAccess';
          $edit_route->addRequirements($route_requirements);
          $collection->add("entity.$entity_type_id.edit_form_$form_mode_name", $edit_route);
        }
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
