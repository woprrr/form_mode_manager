<?php

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for form_mode_manager routes.
 */
class FormModeManagerController extends ControllerBase implements ContainerInjectionInterface {

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
   * Get formModeMachineName
   *
   * @param array $form_display
   *   An array represent needed Form mode for an entity.
   *
   * @return string
   *   The form mode machine name without prefixe of entity (entity.form_mode_name).
   */
  public function getFormModeMachineName(array $form_display) {
    return preg_replace('/^.*\./', '', $form_display['id']);
  }

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, AccountInterface $account) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * Displays add content links for available entity types.
   *
   * Redirects to entity/add/[bundle] if only one content type is available.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   * @param string $form_display
   *   The operation name identifying the form variation (form_mode).
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the entity types that can be added; however,
   *   if there is only one entity type defined for the site, the function
   *   will return a RedirectResponse to the entity add page for that one entity
   *   type.
   */
  public function addPage(EntityTypeInterface $entity_type, $form_display) {
      $build = [
      '#theme' => 'form_mode_manager_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition($entity_type->getBundleEntityType())->getListCacheTags(),
      ],
    ];

    $content = [];
    foreach ($this->entityTypeManager()->getStorage($entity_type->getBundleEntityType())->loadMultiple() as $bundle) {
      $access = $this->entityTypeManager()->getAccessControlHandler($entity_type->id())->createAccess($bundle->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$bundle->id()] = $bundle;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the entity/add listing if only one content type is available.
    if (count($content) == 1) {
      $bundle = array_shift($content);
      return $this->redirect("entity.add.$form_display", ['entity_bundle_id' => $bundle->id(), 'form_display' => $form_display]);
    }

    $build['#content'] = $content;
    $build['#form_mode'] = $form_display;

    return $build;
  }

  /**
   * Provides the node submission form.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param array $form_display
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   *
   * @return array
   *   A node submission form.
   */
  public function entityAdd($entity_bundle_id, $form_display, EntityTypeInterface $entity_type) {
    $entity_interface = $this->entityTypeManager()->getStorage($entity_type->id())->create([
      $entity_type->getKey('bundle') => $entity_bundle_id,
      $entity_type->getKey('uid') => $this->account->id()
    ]);
    $entity_form = $this->entityFormBuilder()->getForm($entity_interface, $this->getFormModeMachineName($form_display));

    return $entity_form;
  }

  /**
   * The _title_callback for the entity.add routes,
   * provide by form_mode_manager module.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param array $form_display
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle($entity_bundle_id, $form_display, EntityTypeInterface $entity_type) {
    $bundle = $this->entityTypeManager()->getStorage($entity_type->getBundleEntityType())->load($entity_bundle_id);
    return $this->t('Create @name as @form_display', ['@name' => $bundle->get('name'), '@form_display' => $form_display['label']]);
  }

  /**
   * Checks access for the FormModeManager routes.
   *
   * @param string $form_display
   *   The operation name identifying the form variation (form_mode).
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess($form_display) {
    return AccessResult::allowedIfHasPermission($this->currentUser(), "use {$form_display} form mode")->cachePerPermissions();
  }

}
