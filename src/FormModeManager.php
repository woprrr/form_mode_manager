<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * FormDisplayManager service.
 */
class FormModeManager implements FormModeManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * List of form_modes unavailable to expose by FormModeManager.
   *
   * @var array
   */
  private $formModesExcluded = ['user' => 'register'];

  /**
   * Constructs a FormDisplayManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveDisplays($entity_type_id) {
    $load_ids = [];
    $form_mode_ids = [];
    /** @var \Drupal\Core\Config\Entity\ConfigEntityType $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition('entity_form_display');
    $config_prefix = $entity_type->getConfigPrefix();
    $ids = $this->configFactory->listAll($config_prefix . '.' . $entity_type_id . '.');
    foreach ($ids as $id) {
      $config_id = str_replace($config_prefix . '.', '', $id);
      list(,, $form_mode_name) = explode('.', $config_id);
      if ($form_mode_name != 'default') {
        $load_ids[] = $config_id;
      }
    }

    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $form_mode */
    foreach ($this->entityTypeManager->getStorage($entity_type->id())->loadMultiple($load_ids) as $form_mode) {
      $form_mode_ids[$form_mode->getMode()] = $form_mode;
    }
    return $form_mode_ids;
  }

  /**
   * Gets the path of specified entity type for a form mode.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $form_mode_id
   *   The form mode machine name.
   *
   * @return string
   *   The path to use the specified form mode.
   */
  public function getFormModeManagerPath(EntityTypeInterface $entity_type, $form_mode_id) {
    return $entity_type->getLinkTemplate('canonical') . "/" . $form_mode_id;
  }

  /**
   * Gets the entity form mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return array
   *   An array contain all available form mode machine name.
   */
  public function getFormModesIdByEntity($entity_type_id) {
    return array_keys($this->getFormModesByEntity($entity_type_id));
  }

  /**
   * Gets the entity form mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return array
   *   An array contain all available form mode machine name.
   */
  public function getFormModesByEntity($entity_type_id) {
    $form_modes = $this->entityDisplayRepository->getFormModes($entity_type_id);
    $this->filterExcludedFormModes($form_modes);

    return $form_modes;
  }

  /**
   * Gets the entity form mode info for all entity types.
   *
   * @param array $form_mode
   *   A form mode collection to be filtered.
   *
   * @return array|null
   *   The collection without uneeded form modes.
   */
  public function getAllFormModesDefinitions() {
    $filtered_form_modes = [];
    $form_modes = $this->entityDisplayRepository->getAllFormModes();
    foreach ($form_modes as $entity_type_id => $form_mode) {
      $form_mode = $this->filterExcludedFormModes($form_mode);
      if (!empty($form_mode)) {
        $filtered_form_modes[$entity_type_id] = $form_mode;
      }
    }

    return $filtered_form_modes;
  }

  /**
   * Filter a form mode collection.
   *
   * @param array $form_mode
   *   A form mode collection to be filtered.
   *
   * @return array
   *   The collection without uneeded form modes.
   */
  public function filterExcludedFormModes(array &$form_mode) {
    foreach ($this->formModesExcluded as $entity_type_id => $form_mode_to_exclude) {
      if (isset($form_mode[$form_mode_to_exclude])
        && $form_mode[$form_mode_to_exclude]['targetEntityType'] === $entity_type_id
      ) {
        unset($form_mode[$form_mode_to_exclude]);
      }
    }

    return $form_mode;
  }

  /**
   * Check if a bundle use a form mode.
   *
   * @param string $bundle_id
   *   Name of bundle.
   *
   * @return array|null
   *   The form mode activated for defined bundle.
   */
  public function getActiveDisplaysByBundle($entity_type_id, $bundle_id) {
    $form_modes = [];
    $entities_form_modes = $this->getFormModesByEntity($entity_type_id);
    foreach (array_keys($entities_form_modes) as $form_mode_machine_name) {
      if ($this->is_active($entity_type_id, $bundle_id, $form_mode_machine_name)) {
        $form_modes[$entity_type_id][$form_mode_machine_name] = $entities_form_modes[$form_mode_machine_name];
      }
    }

    return $form_modes;
  }

  /**
   * Check if needed form mode is used by bundle.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_id
   *   Name of bundle for current entity.
   * @param string $form_mode_machine_name
   *   Machine name of form mode.
   *
   * @return array
   *   The form mode activated for defined bundle,
   *   default are forever available and can be an instance of TranslatableMarkup.
   */
  public function is_active($entity_type_id, $bundle_id, $form_mode_machine_name) {
    $form_mode_active = array_keys($this->entityDisplayRepository->getFormModeOptionsByBundle($entity_type_id, $bundle_id));
    return in_array($form_mode_machine_name, $form_mode_active);
  }

  /**
   * Get Form Mode Machine Name.
   *
   * @TODO Move it in FormModeManager service.
   *
   * @param string $form_mode_id
   *   Machine name of form mode.
   *
   * @return string
   *   The form mode machine name without prefixe of,
   *   entity (entity.form_mode_name).
   */
  public function getFormModeMachineName($form_mode_id) {
    return preg_replace('/^.*\./', '', $form_mode_id);
  }

}
