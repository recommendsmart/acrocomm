<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the last minute fraud rule.
 *
 * @CommerceFraudRule(
 *   id = "last_minute",
 *   label = @Translation("Compare Last minutes of order completion with given minutes"),
 * )
 */
class LastMinuteFraudRule extends FraudRuleBase {

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $database;

  /**
   * Constructs a new Last Minute object.
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
  public function defaultConfiguration() {
    return [
      'last_minute' => 5,
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
    // Remove the main fieldset.
    $form['time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time limit'),
      '#collapsible' => FALSE,
    ];
    $form['time']['last_minute'] = [
      '#type' => 'number',
      '#title' => $this->t('Last Minute'),
      '#default_value' => $this->configuration['last_minute'],
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
      $this->configuration['last_minute'] = $values['time']['last_minute'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $customer_id = $order->getCustomerId();

    $query = $this->database->select('commerce_order', 'o')
      ->fields('o', ['order_id'])
      ->condition('uid', $customer_id, '=')
      ->condition('state', ['completed'], 'IN')
      ->condition('completed', $this->timestampFromMinutes($this->configuration['last_minute']), '>=');

    return !empty($query->execute()->fetchAssoc());
  }

  /**
   * Returns a timestamp matching x minutes before current time.
   *
   * @param int $minutes
   *   Minute to get time before.
   *
   * @return int
   *   Timestamp.
   */
  public function timestampFromMinutes($minutes) {
    $date = new \DateTimeImmutable();
    $date = $date->modify('- ' . $minutes . ' minutes');
    return $date->getTimestamp();
  }

}
