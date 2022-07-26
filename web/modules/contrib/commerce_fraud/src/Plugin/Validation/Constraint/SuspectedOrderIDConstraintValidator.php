<?php

namespace Drupal\commerce_fraud\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SuspectedOrderID constraint.
 */
class SuspectedOrderIDConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    if (!$item = $items->first()) {
      return;
    }
    $order_id = $item->target_id;

    $suspectedOrder = \Drupal::entityTypeManager()->getStorage('suspected_order')->loadByProperties(['order_id' => $order_id]);
    $suspectedOrder = reset($suspectedOrder);
    if (empty($suspectedOrder)) {
      return;
    }
    if ($item->getEntity()->id() == $suspectedOrder->id()) {
      return;
    }

    $this->context->addViolation($constraint->notUnique, ['%value' => $order_id]);
  }

}
