<?php

namespace Drupal\form_mode_manager\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions for all entity bundles.
 */
class FormModeManagerLocalAction extends DeriverBase implements ContainerDeriverInterface {

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
   * Constructs a FormModeManagerLocalAction object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The new entity display repository.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, AccountInterface $account) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
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
      $modes_enable = \Drupal::service('form_mode.manager')->getActiveDisplays($entity_type_id);
      $active_modes = array_intersect_key($display_modes, $modes_enable);
      unset($active_modes['register']);
      foreach ($active_modes as $form_mode_name => $form_mode) {
        $this->derivatives["form_mode_manager.{$form_mode['id']}"] = [
          'route_name' => "form_mode_manager.{$form_mode['id']}.add_page",
          'title' => $this->t('Add @entity_label as @form_mode', [
            '@form_mode' => $form_mode['label'],
            '@entity_label' => $entity_type_id,
          ]),
          'route_parameters' => ['form_mode_name' => $form_mode_name],
          'appears_on' => ["entity.{$entity_type_id}.collection"],
        ];

        if ('user' === $entity_type_id) {
          $this->derivatives["form_mode_manager.{$form_mode['id']}"]['route_name'] = 'admin.' . $form_mode['id'];
        }

        if ('node' === $entity_type_id) {
          $this->derivatives["form_mode_manager.{$form_mode['id']}"]['appears_on'] = ['system.admin_content'];
        }

        if ('media' === $entity_type_id) {
          $this->derivatives["form_mode_manager.{$form_mode['id']}"]['appears_on'] = ['view.media.media_page_list'];
        }

        if ('taxonomy_term' === $entity_type_id) {
          $this->derivatives["form_mode_manager.{$form_mode['id']}"]['appears_on'] = ['entity.taxonomy_vocabulary.overview_form'];
          $this->derivatives["form_mode_manager.{$form_mode['id']}"]['title'] = $this->t('Add @entity_label as @form_mode', [
            '@form_mode' => $form_mode['label'],
            '@entity_label' => 'term',
          ]);
        }
      }
    }

    return $this->derivatives;
  }

}
