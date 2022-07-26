<?php

namespace Drupal\Tests\commerce_fraud\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the Rules entity.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\Entity\Rules
 *
 * @group commerce
 */
class RulesTest extends OrderKernelTestBase {

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
      'label' => 'Total Price',
      'status' => TRUE,
      'plugin' => 'total_price',
      'configuration' => [
        'buy_amount' => [
          'number' => 10,
          'currency_code' => 'USD',
        ],
      ],
      'score' => 9,
    ]);

    $this->rule->save();

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 4,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    $this->order->addItem($order_item);
    $this->order->save();

  }

  /**
   * @covers ::getScore
   * @covers ::setScore
   */
  public function testRuleScore() {

    $this->rule->setScore(12);
    $this->assertEquals(12, $this->rule->getScore());

  }

  /**
   * @covers ::getPluginId
   * @covers ::setPluginId
   */
  public function testRulePluginId() {

    $this->assertEquals('total_price', $this->rule->getPluginId());
    $this->rule->setPluginId('new_id');
    $this->assertEquals('new_id', $this->rule->getPluginId());

  }

  /**
   * @covers ::getPluginConfiguration
   * @covers ::setPluginConfiguration
   */
  public function testRuleConfiguration() {

    $buy_amount = [
      "buy_amount" => [
        "number" => 10,
        "currency_code" => "USD",
      ],
    ];

    $this->assertEquals($buy_amount, $this->rule->getPluginConfiguration());

    $this->assertEquals(TRUE, $this->rule->getPlugin()->apply($this->order));

    $this->rule->setPluginId('total_quantity');
    $this->assertEquals('total_quantity', $this->rule->getPluginId());

    $buy_quantity = [
      "buy_quantity" => 3,
    ];

    $this->rule->setPluginConfiguration($buy_quantity);
    $this->assertEquals($buy_quantity, $this->rule->getPluginConfiguration());

    $this->assertEquals(TRUE, $this->rule->getPlugin()->apply($this->order));

  }

}
