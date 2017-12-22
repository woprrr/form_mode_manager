<?php

namespace Drupal\form_mode_manager;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Interface EntityFormModeManagerInterface.
 */
interface EntityFormModeManagerInterface {

  /**
   * Displays add content links for available entity types.
   *
   * Redirects to entity/add/[bundle] if only one content type is available.
   */
  public function addPage(RouteMatchInterface $route_match);

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function addPageTitle(RouteMatchInterface $route_match);

  /**
   * Checks access for the Form Mode Manager routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function checkAccess(RouteMatchInterface $route_match);

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function editPageTitle(RouteMatchInterface $route_match);

  /**
   * Provides the entity add submission form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function entityAdd(RouteMatchInterface $route_match);

  /**
   * Provides the entity 'edit' form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function entityEdit(RouteMatchInterface $route_match);

}
