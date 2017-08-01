<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the form_mode_manager module.
 */
class FormModeManagerPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, FormModeManagerInterface $form_mode_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_mode.manager')
    );
  }

  /**
   * Returns an array of Form mode manager permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function formModeManagerPermissions() {
    $perms = [];

    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    foreach ($form_modes_definitions as $entity_type_id => $form_modes) {
      $perms += $this->buildDefaultPermissions($entity_type_id);
      $perms += $this->buildFormModePermissions($entity_type_id, array_keys($form_modes));
    }

    return $perms;
  }

  /**
   * Returns a list of form mode `Default` permissions for a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildDefaultPermissions($entity_type_id) {
    $placeholders = ['%type_id' => $entity_type_id];
    return [
      "use $entity_type_id.default form mode" => [
        'title' => $this->t('Use default form mode for %type_id entity', $placeholders),
        'description' => [
          '#prefix' => '<em>',
          '#markup' => $this->t("This permission can hide defaults operation (edit/add) for <b>%type_id</b> entity.", $placeholders),
          '#suffix' => '</em>',
        ],
        'restrict access' => TRUE,
      ],
    ];
  }

  /**
   * Returns a list of form modes permissions available for given entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $form_modes
   *   All form-modes available for specified entity_type_id.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildFormModePermissions($entity_type_id, array $form_modes) {
    $perms_per_mode = [];
    $entity_placeholder = ['%type_id' => $entity_type_id];

    $form_modes_storage = $this->entityTypeManager->getStorage('entity_form_mode');
    foreach ($form_modes as $form_mode) {
      $form_mode_loaded = $form_modes_storage->loadByProperties(['id' => "$entity_type_id.$form_mode"]);
      $form_mode_loaded = reset($form_mode_loaded);
      if ($form_mode_loaded instanceof EntityFormMode) {
        $placeholders = array_merge($entity_placeholder, [
          '%form_mode_label' => $form_mode_loaded->label(),
          ':url' => $form_mode_loaded->url(),
        ]);

        $perms_per_mode += [
          "use {$form_mode_loaded->id()} form mode" => [
            'title' => $this->t('Use <a href=":url">%form_mode_label</a> form mode with <b>%type_id</b> entity', $placeholders),
            'description' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('This permission control access of <b>%type_id</b> entity with %form_mode_label form mode.', $placeholders),
              '#suffix' => '</em>',
            ],
            'restrict access' => TRUE,
          ],
        ];
      }
    }

    return $perms_per_mode;
  }

}
