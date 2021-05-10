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

  public function render() {
    $this->messenger()->addWarning($this->t(
      '<strong>Hint:</strong> If styles or scripts are missing that you ' .
      'need for the cookie banner to work properly then the reason could be ' .
      'the way <em>loom_cookie_compliance</em> renders the banner template. In ' .
      'that case you will have to attach the missing styles or scripts as ' .
      'libraries to the site\'s theme.'
    ));
    return parent::render();
  }
}
