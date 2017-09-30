<?php

namespace Drupal\form_mode_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions for all entity bundles.
 */
class FormModeManagerLocalAction extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * Constructs a FormModeManagerLocalAction object.
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
    foreach ($form_modes_definitions as $entity_type_id => $form_modes) {
      foreach ($form_modes as $form_mode_name => $form_mode) {
        if ($this->formModeManager->hasActiveFormMode($entity_type_id, $form_mode_name)) {

          $this->setDefaultLocalTask($form_mode, $entity_type_id, $form_mode_name);

          if ($this->isUserEntityType($entity_type_id)) {
            $this->derivatives[$form_mode['id']]['route_name'] = "user.admin_create.$form_mode_name";
            unset($this->derivatives[$form_mode['id']]['route_parameters']);
          }

          $this->setNodeEntityType($form_mode, $entity_type_id);

          $this->setMediaEntityType($form_mode, $entity_type_id);

          $this->setTaxonomyTermEntityType($form_mode, $entity_type_id);
        }
      }
    }

    return $this->derivatives;
  }

  /**
   * Set default local task.
   *
   * @param array $form_mode
   *   A form mode.
   * @param string $entity_type_id
   *   An entity type id.
   * @param string $form_mode_name
   *   A form mode name.
   */
  public function setDefaultLocalTask(array $form_mode, $entity_type_id, $form_mode_name) {
    $this->derivatives[$form_mode['id']] = [
      'route_name' => "form_mode_manager.$entity_type_id.add_page.$form_mode_name",
      'title' => $this->t('Add @entity_label as @form_mode', [
        '@form_mode' => $form_mode['label'],
        '@entity_label' => $entity_type_id,
      ]),
      'route_parameters' => ['form_mode_name' => $form_mode_name],
      'appears_on' => ["entity.{$entity_type_id}.collection"],
      'cache_tags' => $this->formModeManager->getListCacheTags(),
    ];
  }

  /**
   * Determine if the current entity type is 'user'.
   *
   * @param string $entity_type_id
   *   An entity type id.
   *
   * @return bool
   *   True if this $entity_type_id is user.
   */
  public function isUserEntityType($entity_type_id) {
    return ('user' === $entity_type_id);
  }

  /**
   * Set derivative the current entity type is 'node'.
   *
   * @param array $form_mode
   *   A form mode.
   * @param string $entity_type_id
   *   An entity type id.
   */
  public function setNodeEntityType(array $form_mode, $entity_type_id) {
    if ('node' === $entity_type_id) {
      $this->derivatives[$form_mode['id']]['appears_on'] = ['system.admin_content'];
    }
  }

  /**
   * Set derivative the current entity type is 'media'.
   *
   * @param array $form_mode
   *   A form mode.
   * @param string $entity_type_id
   *   An entity type id.
   */
  public function setMediaEntityType(array $form_mode, $entity_type_id) {
    if ('media' === $entity_type_id) {
      $this->derivatives[$form_mode['id']]['appears_on'] = ['view.media.media_page_list'];
    }
  }

  /**
   * Set derivative the current entity type is 'taxonomy_term'.
   *
   * @param array $form_mode
   *   A form mode.
   * @param string $entity_type_id
   *   An entity type id.
   */
  public function setTaxonomyTermEntityType(array $form_mode, $entity_type_id) {
    if ('taxonomy_term' === $entity_type_id) {
      $this->derivatives[$form_mode['id']]['appears_on'] = ['entity.taxonomy_vocabulary.overview_form'];
      $this->derivatives[$form_mode['id']]['title'] = $this->t('Add @entity_label as @form_mode', [
        '@form_mode' => $form_mode['label'],
        '@entity_label' => 'term',
      ]);
    }
  }

}
