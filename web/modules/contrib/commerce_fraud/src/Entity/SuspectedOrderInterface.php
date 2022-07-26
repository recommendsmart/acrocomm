<?php

namespace Drupal\commerce_fraud\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Suspected order entities.
 *
 * @ingroup commerce_fraud
 */
interface SuspectedOrderInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Suspected order Order ID.
   *
   * @return int
   *   Order ID of the Suspected order.
   */
  public function getOrderId();

  /**
   * Sets order id of Suspected order .
   *
   * @param int $orderId
   *   Order id of suspected order.
   *
   * @return $this
   */
  public function setOrderId($orderId);

  /**
   * Gets the Suspected order creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Suspected order.
   */
  public function getCreatedTime();

  /**
   * Sets the Suspected order creation timestamp.
   *
   * @param int $timestamp
   *   The Suspected order creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets list of rules applied to suspected order.
   *
   * @return array
   *   List of rules applied to suspected order.
   */
  public function getRules();

  /**
   * Sets the list of rules applicable to suspected order.
   *
   * @param array|\Drupal\commerce_order\Entity\RulesInterface $rule
   *   List of rules applicable to suspected order.
   *
   * @return $this
   */
  public function setRules($rule);

  /**
   * Appends a rule to suspected order rules.
   *
   * @param \Drupal\commerce_order\Entity\RulesInterface $rule
   *   New rule applicable to suspected order.
   *
   * @return $this
   */
  public function addRule(RulesInterface $rule);

  /**
   * Checks whether the suspected order has a given rule.
   *
   * @param \Drupal\commerce_order\Entity\RulesInterface $rule
   *   Rule to check if is applied to suspected order.
   *
   * @return bool
   *   TRUE if the rule was found, FALSE otherwise.
   */
  public function hasRule(RulesInterface $rule);

  /**
   * Calculats and returns the score.
   *
   * @return int
   *   Total fraud score of suspected order.
   */
  public function getScore();

}
