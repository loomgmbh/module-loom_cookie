<?php /** @noinspection PhpUnused */

/**
 * @file
 * This file provides administration form for the module.
 */

require_once __DIR__ . '/../src/Category.php';

/**
 * @param array $form
 *   Form array.
 * @param array $form_state
 *   For state array.
 *
 * @return array
 *   Admin form render array.
 */
function loom_cookie_categories_export_form(array $form, array $form_state) {
  $form = [];

  $form['headline'] = [
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => 'Kategorien',
  ];

  $form['description'] = [
    '#type' => 'container',
    0 => [
      '#markup' => 'Use the following code to initialize the categories via ' .
        'settings.php.<br/><br/>',
    ],
  ];

  $categories = array_values(loom_cookie_get_categories(FALSE));

  $translatable_fields = [
    'label',
    'description',
    'detailedDescription',
  ];

  foreach ($categories as &$category) {
    $variable_name = 'loom_cookie_category__' . $category['id'];

    foreach ($translatable_fields as $field) {
      unset($category[$field]);
    }

    $category['translations'] = [];
    foreach (i18n_language_list() as $langcode => $lang) {
      $translation = variable_realm_get('language', $langcode, $variable_name);
      if (!$translation) {
        continue;
      }

      $category['translations'][$langcode] = [];
      foreach ($translatable_fields as $field) {
        if (!empty($translation[$field])) {
          $category['translations'][$langcode][$field] =
            str_replace("\n", '\n',
              $translation[$field]);
        }
      }
    }

    foreach ($category as $field => &$value) {
      $value = str_replace("\n", '\n', $value);
    }
  }

  $export = preg_replace("%=> '(.*)',\n%", "=> \"$1\",\n",
    str_replace('\\\\', '\\',
      var_export($categories, TRUE)));

  $form['export'] = [
      '#type' => 'html_tag',
      '#tag' => 'textarea',
      '#value' => '$conf[\'loom_cookie_predefined_categories\'] = ' . $export . ';',
      '#attributes' => [
        'cols' => 120,
        'rows' => 20,
        'readonly' => TRUE,
      ],
  ];

  return $form;
}
