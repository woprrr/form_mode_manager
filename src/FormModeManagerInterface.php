<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 *
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
   * Returns entity (form) displays for the current entity display type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type ID to check active modes.
   * @param string $form_mode_id
   *   The entity type ID to check active modes.
   *
   * @return string
   *   The Display mode id for defined entity_type_id.
   */
  public function getFormModeManagerPath(EntityTypeInterface $entity_type, $form_mode_id);

}
