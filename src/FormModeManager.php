<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * FormDisplayManager service.
 */
class FormModeManager {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a FormDisplayManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Returns entity (form) displays for the current entity display type.
   *
   * @param string $entity_type_id
   *   The entity type ID to check active modes.
   *
   * @return array
   *   The Display mode id for defined entity_type_id.
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

}
