<?php

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for entity form mode support.
 *
 * @see \Drupal\form_mode_manager\Routing\RouteSubscriber
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalAction
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalTasks
 */
abstract class EntityFormModeBase extends ControllerBase implements ContainerInjectionInterface {

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
   * @var \Drupal\form_mode_manager\FormModeManager
   */
  protected $formModeManager;

  /**
   * Constructs a EntityFormModeController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(RendererInterface $renderer, AccountInterface $account, FormModeManagerInterface $form_mode_manager) {
    $this->renderer = $renderer;
    $this->account = $account;
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('form_mode.manager')
    );
  }

  /**
   * Displays add content links for available entity types.
   *
   * Redirects to entity/add/[bundle] if only one content type is available.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param string $form_mode_name
   *   The operation name identifying the form variation (form_mode).
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the entity types that can be added; however,
   *   if there is only one entity type defined for the site, the function
   *   will return a RedirectResponse to the entity add page for that one entity
   *   type.
   */
  public function addPage(EntityTypeInterface $entity_type, $form_mode_name) {
    $build = [
      '#theme' => 'form_mode_manager_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()
          ->getDefinition($entity_type->getBundleEntityType())
          ->getListCacheTags(),
      ],
    ];

    $content = [];
    foreach ($this->entityTypeManager()->getStorage($entity_type->getBundleEntityType())->loadMultiple() as $bundle) {
      $access = $this->entityTypeManager()->getAccessControlHandler($entity_type->id())->createAccess($bundle->id(), NULL, [], TRUE);
      if ($access->isAllowed() && $this->formModeManager->is_active($entity_type->id(), $bundle->id(), $form_mode_name)) {
        $content[$bundle->id()] = $bundle;
        $this->renderer->addCacheableDependency($build, $access);
      }
    }

    // Bypass the entity/add listing if only one content type is available.
    if (1 == count($content)) {
      $bundle = array_shift($content);
      return $this->redirect("entity.{$entity_type->id()}.add_form.$form_mode_name", [
        $entity_type->getBundleEntityType() => $bundle->id(),
      ]);
    }
    $build['#content'] = $content;
    $build['#form_mode'] = $form_mode_name;

    return $build;
  }

  /**
   * Provides the node submission form.
   *
   * @TODO Optimize function.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param array $form_mode_name
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   *
   * @return array
   *   A node submission form.
   */
  public function entityAdd(RouteMatchInterface $route_match) {
    // On check le context (une route de add sans entity dans la route ou un edit avec une entity dans la route).
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entityTypeManager()->getStorage($route_entity_type_info['entity_type_id'])->create([
        $route_entity_type_info['entity_key'] => $route_entity_type_info['bundle'],
      ]);
    }

    $form_mode_id = $this->getFormModeMachineName($route_match->getRouteObject()->getDefault('_entity_form'));
    $operation = empty($form_mode_id) ? 'default' : $form_mode_id;
    if ($entity instanceof EntityInterface) {
      return $this->entityFormBuilder()->getForm($entity, $operation);
    }

    throw new \Exception('Invalide entity passed or inexistant form mode');
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @TODO Refactor if/else part.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param array $form_mode
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(RouteMatchInterface $route_match) {
    $entity_storage = $this->getEntityBundle($route_match);
    $form_mode_label = isset($route_entity_type_info) ? $route_entity_type_info['form_mode']['label'] : $route_match->getRouteObject()->getOption('parameters')['form_mode']['label'];
    return $this->t('Create @name as @form_mode_label', [
      '@name' => (!$entity_storage instanceof UserStorageInterface) ? $entity_storage->get('name') : $entity_storage->getEntityType()->id(),
      '@form_mode_label' => $form_mode_label,
    ]);
  }

  /**
   * Get EntityStorage of entity.
   *
   * @return EntityStorageInterface|EntityInterface
   *   The storage of current entity or EntityInterface.
   */
  private function getEntityBundle(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager()
        ->getStorage($route_entity_type_info['bundle_entity_type'])
        ->load($route_entity_type_info['bundle']);
    }
    else {
      /* @var \Drupal\Core\Entity\EntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager()
        ->getStorage($route_match->getRouteObject()->getOption('_form_mode_manager_bundle_entity_type_id'))
        ->load($entity->bundle());
    }

    if (empty($bundle)) {
      /* @var \Drupal\Core\Entity\EntityStorageInterface $bundle */
      $bundle = $this->entityTypeManager()
        ->getStorage($route_match->getRouteObject()->getOption('_form_mode_manager_bundle_entity_type_id'));
    }

    return $bundle;
  }

  /**
   * Checks access for the Form Mode Manager routes.
   *
   * @TODO check permission after refactor only...
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is,
   *   used for multiple, possibly dynamic entity types.
   * @param string $form_mode_name
   *   The operation name identifying the form variation (form_mode).
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match) {
    return AccessResult::allowed();
  }

  /**
   * Get Form Mode Machine Name.
   *
   * @TODO Move it in FormModeManager service.
   *
   * @param string $form_mode_id
   *   Machine name of form mode.
   *
   * @return string
   *   The form mode machine name without prefixe of,
   *   entity (entity.form_mode_name).
   */
  protected function getFormModeMachineName($form_mode_id) {
    return preg_replace('/^.*\./', '', $form_mode_id);
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
    $parameter_name = $route_match->getRouteObject()->getOption('_form_mode_manager_entity_type_id');
    $entity = $route_match->getParameter($parameter_name);
    return $entity;
  }

  /**
   * Retrieves entity from route match.
   *
   * @TODO TO Refactor / simplify...
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityTypeFromRouteMatch(RouteMatchInterface $route_match) {
    // On récupéres toutes les datas necessaire depuis la route object.
    // On ce sert plus du param converter car les routes sont toutes identiques et c'est bad ...
    $route = $route_match->getRouteObject();
    $entity_type_id = $route->getOption('_form_mode_manager_entity_type_id');
    $bundle_entity_type_id = $route->getOption('_form_mode_manager_bundle_entity_type_id');
    $form_mode = $this->getFormModeMachineName($route->getDefault('_entity_form'));
    $bundle = $route_match->getParameter($bundle_entity_type_id);
    $form_mode_definition = $this->formModeManager->getActiveDisplaysByBundle($entity_type_id, $bundle);
    $entity_type_key = $this->entityTypeManager()->getDefinition($entity_type_id)->getKey('bundle');

    return [
      'bundle' => $bundle,
      'bundle_entity_type' => $bundle_entity_type_id,
      'entity_key' => $entity_type_key,
      'entity_type_id' => $entity_type_id,
      'form_mode' => !empty($form_mode_definition) ? $form_mode_definition[$entity_type_id][$form_mode] : NULL,
    ];
  }

}
