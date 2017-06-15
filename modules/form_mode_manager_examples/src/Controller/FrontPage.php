<?php

namespace Drupal\form_mode_manager_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple front page controller for form_mode_manager_examples module.
 */
class FrontPage extends ControllerBase {

  /**
   * Node types that were created for Form Mode Manager Example.
   *
   * @var array
   */
  protected $fmmExampleEntityBundles = [
    'node_form_mode_example',
  ];

  /**
   * The entity display repository.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Form mode manager FrontPage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
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
   * Displays useful information for form_mode_manager on the front page.
   */
  public function content() {
    $items = [];
    foreach ($this->fmmExampleEntityBundles as $node_type) {
      $node_type = $this->entityTypeManager->getStorage('node_type')->load($node_type);
      $items['#items'][] = $this->t('<a href="@url">@label',
        [
          '@url' => Url::fromRoute('node.add', ['node_type' => $node_type->id()])->toString(),
          '@label' => $node_type->label(),
        ]
      );
    }
    return [
      'intro' => [
        '#markup' => '<p>' . $this->t('Welcome to Form Mode Manager example.') . '</p>',
      ],
      'description' => [
        '#markup' => '<p>' . $this->t('Form Mode Manager allows to use form_mode implement on Drupal 8 on each Entity.') . '</p>'
        . '<p>' . $this->t('You can test the functionality with custom content types created for the demonstration of features Form Mode Manager examples:') . '</p>',
      ],
      'content_types' => [
        '#type' => 'item',
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            array_values($items),
          ],
        ],
      ],
    ];
  }

}
