<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\commerce\ConditionGroup;
use Drupal\commerce\ConditionManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the infinite order number generator.
 *
 * @CommerceFraudRule(
 *   id = "product_attribute",
 *   label = @Translation("Check Product Attribute"),
 *   description = @Translation("Checks Product Attribute"),
 * )
 */
class ProductAttributeFraudRule extends FraudRuleBase {

  /**
   * The condition manager.
   *
   * @var \Drupal\commerce\ConditionManagerInterface
   */
  protected $conditionManager;

  /**
   * Constructs a new Total Quantity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce\ConditionManagerInterface $condition_manager
   *   The condition manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionManagerInterface $condition_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->conditionManager = $condition_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'product_conditions' => [],
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

    $form['product'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product Attributes'),
      '#collapsible' => FALSE,
    ];
    $form['product']['conditions'] = [
      '#type' => 'commerce_conditions',
      '#title' => $this->t('Applies to'),
      '#parent_entity_type' => 'rules',
      '#entity_types' => ['commerce_order_item'],
      '#default_value' => $this->configuration['product_conditions'],
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
      $this->configuration['product_conditions'] = $values['product']['conditions'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $order_items = $order->getItems();

    $product_conditions = $this->buildConditionGroup($this->configuration['product_conditions']);

    $applied = $this->evaluateConditions($order_items, $product_conditions);

    return $applied;
  }

  /**
   * Builds a condition group for the given condition configuration.
   *
   * @param array $condition_configuration
   *   The condition configuration.
   *
   * @return \Drupal\commerce\ConditionGroup
   *   The condition group.
   */
  protected function buildConditionGroup(array $condition_configuration) {
    $conditions = [];
    foreach ($condition_configuration as $condition) {
      if (!empty($condition['plugin'])) {
        $conditions[] = $this->conditionManager->createInstance($condition['plugin'], $condition['configuration']);
      }
    }

    return new ConditionGroup($conditions, 'OR');
  }

  /**
   * Evaluate conditions for each order item.
   *
   * Returns bool as per condition evaluation.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The order items.
   * @param \Drupal\commerce\ConditionGroup $conditions
   *   The conditions.
   *
   * @return bool
   *   Conditions apply condition.
   */
  protected function evaluateConditions(array $order_items, ConditionGroup $conditions) {

    foreach ($order_items as $order_item) {
      if ($conditions->evaluate($order_item)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
