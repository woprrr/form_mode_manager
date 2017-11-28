<?php

namespace Drupal\form_mode_manager\Plugin\EntityRoutingMap;

use Drupal\form_mode_manager\EntityRoutingMapBase;

/**
 * Class User.
 *
 * @EntityRoutingMap(
 *   id = "user",
 *   label = @Translation("User Routes properties"),
 *   targetEntityType = "user",
 *   defaultFormClass = "register",
 *   editFormClass = "default",
 *   operations = {
 *     "add_form" = "user.register",
 *     "edit_form" = "entity.user.edit_form",
 *     "admin_add" = "user.admin_create"
 *   }
 * )
 */
class User extends EntityRoutingMapBase {

}
