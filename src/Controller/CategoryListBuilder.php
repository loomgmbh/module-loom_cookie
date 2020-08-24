<?php

namespace Drupal\loom_cookie\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Catetories.
 */
class CategoryListBuilder extends DraggableListBuilder {

  protected $weightKey = 'weight';

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['label'] = $this->t('Category');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = ['#markup' => $entity->id()];
    $row['label'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

  public function getFormId() {
    return 'loom_cookie_category_list';
  }
}
