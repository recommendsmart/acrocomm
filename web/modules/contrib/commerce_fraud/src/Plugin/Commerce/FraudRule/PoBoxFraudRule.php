<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the fraud rule.
 *
 * @CommerceFraudRule(
 *   id = "po_box",
 *   label = @Translation("Check if order address have Po Box"),
 *   description = @Translation("Checks Order Address for Po Box"),
 * )
 */
class PoBoxFraudRule extends FraudRuleBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

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

    $order_address = $order->getBillingProfile()->get('address');

    return $this->containsPoBox($order_address->address_line1) || $this->containsPoBox($order_address->address_line2);
  }

  /**
   * Checks the string for PO Box.
   *
   * @param string $address
   *   The address string.
   *
   * @return bool
   *   Bool as per preg match.
   */
  public function containsPoBox(string $address) {
    return preg_match("/\s*((?:P(?:OST)?.?\s*(?:O(?:FF(?:ICE)?)?)?.?\s*(?:B(?:IN|OX)?)?)+|(?:B(?:IN|OX)+\s+)+)\s*\d+\s*(^|\s|$)/i", $address);
  }

}
