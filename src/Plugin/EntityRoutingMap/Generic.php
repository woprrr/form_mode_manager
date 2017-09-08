<?php

namespace Drupal\form_mode_manager\Plugin\EntityRoutingMap;

use Drupal\form_mode_manager\EntityRoutingMapBase;

/**
 * Class Generic routing entity mapper.
 *
 * @EntityRoutingMap(
 *   id = "generic",
 *   label = @Translation("Generic Routes properties"),
 * )
 */
class Generic extends EntityRoutingMapBase {

  /**
   * {@inheritdoc}
   */
  public function setOperations() {
    $operations = [
      'add_form' => "entity.{$this->targetEntityType}.add_form",
      'edit_form' => "entity.{$this->targetEntityType}.edit_form",
      'add_page' => "{$this->targetEntityType}.add_page",
    ];

    $this->pluginDefinition['operations'] += $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entityTypeId' => '',
    ] + parent::defaultConfiguration();
  }

}
