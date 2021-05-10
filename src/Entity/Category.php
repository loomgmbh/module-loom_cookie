<?php

namespace Drupal\loom_cookie\Entity;

use Drupal;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\loom_cookie\CategoryInterface;

/**
 * Defines the Category entity.
 *
 * @ConfigEntityType(
 *   id = "loom_cookie_category",
 *   label = @Translation("Category"),
 *   handlers = {
 *     "list_builder" = "Drupal\loom_cookie\Controller\CategoryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\loom_cookie\Form\CategoryForm",
 *       "edit" = "Drupal\loom_cookie\Form\CategoryForm",
 *       "delete" = "Drupal\loom_cookie\Form\CategoryDeleteForm",
 *     }
 *   },
 *   config_prefix = "category",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "langcode",
 *     "status",
 *     "dependencies",
 *     "weight",
 *     "cookies",
 *     "attachmentNames",
 *     "scriptUrlRegexes",
 *     "scriptBlockRegexes",
 *     "scriptUrlRegexesClientSide",
 *     "embedUrlRegexes",
 *     "embedMessage",
 *     "description",
 *     "detailedDescription",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/eu-cookie-compliance/loom-cookie/categories/{loom_cookie_category}",
 *     "delete-form" = "/admin/config/system/eu-cookie-compliance/loom-cookie/categories/{loom_cookie_category}/delete",
 *   }
 * )
 */
class Category extends ConfigEntityBase implements CategoryInterface {

  /**
   * The Category ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Category label.
   *
   * @var string
   */
  public $label;

  public $weight;

  /**
   * @var array
   */
  public $description = ['value' => ''];

  /**
   * @var array
   */
  public $detailedDescription = ['value' => ''];

  /**
   * Names of cookies to be filtered.
   *
   * @var array
   */
  public $cookies = [];

  /**
   * Names of attachments to be filtered.
   *
   * @var array
   */
  public $attachmentNames = [];

  /**
   * Regexes for script urls to be filtered.
   *
   * @var array
   */
  public $scriptUrlRegexes = [];

  /**
   * Regexes for script blocks to be filtered.
   *
   * @var array
   */
  public $scriptBlockRegexes = [];

  /**
   * Regexes for script urls to be filtered by JS.
   *
   * @var array
   */
  public $scriptUrlRegexesClientSide = [];

  /**
   * Regexes for embed urls to be filtered.
   *
   * @var array
   */
  public $embedUrlRegexes = [];

  public $embedMessage = '';

  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $ids = Drupal::entityQuery('loom_cookie_category')
      ->sort('weight', 'DESC')
      ->range(0, 1)
      ->execute();

    if (!empty($ids)) {
      /** @var self $category_with_highest_weight */
      $category_with_highest_weight = self::load(current($ids));
      $values['weight'] = $category_with_highest_weight->weight + 1;
    }

    return parent::preCreate($storage, $values);
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    self::updateEUCookieComplianceSettings();

    return parent::postSave($storage, $update);
  }

  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    self::updateEUCookieComplianceSettings();

    parent::postDelete($storage, $entities);
  }

  private static function updateEUCookieComplianceSettings() {
    // update LOOM Cookie Compliance settings
    $category_ids = Drupal::entityQuery('loom_cookie_category')
      ->sort('weight')
      ->execute();

    /** @var CategoryInterface[] $categories */
    $categories = Drupal::entityTypeManager()
      ->getStorage('loom_cookie_category')
      ->loadMultiple($category_ids);

    $cookie_categories = [];
    $whitelisted_cookies = [];

    foreach ($categories as $category) {
      $category_id = $category->id();

      $cookie_categories[] = $category_id . '|' . $category->label;

      foreach ($category->cookies as $cookie) {
        $whitelisted_cookies[] = $category_id . ':' . $cookie;
      }
    }

    Drupal::configFactory()
      ->getEditable('loom_cookie_compliance.settings')
      ->set('whitelisted_cookies', implode("\r\n", $whitelisted_cookies))
      ->set('cookie_categories', implode("\r\n", $cookie_categories))
      ->save();
  }

}
