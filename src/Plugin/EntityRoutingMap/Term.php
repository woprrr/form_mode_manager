<?php

namespace Drupal\form_mode_manager\Plugin\EntityRoutingMap;

use Drupal\form_mode_manager\EntityRoutingMapBase;

/**
 * Class Term.
 *
 * @EntityRoutingMap(
 *   id = "taxonomy_term",
 *   label = @Translation("Term Routes properties"),
 *   targetEntityType = "taxonomy_term",
 *   defaultFormClass = "default",
 *   editFormClass = "default",
 *   operations = {
 *     "add_form" = "entity.taxonomy_term.add_form",
 *     "edit_form" = "entity.taxonomy_term.edit_form",
 *     "add_page" = "taxonomy_term.add_page"
 *   }
 * )
 */
class Term extends EntityRoutingMapBase {

}
