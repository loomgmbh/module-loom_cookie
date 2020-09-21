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
