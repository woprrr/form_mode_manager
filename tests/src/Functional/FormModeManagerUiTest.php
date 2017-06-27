<?php

namespace Drupal\Tests\form_mode_manager\Functional;

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
   * Stores the taxonomy vocabulary used by this test.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  public $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->vocabulary = $this->createVocabulary();
    // Generate contents to this tests.
    for ($i = 0; $i < 3; $i++) {
      $this->createTerm($this->vocabulary, ['title' => "Term $i"]);
      $this->nodes[] = $this->createNode(['type' => $this->nodeTypeFmm1->id()]);
    }
  }

  /**
   * Tests the Form Mode Manager Settings interface.
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
   */
  public function testEntityFormModeManagerExcludeMalFormedEntities() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/form_mode_manager');
    $this->assertSession()
      ->elementNotExists('xpath', '//select[contains(@name, "element_entity_test[]")]');
  }

  /**
   * Tests the Form Mode Manager user Links positions interface.
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
   */
  public function testFormModeManagerNodeOverview() {
    Role::load($this->adminUser->getRoles()[1])
      ->grantPermission('access content overview')
      ->save();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');
    $this->assertSession()
      ->linkExists("Add node as {$this->nodeFormMode->label()}");
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
