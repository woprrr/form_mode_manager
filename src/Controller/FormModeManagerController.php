<?php

/**
 * @file
 * Contains \Drupal\form_mode_manager\Controller\FormModeManagerController.
 */

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media_entity\MediaBundleInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Entities routes.
 */
class FormModeManagerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * Constructs a FormModeManagerController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   */
  public function nodeAdd(NodeTypeInterface $node_type, $form_display) {
    $form_class = str_replace('-', '_', $form_display);
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => $node_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($node, $form_class);

    return $form;
  }

  /**
   * Provides the node submission form.
   *
   * @param MediaBundleInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   */
  public function mediaAdd(MediaBundleInterface $media_bundle, $form_display) {
    $user = \Drupal::currentUser();
    $form_class = str_replace('-', '_', $form_display);
    $bundle = $media_bundle->id();
    $langcode = $this->moduleHandler()->invoke('language', 'get_default_langcode', ['media', $bundle]);
    $media = $this->entityTypeManager->getStorage('media')->create([
      'uid' => $user->id(),
      'bundle' => $bundle,
      'langcode' => $langcode ? $langcode : $this->languageManager->getDefaultLanguage()->getId(),
    ]);

    return $this->entityFormBuilder()->getForm($media, $form_class);
  }
  /**
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(NodeTypeInterface $node_type) {
    return $this->t('Create @name', ['@name' => $node_type->label()]);
  }

}
