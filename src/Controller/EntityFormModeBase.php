<?php

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Drupal\form_mode_manager\EntityRoutingMapManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for entity form mode support.
 *
 * @see \Drupal\form_mode_manager\Routing\RouteSubscriber
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalAction
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalTasks
 */
abstract class EntityFormModeBase implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The Routes Manager Plugin.
   *
   * @var \Drupal\form_mode_manager\EntityRoutingMapManager
   */
  protected $entityRoutingMap;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a EntityFormModeController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\form_mode_manager\EntityRoutingMapManager $plugin_routes_manager
   *   Plugin EntityRoutingMap to retrieve entity form operation routes.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(RendererInterface $renderer, AccountInterface $account, FormModeManagerInterface $form_mode_manager, EntityTypeManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, EntityRoutingMapManager $plugin_routes_manager, FormBuilderInterface $form_builder) {
    $this->renderer = $renderer;
    $this->account = $account;
    $this->formModeManager = $form_mode_manager;
    $this->entityTypeManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityRoutingMap = $plugin_routes_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('form_mode.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('plugin.manager.entity_routing_map'),
      $container->get('form_builder')
    );
  }
  /**
   * Displays add content links for available entity types.
   *
   * Redirects to entity/add/[bundle] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the entity types that can be added; however,
   *   if there is only one entity type defined for the site, the function
   *   will return a RedirectResponse to the entity add page for that one entity
   *   type.
   */
  public function addPage(RouteMatchInterface $route_match) {
    $entity_type_id = $route_match->getRouteObject()->getOption('_form_mode_manager_entity_type_id');
    $entity_bundle_name = $route_match->getRouteObject()->getOption('_form_mode_manager_bundle_entity_type_id');
    $form_mode_name = $route_match->getRouteObject()->getDefault('form_mode_name');

    $entity_type_cache_tags = $this->entityTypeManager
      ->getDefinition($entity_bundle_name)
      ->getListCacheTags();
    $entity_type_definitions = $this->entityTypeManager
      ->getStorage($entity_bundle_name)
      ->loadMultiple();

    $build = [
      '#theme' => 'form_mode_manager_add_list',
      '#entity_type' => $entity_type_id,
      '#cache' => [
        'tags' => Cache::mergeTags($entity_type_cache_tags, $this->formModeManager->getListCacheTags()),
      ],
    ];

    $content = [];
    foreach ($entity_type_definitions as $bundle) {
      $bundle_id = $bundle->id();
      $access = $this->entityTypeManager
        ->getAccessControlHandler($entity_type_id)
        ->createAccess($bundle_id, $this->account, [], TRUE);

      if ($access->isAllowed() && $this->formModeManager->isActive($entity_type_id, $bundle_id, $form_mode_name)) {
        $content[$bundle_id] = $bundle;
        $this->renderer->addCacheableDependency($build, $access);
      }
    }

    // Bypass the entity/add listing if only one content type is available.
    if (1 == count($content)) {
      $bundle = array_shift($content);
      $entity_routes_infos = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id])->getPluginDefinition();
      return $this->redirect($entity_routes_infos['operations']['add_form'] . ".$form_mode_name", [
        $entity_bundle_name => $bundle->id(),
      ]);
    }

    $build['#content'] = $content;
    $build['#form_mode'] = $form_mode_name;

    return $build;
  }

  /**
   * Provides the entity add submission form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   The entity add Form.
   */
  public function entityAdd(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entityTypeManager
        ->getStorage($route_entity_type_info['entity_type_id'])
        ->create([
          $route_entity_type_info['entity_key'] => $route_entity_type_info['bundle'],
        ]);
    }

    $form_mode_id = $this->formModeManager->getFormModeMachineName($route_match->getRouteObject()
      ->getDefault('_entity_form'));
    $operation = empty($form_mode_id) ? 'default' : $form_mode_id;
    if ($entity instanceof EntityInterface) {
      return $this->entityFormBuilder->getForm($entity, $operation);
    }
  }

  /**
   * Provides the entity 'edit' form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   The entity edit Form.
   */
  public function entityEdit(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    $form_mode_id = $this->formModeManager->getFormModeMachineName($route_match->getRouteObject()->getOption('parameters')['form_mode']['id']);
    $operation = empty($form_mode_id) ? 'default' : 'edit_' . $form_mode_id;

    if ($entity instanceof EntityInterface) {
      return $this->getForm($entity, $operation);
    }
  }

  /**
   * Gets the built and processed entity form for the given entity.
   *
   * This method are very similar to EntityFormBuilderInterface::getForm,
   * for this module we need to add two form handler by form mode eg :
   * form_mode_1 => EntityFormClass
   * edit_form_mode_1 => EntityFormClass
   * to provide ability to define different EntityForm class for form,
   * for add/edit (or others) contexts.
   * Actually EntityFormBuilderInterface::getForm are designed to only have,
   * one operation (form mode) by action (add/edit/xxxx).
   *
   * In that method we use $operation parameter to retrieve the correct,
   * FormObject with our context prefixed by 'edit_' or not and in next step we,
   * set the correct Operation form with only the form mode name,
   * with ->setOperation() method onto FormObject.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be created or edited.
   * @param string $operation
   *   (optional) The operation identifying the form variation to be returned.
   *   Defaults to 'default'. This is typically used in routing:.
   * @param array $form_state_additions
   *   (optional) An associative array used to build the current state of the
   *   form. Use this to pass additional information to the form, such as the
   *   langcode. Defaults to an empty array.
   *
   * @return array
   *   The entity Form.
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state_additions = []) {
    $form_object = $this->entityTypeManager->getFormObject($entity->getEntityTypeId(), $operation);
    $form_object->setEntity($entity)
      ->setOperation($this->getFormModeOperationName($operation));

    $form_state = (new FormState())->setFormState($form_state_additions);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }

  /**
   * Retrieve the operation (form mode) name in edit context.
   *
   * In Form Mode Manager all edit routes use a contextual FormClass to provide,
   * a FormClass handler different by context (add/edit).
   *
   * @param string $operation
   *   The form mode id with contextual prefix.
   *
   * @return string
   *   The form mode id without contextual prefix 'edit_'.
   */
  public function getFormModeOperationName($operation) {
    return preg_replace('/^(edit_)/', '', $operation);
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(RouteMatchInterface $route_match) {
    return $this->pageTitle($route_match, $this->t('Create'));
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return string
   *   The page title.
   */
  public function editPageTitle(RouteMatchInterface $route_match) {
    return $this->pageTitle($route_match, $this->t('Edit'));
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param string $operation
   *   Name of current context operation to display title (create/edit).
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(RouteMatchInterface $route_match, $operation) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityBase $entity_storage */
    $entity_storage = $this->getEntityBundle($route_match);
    $form_mode_label = $route_match->getRouteObject()
      ->getOption('parameters')['form_mode']['label'];
    return $this->t('@op @name as @form_mode_label', [
      '@name' => $entity_storage->label(),
      '@form_mode_label' => $form_mode_label,
      '@op' => $operation,
    ]);
  }

  /**
   * Get EntityStorage of entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|\Drupal\Core\Entity\EntityInterface
   *   The storage of current entity or EntityInterface.
   */
  private function getEntityBundle(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager
        ->getStorage($route_entity_type_info['bundle_entity_type'])
        ->load($route_entity_type_info['bundle']);
    }
    else {
      /* @var \Drupal\Core\Entity\EntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager
        ->getStorage($route_match->getRouteObject()
          ->getOption('_form_mode_manager_bundle_entity_type_id'))
        ->load($entity->bundle());
    }

    if (empty($bundle)) {
      /* @var \Drupal\Core\Entity\EntityStorageInterface $bundle */
      $bundle = $this->entityTypeManager
        ->getStorage($route_match->getRouteObject()
          ->getOption('_form_mode_manager_bundle_entity_type_id'));
    }

    return $bundle;
  }

  /**
   * Checks access for the Form Mode Manager routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    $route = $route_match->getRouteObject();
    $form_mode_id = $route->getDefault('_entity_form');
    $cache_tags = $this->formModeManager->getListCacheTags();

    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      $entity_type_id = $route_entity_type_info['entity_type_id'];
      $bundle_id = isset($route_entity_type_info['bundle']) ? $route_entity_type_info['bundle'] : $route->getOption('_form_mode_manager_bundle_entity_type_id');
    }
    else {
      $entity_type_id = $route->getOption('_form_mode_manager_entity_type_id');
      $bundle_id = !empty($route_match->getParameter($entity_type_id)) ? $route_match->getParameter($entity_type_id)->bundle() : 'user';
    }

    $operation = $this->getFormModeOperationName($this->formModeManager->getFormModeMachineName($form_mode_id));
    $result = AccessResult::allowedIf($this->formModeManager->isActive($entity_type_id, $bundle_id, $operation))->addCacheTags($cache_tags);
    if ($entity) {
      $result->addCacheableDependency($entity);
    }

    return $result;
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()
      ->getOption('_form_mode_manager_entity_type_id');
    $entity = $route_match->getParameter($parameter_name);
    return $entity;
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityTypeFromRouteMatch(RouteMatchInterface $route_match) {
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
