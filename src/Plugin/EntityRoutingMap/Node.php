<?php

namespace Drupal\form_mode_manager\Plugin\EntityRoutingMap;

use Drupal\form_mode_manager\EntityRoutingMapBase;

/**
 * Class Node.
 *
 * @EntityRoutingMap(
 *   id = "node",
 *   label = @Translation("Node Routes properties"),
 *   targetEntityType = "node",
 *   defaultFormClass = "default",
 *   editFormClass = "edit",
 *   operations = {
 *     "add_form" = "node.add",
 *     "edit_form" = "entity.node.edit_form",
 *     "add_page" = "node.add_page"
 *   }
 * )
 */
class Node extends EntityRoutingMapBase {

}
