<?php

namespace Drupal\Tests\commerce_fraud\Kernel\Plugin\Commerce\FraudRule;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;

/**
 * Tests the quantity rule plugin.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\Plugin\Commerce\FraudRule\TotalQuantityFraudRule
 *
 * @group commerce
 */
class TotalQuantityFraudRuleTest extends OrderKernelTestBase {

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
      'label' => 'Total Quantity',
      'status' => TRUE,
      'plugin' => 'total_quantity',
      'configuration' => [
        'buy_quantity' => 7,
      ],
      'score' => 9,
    ]);

    $this->rule->save();

  }

  /**
   * Tests Quantity rule.
   *
   * @covers ::apply
   */
  public function testQuantityRule() {

    // non-applicable use case.
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('5.00', 'USD'),
    ]);
    $order_item->save();

    $this->order->addItem($order_item);
    $this->order->save();
    $this->assertEquals(FALSE, $this->rule->getPlugin()->apply($this->order));

    // Applicable use case.
    $this->order->removeItem($order_item);

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 8,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    $this->order->addItem($order_item);
    $this->order->save();
    $this->assertEquals(TRUE, $this->rule->getPlugin()->apply($this->order));

  }

}
