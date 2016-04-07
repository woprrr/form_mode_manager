<?php

/**
 * @file
 * Contains \Drupal\form_mode_manager\Controller\FormModeManagerController.
 */

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Entities routes.
 */
class FormModeManagerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a FormModeManagerController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $account) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Provides the node submission form.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param string $form_display
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   *
   * @return array
   *   A node submission form.
   */
  public function entityAdd($entity_bundle_id, $form_display, EntityTypeInterface $entity_type) {
    $form_class = preg_replace('#([^a-z0-9])#', '_', $form_display);
    $entity = $this->entityTypeManager->getStorage($entity_type->id())->create([
      $entity_type->getKey('bundle') => $entity_bundle_id,
      $entity_type->getKey('uid') => $this->account->id()
    ]);
    $entity_form = $this->entityFormBuilder()->getForm($entity, $form_class);

    return $entity_form;
  }

  /**
   * The _title_callback for the entity.add routes,
   * provide by form_mode_manager module.
   *
   * @param string $entity_bundle_id
   *   The id of entity bundle from the first route parameter.
   * @param string $form_display
   *   The operation name identifying the form variation (form_mode).
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle($entity_bundle_id, $form_display, EntityTypeInterface $entity_type) {
    return $this->t('Create @name', ['@name' => $entity_type->getLabel()]);
  }

}
