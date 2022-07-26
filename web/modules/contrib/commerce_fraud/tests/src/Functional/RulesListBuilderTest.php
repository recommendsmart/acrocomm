<?php

namespace Drupal\Tests\commerce_fraud\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the RulesListBuilder class.
 *
 * @group commerce_fraud
 *
 * @see \Drupal\commerce_fraud\RulesListBuilder
 */
class RulesListBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_fraud',
  ];
  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  private $adminUser;

  /**
   * {@inheritDoc}
   */
  public function setUp() : void {

    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);

  }

  /**
   * Tests the display of the RulesListBuilder when no rules are created.
   *
   * @covers \Drupal\commerce_fraud\RulesListBuilder::buildHeader
   * @covers \Drupal\commerce_fraud\RulesListBuilder::render
   */
  public function testRulesListBuilderEmptyDisplay() {

    $this->drupalLogin($this->adminUser);

    // Load the list builder.
    $this->drupalGet(Url::fromRoute('entity.rules.collection'));
    $this->assertSession()->statusCodeEquals(200);

    // Check that both status sections were generated.
    $this->assertSession()->pageTextContains('Enabled');
    $this->assertSession()->pageTextContains('Disabled');

    // Check that the header was generated.
    $this->assertSession()->pageTextContains('Rule ID');
    $this->assertSession()->pageTextContains('Name');
    $this->assertSession()->pageTextContains('Rule name');
    $this->assertSession()->pageTextContains('Score');

    // Check that the tables were generated.
    $this->assertSession()->pageTextContains('There are no enabled rules.');
    $this->assertSession()->pageTextContains('There are no disabled rules.');

  }

  /**
   * Tests the display of the RulesListBuilder when there are rules.
   *
   * @covers \Drupal\commerce_fraud\RulesListBuilder::buildRow
   */
  public function testRulesListBuilderDisplay() {

    $this->drupalLogin($this->adminUser);

    // Create dummy Rules.
    $rules_storage = \Drupal::entityTypeManager()->getStorage('rules');
    $rule_one = $rules_storage->create([
      'id' => 'ruleOne',
      'label' => $this->randomMachineName(),
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 9,
    ]);
    $rule_one->save();
    $rule_two = $rules_storage->create([
      'id' => 'ruleTwo',
      'label' => $this->randomMachineName(),
      'status' => FALSE,
      'plugin' => 'check_user_ip',
      'score' => 9,
    ]);
    $rule_two->save();

    // Load the list builder.
    $this->drupalGet(Url::fromRoute('entity.rules.collection'));
    $this->assertSession()->statusCodeEquals(200);

    // Check rules were loaded.
    $this->assertSession()->pageTextNotContains('There are no enabled rules.');
    $this->assertSession()->pageTextNotContains('There are no disabled rules.');

    $this->assertSession()->pageTextContainsOnce($rule_one->get('label'));
    $this->assertSession()->pageTextContainsOnce($rule_one->getPlugin()->getLabel());
    $this->assertSession()->pageTextContainsOnce($rule_two->get('label'));

  }

}
