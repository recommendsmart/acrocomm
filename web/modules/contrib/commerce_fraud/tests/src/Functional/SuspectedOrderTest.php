<?php

namespace Drupal\Tests\commerce_fraud\Functional;

use Drupal\Core\Url;
use Drupal\commerce_fraud\Entity\Rules;
use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

/**
 * Tests the Suspected Order entity.
 *
 * @group commerce_fraud
 */
class SuspectedOrderTest extends OrderBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_fraud',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer suspected order entities',
      'add suspected order entities',
      'view suspected order entities',
      'edit suspected order entities',
      'delete suspected order entities',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
      'order_items' => [],
    ]);
    $this->order->save();

    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

    $rule = Rules::create([
      'id' => 'example',
      'label' => 'ANONYMOUS',
      'status' => TRUE,
      'plugin' => 'anonymous_user',
      'score' => 9,
    ]);
    $rule->save();

  }

  /**
   * Tests the addition of the Suspected Order only  when unique.
   */
  public function testSuspectedOrder() {

    // Load the add suspected order page.
    $this->drupalGet(Url::fromRoute('entity.suspected_order.add_form'));

    // Check that the page was generated.
    $this->assertSession()->pageTextContains('Order ID');
    $this->assertSession()->pageTextContains('Fraud Rules');
    $this->assertSession()->pageTextContains('The Order ID for the Suspected order entity. ');

    $this->assertSession()->fieldExists("order_id[0][target_id]");
    $this->assertSession()->fieldExists("rules[0][target_id]");

    // Auto complete valid.
    $edit = [
      'order_id[0][target_id]' => $this->order->getOrderNumber(),
      'rules[0][target_id]' => 'ANONYMOUS',
    ];

    $this->submitForm($edit, 'Save');

    // Suspected order created.
    $this->assertSession()->pageTextContains("Created the " . $this->order->id() . " Suspected order.");

    // Editing is only of suspected order.
    $this->drupalGet('/admin/commerce/config/suspected_order/' . $this->order->id() . '/edit');

    $this->submitForm([], 'Save');

    $this->assertSession()->pageTextContains("Saved the " . $this->order->id() . " Suspected order.");

    $this->drupalGet(Url::fromRoute('entity.suspected_order.add_form'));

    $edit = [
      'order_id[0][target_id]' => $this->order->id(),
      'rules[0][target_id]' => 'ANONYMOUS',
    ];

    $this->submitForm($edit, 'Save');

    // Only unique suspected order to save.
    $this->assertSession()->pageTextNotContains("Saved the " . $this->order->id() . " Suspected order.");

  }

  /**
   * Tests the editing of the Suspected Order.
   */
  public function testSuspectedOrderEdit() {

    // Create suspected order.
    $this->drupalGet(Url::fromRoute('entity.suspected_order.add_form'));

    $edit = [
      'order_id[0][target_id]' => $this->order->getOrderNumber(),
      'rules[0][target_id]' => 'ANONYMOUS',
    ];

    $this->submitForm($edit, 'Save');

    // Editing is only of suspected order.
    $this->drupalGet('/admin/commerce/config/suspected_order/' . $this->order->id() . '/edit');

    // Addition of another rule.
    $this->getSession()->getPage()->pressButton('Add another item');

    $rule = Rules::create([
      'id' => 'example2',
      'label' => 'Check User IP',
      'status' => TRUE,
      'plugin' => 'check_user_ip',
      'score' => 9,
    ]);
    $rule->save();

    $edit = [
      'rules[1][target_id]' => 'Check User IP',
    ];

    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains("Saved the " . $this->order->id() . " Suspected order.");

  }

  /**
   * Tests deleting of Suspected Order via the admin.
   */
  public function testSuspectedOrderDelete() {
    $suspecteOrder = $this->createEntity('suspected_order', [
      'order_id' => $this->order->getOrderNumber(),
    ]);
    $this->drupalGet('admin/commerce/config/suspected_order/' . $suspecteOrder->id() . '/delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the suspected order " . $suspecteOrder->label());
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Delete');

    $this->assertSession()->pageTextContains("The suspected order " . $this->order->getOrderNumber() . " has been deleted.");
  }

}
