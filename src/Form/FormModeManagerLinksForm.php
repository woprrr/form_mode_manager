<?php

namespace Drupal\form_mode_manager\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Form Mode Manager links.
 */
class FormModeManagerLinksForm extends ConfigFormBase {

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Available positioning of generated Local tasks.
   *
   * @var array
   */
  protected $localTaskTypes = [
    'primary' => 'Primary tasks',
    'secondary' => 'Secondary tasks',
  ];

  /**
   * Constructs a CropWidgetForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FormModeManagerInterface $form_mode_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($config_factory);
    $this->settings = $this->config('form_mode_manager.links');
    $this->formModeManager = $form_mode_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('form_mode.manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_mode_manager_links_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_mode_manager.links'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['local_taks'] = [
      '#type' => 'details',
      '#title' => $this->t('Local Tasks'),
      '#open' => TRUE,
    ];

    $form['local_taks']['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    $form_modes = array_keys($this->formModeManager->getAllFormModesDefinitions(TRUE));
    foreach ($form_modes as $entity_type_id) {
      $form['local_taks']["{$entity_type_id}_local_taks"] = [
        '#type' => 'details',
        '#title' => $entity_type_id,
        '#description' => $this->t('The following options are available for make a better flexibility of local task displaying.'),
        '#group' => 'vertical_tabs',
      ];

      $form['local_taks']["{$entity_type_id}_local_taks"]['tasks_location_' . $entity_type_id] = [
        '#title' => $this->t('Position of Local tasks'),
        '#type' => 'select',
        '#options' => $this->localTaskTypes,
        '#default_value' => $this->settings->get("local_tasks.{$entity_type_id}.position"),
        '#description' => $this->t('The location of local tasks. <ul><li><b>Primary level</b> are at the same position as "Edit" default task</li><li><b>Secondary</b> level place all form-modes tasks below "Edit" task (at secondary menu). </li></ul>'),
        '#weight' => 0,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_modes = array_keys($this->formModeManager->getAllFormModesDefinitions(TRUE));
    foreach ($form_modes as $entity_type_id) {
      $this->settings->set("local_tasks.{$entity_type_id}.position", $form_state->getValue('tasks_location_' . $entity_type_id));
    }
    $this->settings->save();

    $this->cacheTagsInvalidator->invalidateTags([
      'local_task',
      'rendered',
    ]);
  }

}
