<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Namespace of Form Mode Manager EntityFormDisplayEditForm overrides.
   */
  const FFM_ENTITY_FORM_DISPLAY_EDIT = 'Drupal\form_mode_manager\Form\FormModeManagerDisplayEditForm';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(AccountInterface $current_user, FormModeManagerInterface $form_mode_manager) {
    $this->currentUser = $current_user;
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('form_mode.manager')
    );
  }

  /**
   * Adds Form Mode Manager forms/links to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    $available_entity_types = array_keys($this->formModeManager->getAllFormModesDefinitions());
    foreach ($available_entity_types as $entity_type_id) {
      if ($entity_definition = $entity_types[$entity_type_id]) {
        $this->formModeManager->setEntityHandlersPerFormModes($entity_definition);
      }
    }
  }

  /**
   * Add new properties onto entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_build()
   */
  public function entityTypeBuild(array &$entity_types) {
    $entity_types['entity_form_display']->setFormClass('edit', self::FFM_ENTITY_FORM_DISPLAY_EDIT);
  }

  /**
   * Adds Form Mode Manager operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    $entity_type_id = $entity->getEntityTypeId();
    $form_modes = $this->formModeManager->getFormModesByEntity($entity_type_id);
    $active_form_modes = $this->formModeManager->getActiveDisplaysByBundle($entity_type_id, $entity->bundle());
    if (isset($active_form_modes[$entity_type_id])
      && $active_modes = array_intersect_key($form_modes, $active_form_modes[$entity_type_id])
    ) {
      foreach ($active_modes as $form_mode_name => $form_mode) {
        if ($this->currentUser->hasPermission("use {$form_mode['id']} form mode") && $entity->hasLinkTemplate("edit-form.$form_mode_name")) {
          $operations += [
            $form_mode_name => [
              'title' => $this->t('Edit as @form_mode_name', ['@form_mode_name' => $form_mode['label']])
                ->render(),
              'url' => $entity->toUrl("edit-form.$form_mode_name"),
              'weight' => 31,
            ],
          ];
        }
      }
    }

    return $operations;
  }

  /**
   * Take control of default operations.
   *
   * @param array $operations
   *   Operations array as returned by getOperations().
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation_alter()
   * @see EntityListBuilderInterface::getOperations()()
   */
  public function entityOperationAlter(array &$operations, EntityInterface $entity) {
    // Operation doesn't check permission on base_route,
    // we need to hide if we don't show it.
    if (isset($operations['edit'])
      && !$this->currentUser->hasPermission("use {$entity->getEntityTypeId()}.default form mode")
    ) {
      unset($operations['edit']);
    }

    return $operations;
  }

}
