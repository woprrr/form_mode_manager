<?php

namespace Drupal\form_mode_manager_theme_switcher\Theme;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\Routing\Route;

/**
 * Class FormModeThemeNegociator.
 */
class FormModeThemeNegociator implements ThemeNegotiatorInterface {

  /**
   * Protected theme variable to store the theme to active.
   *
   * @var string
   */
  protected $theme = NULL;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a new RoleThemeSwitcherNegotiator instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_object = $route_match->getRouteObject();
    // Unsure we are in Form Mode Manager (form mode) route context.
    if (!$route_object->hasOption('_form_mode_manager_entity_type_id')) {
      return FALSE;
    }

    return $this->isApplicable($route_object);
  }

  /**
   * Determine if current route has correct options or use joker parameter.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object of entity.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE.
   */
  public function isApplicable(Route $route) {
    $route_form_mode_theme = $route->getOption('form_mode_theme');
    $form_mode_id = str_replace('.', '_', $route->getDefault('_entity_form'));
    $form_mode_theme_type = $this->configFactory->get('form_mode.theme_switcher')->get("type.$form_mode_id");
    return (isset($form_mode_theme_type) || isset($route_form_mode_theme));
  }

  /**
   * Determine the active theme from the route or configuration.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string
   *   The name of the theme
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $route_object = $route_match->getRouteObject();
    $route_form_mode_theme = $route_object->getOption('form_mode_theme');

    // Priority to route 'form_mode_theme' parametter theme.
    if (isset($route_form_mode_theme)) {
      return $route_form_mode_theme;
    }

    $form_mode_id = str_replace('.', '_', $route_object->getDefault('_entity_form'));
    $theme_type = $this->configFactory
      ->get('form_mode.theme_switcher')
      ->get("type.$form_mode_id");

    // If theme set from settings is set to 'default admin theme'.
    if ($this->isAdminTheme($route_match, $theme_type)) {
      return $this->configFactory->get('system.theme')->get('admin');
    }

    // If theme set from settings is set to 'default theme (front)'.
    if (!$this->isCustomTheme($theme_type)) {
      return $this->configFactory->get('system.theme')->get($theme_type);
    }
    // If the theme type is set to 'Specific theme' from settings.
    elseif ($this->isCustomTheme($theme_type)) {
      return $this->configFactory
        ->get('form_mode.theme_switcher')
        ->get("form_mode.$form_mode_id");
    }

    return $this->theme;
  }

  /**
   * Evaluate if given theme is admin and user has access to view admin theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   * @param string $theme_type
   *   The kind of theme needed by route or configuration 'admin' or 'default'.
   *
   * @return bool
   *   True if current user is an admin theme and user can view it.
   */
  public function isAdminTheme(RouteMatchInterface $route_match, $theme_type) {
    if ($route_match->getParameter('_admin_route')) {
      return $this->currentUser->hasPermission('view the administration theme');
    }

    return $this->currentUser->hasPermission('view the administration theme') && $theme_type === 'admin';
  }

  /**
   * Evaluate if the theme type needed by settings is specific theme.
   *
   * @param string $theme_type
   *   The kind of theme needed by route or configuration 'admin' or 'default'.
   *
   * @return bool
   *   True if the theme type from settings is set to "Specific theme".
   */
  public function isCustomTheme($theme_type) {
    return isset($theme_type) && $theme_type === '_custom';
  }

}
