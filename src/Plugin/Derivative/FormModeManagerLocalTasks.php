<?php

namespace Drupal\form_mode_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines dynamic local tasks.
 */
class FormModeManagerLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new DynamicLocalTasks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The new entity display repository.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, AccountInterface $account) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityDisplayRepository->getAllFormModes() as $entity_type_id => $display_modes) {
      foreach ($display_modes as $machine_name => $display_mode) {
        if ($machine_name != 'register') {
          $this->derivatives["form_mode_manager.{$display_mode['id']}.task_tab"] = [
            'route_name' => "entity." . $display_mode['id'],
            'title' => $this->t('Edit as @form_mode', ['@form_mode' => $display_mode['label']]),
            'base_route' => "entity.$entity_type_id.canonical",
          ];
        }
      }
    }
    return $this->derivatives;
  }

}
