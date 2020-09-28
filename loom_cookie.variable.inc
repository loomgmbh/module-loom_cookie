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
  loom_cookie_import_predefined_settings();

  $variable_realm_list_language = variable_get('variable_realm_list_language', []);

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

function loom_cookie_import_predefined_settings() {
  $default_langcode = language_default('language');

  // insert predefined ecc settings
  $predefined_ecc_settings = variable_get('loom_cookie_predefined_ecc_settings', []);
  if (count($predefined_ecc_settings)) {
    foreach ($predefined_ecc_settings as $variable_name => $item) {
      $variable = variable_get($variable_name);
      if (!$variable) {
        continue;
      }

      if (!empty($item['translations'])) {
        $translations = $item['translations'];
        unset($item['translations']);
      }
      else {
        $translations = [];
      }

      foreach ($item as $key => $value) {
        $variable[$key] = $value;
      }

      variable_set($variable_name, $variable);
      $variable = variable_realm_get('language', $default_langcode, $variable_name, []);
      foreach ($item as $key => $value) {
        $variable[$key] = $value;
      }
      variable_realm_set('language', $default_langcode, $variable_name,
        $variable);

      foreach ($translations as $langcode => $translation) {
        $variable = variable_realm_get('language', $langcode, $variable_name, []);

        foreach ($item as $key => $value) {
          $variable[$key] = $value;
        }
        foreach ($translation as $key => $value) {
          $variable[$key] = $value;
        }

        variable_realm_set('language', $langcode, $variable_name,
          $variable);
      }
    }
  }

  // insert predefined categories
  $predefined_categories = variable_get('loom_cookie_predefined_categories', []);
  $override_categories = variable_get('loom_cookie_override_categories', FALSE);

  foreach ($predefined_categories as $item) {
    if (empty($item['id'])) {
      continue;
    }

    $variable_name = 'loom_cookie_category__' . $item['id'];

    if (!$override_categories && variable_get($variable_name) != NULL) {
      continue;
    }

    if (!empty($item['translations'])) {
      $translations = $item['translations'];
      unset($item['translations']);
    }
    else {
      $translations = [];
    }

    if (!empty($translations)) {
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
}
