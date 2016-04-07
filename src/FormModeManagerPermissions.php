<?php

/**
 * @file
 * Contains \Drupal\form_mode_manager\FormModeManagerPermissions.
 */

namespace Drupal\form_mode_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the form_mode_manager module.
 */
class FormModeManagerPermissions implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The string translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;


  /**
   * Constructs a new FormModeManagerPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationManager $string_translation) {
    $this->entityTypeManager = $entity_manager;
    $this->translationManager = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Returns an array of entity_clone permissions.
   *
   * @return array
   *   The permission list.
   */
  public function permissions() {
    $permissions = [];

    $form_modes = \Drupal::service('entity_display.repository')->getAllFormModes();
    foreach ($form_modes as $entity_type_id => $display_modes) {
      foreach ($display_modes as $machine_name => $form_display) {
        if (!isset($form_display['_core'])) {
          $form_modes_storage = \Drupal::entityTypeManager()->getStorage('entity_form_mode');
          $form_mode = $form_modes_storage->loadByProperties(['id' => $form_display['id']]);
          $permissions["use $machine_name form mode with $entity_type_id entity"] = [
            'title' => $this->translationManager->translate('Use <a href=":url">@form_mode</a> form mode with <b>@entity_type_id</b> entity', [
              '@entity_type_id' => $entity_type_id,
              '@form_mode' => $form_display['label'],
              ':url' => $form_mode[$form_display['id']]->url()
            ]),
            'description' => [
              '#prefix' => '<em>',
              '#markup' => $this->translationManager->translate('Warning: This permission may have security implications depending on how the <b>@entity_type_id</b> entity is configured.', [
                '@entity_type_id' => $entity_type_id,
              ]),
              '#suffix' => '</em>'
            ]
          ];
        }
      }
    }
    return $permissions;
  }

}
