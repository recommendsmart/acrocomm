<?php

namespace Drupal\Tests\commerce_fraud\Kernel\Plugin\Commerce\FraudRule;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the commerce fraud rule plugin.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\Plugin\Commerce\FraudRule\LastMinuteFraudRule
 *
 * @group commerce
 */
class LastMinuteFraudRuleTest extends OrderKernelTestBase {

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
   * A simple user to create order.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

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

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $this->user,
      'store_id' => $this->store,
      'order_items' => [],
    ]);

    $this->new_order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $this->user,
      'store_id' => $this->store,
      'order_items' => [],
    ]);

    $this->rule = Rules::create([
      'id' => 'example',
      'label' => 'Last Minute',
      'status' => TRUE,
      'plugin' => 'last_minute',
      'configuration' => [
        'last_minute' => 8,
      ],
      'score' => 9,
    ]);

    $this->rule->save();

  }

  /**
   * Tests last minute rule.
   *
   * @covers ::apply
   */
  public function testLastMinuteRule() {

    // non-applicable use case.
    $ten_minutes_ago = \Drupal::time()->getRequestTime() - (60 * 10);
    $this->order->setCompletedTime($ten_minutes_ago);
    $this->order->save();
    $this->assertEquals(FALSE, $this->rule->getPlugin()->apply($this->new_order));

    // Applicable use case.
    $four_minutes_ago = \Drupal::time()->getRequestTime() - (60 * 4);
    $this->order->setCompletedTime($four_minutes_ago);
    $this->order->save();
    $this->assertEquals(TRUE, $this->rule->getPlugin()->apply($this->new_order));

  }

}
