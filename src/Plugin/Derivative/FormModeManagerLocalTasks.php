<?php

namespace Drupal\form_mode_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines dynamic 'FormModeManager' local tasks.
 */
class FormModeManagerLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The FormModeManager service.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * Constructs a new FormModeManagerLocalTasks.
   *
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(FormModeManagerInterface $form_mode_manager) {
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('form_mode.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    // Add Taks on each entity_types compatible.
    foreach ($form_modes_definitions as $entity_type_id => $form_modes) {
      $this->derivatives["form_mode_manager.$entity_type_id.default.task_tab"] = [
        'route_name' => "entity.$entity_type_id.edit_form",
        'title' => $this->t('Edit as @form_mode', ['@form_mode' => 'Default']),
        'parent_id' => "entity.$entity_type_id.edit_form",
        'cache_tags' => $this->formModeManager->getListCacheTags(),
      ];

      // Add one sub-task by form-mode active.
      foreach ($form_modes as $form_mode_name => $form_mode) {
        $this->derivatives["form_mode_manager.{$form_mode['id']}.task_tab"] = [
          'route_name' => "entity.$entity_type_id.edit_form.$form_mode_name",
          'title' => $this->t('Edit as @form_mode', [
            '@form_mode' => $form_mode['label'],
          ]),
          'parent_id' => "entity.$entity_type_id.edit_form",
          'cache_tags' => $this->formModeManager->getListCacheTags(),
        ];

        if ('user' === $entity_type_id) {
          $this->setUserRegisterTask($form_mode);
        }
      }
    }

    // Ensure Base Plugin Definition are added onto all derivatives.
    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * Set a Specific local tasks to `user.page` pages (register).
   *
   * @param array $form_mode
   *   The definition array of the base plugin.
   */
  private function setUserRegisterTask(array $form_mode) {
    $this->derivatives["form_mode_manager.{$form_mode['id']}.task_tab"] = [
      'route_name' => "user.register.{$this->formModeManager->getFormModeMachineName($form_mode['id'])}",
      'title' => $this->t('Create new account as @form_mode', ['@form_mode' => $form_mode['label']]),
      'base_route' => "user.page",
    ];
  }

}
