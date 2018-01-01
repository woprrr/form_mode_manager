<?php

namespace Drupal\Tests\form_mode_manager\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the Form mode manager user interfaces.
 *
 * @group form_mode_manager
 */
class FormModeManagerUiTest extends FormModeManagerBase {

  use TaxonomyTestTrait;

  /**
   * Stores the node content used by this test.
   *
   * @var array
   */
  public $nodes;

  /**
   * Stores the node content used by this test.
   *
   * @var array
   */
  public $terms;

  /**
   * Stores the block content used by this test.
   *
   * @var array
   */
  public $blocks;

  /**
   * Stores the user content used by this test.
   *
   * @var array
   */
  public $users;

  /**
   * Stores the taxonomy vocabulary used by this test.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  public $vocabulary;

  /**
   * Basic block form mode to test.
   *
   * @var \Drupal\Core\Entity\EntityDisplayModeInterface
   */
  protected $blockFormMode;

  /**
   * Basic block form mode to test.
   *
   * @var \Drupal\block_content\Entity\BlockContentType
   */
  protected $blockType;

  /**
   * Basic taxonomy form mode to test.
   *
   * @var \Drupal\Core\Entity\EntityDisplayModeInterface
   */
  protected $taxonomyFormMode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Hide field for already existing form modes.
    $this->setHiddenFieldFormMode("admin/config/people/accounts/form-display", 'timezone');
    $this->setHiddenFieldFormMode("admin/structure/types/manage/{$this->nodeTypeFmm1->id()}/form-display", 'body');

    $this->vocabulary = $this->createVocabulary();
    $this->blockType = $this->createBlockContentType();

