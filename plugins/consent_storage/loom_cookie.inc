<?php /** @noinspection PhpUnused */

$plugin = [
  'title' => t('Loom cookie consent storage'),
  'consent_storage_callback' => 'loom_cookie_store_consent',
];

/**
 * Store record of consent in the database.
 *
 * This method stores the following in the
 * database:
 *  - The UID.
 *  - The IP Address.
 *  - Time of consent.
 *  - Revision of the privacy policy at the time of consent.
 *  - Selected categories
 *
 * @param string $consent_type
 *   The consent type (for example banner or form ID).
 *
 * @return bool
 *   Returns TRUE on storage success.
 *
 * @throws \Exception
 */
function loom_cookie_store_consent($consent_type) {
  global $user;

  $revision_id = _eu_cookie_compliance_get_current_policy_node_revision();
  $timestamp = time();
  $ip_address = ip_address();
  $uid = $user->uid;
  $categories = !empty($_COOKIE['cookie-agreed-categories'])
    ? $_COOKIE['cookie-agreed-categories'] : '[]';

  $consent = [
    'type' => $consent_type,
    'categories' => json_decode($categories),
  ];

  db_insert('eu_cookie_compliance_basic_consent')
    ->fields([
      'uid' => $uid,
      'ip_address' => $ip_address,
      'timestamp' => $timestamp,
      'revision_id' => $revision_id ? $revision_id : 0,
      'consent_type' => json_encode($consent),
    ])
    ->execute();

  return TRUE;
}
