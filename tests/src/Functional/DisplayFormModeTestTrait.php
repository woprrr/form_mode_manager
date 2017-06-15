<?php

namespace Drupal\Tests\form_mode_manager\Functional;

use Drupal\Core\Entity\Entity\EntityFormMode;

/**
 * Provides common helper methods for form_mode_manager module tests.
 */
trait DisplayFormModeTestTrait {

  /**
   * Creates a Form Mode based on default settings.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $settings
   *   (optional) An associative array of settings for the form mode.
   *   Override the defaults by specifying the key and value
   *   in the array, for example:.
   *
   * @code
   *     $this->drupalCreateFormMode([
   *       'id' => node.my_form_mode,
   *       'label' => t('Hello, world!'),
   *     ]);
   * @endcode
   *   The following defaults are provided:
   *   - id: Random string.
   *   - label: Random string.
   *   - targetEntityType: 'page'.
   *
   * @return \Drupal\Core\Entity\EntityDisplayModeInterface
   *   The created form mode entity.
   */
  public function drupalCreateFormMode($entity_type_id, array $settings = []) {
    $form_mode = EntityFormMode::create($settings + [
      'id' => "$entity_type_id.{$this->randomMachineName()}",
      'label' => $this->randomMachineName(),
      'targetEntityType' => $entity_type_id,
    ]);
    $form_mode->save();

    return $form_mode;
  }

}
