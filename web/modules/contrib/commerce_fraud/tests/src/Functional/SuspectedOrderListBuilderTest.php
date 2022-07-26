<?php

namespace Drupal\Tests\commerce_fraud\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\commerce_fraud\Entity\Rules;

/**
 * Tests the SuspectedOrderListBuilder class.
 *
 * @group commerce_fraud
 *
 * @see \Drupal\commerce_fraud\SuspectedOrderListBuilder
 */
class SuspectedOrderListBuilderTest extends BrowserTestBase {

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
      'administer suspected order entities',
    ]);

  }

  /**
   * Tests the display of the SuspectedOrderListBuilder when empty.
   *
   * @covers \Drupal\commerce_fraud\SuspectedOrderListBuilder::buildHeader
   * @covers \Drupal\commerce_fraud\SuspectedOrderListBuilder::render
   */
  public function testSuspectedOrderListBuilderEmptyDisplay() {

    $this->drupalLogin($this->adminUser);

    // Load the list builder.
    $this->drupalGet(Url::fromRoute('entity.suspected_order.collection'));
    $this->assertSession()->statusCodeEquals(200);

    // Check that title is generated.
    $this->assertSession()->pageTextContains('Suspected order entities');

    // Check that the header was generated.
    $this->assertSession()->pageTextContains('Suspected order ID');
    $this->assertSession()->pageTextContains('Created');
    $this->assertSession()->pageTextContains('Order ID');
    $this->assertSession()->pageTextContains('Total Score');
    $this->assertSession()->pageTextContains('Operations');

    // Check that the tables were generated.
    $this->assertSession()->pageTextContains('There are no suspected order entities yet.');

  }

  /**
   * Tests the display of the SuspectedOrderListBuilder with suspected orders.
   *
   * @covers \Drupal\commerce_fraud\SuspectedOrderListBuilder::buildRow
   */
  public function testSuspectedOrderListBuilderDisplay() {

    $this->drupalLogin($this->adminUser);

    // Create dummy Suspected order.
    $suspected_order = \Drupal::entityTypeManager()->getStorage('suspected_order');
    $ruleOne = Rules::create([
      'id' => 'example',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 9,
    ]);
    $ruleOne->save();
    $ruleTwo = Rules::create([
      'id' => 'example2',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 3,
    ]);
    $ruleTwo->save();
    $order_one = $suspected_order->create([
      'order_id' => 5,
      'rules' => [$ruleOne],
    ]);
    $order_one->save();
    $order_two = $suspected_order->create([
      'order_id' => 6,
      'rules' => [$ruleOne, $ruleTwo],
    ]);
    $order_two->save();

    // Load the list builder.
    $this->drupalGet(Url::fromRoute('entity.suspected_order.collection'));
    $this->assertSession()->statusCodeEquals(200);

    // Check suspected order were loaded.
    $this->assertSession()->pageTextNotContains('There are no Suspected Order yet.');

    $this->assertSession()->pageTextContains($order_one->getOrderId());
    $this->assertSession()->pageTextContains($order_one->getScore());
    $this->assertSession()->pageTextContains($order_two->getOrderId());
    $this->assertSession()->pageTextContains($order_two->getScore());

  }

}
