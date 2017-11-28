<?php

namespace Drupal\form_mode_manager\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines form mode manager annotation object.
 *
 * @see hook_form_mode_manager_display_info_alter()
 *
 * @Annotation
 */
class EntityRoutingMap extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the display.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Gets the target entity type.
   *
   * @var string
   */
  public $targetEntityType;

  /**
   * Name of Add EntityForm class.
   *
   * @var string
   */
  public $defaultFormClass = 'default';

  /**
   * Name of Edit EntityForm class.
   *
   * @var string
   */
  public $editFormClass = 'edit';

  /**
   * A mapping of entity form operation available for that entity.
   *
   * @var array[]
   */
  public $operations = [];

}
