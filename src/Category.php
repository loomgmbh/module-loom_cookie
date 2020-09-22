<?php

class Category {

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

  public $weight = 0;

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
   * @var string
   */
  public $cookies = '';

  /**
   * Regexes for script urls to be filtered.
   *
   * @var string
   */
  public $scriptUrlRegexes = '';

  /**
   * Regexes for script blocks to be filtered.
   *
   * @var string
   */
  public $scriptBlockRegexes = '';

  /**
   * Regexes for script urls to be filtered by JS.
   *
   * @var string
   */
  public $scriptUrlRegexesClientSide = '';

  /**
   * Regexes for embed urls to be filtered.
   *
   * @var string
   */
  public $embedUrlRegexes = '';

  public $embedMessage;

  public $isNew = TRUE;

  public function __construct($values = []) {
    $fields = [
      'label',
      'id',
      'weight',
      'description',
      'detailedDescription',
      'cookies',
      'scriptUrlRegexes',
      'scriptBlockRegexes',
      'scriptUrlRegexesClientSide',
      'embedUrlRegexes',
      'embedMessage',
    ];
    foreach ($fields as $field) {
      if (!empty($values[$field])) {
        $this->$field = $values[$field];
      }
    }
  }

  public static function load($id) {
    variable_initialize();

    $variable_name = 'loom_cookie_category__' . $id;

    $values = variable_get($variable_name);
    if (!empty($values)) {
      $category = new self($values);
      $category->isNew = FALSE;
      $category->id = $id;
      return $category;
    }

    return NULL;
  }

  public static function updateEUCookieComplianceSettings() {
    $categories = loom_cookie_get_categories();

    $cookie_categories = [];
    $whitelisted_cookies = [];

    foreach ($categories as $category) {
      $category_id = $category->id();

      $cookie_categories[] = $category_id . '|' . $category->label;

      $cookies = loom_cookie_multiline_split($category->cookies);
      foreach ($cookies as $cookie) {
        $whitelisted_cookies[] = $category_id . ':' . $cookie;
      }
    }

    loom_cookie_set_ecc_settings([
      'whitelisted_cookies' => implode("\r\n", $whitelisted_cookies),
      'cookie_categories' => implode("\r\n", $cookie_categories),
    ]);

    drupal_flush_all_caches();
  }

  public function label() {
    return $this->label;
  }

  public function isNew() {
    return $this->isNew;
  }

  public function id() {
    return $this->id;
  }

  public function set($key, $value) {
    $variable_name = $this->variableName();
    foreach (i18n_language_list() as $langcode => $lang) {
      $variable = variable_realm_get('language', $langcode, $variable_name);
      $variable[$key] = $value;
      variable_realm_set('language', $langcode, $variable_name, $variable, FALSE);
    }
  }

  public function delete() {
    $variable_name = $this->variableName();
    variable_del($variable_name);
    foreach (i18n_language_list() as $langcode => $lang) {
      variable_realm_del('language', $langcode, $variable_name);
    }

    self::updateEUCookieComplianceSettings();

    // delete entry from i18n list
    $variable_realm_list_language = variable_get('variable_realm_list_language');
    if (is_array($variable_realm_list_language)) {
      foreach ($variable_realm_list_language as $key => $value) {
        if ($value == $variable_name) {
          unset($variable_realm_list_language[$key]);
        }
      }
      variable_set('variable_realm_list_language', $variable_realm_list_language);
    }

    drupal_flush_all_caches();
  }

  private function variableName() {
    return 'loom_cookie_category__' . $this->id;
  }
}
