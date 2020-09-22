<?php /** @noinspection PhpUnused */

/**
 * @file
 * Configuration for translations.
 */

/**
 * Implements hook_variable_info().
 *
 * @param $options
 *
 * @return array
 */
function loom_cookie_variable_info($options) {
  // insert predefined categories
  $predefined_categories = variable_get('loom_cookie_predefined_categories', []);

  foreach ($predefined_categories as $item) {
    if (empty($item['id'])) {
      continue;
    }

    if (!empty($item['translations'])) {
      $translations = $item['translations'];
      unset($item['translations']);
    }
    else {
      $translations = [];
    }

    $variable_name = 'loom_cookie_category__' . $item['id'];

    if (!empty($translations)) {
      $default_langcode = language_default('language');
      if (!empty($translations[$default_langcode])) {
        $item = array_merge($item, $translations[$default_langcode]);
      }
    }

    variable_set($variable_name, $item);

    foreach ($translations as $langcode => $translation) {
      variable_realm_set('language', $langcode, $variable_name,
        array_merge($item, $translation));
    }
  }

  variable_realm_rebuild();
  Category::updateEUCookieComplianceSettings();

  $variable_realm_list_language = variable_get('variable_realm_list_language');

  $categories = loom_cookie_get_categories();

  $variables = [];

  foreach ($categories as $category) {
    $key = 'loom_cookie_category__' . $category->id;
    $variables[$key] = [
      'title' => t('LOOM Cookie: Category: ' . $category->label),
      'description' => t('LOOM Cookie: Category'),
      'type' => 'array',
      'access' => 'administer EU Cookie Compliance popup',
      'localize' => TRUE,
      'multidomain' => TRUE,
    ];

    $variable_realm_list_language[] = $key;
  }

  $variable_realm_list_language = array_unique($variable_realm_list_language);
  variable_set('variable_realm_list_language', $variable_realm_list_language);

  return $variables;
}
