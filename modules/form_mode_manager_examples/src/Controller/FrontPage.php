<?php

namespace Drupal\form_mode_manager_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Simple front page controller for form_mode_manager_examples module.
 */
class FrontPage extends ControllerBase {

  /**
   * Node types that were created for FormModeManager Example.
   *
   * @var array
   */
  protected $fmmExampleEntityBundles = [
    'article',
    'page',
    'node_form_mode_example',
  ];

  /**
   * Displays useful information for form_mode_manager on the front page.
   */
  public function content() {
    $entity_items = [];
    foreach ($this->fmmExampleEntityBundles as $entity_bundle) {
      $entity_type = $this->entityTypeManager()->getStorage('node_type')->load($entity_bundle);
      $entity_items['#items'][] = $this->t('<a href="@url">@label',
        [
          '@url' => Url::fromRoute('node.add', ['node_type' => $entity_type->id()])->toString(),
          '@label' => $entity_type->label(),
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
            array_values($entity_items),
          ],
        ],
      ],
    ];
  }

}
