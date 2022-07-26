<?php

namespace Drupal\commerce_fraud\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the fraud rule plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\FraudRuleBase.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceFraudRule extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The rule entity type ID.
   *
   * @var string
   */
  public $entity_type;

}
