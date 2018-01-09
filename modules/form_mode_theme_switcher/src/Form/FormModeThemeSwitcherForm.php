<?php

namespace Drupal\form_mode_manager_theme_switcher\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\form_mode_manager\Form\FormModeManagerFormBase;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Form for Form Mode Manager theme switcher settings.
 */
class FormModeThemeSwitcherForm extends FormModeManagerFormBase {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, FormModeManagerInterface $form_mode_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator, EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    parent::__construct($config_factory, $entity_display_repository, $form_mode_manager, $cache_tags_invalidator, $entity_type_manager);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('form_mode.manager'),
      $container->get('cache_tags.invalidator'),
      $container->get('entity_type.manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_mode_theme_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_mode.theme_switcher'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableThemeTypeOptions() {
    $options = [
      'default' => $this->t('Default Theme (@theme)', ['@theme' => $this->configFactory->get('system.theme')->get('default')]),
      'admin' => $this->t('Administration theme (@theme)', ['@theme' => $this->configFactory->get('system.theme')->get('admin')]),
      '_custom' => $this->t('Specific theme'),
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeOptions() {
    $options = [];
    foreach ($this->state->get('system.theme.data') as $theme_name => $theme_extension) {
      $options[$theme_name] = $theme_extension->info['name'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['theme_switcher_form_mode_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Theme Switcher per form mode'),
      '#open' => TRUE,
    ];

    $form['theme_switcher_form_mode_settings']['vertical_tabs_per_modes'] = [
      '#type' => 'vertical_tabs',
    ];

    $this->ignoreExcluded = TRUE;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormPerEntity(array &$form, array $form_modes, $entity_type_id) {
    $entity_label = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType()->getLabel();
    $form['theme_switcher_form_mode_settings'][$entity_type_id] = [
      '#type' => 'details',
      '#title' => $entity_label,
      '#description' => $this->t('Allows you to configure the negotiation of the themes used by the routes using the form modes for the entity <b>@entity_type_id</b>', ['@entity_type_id' => $entity_label]),
      '#group' => 'vertical_tabs_per_modes',
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormPerFormMode(array &$form, array $form_mode, $entity_type_id) {
    $form_mode_id = str_replace('.', '_', $form_mode['id']);
    $form['theme_switcher_form_mode_settings'][$entity_type_id]['form_modes'] = [
      '#type' => 'details',
      '#title' => $form_mode['label'],
      '#description' => $this->t('Configure the theme negotiation for specific form mode (<b>@form_mode_id</b>)', ['@form_mode_id' => $form_mode['label']]),
      '#open' => TRUE,
    ];

    $form['theme_switcher_form_mode_settings'][$entity_type_id]['form_modes']["{$form_mode['id']}_theme_type"] = [
      '#title' => $this->t('Default themes'),
      '#type' => 'select',
      '#options' => $this->getAvailableThemeTypeOptions(),
      '#default_value' => $this->settings->get("type.$form_mode_id"),
      '#description' => $this->t("Select the type of theme you want to use. You can choose the themes defined by the drupal configuration (default or admin) or select one with the 'custom' option"),
      '#weight' => 0,
      '#empty_value' => '_none',
      '#empty_option' => $this->t('- Default settings -'),
    ];

    $form['theme_switcher_form_mode_settings'][$entity_type_id]['form_modes']["{$form_mode['id']}_theme_form_mode"] = [
      '#title' => $this->t('Specific active theme'),
      '#type' => 'select',
      '#options' => $this->getActiveThemeOptions(),
      '#default_value' => $this->settings->get("form_mode.$form_mode_id"),
      '#description' => $this->t('Select a theme from the available active themes list.'),
      '#weight' => 0,
      '#states' => [
        'visible' => [
          ":input[name=\"{$form_mode['id']}_theme_type\"]" => ['value' => '_custom'],
        ],
      ],
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsPerEntity(FormStateInterface $form_state, array $form_modes, $entity_type_id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsPerFormMode(FormStateInterface $form_state, array $form_mode, $entity_type_id) {
    $form_mode_id = str_replace('.', '_', $form_mode['id']);
    $user_input = $form_state->getUserInput();
    $this->settings
      ->set("type.{$form_mode_id}", $user_input["{$form_mode_id}_theme_type"])
      ->set("form_mode.{$form_mode_id}", $user_input["{$form_mode_id}_theme_form_mode"]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->cacheTagsInvalidator->invalidateTags([
      'rendered',
    ]);
  }

}
