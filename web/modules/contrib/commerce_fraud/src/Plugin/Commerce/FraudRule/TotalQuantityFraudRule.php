<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the total quantity fraud rule.
 *
 * @CommerceFraudRule(
 *   id = "total_quantity",
 *   label = @Translation("Compare total quantity with given quantity"),
 * )
 */
class TotalQuantityFraudRule extends FraudRuleBase {

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
      'buy_quantity' => 10,
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
      '#title' => $this->t('Quantity limit'),
      '#collapsible' => FALSE,
    ];
    $form['buy']['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#default_value' => $this->configuration['buy_quantity'],
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
      $this->configuration['buy_quantity'] = $values['buy']['quantity'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $order_item = $order->getItems();
    $quantity = 0;
    foreach ($order_item as $item) {
      $quantity += number_format($item->getQuantity());
    }

    if ($quantity > $this->configuration['buy_quantity']) {

      return TRUE;
    }
    return FALSE;
  }

}
