<?php
/** @noinspection HtmlUnknownAnchorTarget */
/** @noinspection PhpUnused */

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
 * @param string|null $category_id
 *
 * @return array
 *   Admin form render array.
 */
function loom_cookie_category_form(array $form, array $form_state, $category_id = NULL) {
  $category = $category_id != NULL ? Category::load($category_id) : new Category();

  if (!$category) {
    return [
      '#markup' => 'Category not found.',
    ];
  }

  $form['headline'] = [
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => $category->isNew() ? 'Add cookie category' : 'Edit cookie category',
  ];

  if (isset($form_state['input']['loom_cookie_variable_name'])) {
    $variable_name = $form_state['input']['loom_cookie_variable_name'];
  }
  else {
    if ($category->isNew()) {
      $variable_name = 'loom_cookie_category__' . time();
    }
    else {
      $variable_name = 'loom_cookie_category__' . $category_id;
    }
  }

  $form['loom_cookie_variable_name'] = [
    '#type' => 'hidden',
    '#value' => $variable_name,
  ];

  $form['loom_cookie_is_new'] = [
    '#type' => 'hidden',
    '#value' => $category->isNew(),
  ];

  $form[$variable_name] = [
    '#type' => 'item',
    '#tree' => TRUE,
  ];
  $form[$variable_name]['label'] = [
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#maxlength' => 255,
    '#default_value' => $category->label(),
    '#description' => 'Label for the category.',
    '#required' => TRUE,
  ];

  $form[$variable_name]['id'] = [
    '#type' => 'textfield',
    '#title' => 'Systemname',
    '#required' => TRUE,
    '#default_value' => $category->id(),
    '#disabled' => !$category->isNew(),
  ];

  $form[$variable_name]['weight'] = [
    '#type' => 'hidden',
    '#default_value' => $category->weight,
  ];

  $form[$variable_name]['description'] = [
    '#type' => 'text_format',
    '#title' => t('Description'),
    '#default_value' => $category->description['value'],
  ];

  $form[$variable_name]['detailedDescription'] = [
    '#type' => 'text_format',
    '#title' => 'Detailierte Beschreibung',
    '#default_value' => $category->detailedDescription['value'],
    '#description' => 'Kann genutzt werden, um die zu dieser Kategorie gehörenden Cookies und Skripte näher zu beschreiben.',
  ];

  $form['cookies_section'] = [
    '#type' => 'fieldset',
    '#title' => 'Cookies',
    0 => [
      '#markup' =>
        'Von vornherein werden erst einmal alle Cookies geblockt (Ausnahme: ' .
        'Cookies, die von Drupal benötigt werden). Erst wenn der Nutzer eine ' .
        'oder mehrere Kategorien über den Cookiebanner aktiviert, werden die ' .
        'angegebenen Cookies aktiviert.<br/>' .
        'Das Blocken der Cookies erfolgt clientseitig. Dabei werden alle paar ' .
        'Sekunden alle Cookies, die nicht per Kategorie zugelassen werden ' .
        'sollen, gelöscht. Dadurch kann es trotzdem vorkommen, dass der ' .
        'Nutzer getrackt wird, weil die Cookies für eine gewisse Zeit gesetzt ' .
        'sein können. Deshalb wird empfohlen, zusätzlich diejenigen Skripte zu ' .
        'blockieren, welche diese Cookies setzen. Siehe Abschnitt ' .
        '<a href="#edit-scripts-section">Skripte</a>.',
    ],
    1 =>
      [
        '#type' => 'container',
        '#children' => '<br/>Alle Cookies, die zu dieser Kategorie gehören, müssen hier angegeben werden. Wenn der Nutzer ' .
          'seine Auswahl im Cookiebanner trifft, dann werden nur die Cookies der ' .
          'ausgewählten Kategorien aktiviert. Alle anderen werden gelöscht.<br/>' .
          'Ein Cookiename pro Zeile.<br/>',
      ],
    2 => [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => 'Beispiele',
      0 => [
        '#markup' =>
          'SID<br/>' .
          'SSID<br/>' .
          'APISID<br/>' .
          'IDE',
      ],
    ],
  ];

  $form['cookies_section'][$variable_name] = [
    '#type' => 'item',
    '#tree' => TRUE,
  ];

  $form['cookies_section'][$variable_name]['cookies'] = [
    '#type' => 'textarea',
    '#default_value' => $category->cookies,
  ];

  $form['scripts_section'] = [
    '#type' => 'fieldset',
    '#title' => 'Skripte',
    0 => [
      '#markup' =>
        'Skripte können serverseitig und clientseitig geblockt werden. Aus ' .
        'Performancegründen sollte bevorzugt das serverseitige Blockieren ' .
        'genutzt werden, sofern das möglich ist. Einige Skripte lassen sich allerdings ' .
        'nur clientseitig blockieren. Dazu gehören beispielsweise per Skripte, ' .
        'die per Google Tag Manager nachgeladen werden.',
    ],
    1 => [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => 'Beispiele',
      '#open' => FALSE,
      0 => [
        '#markup' =>
          '<li>Skripte, die per Google Tag Manager nachgeladen werden (bspw. <strong>Google Analytics</strong>): <a href="#edit-scripturlregexesclientside-wrapper">clientseitig</a></li>' .
          '<li>' .
          'Skripte, die von Drupal eingebunden werden:' .
          '<ul>' .
          '<li>' .
          'als &lt;script&gt;-Tag (bspw. per Template)' .
          '<ul>' .
          '<li>mit src: <a href="#edit-scripturlregexes">serverseitig &rarr; Script URL regexes</a></li>' .
          '<li>als Script-Block: <a href="#edit-scriptblockregexes">serverseitig &rarr; Script Block regexes</a></li>' .
          '</ul>' .
          '</li>' .
          '</ul>' .
          '</li>' .
          '</ul>',
      ],
    ],
  ];

  $form['scripts_section']['server_side'] = [
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Serverseitiges Blockieren'),
    '#open' => FALSE,
  ];

  $form['scripts_section']['server_side']['scriptUrlRegexes_wrapper'] = [
    '#type' => 'fieldset',
    '#title' => 'Script-URLs',
    0 => [
      [
        '#markup' => 'Ein Regex pro Zeile.',
      ],
      [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => 'Beispiele',
        '#open' => FALSE,
        0 => [
          '#markup' =>
            '<strong>Google Analytics (sofern es serverseitig eingebunden wird):</strong><br/>' .
            '.*google-analytics\.com/analytics\.js',
        ],
      ],
    ],
  ];

  $form['scripts_section']['server_side']['scriptUrlRegexes_wrapper'][$variable_name] = [
    '#type' => 'item',
    '#tree' => TRUE,
  ];

  $form['scripts_section']['server_side']['scriptUrlRegexes_wrapper'][$variable_name]['scriptUrlRegexes'] = [
    '#type' => 'textarea',
    '#title' => 'Script-URLs',
    '#default_value' => $category->scriptUrlRegexes,
  ];

  $form['scripts_section']['server_side']['scriptBlockRegexes_wrapper'] = [
    '#type' => 'fieldset',
    '#title' => 'Inline-Script-Blöcke',
    0 => [
      [
        '#markup' =>
          'Diese regulären Ausdrücke werden genutzt, um Inline-Script-Blöcke ' .
          'zu blockieren<br/>' .
          'Ein Regex pro Zeile.',
      ],
      [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => 'Beispiele',
        '#open' => FALSE,
        0 => [
          '#markup' =>
            '.*document\.cookie=.*',
        ],
      ],
    ],
  ];

  $form['scripts_section']['server_side']['scriptBlockRegexes_wrapper'][$variable_name] = [
    '#type' => 'item',
    '#tree' => TRUE,
  ];

  $form['scripts_section']['server_side']['scriptBlockRegexes_wrapper'][$variable_name]['scriptBlockRegexes'] = [
    '#type' => 'textarea',
    '#title' => 'Inline-Script-Blöcke',
    '#default_value' => $category->scriptBlockRegexes,
  ];

  $form['scripts_section']['client_side'] = [
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Clientseitiges Blockieren'),
    '#open' => FALSE,
  ];

  $form['scripts_section']['client_side']['scriptUrlRegexesClientSide_wrapper'] = [
    '#type' => 'fieldset',
    '#title' => 'Script-URLs',
    0 => [
      [
        '#markup' =>
          'Für Skripte, die nicht serverseitig geblockt werden können.<br/>' .
          'Ein Regex pro Zeile.',
      ],
      [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => 'Beispiele',
        '#open' => FALSE,
        0 => [
          '#markup' =>
            '<strong>Google Analytics (sofern es per Google Tag Manager eingebunden wird):</strong><br/>' .
            '.*google-analytics\.com/analytics\.js',
        ],
      ],
    ],
  ];

  $form['scripts_section']['client_side']['scriptUrlRegexesClientSide_wrapper'][$variable_name] = [
    '#type' => 'item',
    '#tree' => TRUE,
  ];

  $form['scripts_section']['client_side']['scriptUrlRegexesClientSide_wrapper'][$variable_name]['scriptUrlRegexesClientSide'] = [
    '#type' => 'textarea',
    '#title' => 'Script-URLs',
    '#default_value' => $category->scriptUrlRegexesClientSide,
  ];

  $form['embeds'] = [
    '#type' => 'fieldset',
    '#title' => t('Embeds + iFrames'),
    0 => [
      '#markup' => 'Embed und iFrames werden serverseitig blockiert. Sie ' .
        'werden nachgeladen, wenn der Nutzer die entsprechene Kategorie im ' .
        'Cookiebanner aktiviert.',
    ],
  ];

  $form['embeds']['embedUrlRegexes_wrapper'] = [
    '#type' => 'container',
    0 => [
      [
        '#markup' =>
          'Ein Regex pro Zeile.',
      ],
      [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => 'Beispiele',
        '#open' => FALSE,
        0 => [
          '#markup' =>
            '<strong>Google Maps:</strong><br/>' .
            'https://www\.google\.com/maps/embed\?.*' .
            '<br/><br/>' .
            '<strong>Youtube:</strong><br/>' .
            'https://www\.youtube\.com/.*',
        ],
      ],
    ],
  ];

  $form['embeds']['embedUrlRegexes_wrapper'][$variable_name] = [
    '#type' => 'item',
    '#tree' => TRUE,
  ];

  $form['embeds']['embedUrlRegexes_wrapper'][$variable_name]['embedUrlRegexes'] = [
    '#type' => 'textarea',
    '#title' => 'Embed-URLs (Embeds + iFrames)',
    '#default_value' => $category->embedUrlRegexes,
  ];
  $form['embeds']['embedUrlRegexes_wrapper'][$variable_name]['embedMessage'] = [
    '#type' => 'textarea',
    '#title' => 'Nachricht, die anstelle eines geblockten Embeds/iFrames angezeigt werden soll',
    '#default_value' => $category->embedMessage,
    '#description' => ('Sie können <code>&lt;a href="#" onclick="Drupal.behaviors.loom_cookie_filter_scripts.reopenBanner(); return false;"&gt;click&lt;/a&gt;</code> ' .
      'nutzen, um einen Link einzufügen, der den Cookiebanner öffnet.'),
  ];

  $form['actions'] = ['#type' => 'actions'];
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save'),
  ];

  $form = system_settings_form($form);
  $form['#submit'][] = 'loom_cookie_category_form_submit';
  return $form;
}

