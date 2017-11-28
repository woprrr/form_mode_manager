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
  const FMM_ENTITY_FORM_DISPLAY_EDIT = 'Drupal\form_mode_manager\Form\FormModeManagerDisplayEditForm';

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
    $entity_types['entity_form_display']->setFormClass('edit', self::FMM_ENTITY_FORM_DISPLAY_EDIT);
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
    $form_modes = $this->formModeManager->getFormModesByEntity($entity->getEntityTypeId());
    foreach ($form_modes as $form_mode_name => $form_mode) {
      if ($this->grantAccessToFormModeOperation($form_mode, $entity)) {
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
    if (empty($this->formModeManager->getFormModesByEntity($entity->getEntityTypeId()))) {
      return $operations;
    }
    elseif ($this->grantAccessToEditOperation($operations, $entity)) {
      unset($operations['edit']);
    }

    return $operations;
  }

  /**
   * Evaluate if current user has access to "edit" operations.
   *
   * @param array $operations
   *   Operations array as returned by getOperations().
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return bool
   *   TRUE if user have access to default edit button, if not FALSE.
   *
   * @see entityOperationAlter()
   */
  public function grantAccessToEditOperation($operations, EntityInterface $entity) {
    return isset($operations['edit']) && !$this->currentUser->hasPermission("use {$entity->getEntityTypeId()}.default form mode");
  }

  /**
   * Evaluate if current user has access to given form mode operations.
   *
   * @param array $form_mode
   *   Current form mode definition.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return bool
   *   TRUE if user have access to given form mode, if not FALSE.
   *
   * @see entityOperation()
   */
  public function grantAccessToFormModeOperation(array $form_mode, EntityInterface $entity) {
    $form_mode_id = $form_mode['id'];
    $form_mode_machine_name = $this->formModeManager->getFormModeMachineName($form_mode_id);
    return $this->formModeManager->isActive($entity->getEntityTypeId(), $entity->bundle(), $form_mode_machine_name) && $this->currentUser->hasPermission("use {$form_mode['id']} form mode");
  }

}
