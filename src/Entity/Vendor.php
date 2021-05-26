<?php

namespace Drupal\loom_cookie\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the loom_cookie_vendor entity.
 *
 * @ContentEntityType(
 *   id = "loom_cookie_vendor",
 *   label = @Translation("Vendor"),
 *   base_table = "loom_cookie_vendor",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   handlers = {
 *     "access" = "Drupal\loom_cookie\VendorAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 * )
 */
class Vendor extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the vendor.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the vendor was last edited.'))
      ->setRequired(TRUE);

    $fields['attachment_names'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Attachment names'))
      ->setDescription(t('Drupal attachment names, which should be blocked.'));

    $fields['script_url_regexes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Script-URL-Regexes'))
      ->setDescription(t('Regexes to identify script URLs, which should be blocked.'));

    $fields['embed_url_regexes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Embed-URL-Regexes'))
      ->setDescription(t('Regexes to identify embed URLs, which should be blocked.'));

    return $fields;
  }

}