    // Generate contents to this tests.
    for ($i = 0; $i < 3; $i++) {
      $this->terms[] = $this->createTerm($this->vocabulary, ['title' => "Term $i"]);
      $this->nodes[] = $this->createNode(['type' => $this->nodeTypeFmm1->id()]);
      $this->blocks[] = $this->createBlockContent(['type' => $this->blockType->id()]);
      $this->users[] = $this->createUser([
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
    }

    $this->drupalLogin($this->rootUser);

    $this->blockFormMode = $this->drupalCreateFormMode('block_content');
    $this->setUpFormMode("admin/structure/block/block-content/manage/{$this->blockType->id()}/form-display", $this->blockFormMode->id());
    $this->setHiddenFieldFormMode("admin/structure/block/block-content/manage/{$this->blockType->id()}/form-display", 'info');

    $this->taxonomyFormMode = $this->drupalCreateFormMode('taxonomy_term');
    $this->setUpFormMode("admin/structure/taxonomy/manage/{$this->vocabulary->id()}/overview/form-display", $this->taxonomyFormMode->id());
    $this->setHiddenFieldFormMode("admin/structure/taxonomy/manage/{$this->vocabulary->id()}/overview/form-display", 'description');

    $this->container->get('router.builder')->rebuild();

    // Add additional permissions to users.
    $this->setUsersTestPermissions([
      "use {$this->blockFormMode->id()} form mode",
      "use {$this->taxonomyFormMode->id()} form mode",
      'administer users',
      'administer user form display',
      "edit terms in {$this->vocabulary->id()}",
    ]);
  }

  /**
   * Add permissions for each users used to this test.
   */
  public function setUsersTestPermissions(array $permissions) {
    foreach ($this->users as $user) {
      $role = Role::load($user->getRoles()[1]);
      user_role_grant_permissions($role->id(), $permissions);
    }
  }

  /**
   * Test each entities using form mode works.
   *
   * @dataProvider entityFormModeTestProvider
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFieldFormFormModeManager(array $test_parameters, $add_path, $edit_path, $field_name) {
    $form_mode_machine_name = $this->{$test_parameters[2]}->id();
    $this->setUsersTestPermissions(["use $form_mode_machine_name form mode"]);
    $add_path = new FormattableMarkup($add_path, ['@type' => isset($test_parameters[1]) ? $this->{$test_parameters[1]}->id() : 'people']);
    $edit_path = new FormattableMarkup($edit_path, ['@id' => $this->{$test_parameters[0]}[0]->id()]);
    $form_mode_name = $this->formModeManager->getFormModeMachineName($form_mode_machine_name);

    $this->drupalGet("$add_path/$form_mode_name");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists($field_name);
    $this->drupalGet($add_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists($field_name);
    $this->drupalGet("$edit_path/$form_mode_name");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists($field_name);
    $this->drupalGet($edit_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists($field_name);
  }

  /**
   * Provides all parameters needed to test form display and form mode manager.
   *
   * This Data provider are more generic as possible and are strongly,
   * linked to this class test.
   *
   * @see \Drupal\Tests\form_mode_manager\Functional\FormModeManagerUiTest::testFieldFormFormModeManager()
   */
  public function entityFormModeTestProvider() {
    $data = [];
    $data[] = [
      [
        'users',
        NULL,
        'userFormMode',
      ],
      'admin/@type/create',
      "user/@id/edit",
      'timezone',
    ];
    $data[] = [
      [
        'nodes',
        'nodeTypeFmm1',
        'nodeFormMode',
      ],
      'node/add/@type',
      "node/@id/edit",
      'body[0][value]',
    ];
    $data[] = [
      [
        'blocks',
        'blockType',
        'blockFormMode',
      ],
      'block/add/@type',
      "block/@id",
      'info[0][value]',
    ];
    $data[] = [
      [
        'terms',
        'vocabulary',
        'taxonomyFormMode',
      ],
      'admin/structure/taxonomy/manage/@type/add',
      "taxonomy/term/@id/edit",
      'description[0][value]',
    ];
    return $data;
  }

  /**
   * Tests the Form Mode Manager Settings interface.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEntityFormModeManagerSettingsUi() {
    $node_form_mode_id = $this->formModeManager->getFormModeMachineName($this->nodeFormMode->id());

    // Test the Form Mode Manager UI page.
    $this->drupalLogin($this->anonymousUser);
    $this->drupalGet('admin/config/content/form_mode_manager');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/form_mode_manager');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->titleEquals('Form Mode Manager settings | Drupal');

    $this->assertLocalTasks(self::$uiLocalTabsExpected);

    // Check existance of select element.
    $this->assertSession()->selectExists('element_node[]');

    $this->getSession()
      ->getPage()
      ->selectFieldOption('element_node[]', $node_form_mode_id);

    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()
      ->pageTextContains(t('The configuration options have been saved.'));
  }

  /**
   * Tests the Form Mode Manager user interface.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEntityFormModeManagerExcludeMalFormedEntities() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/form_mode_manager');
    $this->assertSession()
      ->elementNotExists('xpath', '//select[contains(@name, "element_entity_test[]")]');
  }

  /**
   * Tests the Form Mode Manager user Links positions interface.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEntityFormModeManagerLinksUi() {
    // Test the Form Mode Manager UI page.
    $this->drupalLogin($this->anonymousUser);
    $this->drupalGet('admin/config/content/form_mode_manager/links-task');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/form_mode_manager/links-task');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->titleEquals('Form Mode Manager settings Links task | Drupal');
    $this->assertLocalTasks(self::$uiLocalTabsExpected);
    $this->assertSession()->selectExists('tasks_location_node');
  }

  /**
   * Tests Form Mode links provide by module for Node entity.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFormModeManagerNodeOverview() {
    Role::load($this->adminUser->getRoles()[1])
      ->grantPermission('access content overview')
      ->save();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');
    $this->assertSession()
      ->linkExists("Add content as {$this->nodeFormMode->label()}");
    $this->assertSession()
      ->linkExists("Edit as {$this->nodeFormMode->label()}");
  }

  /**
   * Test Form mode manager Local tasks.
   */
  public function testFormModeManagerNodeLocalTasks() {
    Role::load($this->adminUser->getRoles()[1])
      ->grantPermission('access content overview')
      ->save();

    $this->drupalGet("node/{$this->nodes[0]->id()}/edit");
    $this->assertLocalTasks([
      "Edit as Default",
      "Edit as {$this->nodeFormMode->label()}",
    ]);
  }

  /**
   * Tests Form Mode links provide by module for User entity.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFormModeManagerUserOverview() {
    $user_form_mode = $this->drupalCreateFormMode('user');

    Role::load($this->adminUser->getRoles()[1])
      ->grantPermission('administer users')
      ->grantPermission('use ' . $user_form_mode->id() . ' form mode')
      ->save();

    $this->drupalGet("admin/config/people/accounts/form-display");
    $edit = ["display_modes_custom[{$this->formModeManager->getFormModeMachineName($user_form_mode->id())}]" => TRUE];
    $this->drupalPostForm("admin/config/people/accounts/form-display", $edit, t('Save'));

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people');
    $this->assertSession()
      ->linkExists("Add user as {$user_form_mode->label()}");
    $this->assertSession()
      ->linkExists("Edit as {$user_form_mode->label()}");
  }

  /**
   * Tests Form Mode links provide by module for Term entity.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFormModeManagerTaxonomyTermOverview() {
    $term_form_mode = $this->drupalCreateFormMode('taxonomy_term');

    $this->drupalGet("admin/structure/taxonomy/manage/{$this->vocabulary->id()}/overview/form-display");
    $edit = ["display_modes_custom[{$this->formModeManager->getFormModeMachineName($term_form_mode->id())}]" => TRUE];
    $this->drupalPostForm("admin/structure/taxonomy/manage/{$this->vocabulary->id()}/overview/form-display", $edit, t('Save'));

    Role::load($this->adminUser->getRoles()[1])
      ->grantPermission('administer taxonomy')
      ->grantPermission('use ' . $term_form_mode->id() . ' form mode')
      ->save();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/structure/taxonomy/manage/{$this->vocabulary->id()}/overview");
    $this->assertSession()
      ->linkExists("Add term as {$term_form_mode->label()}");

    // Enable this cas whenever issue,
    // https://www.drupal.org/node/2469567 are switch to fixed.
    /* $this->assertSession()
    ->linkExists("Edit as {$term_form_mode->label()}"); */
  }

}
