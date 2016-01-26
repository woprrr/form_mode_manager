<?php

/**
 * @file
 * Contains \Drupal\form_mode_manager\Routing\FormModeManagerRoutes.
 */

namespace Drupal\form_mode_manager\Routing;

use Drupal\Core\Entity\EntityDisplayRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Defines dynamic routes.
 */
class FormModeManagerRoutes implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $formModes;

  /**
   * Constructor.
   */
  public function __construct(EntityDisplayRepository $entity_display) {
    $this->formModes = $entity_display->getAllFormModes();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    foreach ($this->formModes as $entity_type => $display_modes) {
      foreach ($display_modes as $key => $display_mode) {
        if ($key != 'register') {
          // Returns an array of Route objects.
          $routes['entity.' . $display_mode['id']] = new Route(
          // Path to attach this route to:
            '/' . $display_mode['targetEntityType'] . '/{' . $display_mode['targetEntityType'] . '}/' . $display_mode['label'],
            // Route defaults:
            [
              '_entity_form' => $display_mode['id'],
              '_title' => t('Edit as @label', ['@label' => $display_mode['label']])->render(),
            ],
            // Route requirements:
            [
              '_permission'  => 'administer nodes',
            ],
            [
              '_node_operation_route' => TRUE
            ]
          );
          // @TODO Found an solution to use same methods for all entities.
          if ($display_mode['targetEntityType'] === 'node') {
            $routes[$display_mode['targetEntityType'] . '.add.' . $key] = new Route(
            // Path to attach this route to:
              '/' . $display_mode['targetEntityType'] . '/add/{node_type}/{form_display}',
              // Route defaults:
              [
                '_entity_form' => $display_mode['id'],
                '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::nodeAdd',
                '_title_callback' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPageTitle',
              ],
              [
                '_node_add_access' => 'node:{node_type}',
              ],
              [
                '_node_operation_route' => TRUE
              ]
            );
          }

          if ($display_mode['targetEntityType'] === 'media') {
            $routes[$display_mode['targetEntityType'] . '.add.' . $key] = new Route(
            // Path to attach this route to:
              '/' . $display_mode['targetEntityType'] . '/add/{media_bundle}/' . $display_mode['label'],
              // Route defaults:
              [
                '_controller' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::mediaAdd',
                '_title_callback' => '\Drupal\form_mode_manager\Controller\FormModeManagerController::addPageTitle',
              ],
              [
                '_entity_create_access' => $display_mode['targetEntityType'] . ':{media_bundle}',
              ],
              [
                '_node_operation_route' => TRUE
              ]
            );
          }
        }
      }
    }
    return $routes;
  }

}
