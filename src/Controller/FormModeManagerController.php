<?php

/**
 * @file
 * Contains \Drupal\form_mode_manager\Controller\FormModeManagerController.
 */

namespace Drupal\form_mode_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media_entity\MediaBundleInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Entities routes.
 */
class FormModeManagerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
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
    // @TODO change entityManager() to entityTypeManager().
    $node = $this->entityManager()->getStorage('node')->create(array(
      'type' => $node_type->id(),
    ));

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
    $langcode = $this->moduleHandler()->invoke('language', 'get_default_langcode', array('media', $bundle));
    // @TODO change entityManager() to entityTypeManager().
    $media = $this->entityManager()->getStorage('media')->create(array(
      'uid' => $user->id(),
      'bundle' => $bundle,
      'langcode' => $langcode ? $langcode : $this->languageManager->getDefaultLanguage()->getId(),
    ));

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
    return $this->t('Create @name', array('@name' => $node_type->label()));
  }

}
