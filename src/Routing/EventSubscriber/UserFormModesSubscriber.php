<?php

namespace Drupal\form_mode_manager\Routing\EventSubscriber;

use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for form_mode_manager routes.
 */
class UserFormModesSubscriber extends FormModesSubscriber {

  /**
   * {@inheritdoc}
   */
  const FORM_MODE_DEFAULT_CONTROLLER = '\Drupal\form_mode_manager\Controller\UserFormModeController';

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $form_modes_definitions = $this->formModeManager->getAllFormModesDefinitions();
    $entity_type_id = 'user';
    if (isset($form_modes_definitions[$entity_type_id])) {
      $form_modes = $form_modes_definitions[$entity_type_id];
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $this->addFormModesRoutes($collection, $entity_type, $form_modes);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Ensure fire after FormModesSubscriber alters.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 10];
    return $events;
  }

}
