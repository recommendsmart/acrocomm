<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;

/**
 * Provides the total price fraud rule.
 *
 * @CommerceFraudRule(
 *   id = "total_price",
 *   label = @Translation("Compare total price with given price"),
 * )
 */
class TotalPriceFraudRule extends FraudRuleBase {

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
  public function defaultConfiguration() {
    return [
      'buy_amount' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);
    $form['#type'] = 'container';
    $form['#title'] = $this->t('Rule');
    $form['#collapsible'] = FALSE;

    $form['buy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Price limit'),
      '#collapsible' => FALSE,
    ];
    $form['buy']['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Price'),
      '#default_value' => $this->configuration['buy_amount'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['buy_amount'] = $values['buy']['amount'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $order_price = $order->getTotalPrice();

    $price = $this->configuration['buy_amount'];

    // If buy amount not set.
    if (!$price) {
      return FALSE;
    }

    $new_price = new Price($price['number'], $price['currency_code']);

    return $order_price->greaterThan($new_price);
  }

}
