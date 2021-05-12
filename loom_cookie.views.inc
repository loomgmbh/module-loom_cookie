<?php

/**
 * @file
 * Provides views data for loom_cookie.module.
 */

define('LOOM_COOKIE_CONSENT_BASE_TABLE', 'loom_cookie_basic_consent');

/**
 * Implements hook_views_data().
 */
function loom_cookie_views_data() {
  $data[LOOM_COOKIE_CONSENT_BASE_TABLE] = [];

  $data[LOOM_COOKIE_CONSENT_BASE_TABLE]['table']['base'] = [
    'field' => 'cid',
    'title' => t('LOOM Cookie Consents'),
    'help' => t('LOOM Cookie consent data from the database.'),
    'weight' => -10,
  ];

  $data[LOOM_COOKIE_CONSENT_BASE_TABLE]['table']['group'] = t('LOOM Cookie Consents');

  $data[LOOM_COOKIE_CONSENT_BASE_TABLE]['uid'] = [
    'title' => t('User ID'),
    'help' => t('User ID.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'user_name',
    ],
    'argument' => [
      'id' => 'user_uid',
    ],
    'relationship' => [
      // Views name of the table to join to for the relationship.
      'base' => 'users',
      // Database field name in the user table to join on.
      'base field' => 'uid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t('Users'),
    ],
  ];

  $data[LOOM_COOKIE_CONSENT_BASE_TABLE]['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('Time of consent.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
  ];

  $data[LOOM_COOKIE_CONSENT_BASE_TABLE]['ip_address'] = [
    'title' => t('IP address'),
    'help' => t('The IP address.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data[LOOM_COOKIE_CONSENT_BASE_TABLE]['consent_type'] = [
    'title' => t('Consent Type'),
    'help' => t('The type of consent, such as "banner" for the banner and form_id for forms.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data[LOOM_COOKIE_CONSENT_BASE_TABLE]['revision_id'] = [
    'title' => t('Revision ID'),
    'help' => t('Revision of the privacy policy at the time of consent.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}
