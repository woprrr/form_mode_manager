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
   * List of form_modes unavailable to expose by Form Mode Manager.
   *
   * @var array
   */
  public $formModesExcluded;

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
    $this->setFormModesToExclude();
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
      list(, , $form_mode_name) = explode('.', $config_id);
      if ($form_mode_name != 'default') {
        $load_ids[] = $config_id;
      }
    }

    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay[] $entity_storage */
    $entity_storage = $this->entityTypeManager->getStorage($entity_type->id())
      ->loadMultiple($load_ids);
    foreach ($entity_storage as $form_mode) {
      $form_mode_ids[$form_mode->getMode()] = $form_mode;
    }

    return $form_mode_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeManagerPath(EntityTypeInterface $entity_type, $form_mode_id) {
    return $entity_type->getLinkTemplate('canonical') . "/" . $form_mode_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModesIdByEntity($entity_type_id) {
    return array_keys($this->getFormModesByEntity($entity_type_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModesByEntity($entity_type_id) {
    $form_modes = $this->entityDisplayRepository->getFormModes($entity_type_id);
    $this->filterExcludedFormModes($form_modes, $entity_type_id);

    return $form_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFormModesDefinitions() {
    $filtered_form_modes = [];
    $form_modes = $this->entityDisplayRepository->getAllFormModes();
    foreach ($form_modes as $entity_type_id => $form_mode) {
      $form_mode = $this->filterExcludedFormModes($form_mode, $entity_type_id);
      if (!empty($form_mode)) {
        $filtered_form_modes[$entity_type_id] = $form_mode;
      }
    }

    return $filtered_form_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidFormMode(array $form_mode) {
    return (isset($form_mode['targetEntityType']) && isset($form_mode['id']));
  }

  /**
   * {@inheritdoc}
   */
  public function candidateToExclude(array $form_mode, $entity_type_id) {
    if ($this->isValidFormMode($form_mode) && $entity_type_id === $form_mode['targetEntityType']) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExcludedFormModes(array &$form_mode, $entity_type_id) {
    foreach ($form_mode as $form_mode_id => $form_mode_definition) {
      $form_modes = $this->getFormModeExcluded($entity_type_id);
      if (in_array($form_mode_id, $form_modes)) {
        unset($form_mode[$form_mode_id]);
      }
      elseif ($this->candidateToExclude($form_mode_definition, $entity_type_id)) {
        unset($form_mode[$form_mode_id]);
      }
    }

    return $form_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeExcluded($entity_type_id) {
    $form_modes = [];

    if (isset($this->formModesExcluded[$entity_type_id])) {
      $form_modes = $this->formModesExcluded[$entity_type_id];
    }

    return $form_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveDisplaysByBundle($entity_type_id, $bundle_id) {
    $form_modes = [];
    $entities_form_modes = $this->getFormModesByEntity($entity_type_id);
    foreach (array_keys($entities_form_modes) as $form_mode_machine_name) {
      if ($this->isActive($entity_type_id, $bundle_id, $form_mode_machine_name)) {
        $form_modes[$entity_type_id][$form_mode_machine_name] = $entities_form_modes[$form_mode_machine_name];
      }
    }

    return $form_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive($entity_type_id, $bundle_id, $form_mode_machine_name) {
    $form_mode_active = array_keys($this->entityDisplayRepository->getFormModeOptionsByBundle($entity_type_id, $bundle_id));
    return in_array($form_mode_machine_name, $form_mode_active);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeMachineName($form_mode_id) {
    return preg_replace('/^.*\./', '', $form_mode_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTags() {
    return $this->entityTypeManager->getDefinition('entity_form_display')
      ->getListCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function tasksIsPrimary($entity_type_id) {
    $links_settings = $this->configFactory
      ->get('form_mode_manager.links')
      ->get("local_tasks.{$entity_type_id}.position");

    return (isset($links_settings) && $links_settings === 'primary') ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveFormMode($entity_type, $form_mode_id) {
    $has_active = FALSE;
    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type));
    foreach ($bundles as $bundle) {
      if (!$has_active) {
        $has_active = $this->isActive($entity_type, $bundle, $form_mode_id);
      }
    }

    return $has_active;
  }

  /**
   * Retrieve Form Mode Manager settings to format the exclusion list.
   */
  protected function setFormModesToExclude() {
    $form_modes_to_exclude = [];
    $config = $this->configFactory->get('form_mode_manager.settings')
      ->get('form_modes');
    $excluded_form_modes = (isset($config)) ? $config : [];
    foreach ($excluded_form_modes as $entity_type_id => $modes_excluded) {
      $form_modes_to_exclude[$entity_type_id][] = $modes_excluded;
    }

    $this->formModesExcluded = $form_modes_to_exclude;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityHandlersPerFormModes(EntityTypeInterface $entity_definition) {
    $form_modes = $this->getFormModesIdByEntity($entity_definition->id());
    if (empty($form_modes)) {
      return;
    }

    foreach ($form_modes as $form_mode_name) {
      $this->setFormClassPerFormModes($entity_definition, $form_mode_name);
      $this->setLinkTemplatePerFormModes($entity_definition, $form_mode_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFormClassPerFormModes(EntityTypeInterface $entity_definition, $form_mode_name) {
    if ($default_form = $entity_definition->getFormClass('default')) {
      $entity_definition->setFormClass($form_mode_name, $default_form);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkTemplatePerFormModes(EntityTypeInterface $entity_definition, $form_mode_name) {
    if ($entity_definition->getFormClass($form_mode_name) && $entity_definition->hasLinkTemplate('edit-form')) {
      $entity_definition->setLinkTemplate("edit-form.$form_mode_name", $entity_definition->getLinkTemplate('edit-form') . '/' . $form_mode_name);
    }
  }

}
