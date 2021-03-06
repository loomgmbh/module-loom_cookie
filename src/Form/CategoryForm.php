<?php /** @noinspection PhpUnused */

namespace Drupal\loom_cookie\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\loom_cookie\CategoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Category add and edit forms.
 */
class CategoryForm extends EntityForm {

  /**
   * Constructs an CategoryForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var CategoryInterface $category */
    $category = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $category->label(),
      '#description' => 'Label for the category.',
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $category->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$category->isNew(),
    ];

    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $category->description['value'],
      '#format' => $category->description['format'],
    ];

    $form['detailedDescription'] = [
      '#type' => 'text_format',
      '#title' => 'Detailierte Beschreibung',
      '#default_value' => $category->detailedDescription['value'],
      '#format' => $category->detailedDescription['format'],
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
    ];

    $form['cookies_section']['cookies'] = [
      '#type' => 'textarea',
      '#default_value' => implode("\n", $category->cookies),
      '#description_display' => 'before',
      '#description' => [
        [
          '#markup' => 'Alle Cookies, die zu dieser Kategorie gehören, müssen hier angegeben werden. Wenn der Nutzer ' .
            'seine Auswahl im Cookiebanner trifft, dann werden nur die Cookies der ' .
            'ausgewählten Kategorien aktiviert. Alle anderen werden gelöscht.<br/>' .
            'Ein Cookiename pro Zeile.<br/>',
        ],
        [
          '#type' => 'details',
          '#title' => 'Beispiele',
          0 => [
            '#markup' =>
              'SID<br/>' .
              'SSID<br/>' .
              'APISID<br/>' .
              'IDE',
          ],
        ],
      ],
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
    ];

    $form['scripts_section']['examples'] = [
      '#type' => 'details',
      '#title' => 'Beispiele',
      '#open' => FALSE,
      0 => [
        '#markup' =>
          '<li>Skripte, die per Google Tag Manager nachgeladen werden (bspw. <strong>Google Analytics</strong>): <a href="#edit-scripturlregexesclientside">clientseitig</a></li>' .
          '<li>' .
          'Skripte, die von Drupal eingebunden werden:' .
          '<ul>' .
          '<li>per Attachment (bspw. <strong>Matomo</strong>): <a href="#edit-attachmentnames">serverseitig &rarr; Attachment</a></li>' .
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
    ];

    $form['scripts_section']['server_side'] = [
      '#type' => 'details',
      '#title' => t('Serverseitiges Blockieren'),
      '#open' => FALSE,
    ];

    $form['scripts_section']['server_side']['attachmentNames'] = [
      '#type' => 'textarea',
      '#title' => 'Attachments',
      '#default_value' => implode("\n", $category->attachmentNames),
      '#description_display' => 'before',
      '#description' =>
        [
          [
            '#markup' => 'Namen von Drupal-Attachments. Aus Performancegründen sollte diese ' .
              'Methode bevorzugt werden, wenn möglich. Namen von Drupal-Attachments ' .
              'können durch Lesen der Modulquelltexte oder durch Debugging ' .
              'herausgefunden werden.<br/>' .
              'Ein Attachment pro Zeile.<br/>',
          ],
          [
            '#type' => 'details',
            '#title' => 'Beispiele',
            '#open' => FALSE,
            0 => [
              '#markup' =>
                '<strong>Matomo:</strong><br/>' .
                'matomo_tracking_script',
            ],
          ],
        ],
    ];

    $form['scripts_section']['server_side']['scriptUrlRegexes'] = [
      '#type' => 'textarea',
      '#title' => 'Script-URLs',
      '#default_value' => implode("\n", $category->scriptUrlRegexes),
      '#description_display' => 'before',
      '#description' =>
        [
          [
            '#markup' => 'Ein Regex pro Zeile.',
          ],
          [
            '#type' => 'details',
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

    $form['scripts_section']['server_side']['scriptBlockRegexes'] = [
      '#type' => 'textarea',
      '#title' => 'Inline-Script-Blöcke',
      '#default_value' => implode("\n", $category->scriptBlockRegexes),
      '#description_display' => 'before',
      '#description' =>
        [
          [
            '#markup' =>
              'Diese regulären Ausdrücke werden genutzt, um Inline-Script-Blöcke ' .
              'zu blockieren<br/>' .
              'Ein Regex pro Zeile.',
          ],
          [
            '#type' => 'details',
            '#title' => 'Beispiele',
            '#open' => FALSE,
            0 => [
              '#markup' =>
                '.*document\.cookie=.*',
            ],
          ],
        ],
    ];

    $form['scripts_section']['client_side'] = [
      '#type' => 'details',
      '#title' => t('Clientseitiges Blockieren'),
      '#open' => FALSE,
    ];

    $form['scripts_section']['client_side']['scriptUrlRegexesClientSide'] = [
      '#type' => 'textarea',
      '#title' => 'Script-URLs',
      '#default_value' => implode("\n", $category->scriptUrlRegexesClientSide),
      '#description_display' => 'before',
      '#description' =>
        [
          [
            '#markup' =>
              'Für Skripte, die nicht serverseitig geblockt werden können.<br/>' .
              'Ein Regex pro Zeile.',
          ],
          [
            '#type' => 'details',
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

    $form['embeds'] = [
      '#type' => 'fieldset',
      '#title' => t('Embeds + iFrames'),
      0 => [
        '#markup' => 'Embed und iFrames werden serverseitig blockiert. Sie ' .
          'werden nachgeladen, wenn der Nutzer die entsprechene Kategorie im ' .
          'Cookiebanner aktiviert.',
      ],
    ];

    $form['embeds']['embedUrlRegexes'] = [
      '#type' => 'textarea',
      '#title' => 'Embed-URLs (Embeds + iFrames)',
      '#default_value' => implode("\n", $category->embedUrlRegexes),
      '#description_display' => 'before',
      '#description' =>
        [
          [
            '#markup' =>
              'Ein Regex pro Zeile.',
          ],
          [
            '#type' => 'details',
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

    $form['embeds']['embedMessage'] = [
      '#type' => 'textarea',
      '#title' => 'Nachricht, die anstelle eines geblockten Embeds/iFrames angezeigt werden soll',
      '#default_value' => $category->embedMessage,
      '#description' => 'Sie können <code>&lt;a href="#" onclick="Drupal.behaviors.loom_cookie_filter_scripts.reopenBanner(); return false;"&gt;click&lt;/a&gt;</code> ' .
        'nutzen, um einen Link einzufügen, der den Cookiebanner öffnet.',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var CategoryInterface $category */
    $category = $this->entity;

    // convert multiline strings to arrays
    $multiline_fields = [
      'cookies',
      'attachmentNames',
      'scriptUrlRegexes',
      'scriptBlockRegexes',
      'scriptUrlRegexesClientSide',
      'embedUrlRegexes',
    ];

    foreach ($multiline_fields as $field) {
      $category->$field = loom_cookie_multiline_split($form_state->getValue($field));
    }

    $status = $category->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label Category created.', [
        '%label' => $category->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label Category updated.', [
        '%label' => $category->label(),
      ]));
    }
  }

  /**
   * Helper function to check whether an Category configuration entity exists.
   *
   * @param $id
   *
   * @return bool
   */
  public function exists($id) {
    $entity = $this->entityTypeManager->getStorage('loom_cookie_category')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }
}
