<?php

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Controller for specific User entity form mode support.
 *
 * @see \Drupal\form_mode_manager\Routing\RouteSubscriber
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalAction
 * @see \Drupal\form_mode_manager\Plugin\Derivative\FormModeManagerLocalTasks
 */
class UserFormModeController extends EntityFormModeBase {

  /**
   * Provides the entity submission form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   A User submission form.
   *
   * @throws \Exception
   *   If invalid entity type or form mode not exist.
   */
  public function entityAdd(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    if (empty($entity)) {
      $route_entity_type_info = $this->getEntityTypeFromRouteMatch($route_match);
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($route_entity_type_info['entity_type_id'])->create([
        $route_entity_type_info['entity_key'] => $route_entity_type_info['bundle'],
      ]);
    }

    $form_mode_id = $this->formModeManager->getFormModeMachineName($route_match->getRouteObject()->getOption('parameters')['form_mode']['id']);
    $operation = empty($form_mode_id) ? 'register' : $form_mode_id;

    if ($entity instanceof EntityInterface) {
      return $this->entityFormBuilder->getForm($entity, $operation);
    }
  }

  /**
   * Provides the entity 'edit' form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   The entity edit Form.
   */
  public function entityEdit(RouteMatchInterface $route_match) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getEntityFromRouteMatch($route_match);
    $form_mode_id = $this->formModeManager->getFormModeMachineName($route_match->getRouteObject()->getOption('parameters')['form_mode']['id']);
    $operation = empty($form_mode_id) ? 'register' : 'edit_' . $form_mode_id;

    if ($entity instanceof EntityInterface) {
      return $this->getForm($entity, $operation);
    }
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param string $operation
   *   Name of current context operation to display title (create/edit).
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(RouteMatchInterface $route_match, $operation) {
    $form_mode_label = $route_match->getRouteObject()
      ->getOption('parameters')['form_mode']['label'];
    return $this->t('@op @name as @form_mode_label', [
      '@name' => 'User',
      '@form_mode_label' => $form_mode_label,
      '@op' => $operation,
    ]);
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(RouteMatchInterface $route_match) {
    return $this->pageTitle($route_match, $this->t('Create'));
  }

  /**
   * The _title_callback for the entity.add routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return string
   *   The page title.
   */
  public function editPageTitle(RouteMatchInterface $route_match) {
    return $this->pageTitle($route_match, $this->t('Edit'));
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityTypeFromRouteMatch(RouteMatchInterface $route_match) {
    $parametters = parent::getEntityTypeFromRouteMatch($route_match);
    $form_mode = $this->formModeManager->getFormModeMachineName($route_match->getRouteObject()->getOption('parameters')['form_mode']['id']);
    $form_mode_definition = $this->formModeManager->getActiveDisplays($parametters['entity_type_id']);
    $parametters['form_mode'] = $form_mode_definition[$form_mode];
    return $parametters;
  }

}
