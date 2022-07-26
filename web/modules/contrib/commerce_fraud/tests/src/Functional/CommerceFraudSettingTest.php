<?php

namespace Drupal\Tests\commerce_fraud\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the commerce fraud settings and settings page.
 *
 * @group commerce
 */
class CommerceFraudSettingTest extends CommerceBrowserTestBase {

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
      'administer site configuration',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests Commerce Fraud settings page.
   */
  public function testCommerceFraudSetting() {
    $this->drupalGet('/admin/commerce/config/commerce_fraud/configuration');

    $this->assertSession()->pageTextContains('Commerce Fraud Caps Settings');
    $this->assertSession()->pageTextContains('Activate this to stop blocklisted orders from being completed. Warning, this may cause lost orders if enabled.');
    $this->assertSession()->pageTextContains('Email');
    $this->assertSession()->checkboxNotChecked('stop_order');

    $edit = [
      'checklist_cap' => 20,
      'blocklist_cap' => 15,
    ];

    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('Check List value cannot be equal to or more than Block List value');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    $edit = [
      'checklist_cap' => 20,
      'blocklist_cap' => 15,
    ];

    $this->submitForm($edit, 'Save configuration');
  }

}
