<?php

namespace Drupal\loom_cookie\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Entity reference selection.
 *
 * @EntityReferenceSelection(
 *   id = "default:vendor",
 *   label = @Translation("Vendor selection"),
 *   entity_types = {"media"},
 *   group = "default",
 *   weight = 1
 * )
 */
class VendorSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // filter for non empty fields
    $fields = $this->configuration['fields'] ?? [];
    if ($fields) {
      $and = $query->andConditionGroup();
      foreach ($fields as $field) {
        $or = $query->orConditionGroup();
        $or->exists($field);
        $and->condition($or);
      }
      $query->condition($and);
    }

    return $query;
  }

}
