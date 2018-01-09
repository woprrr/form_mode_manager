<?php

namespace Drupal\form_mode_manager\Plugin\EntityRoutingMap;

use Drupal\form_mode_manager\EntityRoutingMapBase;

/**
 * Class BlockContent.
 *
 * @EntityRoutingMap(
 *   id = "block_content",
 *   label = @Translation("Block Content Routes properties"),
 *   targetEntityType = "block_content",
 *   defaultFormClass = "default",
 *   editFormClass = "edit",
 *   operations = {
 *     "add_form" = "block_content.add_form",
 *     "edit_form" = "entity.block_content.edit_form",
 *     "add_page" = "block_content.add_page"
 *   },
 *   contextualLinks = {
 *     "edit" = "block_content.block_edit"
 *   }
 * )
 */
class BlockContent extends EntityRoutingMapBase {

}
