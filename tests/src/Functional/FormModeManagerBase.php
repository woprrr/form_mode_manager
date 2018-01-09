<?php

namespace Drupal\Tests\form_mode_manager\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Utility\Unicode;
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
    'entity_test',
    'field',
    'field_ui',
    'media',
    'block',
    'block_content',
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
   * Basic user form mode to test.
   *
   * @var \Drupal\Core\Entity\EntityDisplayModeInterface
   */
  protected $userFormMode;

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
    $this->userFormMode = $this->drupalCreateFormMode('user');

    $this->container->get('router.builder')->rebuildIfNeeded();

    $this->drupalLogin($this->rootUser);

    $this->setUpFormMode("admin/structure/types/manage/{$this->nodeTypeFmm1->id()}/form-display", $this->nodeFormMode->id());
    $this->setUpFormMode("admin/config/people/accounts/form-display", $this->userFormMode->id());
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
      'use user.default form mode',
      'use ' . $this->nodeFormMode->id() . ' form mode',
      'use ' . $this->userFormMode->id() . ' form mode',
      'edit any ' . $this->nodeTypeFmm1->id() . ' content',
      'create ' . $this->nodeTypeFmm1->id() . ' content',
    ]);
    $this->testUser = $this->drupalCreateUser(['access content']);
  }

  /**
   * Helper method to configure form display for given form_mode.
   */
  public function setUpFormMode($path, $form_mode_id) {
    $this->drupalGet($path);
    $this->formModeManager = $this->container->get('form_mode.manager');
    $edit = ["display_modes_custom[{$this->formModeManager->getFormModeMachineName($form_mode_id)}]" => TRUE];
    $this->drupalPostForm($path, $edit, t('Save'));
  }

  /**
   * Helper method to hide field for given entity form path.
   */
  public function setHiddenFieldFormMode($path, $field_name) {
    $this->drupalGet($path);
    $edit = ["fields[$field_name][region]" => 'hidden'];
    $this->drupalPostForm($path, $edit, t('Save'));
  }

  /**
   * Tests the EntityFormMode user interface.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function assertLocalTasks($tabs_expected) {
    foreach ($tabs_expected as $link) {
      $this->assertSession()->linkExists($link);
    }
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param bool $create_body
   *   Whether or not to create the body field.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created custom block type.
   */
  protected function createBlockContentType($create_body = FALSE) {
    $bundle = BlockContentType::create([
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'revision' => FALSE,
    ]);
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
    return $bundle;
  }

  /**
   * Creates a block_content based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the node, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array, for example:.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The created block_content entity.
   */
  protected function createBlockContent(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'body' => [
        [
          'value' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ];
    $block_content = BlockContent::create($settings);
    $block_content->save();

    return $block_content;
  }

}
