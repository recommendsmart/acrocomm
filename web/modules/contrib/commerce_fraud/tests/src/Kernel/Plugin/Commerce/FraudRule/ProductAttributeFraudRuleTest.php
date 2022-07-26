<?php

namespace Drupal\Tests\commerce_fraud\Kernel\Plugin\Commerce\FraudRule;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the commerce fraud rule plugin.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\Plugin\Commerce\FraudRule\ProductAttributeFraudRule
 *
 * @group commerce
 */
class ProductAttributeFraudRuleTest extends OrderKernelTestBase {

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
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variations = [];

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

    $this->orderItemStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');

    for ($i = 0; $i < 2; $i++) {
      $this->variations[$i] = ProductVariation::create([
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => Calculator::multiply('10', $i + 1),
          'currency_code' => 'USD',
        ],
      ]);
      $this->variations[$i]->save();
    }

    $first_product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variations[0]],
    ]);
    $first_product->save();

    $second_product = Product::create([
      'type' => 'test',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variations[1]],
    ]);
    $second_product->save();

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
      'label' => 'Product Attribute',
      'status' => TRUE,
      'plugin' => 'product_attribute',
      'configuration' => [
        'product_conditions' => [
              [
                'plugin' => 'order_item_product_type',
                'configuration' => [
                  'product_types' => ['test'],
                ],
              ],
        ],
      ],
      'score' => 9,
    ]);

    $this->rule->save();

  }

  /**
   * Tests the product attribute rule.
   *
   * @covers ::apply
   */
  public function testProductAttributeRule() {

    // non-applicable use case.
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      'quantity' => '2',
    ]);
    $order_item->save();

    $this->order->addItem($order_item);
    $this->order->save();
    $this->assertEquals(FALSE, $this->rule->getPlugin()->apply($this->order));

    // Applicable use case.
    $this->order->removeItem($order_item);
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($this->variations[1], [
      'quantity' => '2',
    ]);

    $order_item->save();

    $this->order->addItem($order_item);
    $this->order->save();
    $this->assertEquals(TRUE, $this->rule->getPlugin()->apply($this->order));

  }

}
