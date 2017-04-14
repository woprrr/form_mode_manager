<?php

namespace Drupal\form_mode_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines dynamic local tasks.
 */
class FormModeManagerLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

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
   * Constructs a new DynamicLocalTasks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The new entity display repository.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, AccountInterface $account, FormModeManagerInterface $form_mode_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->account = $account;
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('current_user'),
      $container->get('form_mode.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    foreach ($form_modes_definitions as $entity_type_id => $form_modes) {
      $this->derivatives["$entity_type_id.form_mode_manager"] = [
        'route_name' => "entity.$entity_type_id.form_modes_links_task",
        'title' => $this->t('Edit as ...'),
        'base_route' => "entity.$entity_type_id.canonical",
        'weight' => 10,
      ];

      // @TODO we need to discuss about it that can duplicate "edit" tabs when we user "default" mode.
      $this->derivatives["form_mode_manager.$entity_type_id.default.task_tab"] = [
        'route_name' => "entity.$entity_type_id.edit_form",
        'title' => $this->t('Edit as @form_mode', ['@form_mode' => 'Default']),
        'parent_id' => "form_mode_manager.entities:$entity_type_id.form_mode_manager",
      ];

      foreach ($form_modes as $form_mode_name => $form_mode) {
        $this->derivatives["form_mode_manager.{$form_mode['id']}.task_tab"] = [
          'route_name' => "entity.$entity_type_id.edit_form_$form_mode_name",
          'title' => $this->t('Edit as @form_mode', ['@form_mode' => $form_mode['label']]),
          'parent_id' => "form_mode_manager.entities:$entity_type_id.form_mode_manager",
        ];

        if ('user' === $entity_type_id) {
          $this->derivatives["form_mode_manager.{$form_mode['id']}.task_tab"] = [
            'route_name' => "user.register.$form_mode_name",
            'title' => $this->t('Create new account as @form_mode', ['@form_mode' => $form_mode['label']]),
            'base_route' => "user.page",
          ];
        }
      }
    }

    return $this->derivatives;
  }

}
