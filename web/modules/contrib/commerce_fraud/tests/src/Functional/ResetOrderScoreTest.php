<?php

namespace Drupal\Tests\commerce_fraud\Functional;

use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

/**
 * Tests the reset order forms.
 *
 * @group commerce
 */
class ResetOrderScoreTest extends OrderBrowserTestBase {

  /**
   * A test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritDoc}
   */
  public static $modules = [
    'commerce_fraud',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer profile',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $this->order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser->id(),
      'order_items' => [$order_item],
      'store_id' => $this->store,
    ]);

  }

  /**
   * Tests resetting an orders fraud score through the UI.
   */
  public function testResetOrder() {

    $this->drupalGet('/admin/commerce/orders/' . $this->order->id() . '/reset_fraud');
    $this->assertSession()->pageTextContains("Do you want to reset order fraud score for order {$this->order->id()}?");
    $this->assertSession()->pageTextContains("Reset this orders fraud score to 0");
    $this->submitForm([], 'Reset Fraud Score');
    $collection_url = $this->order->toUrl('collection', ['absolute' => TRUE]);
    $this->assertSession()->addressEquals($collection_url);
    $this->assertSession()->pageTextContains('The orders score has been reset.');

  }

}
