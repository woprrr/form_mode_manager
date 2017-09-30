<?php

namespace Drupal\form_mode_manager_examples\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests form_mode_manager_examples.
 *
 * @group form_mode_manager_examples
 *
 * @ingroup form_mode_manager
 */
class FormModeManagerExamplesTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'menu_ui',
    'path',
    'node',
    'block',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Theme needs to be set before enabling form_mode_manager_examples because,
    // of dependency.
    \Drupal::service('theme_handler')->install(['bartik']);
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();
    $this->assertTrue(\Drupal::service('module_installer')->install(['form_mode_manager_examples']), 'form_mode_manager_examples installed.');
    $this->drupalPlaceBlock('local_actions_block');
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests if form_mode_manager_examples is correctly installed.
   */
  public function testInstalled() {
    $this->drupalGet('');
    $this->assertSession()->titleEquals('Form Mode Manager examples | Drupal');
    $this->assertSession()->pageTextContains('Form Mode Manager examples');
    $this->assertSession()->pageTextContains('Welcome to Form Mode Manager example.');
    $this->assertSession()->pageTextContains('Form Mode Manager allows to use form_mode implement on Drupal 8 on each Entity.');
    $this->assertSession()->pageTextContains('You can test the functionality with custom content types created for the demonstration of features Form Mode Manager examples:');

    // Add test for form mode manager actions.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('');
    $this->assertSession()->linkExists('Add node as Contributor');
  }

}
