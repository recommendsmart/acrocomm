<?php

namespace Drupal\Tests\commerce_fraud\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\commerce_fraud\Entity\SuspectedOrder;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the SuspectedOrder entity.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\Entity\SuspectedOrder
 *
 * @group commerce
 */
class SuspectedOrderTest extends OrderKernelTestBase {

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
   * The test suspected order.
   *
   * @var \Drupal\commerce_fraud\Entity\SuspectedOrderInterface
   */
  protected $suspectedOrder;

  /**
   * {@inheritDoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'commerce_fraud',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->installConfig(['system']);
    $this->installEntitySchema('rules');
    $this->installEntitySchema('suspected_order');
    $this->installSchema('commerce_fraud', ['commerce_fraud_fraud_score']);
    $this->installConfig(['commerce_fraud']);

    $this->pluginManager = $this->container->get('plugin.manager.commerce_fraud_rule');

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

    $this->suspectedOrder = SuspectedOrder::create([
      'order_id' => $this->order->id(),
      'rules' => [$this->rule],
    ]);
    $this->suspectedOrder->save();
  }

  /**
   * @covers ::getOrderId
   * @covers ::setOrderId
   */
  public function testSuspectedOrderId() {

    $this->suspectedOrder->setOrderId($this->order->id());
    $this->assertEquals($this->order->id(), $this->suspectedOrder->getOrderId());
    $this->suspectedOrder->setOrderId(12);
    $this->assertEquals(12, $this->suspectedOrder->getOrderId());
    $this->suspectedOrder->setOrderId($this->order->id());
    $this->assertEquals($this->order->id(), $this->suspectedOrder->getOrderId());

  }

  /**
   * @covers ::getScore
   */
  public function testSuspectedOrderScore() {

    $this->assertEquals(9, $this->suspectedOrder->getScore());
    $newRule = Rules::create([
      'id' => 'example2',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 3,
    ]);
    $newRule->save();
    $this->suspectedOrder->setRules($newRule);
    $this->assertEquals(3, $this->suspectedOrder->getScore());

    $this->suspectedOrder->addRule($this->rule);

    $this->assertEquals(12, $this->suspectedOrder->getScore());

  }

  /**
   * @covers ::getRules
   * @covers ::setRules
   * @covers ::hasRule
   * @covers ::addRule
   */
  public function testSuspectedOrderRules() {

    foreach ($this->suspectedOrder->getRules() as $rule) {
      $this->assertEquals("Compare total price with given price", $rule->getPlugin()->getLabel()->render());
    }

    $newRule = Rules::create([
      'id' => 'example2',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 9,
    ]);
    $newRule->save();
    $this->suspectedOrder->setRules($newRule);
    foreach ($this->suspectedOrder->getRules() as $rule) {
      $this->assertEquals("Check if order by Anonymous User", $rule->getPlugin()->getLabel()->render());
    }

    $this->assertEquals(FALSE, $this->suspectedOrder->hasRule($this->rule));
    $this->assertEquals(TRUE, $this->suspectedOrder->hasRule($newRule));

    $this->suspectedOrder->addRule($this->rule);
    $rules = $this->suspectedOrder->getRules();

    $this->assertEquals("Check if order by Anonymous User", $rules[0]->getPlugin()->getLabel()->render());
    $this->assertEquals("Compare total price with given price", $rules[1]->getPlugin()->getLabel()->render());

  }

}
