<?php

namespace Drupal\form_mode_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines dynamic 'Form Mode Manager' local tasks.
 */
class FormModeManagerLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The specific route name of block_content canonical.
   */
  const BLOCK_CONTENT_CANONICAL = 'entity.block_content.canonical';

  /**
   * The Form Mode Manager service.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * The Form Mode Manager service.
   *
   * @var string[]
   */
  protected $cacheTags;

  /**
   * The Form Mode Manager service.
   *
   * @var array
   */
  protected $formModesDefinitionsList;

  /**
   * Constructs a new Form Mode ManagerLocalTasks.
   *
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(FormModeManagerInterface $form_mode_manager) {
    $this->formModeManager = $form_mode_manager;
    $this->cacheTags = $form_mode_manager->getListCacheTags();
    $this->formModesDefinitionsList = $form_mode_manager->getAllFormModesDefinitions();
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
    foreach ($this->formModesDefinitionsList as $entity_type_id => $form_modes) {
      $this->setDefaultTasks($entity_type_id);
      foreach ($form_modes as $form_mode_name => $form_mode) {
        if ($this->formModeManager->hasActiveFormMode($entity_type_id, $form_mode_name)) {
          $this->setFormModesTasks($form_mode, $entity_type_id, $this->formModeManager->tasksIsPrimary($entity_type_id));
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * Set a Specific local tasks parameters for block_content entity.
   *
   * @param string $element_name
   *   Name of element to enhance.
   * @param string $entity_type_id
   *   The definition of block_content tasks.
   * @param bool $is_default_task
   *   Determine context of tasks (defaults or form mode manager) derivative.
   */
  private function blockContentEnhancer($element_name, $entity_type_id, $is_default_task = TRUE) {
    if ('block_content' === $entity_type_id) {
      if ($is_default_task) {
        $this->derivatives[$element_name]['route_name'] = self::BLOCK_CONTENT_CANONICAL;
      }

      $this->derivatives[$element_name]['parent_id'] = self::BLOCK_CONTENT_CANONICAL;
    }
  }

  /**
   * Set the default tasks on each entities.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  private function setDefaultTasks($entity_type_id) {
    $this->derivatives["form_mode_manager.$entity_type_id.default.task_tab"] = [
      'route_name' => "entity.$entity_type_id.edit_form",
      'title' => $this->t('Edit as Default'),
      'parent_id' => "entity.$entity_type_id.edit_form",
      'cache_tags' => $this->cacheTags,
    ];

    $element_name = "form_mode_manager.$entity_type_id.default.task_tab";
    $this->blockContentEnhancer($element_name, $entity_type_id);
  }

  /**
   * Set the default tasks on each entities.
   *
   * @param array $form_mode
   *   An associative array represent a DisplayForm entity.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param bool $is_primary_tasks
   *   True if we need to place tasks on primary level.
   */
  private function setFormModesTasks(array $form_mode, $entity_type_id, $is_primary_tasks) {
    $this->setFormModesTasksBase($form_mode, $entity_type_id);
    $this->setUserRegisterTasks($form_mode, $entity_type_id);

    $element_name = "form_mode_manager.{$form_mode['id']}.task_tab";
    $this->blockContentEnhancer($element_name, $entity_type_id, FALSE);

    // Evaluate if tasks does be displayed at the primary level.
    if ($is_primary_tasks) {
      $this->derivatives[$element_name]['base_route'] = "entity.$entity_type_id.canonical";
      unset($this->derivatives[$element_name]['parent_id']);
    }
  }

  /**
   * Set a Specific local tasks to `user.page` pages (register).
   *
   * @param array $form_mode
   *   An associative array represent a DisplayForm entity.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  private function setUserRegisterTasks(array $form_mode, $entity_type_id) {
    if ('user' === $entity_type_id) {
      $this->derivatives["form_mode_manager.{$form_mode['id']}.register_task_tab"] = [
        'route_name' => "user.register.{$this->formModeManager->getFormModeMachineName($form_mode['id'])}",
        'title' => $this->t('Create new account as @form_mode', ['@form_mode' => $form_mode['label']]),
        'base_route' => "user.page",
      ];
    }
  }

  /**
   * Set a Specific local tasks to `user.page` pages (register).
   *
   * @param array $form_mode
   *   An associative array represent a DisplayForm entity.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  private function setFormModesTasksBase(array $form_mode, $entity_type_id) {
    $this->derivatives["form_mode_manager.{$form_mode['id']}.task_tab"] = [
      'route_name' => "entity.$entity_type_id.edit_form.{$this->formModeManager->getFormModeMachineName($form_mode['id'])}",
      'title' => $this->t('Edit as @form_mode', [
        '@form_mode' => $form_mode['label'],
      ]),
      'parent_id' => "entity.$entity_type_id.edit_form",
      'cache_tags' => $this->cacheTags,
    ];
  }

}