/**
 * @param array $form
 *   Form array.
 * @param array $form_state
 *   Form state array.
 */
function loom_cookie_category_form_submit(array $form, array &$form_state) {
  if ($form_state['input']['loom_cookie_is_new']) {
    $old_variable_name = $form_state['input']['loom_cookie_variable_name'];
    $variable_name = 'loom_cookie_category__' . $form_state['values'][$old_variable_name]['id'];

    $variable = variable_get($old_variable_name);
    variable_del($old_variable_name);
    variable_set($variable_name, $variable);
    foreach (i18n_language_list() as $langcode => $lang) {
      variable_realm_set('language', $langcode, $variable_name, $variable, TRUE);
    }
  }
  else {
    $variable_name = $form_state['input']['loom_cookie_variable_name'];
    $variable = $form_state['input'][$variable_name];

    // sync non-translatable fields
    $nontranslatable_fields = [
      'cookies',
      'scriptUrlRegexes',
      'scriptBlockRegexes',
      'scriptUrlRegexesClientSide',
      'embedUrlRegexes',
    ];
    $category = new Category($variable);
    $category->id = str_replace('loom_cookie_category__', '', $variable_name);
    $category->isNew = FALSE;
    foreach ($nontranslatable_fields as $field) {
      $category->set($field, $category->$field);
    }
  }

  drupal_flush_all_caches();

  Category::updateEUCookieComplianceSettings();
}
