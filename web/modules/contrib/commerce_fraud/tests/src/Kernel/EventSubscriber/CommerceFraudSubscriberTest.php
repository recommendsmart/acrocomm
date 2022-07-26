<?php

namespace Drupal\Tests\commerce_fraud\Kernel\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the CommerceFraudSubscriber class.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\EventSubscriber\CommerceFraudSubscriber
 *
 * @group commerce
 */
class CommerceFraudSubscriberTest extends OrderKernelTestBase {

  /**
   * Commerce Fraud Subscriber class object.
   *
   * @var \Drupal\commerce_fraud\EventSubscriber\CommerceFraudSubscriber
   */
  protected $commerceFraudSubscriber;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The suspected order storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $suspectedOrderStorage;

  /**
   * {@inheritDoc}
   */
  public static $modules = [
    'commerce_log',
    'commerce_fraud',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_log');
    $this->installEntitySchema('rules');
    $this->installEntitySchema('suspected_order');
    $this->installConfig(['commerce_fraud']);
    $this->installSchema('commerce_fraud', ['commerce_fraud_fraud_score']);

    $config_factory = $this->container->get('config.factory');

    $editConfig = $config_factory->getEditable('system.site');
    $editConfig->set('name', 'SiteName');
    $editConfig->save();

    $this->commerceFraudSubscriber = $this->container->get('commerce_fraud.commerce_fraud_subscriber');

    $this->suspectedOrderStorage = \Drupal::entityTypeManager()->getStorage('suspected_order');

    $this->database = \Drupal::database();

    $rule1 = Rules::create([
      'id' => 'example',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 9,
    ]);
    $rule1->save();

    $rule2 = Rules::create([
      'id' => 'example_2',
      'label' => 'ANONYMOUS2',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 13,
    ]);
    $rule2->save();

    $user = User::getAnonymousUser();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $user,
      'store_id' => $this->store,
      'order_items' => [],
    ]);
    $this->order->save();

    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

  }

  /**
   * Tests the setting of fraud score on an orders place transition.
   *
   * @covers ::setFraudScore
   */
  public function testSetFraudScore() {

    $this->assertEquals('completed', $this->order->getState()->getId());

    $this->config('commerce_fraud.settings')
      ->set('stop_order', TRUE)
      ->save();

    $user = User::getAnonymousUser();
    $order2 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $user,
      'store_id' => $this->store,
      'order_items' => [],
    ]);
    $order2->save();

    $transition = $order2->getState()->getTransitions();
    $order2->getState()->applyTransition($transition['place']);
    $order2->save();

    $this->assertEquals('fraudulent', $order2->getState()->getId());

  }

  /**
   * Tests getMailParamsForBlocklisted function.
   *
   * @covers ::getMailParamsForBlockList
   */
  public function testGetMailParamsForBlocklisted() {

    $params = $this->commerceFraudSubscriber->getMailParamsForBlocklist($this->order, FALSE);

    $this->assertEqual($params['order_id'], $this->order->id());
    $this->assertEqual($params['user_id'], $this->order->getCustomerId());
    $this->assertEqual($params['user_name'], $this->order->getCustomer()->getDisplayName());
    $this->assertEqual($params['status'], 'completed');
    $this->assertEqual($params['fraud_score'], 22);
    $this->assertEqual($params['stopped'], FALSE);

    $fraud_note = [
      "Check if order by Anonymous User: 9",
      "Check if order by Anonymous User: 13",
    ];
    $this->assertEqual($params['fraud_notes'], $fraud_note);

    $this->assertEqual($params['site_name'], 'SiteName');

  }

  /**
   * Tests getFraudRulesNames function.
   *
   * @covers ::getFraudRulesNames
   */
  public function testGetFraudRulesNames() {

    $fraud_note = [
      "Check if order by Anonymous User: 9",
      "Check if order by Anonymous User: 13",
    ];

    $rules = $this->commerceFraudSubscriber->getFraudRulesNames();
    $this->assertCount(2, $rules);
    $this->assertEqual($rules, $fraud_note);

    $order2 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $this->createUser(),
      'store_id' => $this->store,
      'order_items' => [],
    ]);
    $order2->save();

    $transition = $order2->getState()->getTransitions();
    $order2->getState()->applyTransition($transition['place']);
    $order2->save();

    $this->assertEqual($this->commerceFraudSubscriber->getFraudRulesNames(), []);

  }

}
