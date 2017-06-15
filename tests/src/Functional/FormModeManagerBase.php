<?php

namespace Drupal\Tests\form_mode_manager\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a test case for form_mode_manager functional tests.
 *
 * @ingroup form_mode_manager
 */
abstract class FormModeManagerBase extends BrowserTestBase {

  use DisplayFormModeTestTrait;

  /**
   * Common modules to install for form_mode_manager.
   *
   * @var string[]
   */
  public static $modules = [
    'block',
    'entity_test',
    'field_ui',
    'node',
    'user',
    'form_mode_manager',
    'taxonomy',
  ];

  /**
   * Module settings local task expected.
   *
   * @var string[]
   */
  public static $uiLocalTabsExpected = [
    'Settings',
    'Local task settings',
  ];

  /**
   * Routes name of Form mode manager ui(s).
   *
   * @var string[]
   */
  public static $adminRoutesNames = [
    'form_mode_manager.admin_settings',
    'form_mode_manager.admin_settings_links_task',
  ];

  /**
   * An user with Anonymous permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * An user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An test user with random permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Node entity type to test.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeTypeFmm1;

  /**
   * Basic node form mode to test.
   *
   * @var \Drupal\Core\Entity\EntityDisplayModeInterface
   */
  protected $nodeFormMode;

  /**
   * Basic form mode to test.
   *
   * @var \Drupal\form_mode_manager\FormModeManagerInterface
   */
  protected $formModeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup correct blocks in regions.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->nodeTypeFmm1 = $this->drupalCreateContentType([
      'type' => 'fmm_test',
      'name' => 'Form Mode Manager Test 1',
    ]);

    $this->nodeFormMode = $this->drupalCreateFormMode('node');

    $this->container->get('router.builder')->rebuildIfNeeded();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet("admin/structure/types/manage/{$this->nodeTypeFmm1->getEntityTypeId()}/form-display");

    $form_mode_id = $this->nodeFormMode->id();
    $this->formModeManager = $this->container->get('form_mode.manager');
    $edit = ["display_modes_custom[{$this->formModeManager->getFormModeMachineName($form_mode_id)}]" => TRUE];
    $this->drupalPostForm("admin/structure/types/manage/fmm_test/form-display", $edit, t('Save'));
    $this->setUpUsers();
  }

  /**
   * Helper method to create all users needed for tests.
   */
  public function setUpUsers() {
    $this->anonymousUser = $this->drupalCreateUser(['access content']);
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer users',
      'administer permissions',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'administer nodes',
      'administer display modes',
      'use node.default form mode',
      'use ' . $this->nodeFormMode->id() . ' form mode',
      'edit any ' . $this->nodeTypeFmm1->id() . ' content',
      'create ' . $this->nodeTypeFmm1->id() . ' content',
    ]);
    $this->testUser = $this->drupalCreateUser(['access content']);
  }

  /**
   * Tests the EntityFormMode user interface.
   */
  public function assertLocalTasks($tabs_expected) {
    foreach ($tabs_expected as $link) {
      $this->assertSession()->linkExists($link);
    }
  }

}
