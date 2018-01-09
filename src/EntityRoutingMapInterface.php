<?php

namespace Drupal\form_mode_manager;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * EntityRoutingMapInterface interface class.
 */
interface EntityRoutingMapInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Returns the display label.
   *
   * @return string
   *   The display label.
   */
  public function label();

  /**
   * Return the form operation route for given operation.
   *
   * @return string
   *   The route of given operation.
   */
  public function getOperation($operation_name);

  /**
   * Return the form operation route mapping.
   *
   * @return array[]
   *   The mapping of each entity form operation given by plugin annotation.
   */
  public function getOperations();

  /**
   * Set a mapping of operations for Generic plugin.
   */
  public function setOperations();

  /**
   * Gets the target entity type.
   *
   * @return string
   *   The target entity type.
   */
  public function getTargetEntityType();

  /**
   * Return the contextual links mapping.
   *
   * @return array[]
   *   The mapping of each entity contextual links given by plugin annotation.
   */
  public function getContextualLinks();

  /**
   * Return the contextual links mapping for given operation.
   *
   * @return string
   *   The contextual link name for given operation.
   */
  public function getContextualLink($operation_name);

  /**
   * Get the default form class Definition.
   *
   * @return string
   *   The name of entity default form class.
   */
  public function getDefaultFormClass();

  /**
   * Return contextual links route mapping.
   *
   * @return array[]
   *   The mapping of each entity contextual links given by plugin annotation.
   */
  public function setContextualLinks();

  /**
   * Set the default form class Definition.
   */
  public function setDefaultFormClass();

  /**
   * Get the edit form class Definition.
   *
   * @return string
   *   The name of entity default form class.
   */
  public function getEditFormClass();

  /**
   * Set the edit form class Definition.
   */
  public function setEditFormClass();

  /**
   * Set the target entity type.
   */
  public function setTargetEntityType();

}
