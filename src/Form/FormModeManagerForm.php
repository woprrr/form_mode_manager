<?php

namespace Drupal\form_mode_manager\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Form Mode Manager common settings.
 */
class FormModeManagerForm extends ConfigFormBase {

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The form_mode_manager service.
   *
   * @var \Drupal\form_mode_manager\FormModeManager
   */
  protected $formModeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a CropWidgetForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form_mode_manager service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, FormModeManagerInterface $form_mode_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($config_factory);
    $this->settings = $this->config('form_mode_manager.settings');
    $this->entityDisplayRepository = $entity_display_repository;
    $this->formModeManager = $form_mode_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('form_mode.manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_mode_manager_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_mode_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_modes = $this->formModeManager->getAllFormModesDefinitions(TRUE);

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    foreach ($form_modes as $entity_type_id => $modes) {
      $options = array_combine(array_keys($modes), array_keys($modes));

      $form[$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_type_id,
        '#group' => 'vertical_tabs',
      ];

      $form[$entity_type_id]['element_' . $entity_type_id] = [
        '#type' => 'select',
        '#title' => $this->t('Choose what form_mode you need to exclude of Form Mode Manager process.'),
        '#options' => $options,
        '#multiple' => TRUE,
        '#default_value' => $this->settings->get("form_modes.{$entity_type_id}.to_exclude"),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_modes = $this->formModeManager->getAllFormModesDefinitions(TRUE);
    foreach ($form_modes as $entity_type_id => $modes) {
      $this->settings->set("form_modes.{$entity_type_id}.to_exclude", $form_state->getValue('element_' . $entity_type_id));
    }
    $this->settings->save();

    $this->cacheTagsInvalidator->invalidateTags([
      'routes',
      'rendered',
      'local_tasks',
      'local_task',
      'local_action',
      'entity_bundles',
    ]);
  }

}
