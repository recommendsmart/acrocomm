<?php

namespace Drupal\Tests\commerce_fraud\Kernel\Plugin\Commerce\FraudRule;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the commerce fraud rule plugin.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\Plugin\Commerce\FraudRule\AnonymousUserFraudRule
 *
 * @group commerce
 */
class AnonymousUserFraudRuleTest extends OrderKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The test rule.
   *
   * @var \Drupal\commerce_fraud\Entity\RulesInterface
   */
  protected $rule;

  /**
   * {@inheritDoc}
   */
  public static $modules = [
    'commerce_fraud',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->installEntitySchema('rules');
    $this->installConfig(['commerce_fraud']);
    $this->installSchema('commerce_fraud', ['commerce_fraud_fraud_score']);

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $this->createUser(),
      'store_id' => $this->store,
      'order_items' => [],
    ]);

    $this->rule = Rules::create([
      'id' => 'example',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 9,
    ]);

    $this->rule->save();

  }

  /**
   * Tests Anonymous user rule.
   *
   * @covers ::apply
   */
  public function testAnonymousRule() {

    // non-applicable use case.
    $this->assertEquals(FALSE, $this->rule->getPlugin()->apply($this->order));

    // Applicable use case.
    $this->order->setCustomer(User::getAnonymousUser());
    $this->assertEquals(TRUE, $this->rule->getPlugin()->apply($this->order));

  }

}
