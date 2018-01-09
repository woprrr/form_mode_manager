<?php

namespace Drupal\form_mode_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\form_mode_manager\EntityRoutingMapManager;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides contextual links definitions for all entity bundles.
 */
class FormModeManagerContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * The Form Mode Manager service.
   *
   * @var string[]
   */
  protected $cacheTags;

  /**
   * The Form Mode Manager service.
   *
   * @var array
   */
  protected $formModesDefinitionsList;

  /**
   * The Routes Manager Plugin.
   *
   * @var \Drupal\form_mode_manager\EntityRoutingMapManager
   */
  protected $entityRoutingMap;

  /**
   * Constructs a new FormModeManagerContextualLinks object.
   *
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   * @param \Drupal\form_mode_manager\EntityRoutingMapManager $plugin_routes_manager
   *   Plugin EntityRoutingMap to retrieve entity form operation routes.
   */
  public function __construct(FormModeManagerInterface $form_mode_manager, EntityRoutingMapManager $plugin_routes_manager) {
    $this->formModeManager = $form_mode_manager;
    $this->cacheTags = $form_mode_manager->getListCacheTags();
    $this->formModesDefinitionsList = $form_mode_manager->getAllFormModesDefinitions();
    $this->entityRoutingMap = $plugin_routes_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('form_mode.manager'),
      $container->get('plugin.manager.entity_routing_map')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach ($this->formModesDefinitionsList as $entity_type_id => $form_modes) {
      /** @var \Drupal\form_mode_manager\EntityRoutingMapBase $entity_operation_mapping */
      $entity_operation_mapping = $this->entityRoutingMap->createInstance($entity_type_id, ['entityTypeId' => $entity_type_id]);
      $edit_route_name = $entity_operation_mapping->getOperation("edit_form");
      $this->setDefaultTasks($entity_type_id, $edit_route_name);
      foreach ($form_modes as $form_mode_name => $form_mode) {
        if ($this->formModeManager->hasActiveFormMode($entity_type_id, $form_mode_name)) {
          $form_mode_route_name = "$edit_route_name.$form_mode_name";
          $this->derivatives[$form_mode_route_name]['route_name'] = $form_mode_route_name;
          $this->derivatives[$form_mode_route_name]['group'] = $entity_type_id;
          $this->derivatives[$form_mode_route_name]['title'] = $this->t('Edit as @form_mode', [
            '@form_mode' => $form_mode['label'],
          ]);
          $this->derivatives[$form_mode_route_name]['cache_tags'] = Cache::mergeTags(['form_mode_manager_contextual_links'], $this->cacheTags);
        }
      }
    }
    return $this->derivatives;
  }

  /**
   * Set the default Contextual link on each entities.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $edit_route_name
   *   The edit route name of given entity.
   */
  private function setDefaultTasks($entity_type_id, $edit_route_name) {
    $this->derivatives[$edit_route_name] = [
      'route_name' => $edit_route_name,
      'title' => $this->t('Edit as Default'),
      'group' => $entity_type_id,
      'cache_tags' => $this->cacheTags,
    ];
  }

}
