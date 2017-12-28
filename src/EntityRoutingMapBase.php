<?php

namespace Drupal\form_mode_manager;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for form mode manager entity routing plugin.
 *
 * This plugin are used to abstract the concepts implemented by EntityPlugin.
 * In Entity API we have possibility to linked entity form 'handlers' to a,
 * specific FormClass, but the operation name and routes linked with her are,
 * very arbitrary and unpredictable specially in custom entities cases.
 * In that plugin you have the possibility to map operation and,
 * others useful information about entity to reduce complexity of,
 * retrieving each possible cases.
 */
abstract class EntityRoutingMapBase extends PluginBase implements EntityRoutingMapInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * Default form class Definition name.
   *
   * @var string
   */
  protected $defaultFormClass;

  /**
   * Default editing form class Definition name.
   *
   * @var string
   */
  protected $editFormClass;

  /**
   * Entity type id of entity.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs display plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setTargetEntityType();
    $this->setConfiguration($configuration);
    $this->setDefaultFormClass();
    $this->setEditFormClass();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param string $operation_name
   *   The name of needed operation to retrieve.
   */
  public function getOperation($operation_name) {
    if (isset($this->pluginDefinition['operations'][$operation_name])) {
      return $this->pluginDefinition['operations'][$operation_name];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return $this->pluginDefinition['operations'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormClass() {
    return $this->defaultFormClass;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditFormClass() {
    return $this->editFormClass;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += $this->defaultConfiguration();

    if ($this->getPluginId() === 'generic') {
      $this->setTargetEntityType();
      $this->setOperations();
    }

    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperations() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultFormClass() {
    $this->defaultFormClass = $this->pluginDefinition['defaultFormClass'];
  }

  /**
   * {@inheritdoc}
   */
  public function setEditFormClass() {
    $edit_form_operation = $this->pluginDefinition['editFormClass'];

    if (!$this->entityFormClassExist($edit_form_operation)) {
      $edit_form_operation = $this->getDefaultFormClass();
    }

    $this->editFormClass = $edit_form_operation;
  }

  /**
   * Check if the entity have an edit operation handler.
   *
   * @param string $operation_name
   *   The name of form handler operation.
   *
   * @return bool
   *   True if generic `edit` form handler exist for this entity.
   */
  public function entityFormClassExist($operation_name) {
    $entity_definition = \Drupal::entityTypeManager()->getDefinition($this->getTargetEntityType());
    return !empty($entity_definition->getFormClass($operation_name));
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityType() {
    if (empty($this->pluginDefinition['targetEntityType']) && !empty($this->configuration['entityTypeId'])) {
      $this->pluginDefinition['targetEntityType'] = $this->configuration['entityTypeId'];
    }

    $this->targetEntityType = $this->pluginDefinition['targetEntityType'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

}
