<?php

namespace Drupal\commerce_fraud\Plugin\Commerce\FraudRule;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for fraud rules.
 */
interface FraudRuleInterface extends ConfigurableInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the fraud rule label.
   *
   * @return string
   *   The fraud rule label.
   */
  public function getLabel();

  /**
   * Gets the fraud rule description.
   *
   * @return string
   *   The fraud rule description.
   */
  public function getDescription();

  /**
   * Gets the rule entity type ID.
   *
   * This is the entity type ID of the entity passed to apply().
   *
   * @return string
   *   The offer's entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Applies the rule to the given entity.
   *
   * @param \Drupal\Core\Entity\OrderInterface $entity
   *   The entity.
   */
  public function apply(OrderInterface $entity);

}
