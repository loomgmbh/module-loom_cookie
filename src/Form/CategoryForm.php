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
      '#description' => $this->t('Label for the category.'),
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
    ];

    $form['detailedDescription'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Detailed description'),
      '#default_value' => $category->detailedDescription['value'],
      '#description' => $this->t('This can be used to describe the cookies and scripts belonging to the category.'),
    ];

    $form['whitelist'] = [
      '#type' => 'fieldset',
      '#title' => t('Whitelist'),
      '#open' => TRUE,
      0 => [
        '#markup' => $this->t(
          'These cookies and scripts will be blocked unless the user ' .
          'accepts this category.' .
          '<p><strong>Scripts</strong> can be blocked server-side or client-side. ' .
          'It is recommended to prefer server-side blocking due to performance ' .
          'reasons. Some scripts cannot be blocked server-side (e.g. scripts ' .
          'loaded by Google Tag Manager), thus you will have to use client-side ' .
          'blocking for them.</p>' .
          '<p><strong>Cookies</strong> can only be blocked client-side. ' .
          'However, this module cannot prevent cookies from beeing set at all. ' .
          'The cookies are deleted in an interval of 5 seconds. So a user could ' .
          'be tracked nevertheless. Therefore it is recommended that you instead ' .
          'block the scripts that would set the cookies, if possible.</p>'
        ),
      ],
    ];

    $form['whitelist']['server_side'] = [
      '#type' => 'details',
      '#title' => t('Server Side'),
      '#open' => TRUE,
    ];

    $form['whitelist']['server_side']['attachmentNames'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Attachment names'),
      '#default_value' => implode("\n", $category->attachmentNames),
      '#description' => $this->t(
        'Names of Drupal attachments. If possible, you should prefer this ' .
        'method over the others due to performance reasons. You can determine ' .
        'attachments by reading the source code of modules or using a debugger.<br/>' .
        'One attachment name per line.<br/>' .
        'Example:<br/>' .
        'matomo_tracking_script'),
    ];

    $form['whitelist']['server_side']['scriptUrlRegexes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Script URL regexes'),
      '#description' => $this->t(
        'One regex per line.' . '<br/>' .
        'Example:<br/>' .
        '.*google-analytics\.com/analytics\.js'
      ),
      '#default_value' => implode("\n", $category->scriptUrlRegexes),
    ];

    $form['whitelist']['server_side']['scriptBlockRegexes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Script Block regexes'),
      '#description' => $this->t(
        'Use this to block specific script blocks by comparing their
        content with a regular expression<br/>' .
        'One regex per line.' . '<br/>' .
        'Example:<br/>' .
        '.*document\.cookie=.*'
      ),
      '#default_value' => implode("\n", $category->scriptBlockRegexes),
    ];

    $form['whitelist']['server_side']['embedUrlRegexes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Embed URL regexes (embeds + iframes)'),
      '#description' => $this->t(
        'One regex per line.<br/>' .
        'Example:<br/>' .
        'https://www\.google\.com/maps/embed\?.*'
      ),
      '#default_value' => implode("\n", $category->embedUrlRegexes),
    ];

    $form['whitelist']['client_side'] = [
      '#type' => 'details',
      '#title' => t('Client Side'),
      '#open' => TRUE,
    ];

    $form['whitelist']['client_side']['cookies'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Cookie names'),
      '#default_value' => implode("\n", $category->cookies),
      '#description' => $this->t(
        'One cookie name per line.'
      )
    ];

    $form['whitelist']['client_side']['scriptUrlRegexesClientSide'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Script URL regexes (blocked client-side)'),
      '#description' => $this->t(
        'One regex per line.<br/>' .
        'Use this option for scripts that are loaded by JS and therefore cannot' .
        ' be blocked on server side.' . '<br/>' .
        'Example:<br/>' .
        '.*google-analytics\.com/analytics\.js'
      ),
      '#default_value' => implode("\n", $category->scriptUrlRegexesClientSide),
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
