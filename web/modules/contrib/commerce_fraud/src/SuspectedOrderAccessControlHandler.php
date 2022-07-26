<?php

namespace Drupal\commerce_fraud;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Suspected order entity.
 *
 * @see \Drupal\commerce_fraud\Entity\SuspectedOrder.
 */
class SuspectedOrderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_fraud\Entity\SuspectedOrderInterface $entity */

    switch ($operation) {

      case 'view':

        return AccessResult::allowedIfHasPermission($account, 'view suspected order entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit suspected order entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete suspected order entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add suspected order entities');
  }

}
