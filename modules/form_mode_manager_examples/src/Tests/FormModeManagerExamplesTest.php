<?php

namespace Drupal\form_mode_manager_examples\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests form_mode_manager_examples.
 *
 * @group form_mode_manager_examples
 *
 * @ingroup form_mode_manager
 */
class FormModeManagerExamplesTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'menu_ui',
    'path',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Theme needs to be set before enabling form_mode_manager_examples because
    // of dependency.
    \Drupal::service('theme_handler')->install(['bartik']);
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();
    $this->assertTrue(\Drupal::service('module_installer')->install(['form_mode_manager_examples']), 'form_mode_manager_examples installed.');
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests if form_mode_manager_examples is correctly installed.
   */
  public function testInstalled() {
    $this->drupalGet('');
    $this->assertTitle('Form Mode Manager examples | Drupal');
    $this->assertText('Form Mode Manager examples');
    $this->assertText('Welcome to Form Mode Manager example.');
    $this->assertText('Form Mode Manager allows to use form_mode implement on Drupal 8 on each Entity.');
    $this->assertText('You can test the functionality with custom content types created for the demonstration of features Form Mode Manager examples:');
  }

}
