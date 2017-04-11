<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(AccountInterface $current_user, EntityDisplayRepositoryInterface $entity_display_repository, FormModeManagerInterface $form_mode_manager) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->currentUser = $current_user;
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_display.repository'),
      $container->get('form_mode.manager')
    );
  }

  /**
   * Adds FormModeManager forms/links to appropriate entity types.
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
      /* @var \Drupal\Core\Entity\EntityTypeInterface $entity_definition */
      if ($entity_definition = $entity_types[$entity_type_id]) {
        $form_modes = $this->formModeManager->getFormModesIdByEntity($entity_type_id);
        $default_form = $entity_definition->getHandlerClasses()['form']['default'];
        foreach ($form_modes as $form_mode_name) {
          $path = $this->formModeManager->getFormModeManagerPath($entity_definition, $form_mode_name);
          $entity_definition->setFormClass($form_mode_name, $default_form)
            ->setLinkTemplate($form_mode_name, $path);
        }
      }
    }
  }

  /**
   * Adds FormModeManager operations on entity that supports it.
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
        if ($this->currentUser->hasPermission("use {$form_mode['id']} form mode") && $entity->hasLinkTemplate($form_mode_name)) {
          $operations += [
            $form_mode_name => [
              'title' => $this->t('Edit as @form_mode_name', ['@form_mode_name' => $form_mode['label']])->render(),
              'url' => $entity->toUrl($form_mode_name)
                ->setRouteParameters([
                  $entity_type_id => $entity->id(),
                ]),
              'weight' => 100,
            ],
          ];
        }
      }
    }

    return $operations;
  }

}
