<?php

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
   * The Form Mode Manager service.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * Constructs a new FormModeManagerPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The translation manager.
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationManager $string_translation, FormModeManagerInterface $form_mode_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->translationManager = $string_translation;
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('form_mode.manager')
    );
  }

  /**
   * Returns an array of form_mode_manager permissions.
   *
   * @return array
   *   The permission array.
   */
  public function permissions() {
    $permissions = [];
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    foreach ($form_modes_definitions as $entity_type_id => $display_modes) {
      $permissions["use {$entity_type_id}.default form mode"] = [
        'title' => $this->translationManager->translate('Use default form mode for <b>@entity_type_id</b> entity', [
          '@entity_type_id' => $entity_type_id,
          '@form_mode' => $entity_type_id,
        ]),
        'description' => [
          '#prefix' => '<em>',
          '#markup' => $this->translationManager->translate("Warning: This permission can hide defaults operation (edit/add) for @entity_type_id entity.", [
            '@entity_type_id' => $entity_type_id,
          ]),
          '#suffix' => '</em>',
        ],
      ];

      $this->addPermissionsPerFormModes($display_modes, $permissions, $entity_type_id);
    }

    return $permissions;
  }

  /**
   * Generate one permission per Display mode available.
   *
   * These permission are responsible to access this operation,
   * with specific FormMode.
   *
   * This is NOT the only permission checked to access,
   * to your entities operations, FormModeManager retrieve all,
   * parameters of entities before adding this one restrict,
   * or permit access to your operations.
   *
   * @param array $display_modes
   *   The Form Modes collection without uneeded form modes.
   * @param array $permissions
   *   An array containing all Permissions to added.
   * @param string $entity_type_id
   *   Name of entity type using these display modes.
   */
  private function addPermissionsPerFormModes(array $display_modes, array &$permissions, $entity_type_id) {
    foreach ($display_modes as $form_mode_name => $form_mode) {
      if ($form_mode_name != 'register') {
        $form_modes_storage = $this->entityTypeManager->getStorage('entity_form_mode');
        $form_mode_loaded = $form_modes_storage->loadByProperties(['id' => $form_mode['id']]);
        $permissions["use {$form_mode['id']} form mode"] = [
          'title' => $this->translationManager->translate('Use <a href=":url">@form_mode_label</a> form mode with <b>@entity_type_id</b> entity', [
            '@entity_type_id' => $entity_type_id,
            '@form_mode_label' => $form_mode['label'],
            ':url' => $form_mode_loaded[$form_mode['id']]->url(),
          ]),
          'description' => [
            '#prefix' => '<em>',
            '#markup' => $this->translationManager->translate('Warning: This permission may have security implications depending on how the <b>@entity_type_id</b> entity is configured.', [
              '@entity_type_id' => $entity_type_id,
            ]),
            '#suffix' => '</em>',
          ],
        ];
      }
    }
  }

}
