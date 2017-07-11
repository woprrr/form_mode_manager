<?php

namespace Drupal\form_mode_manager\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Converter for form_mode_manager routes.
 *
 * This Converter is only used in add_page context.
 *
 * @see \Drupal\form_mode_manager\Controller\EntityFormModeBase::addPage
 */
class FormModeManagerConverter implements ParamConverterInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * Constructs a new FormModeManagerConverter.
   *
   * @param \Drupal\form_mode_manager\FormModeManagerInterface $form_mode_manager
   *   The form mode manager.
   */
  public function __construct(FormModeManagerInterface $form_mode_manager) {
    $this->formModeManager = $form_mode_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $defaults['_route_object']->getOption('_form_mode_manager_entity_type_id');
    $form_mode_id = $entity_type_id . '.' . $value;
    if ($form_mode_id === $defaults['_entity_form'] && $entity_type_id) {
      return $this->formModeManager->getFormModesByEntity($entity_type_id)[$value];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if ('form_mode_name' === $name && (!empty($definition['type']) && 0 != preg_match('/^.*\./', $definition['type']))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the entity loaded into current route.
   *
   * If we are in classic case _entity_form are always here,
   * but in custom "list_page" route provide by FormModeManager,
   * We need to retrive the entity_type in "_route_object".
   *
   * @param array $defaults
   *   The route defaults array.
   * @param array $definition
   *   The parameter definition provided in the route options.
   *
   * @return string|false
   *   Extract the entity_type_id of current entity.
   */
  protected function getEntityForm(array $defaults, array $definition) {
    $entity_form = $defaults['_entity_form'];
    $form_mode_id = $defaults['_route_object']->getOption('_form_mode_manager_entity_type_id') . '.' . $defaults['form_mode_name'];
    if ($entity_form === $form_mode_id) {
      return explode('.', $defaults['_entity_form'])[0];
    }

    return FALSE;
  }

}
