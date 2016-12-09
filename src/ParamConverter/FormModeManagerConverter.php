<?php

namespace Drupal\form_mode_manager\ParamConverter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converter for form_mode_manager routes.
 */
class FormModeManagerConverter implements ParamConverterInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new FormModeManagerConverter.
   *
   * @param EntityDisplayRepositoryInterface $entity_display_repository
   *   The language manager.
   */
  public function __construct(EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($entity_form = $this->getEntityForm($defaults, $definition)) {
      return $this->entityDisplayRepository->getFormModes($entity_form)[$value];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if ($name === 'form_display' && (!empty($definition['type']) && preg_match('/^.*\./', $definition['type']) != 0)) {
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
    if (isset($defaults['_entity_form'])) {
      return explode('.', $defaults['_entity_form'])[0];
    }
    elseif (preg_match('/^.*\./', $definition['type']) != 0) {
      return $defaults['_route_object']->getDefault('entity_type')->id();
    }

    return FALSE;
  }

}
