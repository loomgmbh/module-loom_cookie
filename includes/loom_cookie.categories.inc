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
function loom_cookie_categories_form(array $form, array $form_state) {
  $form = [];

  $form['headline'] = [
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => 'Kategorien',
  ];

  $categories = loom_cookie_get_categories();

  $base_url = 'admin/config/system/eu-cookie-compliance/loom-cookie/categories';

  $rows = array_map(function ($category, $n) use ($base_url) {
    $id_element = [
      '#type' => 'hidden',
      '#value' => $category->id,
      '#name' => 'id[]',
    ];

    $weight_element = [
      '#type' => 'textfield',
      '#title' => t('Weight'),
      '#title_display' => 'invisible',
      '#default_value' => $n,
      '#value' => $category->weight,
      '#attributes' => ['class' => ['category-weight']],
      '#size' => 3,
      '#name' => 'weight[]',
    ];

    return [
      'data' => [
        ['class' => ['slide-cross']],
        drupal_render($id_element),
        drupal_render($weight_element),
        $category->id,
        $category->label,
        l(t('edit'), "$base_url/$category->id/edit"),
        l(t('delete'), "$base_url/$category->id/delete"),
      ],
      'class' => ['draggable'],
    ];
  }, $categories, array_keys($categories));

  $form['categories'] = [
    '#theme' => 'table',
    '#header' => [
      ['data' => '', 'colspan' => 2],
      'weight',
      'Systemname',
      'Kategorie',
      ['data' => t('Aktionen'), 'colspan' => 2],
    ],
    '#rows' => $rows,
    '#attributes' => [
      'id' => 'loom-cookie-categories-table',
    ],
  ];

  drupal_add_tabledrag('loom-cookie-categories-table', 'order',
    'sibling', 'category-weight');

  $form['actions'] = ['#type' => 'actions'];
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save'),
  ];

  $form['#submit'][] = 'loom_cookie_categories_form_submit';
  return $form;
}

/**
 * @param array $form
 *   Form array.
 * @param array $form_state
 *   Form state array.
 */
function loom_cookie_categories_form_submit(array $form, array &$form_state) {
  if (empty($form_state['input']['id']) && empty($form_state['input']['weight'])) {
    return;
  }

  foreach ($form_state['input']['id'] as $n => $id) {
    $category = Category::load($id);
    if ($category) {
      if (!isset($form_state['input']['weight'][$n])) {
        continue;
      }
      $category->set('weight', $form_state['input']['weight'][$n]);
    }
  }

  variable_realm_rebuild();

  Category::updateEUCookieComplianceSettings();
}
