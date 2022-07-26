<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the fraud rule.
 *
 * @CommerceFraudRule(
 *   id = "check_user_ip",
 *   label = @Translation("Check If user has completed orders with different IP addresses"),
 * )
 */
class CheckUserIpFraudRule extends FraudRuleBase {

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $database;

  /**
   * Constructs a new Check User Ip object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = \Drupal::database();

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
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
    $customer_ip = $order->getIpAddress();

    $orders_count = $this->database->select('commerce_order', 'o')
      ->fields('o', ['ip_address'])
      ->condition('uid', $order->getCustomerId(), '=')
      ->condition('ip_address', [$customer_ip], 'NOT IN')
      ->distinct()
      ->countQuery()
      ->execute()
      ->fetchField();

    return (bool) $orders_count;
  }

}
