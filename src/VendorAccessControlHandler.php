<?php

namespace Drupal\loom_cookie;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the "Vendor" entity type.
 *
 * @see \Drupal\loom_cookie\Entity\Vendor
 */
class VendorAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view label') {
      return AccessResult::allowedIfHasPermission($account, 'administer loom cookie popup');
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
