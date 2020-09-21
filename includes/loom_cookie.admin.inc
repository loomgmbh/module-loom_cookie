<?php /** @noinspection PhpUnused */

/**
 * @file
 * This file provides administration form for the module.
 */

/**
 * @param array $form
 *   Form array.
 * @param array $form_state
 *   For state array.
 *
 * @return array
 *   Admin form render array.
 */
function loom_cookie_admin_form(array $form, array $form_state) {
  $form = [];

  $settings = loom_cookie_get_settings();

  $form['headline'] = [
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => 'Allgemein',
  ];

  $form['loom_cookie'] = [
    '#type' => 'item',
    '#tree' => TRUE,
  ];
  $config_form = &$form['loom_cookie'];

  $config_form['consent_storage'] = [
    '#type' => 'fieldset',
    '#title' => t('Consent Storage'),
    '#open' => TRUE,
    '#tree' => TRUE,
  ];

  $config_form['consent_storage']['lifetime'] = [
    '#type' => 'textfield',
    '#title' => t('Days until stored consents are deleted.'),
    '#default_value' => $settings['consent_storage']['lifetime'],
  ];

  $form = system_settings_form($form);
  return $form;
}
