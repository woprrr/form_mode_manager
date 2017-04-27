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
          $this->derivatives["form_mode_manager.{$form_mode['id']}"] = [
            'route_name' => "form_mode_manager.$entity_type_id.add_page.$form_mode_name",
            'title' => $this->t('Add @entity_label as @form_mode', [
              '@form_mode' => $form_mode['label'],
              '@entity_label' => $entity_type_id,
            ]),
            'route_parameters' => ['form_mode_name' => $form_mode_name],
            'appears_on' => ["entity.{$entity_type_id}.collection"],
            'cache_tags' => $this->formModeManager->getListCacheTags(),
          ];

          if ('user' === $entity_type_id) {
            $this->derivatives["form_mode_manager.{$form_mode['id']}"]['route_name'] = "user.admin_create.$form_mode_name";
            unset($this->derivatives["form_mode_manager.{$form_mode['id']}"]['route_parameters']);
          }

          if ('node' === $entity_type_id) {
            $this->derivatives["form_mode_manager.{$form_mode['id']}"]['appears_on'] = ['system.admin_content'];
          }

          if ('media' === $entity_type_id) {
            $this->derivatives["form_mode_manager.{$form_mode['id']}"]['appears_on'] = ['view.media.media_page_list'];
          }

          if ('taxonomy_term' === $entity_type_id) {
            $this->derivatives["form_mode_manager.{$form_mode['id']}"]['appears_on'] = ['entity.taxonomy_vocabulary.overview_form'];
            $this->derivatives["form_mode_manager.{$form_mode['id']}"]['title'] = $this->t('Add @entity_label as @form_mode', [
              '@form_mode' => $form_mode['label'],
              '@entity_label' => 'term',
            ]);
          }
        }
      }
    }

    return $this->derivatives;
  }

}
