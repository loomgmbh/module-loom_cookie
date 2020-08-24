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
      '#description' => $this->t("Label for the Category."),
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

    $form['cookies'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Cookie names'),
      '#default_value' => implode("\n", $category->cookies),
    ];

    $form['attachmentNames'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Attachment names'),
      '#default_value' => implode("\n", $category->attachmentNames),
    ];

    $regexes_description = $this->t('One regex per line.');

    $form['scriptUrlRegexes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Script URL regexes'),
      '#description' => $regexes_description,
      '#default_value' => implode("\n", $category->scriptUrlRegexes),
    ];

    $form['scriptBlockRegexes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Script Block regexes'),
      '#description' => $regexes_description,
      '#default_value' => implode("\n", $category->scriptBlockRegexes),
    ];

    $form['scriptUrlRegexesClientSide'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Script URL regexes (blocked client-side)'),
      '#description' => $regexes_description . '<br/>' .
        'Use this option for scripts that are loaded by JS and therefore cannot' .
        ' be blocked on server side.',
      '#default_value' => implode("\n", $category->scriptUrlRegexesClientSide),
    ];

    $form['embedUrlRegexes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Embed URL regexes (embeds + iframes)'),
      '#description' => $regexes_description,
      '#default_value' => implode("\n", $category->embedUrlRegexes),
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
      $category->$field = str_replace("\r", '',
        array_filter(
          array_unique(
            explode("\n", $form_state->getValue($field)))));
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
