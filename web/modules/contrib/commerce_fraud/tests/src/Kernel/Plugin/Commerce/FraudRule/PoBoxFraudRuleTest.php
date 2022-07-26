<?php

namespace Drupal\Tests\commerce_fraud\Kernel\Plugin\Commerce\FraudRule;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * Tests the commerce fraud rule plugin.
 *
 * @coversDefaultClass \Drupal\commerce_fraud\Plugin\Commerce\FraudRule\PoBoxFraudRule
 *
 * @group commerce
 */
class PoBoxFraudRuleTest extends OrderKernelTestBase {

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
      'label' => 'PO Box',
      'status' => TRUE,
      'plugin' => 'po_box',
      'score' => 9,
    ]);

    $this->rule->save();
  }

  /**
   * Tests Po Box rule.
   *
   * @covers ::apply
   */
  public function testPoBoxRule() {

    // non-applicable use case.
    $billing_profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Not applicable Po Box rule',
        'address_line2' => 'Address line 2',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
    ]);
    $billing_profile->save();

    $billing_profile = $this->reloadEntity($billing_profile);
    $this->order->setBillingProfile($billing_profile);
    $this->order->save();
    $this->assertEquals(FALSE, $this->rule->getPlugin()->apply($this->order));

    // Applicable use case.
    $billing_profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Applicable Po Box 123 rule',
        'address_line2' => 'Address line 2',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
    ]);
    $billing_profile->save();

    $billing_profile = $this->reloadEntity($billing_profile);
    $this->order->setBillingProfile($billing_profile);
    $this->order->save();
    $this->assertEquals(TRUE, $this->rule->getPlugin()->apply($this->order));

  }

}
