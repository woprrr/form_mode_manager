<?php

namespace Drupal\form_mode_manager\Form;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\field_ui\Form\EntityFormDisplayEditForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form Mode Manager enhancements for edit form of the EntityFormDisplay.
 *
 * This class permit to add a more specific caches and routes invalidate onto,
 * formDisplay entity form elements. We haven't more common way to be plugged,
 * in EntityDisplay form edit event and identify with precision when an user,
 * add a form-mode onto an EntityType. With following code we have a flexible,
 * and light way to add Form Mode Manager custom comportements like field_ui,
 * way with `EntityFormDisplayEditForm`.
 */
class FormModeManagerDisplayEditForm extends EntityFormDisplayEditForm {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a new FormModeManagerDisplayEditForm.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The widget or formatter plugin manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder service.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager, PluginManagerBase $plugin_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator, RouteBuilderInterface $route_builder) {
    parent::__construct($field_type_manager, $plugin_manager);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.widget'),
      $container->get('cache_tags.invalidator'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Add more precise cache/routes invalidation when we are sure these,
   * form-mode as added/deleted onto entities.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($this->isDefaultForm() && $this->formModesUpdated($form, $form_state)) {
      $this->cacheTagsInvalidator->invalidateTags([
        'local_action',
        'local_tasks',
        'entity_types',
        'rendered',
      ]);

      $this->routeBuilder->rebuild();
    }
  }

  /**
   * Determine if the current form-mode is 'default'.
   *
   * @return bool
   *   True if this entityFormDisplay do rebuild routes.
   */
  public function isDefaultForm() {
    return ('default' === $this->entity->getMode());
  }

  /**
   * Determine whenever a formMode(s) has added/deleted onto entityTypes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   True if this entityFormDisplay do rebuild routes.
   */
  private function formModesUpdated(array $form, FormStateInterface $form_state) {
    if (!isset($form['modes'])) {
      return FALSE;
    }

    if ($this->isNewFormMode($this->defaultDisplayModes($form), $this->submittedDisplayModes($form_state))) {
      return TRUE;
    }

    if ($this->updateDisplayModes($form, $form_state)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Determine whenever a formMode(s) has added/deleted onto entityTypes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   True if this entityFormDisplay do rebuild routes.
   */
  public function updateDisplayModes(array $form, FormStateInterface $form_state) {
    return !empty(array_diff_assoc($this->getDefaultModes($form), $this->getSubmittedModes($form_state)));
  }

  /**
   * Retrieve all form-modes selected before changes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   An array with all form-modes selected or empty.
   */
  public function defaultDisplayModes(array $form) {
    $display_modes = $this->getDefaultModes($form);
    if (!empty($display_modes)) {
      return $display_modes;
    }

    return [];
  }

  /**
   * Retrieve all form-modes submitted form-modes.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array with all form-modes selected or empty.
   */
  public function submittedDisplayModes(FormStateInterface $form_state) {
    $display_modes = $this->getSubmittedModes($form_state);
    if (!empty($display_modes) && is_array($display_modes)) {
      return array_keys($display_modes);
    }

    return [];
  }

  /**
   * Get value of 'display_modes_custom' element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   *   An array with form-mode-id selected by users.
   */
  public function getSubmittedModes(FormStateInterface $form_state) {
    return $form_state->getValue('display_modes_custom');
  }

  /**
   * Get value of 'display_modes_custom' element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array|null
   *   An array with form-mode-id present before submission.
   */
  public function getDefaultModes(array $form) {
    return $form['modes']['display_modes_custom']['#default_value'];
  }

  /**
   * Determine if we haven't any form-modes checked previously.
   *
   * @param array $form_mode_enabled
   *   An associative array containing all form-modes already used.
   * @param array $display_mode_selected
   *   An associative array containing all form-modes to be enabled.
   *
   * @return bool
   *   True if this submit is the first form-mode to enable.
   */
  private function isNewFormMode(array $form_mode_enabled, array $display_mode_selected) {
    return (empty($form_mode_enabled) && !empty($display_mode_selected));
  }

}
