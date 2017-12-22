<?php

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\form_mode_manager\ComplexEntityFormModes;
use Drupal\form_mode_manager\EntityFormModeManagerInterface;
use Drupal\form_mode_manager\MediaEntityFormModes;
use Drupal\form_mode_manager\SimpleEntityFormModes;
use Drupal\form_mode_manager\EntityRoutingMapManager;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Drupal\form_mode_manager\TaxonomyEntityFormModes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic Controller for entity using form mode manager routing.
 *
 * This controller are very transverse and use an Abstract Factory to build,
 * objects compatible with all ContentEntities. This controller are linked by,
 * Abstract Factory by EntityFormModeManagerInterface each methods in that,
 * interface are called by routing.
 */
class FormModeManagerEntityController implements EntityFormModeManagerInterface, ContainerInjectionInterface {

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityFormModeController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\form_mode_manager\EntityRoutingMapManager $plugin_routes_manager
   *   Plugin EntityRoutingMap to retrieve entity form operation routes.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RendererInterface $renderer, AccountInterface $account, FormModeManagerInterface $form_mode_manager, EntityFormBuilderInterface $entity_form_builder, EntityRoutingMapManager $plugin_routes_manager, FormBuilderInterface $form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->renderer = $renderer;
    $this->account = $account;
    $this->formModeManager = $form_mode_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityRoutingMap = $plugin_routes_manager;
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('form_mode.manager'),
      $container->get('entity.form_builder'),
      $container->get('plugin.manager.entity_routing_map'),
      $container->get('form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addPage(RouteMatchInterface $route_match) {
    return $this->getEntityControllerResponse(__FUNCTION__, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function addPageTitle(RouteMatchInterface $route_match) {
    return $this->getEntityControllerResponse(__FUNCTION__, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(RouteMatchInterface $route_match) {
    return $this->getEntityControllerResponse(__FUNCTION__, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function editPageTitle(RouteMatchInterface $route_match) {
    return $this->getEntityControllerResponse(__FUNCTION__, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function entityAdd(RouteMatchInterface $route_match) {
    return $this->getEntityControllerResponse(__FUNCTION__, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function entityEdit(RouteMatchInterface $route_match) {
    return $this->getEntityControllerResponse(__FUNCTION__, $route_match);
  }

  /**
   * Instantiate correct objects depending entities.
   *
   * Contain all the logic to use the abstract factory and call,
   * correct entityFormMode object depending entity_type using bundles,
   * or if the entity need to be processed specifically like Taxonomy.
   *
   * All of children object share EntityFormModeManagerInterface to make sure,
   * methods are used by factory.
   *
   * @param string $method
   *   Name of the method we need to build.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\form_mode_manager\EntityFormModeManagerInterface
   *   An instance of correct controller object.
   *
   * @throws \Exception
   *   Thrown when specified method was not found.
   */
  public function getEntityControllerResponse($method, RouteMatchInterface $route_match) {
    $entity_type_id = $route_match->getRouteObject()
      ->getOption('_form_mode_manager_entity_type_id');
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    // Entities without bundles need to be flagged 'unbundled_entity'.
    $entity_type_id = $this->bundledEntity($entity_storage) ? $entity_type_id : 'unbundled_entity';
    $controller_object = $this->getEntityControllerObject($entity_type_id);

    if (!method_exists($controller_object, $method)) {
      throw new \Exception('Specified ' . $method . ' method not found.');
    }

    return $controller_object->{$method}($route_match);
  }

  /**
   * Get the correct controller object Factory depending kind of entity.
   *
   * @param string $entity_type_id
   *   The name of entity type.
   *
   * @return \Drupal\form_mode_manager\EntityFormModeManagerInterface
   *   An instance of correct controller object.
   */
  public function getEntityControllerObject($entity_type_id) {
    switch ($entity_type_id) {
      case 'unbundled_entity':
        $object = new SimpleEntityFormModes(
          $this->renderer,
          $this->account,
          $this->formModeManager,
          $this->entityFormBuilder,
          $this->entityRoutingMap,
          $this->formBuilder,
          $this->entityTypeManager
        );

        break;

      case 'taxonomy_term':
        $object = new TaxonomyEntityFormModes(
          $this->renderer,
          $this->account,
          $this->formModeManager,
          $this->entityFormBuilder,
          $this->entityRoutingMap,
          $this->formBuilder,
          $this->entityTypeManager
        );

        break;

      case 'media':
        $object = new MediaEntityFormModes(
          $this->renderer,
          $this->account,
          $this->formModeManager,
          $this->entityFormBuilder,
          $this->entityRoutingMap,
          $this->formBuilder,
          $this->entityTypeManager
        );

        break;

      default:
        $object = new ComplexEntityFormModes(
          $this->renderer,
          $this->account,
          $this->formModeManager,
          $this->entityFormBuilder,
          $this->entityRoutingMap,
          $this->formBuilder,
          $this->entityTypeManager
        );
        break;
    }

    return $object;
  }

  /**
   * Evaluate if current entity have bundles or not.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   *
   * @return string
   *   The bundle key if entity has bundles or empty.
   */
  public function bundledEntity(EntityStorageInterface $entity_storage) {
    return $entity_storage->getEntityType()->getKey('bundle');
  }

}
