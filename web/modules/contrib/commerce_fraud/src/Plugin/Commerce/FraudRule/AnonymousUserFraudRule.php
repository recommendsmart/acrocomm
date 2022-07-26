<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the fraud rule.
 *
 * @CommerceFraudRule(
 *   id = "anonymous_user",
 *   label = @Translation("Check if order by Anonymous User"),
 *   description = @Translation("Checks if Order by Anonymous User"),
 * )
 */
class AnonymousUserFraudRule extends FraudRuleBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $customer = $order->getCustomer();

    if ($customer->isAnonymous()) {
      return TRUE;
    }
    return FALSE;
  }

}
