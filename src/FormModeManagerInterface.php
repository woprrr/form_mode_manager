<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Interface FormModeManagerInterface.
 */
interface FormModeManagerInterface {

  /**
   * Returns entity (form) displays for the current entity display type.
   *
   * @param string $entity_type_id
   *   The entity type ID to check active modes.
   *
   * @return array
   *   The Display mode id for defined entity_type_id.
   */
  public function getActiveDisplays($entity_type_id);

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
  public function getFormModeManagerPath(EntityTypeInterface $entity_type, $form_mode_id);

  /**
   * Gets all form modes id for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   An array contain all available form mode machine name.
   */
  public function getFormModesIdByEntity($entity_type_id);

  /**
   * Gets the entity form mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return array
   *   An array contain all available form mode machine name.
   */
  public function getFormModesByEntity($entity_type_id);

  /**
   * Gets the entity form mode info for all entity types used.
   *
   * @return array
   *   The collection without uneeded form modes.
   */
  public function getAllFormModesDefinitions();

  /**
   * Filter a form mode collection to exclude all desired form mode id.
   *
   * @param array $form_mode
   *   A form mode collection to be filtered.
   *
   * @return array
   *   The collection without uneeded form modes.
   */
  public function filterExcludedFormModes(array &$form_mode);

  /**
   * Gets the entity form mode info for a specific bundle.
   *
   * @param string $bundle_id
   *   Identifier of bundle.
   *
   * @return array|null
   *   The form mode activated for defined bundle.
   */
  public function getActiveDisplaysByBundle($entity_type_id, $bundle_id);

  /**
   * Determine if a form mode is activated onto bundle of specific entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_id
   *   Name of bundle for current entity.
   * @param string $form_mode_machine_name
   *   Machine name of form mode.
   *
   * @return bool
   *   True if FormMode is activated on needed bundle.
   */
  public function isActive($entity_type_id, $bundle_id, $form_mode_machine_name);

  /**
   * Retrieve Form Mode Machine Name from the form mode id.
   *
   * @param string $form_mode_id
   *   Identifier of form mode prefixed by entity type id.
   *
   * @return string
   *   The form mode machine name without prefixe of,
   *   entity (entity.form_mode_name).
   */
  public function getFormModeMachineName($form_mode_id);

}
