<?php
/** @noinspection PhpUnused */

require_once __DIR__ . '/../src/Category.php';

function loom_cookie_category_delete_confirm($form, &$form_state, $category_id) {
  $category = $category_id != NULL ? Category::load($category_id) : new Category();

  if (!$category) {
    return [
      '#markup' => 'Category not found.',
    ];
  }

  // Always provide entity id in the same form key as in the entity edit form.
  $form['category_id'] = ['#type' => 'value', '#value' => $category->id];
  return confirm_form($form,
    t('Are you sure you want to delete %title?', ['%title' => $category->label]),
    'admin/config/system/eu-cookie-compliance/loom-cookie/categories/' . $category->id . '/edit',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}


function loom_cookie_category_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    $category = Category::load($form_state['values']['category_id']);
    $category->delete();
  }

  $form_state['redirect'] = 'admin/config/system/eu-cookie-compliance/loom-cookie/categories';
}
