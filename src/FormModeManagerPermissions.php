<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
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
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new FormModeManagerPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationManager $string_translation, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityTypeManager = $entity_manager;
    $this->translationManager = $string_translation;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Returns an array of form_mode_manager permissions.
   *
   * @return array
   *   The permission list.
   */
  public function permissions() {
    $permissions = [];
    foreach ($this->entityDisplayRepository->getAllFormModes() as $entity_type_id => $display_modes) {
      $permissions["use {$entity_type_id}.default form mode"] = [
        'title' => $this->translationManager->translate('Use default form mode for <b>@entity_type_id</b> entity', [
          '@entity_type_id' => $entity_type_id,
          '@form_mode' => $entity_type_id,
        ]),
        'description' => [
          '#prefix' => '<em>',
          '#markup' => $this->translationManager->translate('Warning: This permission may have security implications depending on how the <b>@entity_type_id</b> entity is configured.', [
            '@entity_type_id' => $entity_type_id,
          ]),
          '#suffix' => '</em>',
        ],
      ];
      foreach ($display_modes as $machine_name => $form_display) {
        if ($machine_name != 'register') {
          $form_modes_storage = $this->entityTypeManager->getStorage('entity_form_mode');
          $form_mode = $form_modes_storage->loadByProperties(['id' => $form_display['id']]);
          $permissions["use {$form_display['id']} form mode"] = [
            'title' => $this->translationManager->translate('Use <a href=":url">@form_mode</a> form mode with <b>@entity_type_id</b> entity', [
              '@entity_type_id' => $entity_type_id,
              '@form_mode' => $form_display['label'],
              ':url' => $form_mode[$form_display['id']]->url(),
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

    return $permissions;
  }

}
