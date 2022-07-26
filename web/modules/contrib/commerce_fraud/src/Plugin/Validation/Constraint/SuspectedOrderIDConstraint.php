<?php

namespace Drupal\commerce_fraud\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures order id uniqueness.
 *
 * @Constraint(
 *   id = "SuspectedOrderID",
 *   label = @Translation("Suspected Order ID", context = "Validation")
 * )
 */
class SuspectedOrderIDConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $notUnique = 'The order id %value is already in use and must be unique.';

}
