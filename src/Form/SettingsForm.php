<?php

namespace Drupal\loom_cookie\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use function implode;
use function loom_cookie_multiline_split;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loom_cookie_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'loom_cookie.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['consent_storage'] = [
      '#type' => 'details',
      '#title' => t('Consent Storage'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['consent_storage']['lifetime'] = [
      '#type' => 'number',
      '#title' => t('Days until stored consents are deleted.'),
      '#default_value' => $this->config('loom_cookie.settings')
        ->get('consent_storage.lifetime'),
    ];

    $form['frontend'] = [
      '#type' => 'details',
      '#title' => t('Frontend'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['frontend']['attachments'] = [
      '#type' => 'textarea',
      '#title' => t('Attachments'),
      '#default_value' => $this->config('loom_cookie.settings')
        ->get('frontend.attachments') ? implode("\r\n", $this->config('loom_cookie.settings')
        ->get('frontend.attachments')) : '',
      '#description' => t('One attachment per line. WARNING: Every attachment will be loaded on all sites.'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('loom_cookie.settings');

    $config->set('consent_storage', $form_state->getValue('consent_storage'));

    $frontend = $form_state->getValue('frontend');
    $frontend['attachments'] = loom_cookie_multiline_split($frontend['attachments']);
    $config->set('frontend', $frontend);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
