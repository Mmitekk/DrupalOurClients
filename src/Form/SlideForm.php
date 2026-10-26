<?php

namespace Drupal\our_clients\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\our_clients\Entity\Slide;

/**
 * Form controller for the Our clients slide add and edit forms.
 */
class SlideForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\our_clients\Entity\Slide $slide */
    $slide = $this->entity;
    our_clients_maybe_import_russian_translation();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client name'),
      '#maxlength' => 255,
      '#default_value' => $slide->label(),
      '#required' => TRUE,
      '#description' => $this->t('Shown in the slider under the image and as the heading of the modal window.'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $slide->id() ?: '',
      '#machine_name' => [
        'exists' => '\Drupal\our_clients\Entity\Slide::load',
      ],
      '#disabled' => !$slide->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#default_value' => $slide->isNew() ? TRUE : $slide->isEnabled(),
    ];

    // Self-contained media field: pick an existing media item from the grid
    // or upload a new image. Requires nothing but the core media module.
    // Collapsed by default so the media list does not take over the form.
    $form['media_image'] = [
      '#type' => 'details',
      '#title' => $this->t('Media image'),
      '#tree' => TRUE,
      '#open' => FALSE,
      '#description' => $this->t('Select an existing media item or upload a new image. Images are shown in the slider and enlarged in the modal window; videos show an automatic preview in the slider and are played in the modal window.'),
    ];

    $current_fid = $this->currentSourceFileId($slide);

    if (\Drupal::moduleHandler()->moduleExists('media') && ($bundles = Slide::slideMediaBundles())) {
      $query = \Drupal::entityQuery('media')
        ->accessCheck(FALSE)
        ->condition('bundle', $bundles, 'IN')
        ->sort('created', 'DESC')
        ->range(0, 50);
      $mids = $query->execute();
      $options = [];
      if ($mids) {
        foreach (\Drupal::entityTypeManager()->getStorage('media')->loadMultiple($mids) as $media) {
          $thumbnail = $media->get('thumbnail')->entity;
          $thumb = $thumbnail ? [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => $thumbnail->getFileUri(),
            '#attributes' => ['class' => ['our-clients-admin-thumb']],
          ] : [];
          $options[$media->id()] = [
            'thumb' => ['data' => $thumb],
            'name' => ['data' => ['#markup' => Html::escape((string) $media->label())]],
          ];
        }
      }
      $form['media_image']['existing'] = [
        '#type' => 'tableselect',
        '#title' => $this->t('From the media library'),
        '#header' => [
          'thumb' => $this->t('Preview'),
          'name' => $this->t('Media item'),
        ],
        '#options' => $options,
        '#empty' => $this->t('The media library is empty. Upload a new image below.'),
        '#multiple' => FALSE,
        '#js_select' => FALSE,
        '#default_value' => $slide->getImageMedia() ?: NULL,
      ];
    }

    $form['media_image']['upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload new image'),
      '#default_value' => $current_fid ? [$current_fid] : NULL,
      '#upload_location' => 'public://our_clients',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg webp'],
      ],
      '#description' => $this->t('The uploaded image is stored as a media item automatically. Recommended size: at least 600x450 pixels. The alt text equals the client name and can be edited on the media item afterwards.'),
    ];

    $description = $slide->getDescription();
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => isset($description['value']) ? $description['value'] : '',
      '#format' => $this->resolveFormat($description),
      '#description' => $this->t('Information about the client. Shown in the modal window.'),
    ];

    // Multi-value inline reviews: one fieldset per linked review plus any new
    // empty slots added by the "Add review" button (a plain rebuild - no JS).
    $form['reviews_area'] = [
      '#type' => 'details',
      '#title' => $this->t('Client reviews'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('The reviews of the client. Shown in the modal window below the description.'),
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('our_clients_review');
    $review_entities = $slide->getReviews() ? $storage->loadMultiple($slide->getReviews()) : [];
    $map = [];
    $delta = 0;
    foreach ($slide->getReviews() as $review_id) {
      if (!isset($review_entities[$review_id])) {
        continue;
      }
      $map[$delta] = $review_id;
      $form['reviews_area'][$delta] = $this->buildReviewFieldset($delta, $review_entities[$review_id]);
      $delta++;
    }
    $new_count = (int) $form_state->get('our_clients_new_reviews');
    for ($i = 0; $i < $new_count; $i++) {
      $form['reviews_area'][$delta] = $this->buildReviewFieldset($delta, NULL);
      $delta++;
    }
    $form_state->set('our_clients_reviews_map', $map);

    $form['reviews_area']['add'] = [
      '#type' => 'submit',
      '#name' => 'our_clients_add_review',
      '#value' => $this->t('Add review'),
      '#limit_validation_errors' => [],
      '#submit' => ['::addReviewSubmit'],
    ];

    $form['weight'] = [
      '#type' => 'hidden',
      '#default_value' => $slide->isNew() ? $this->nextWeight() : $slide->getWeight(),
    ];

    return $form;
  }

  /**
   * Builds one inline review fieldset.
   *
   * @param int $delta
   *   The fieldset delta.
   * @param \Drupal\our_clients\Entity\Review|null $review
   *   The existing review or NULL for a new one.
   *
   * @return array
   *   The fieldset render array.
   */
  protected function buildReviewFieldset($delta, $review = NULL) {
    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Review @number', ['@number' => $delta + 1]),
    ];

    $element['author'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author name'),
      '#maxlength' => 255,
      '#default_value' => $review ? $review->getAuthor() : '',
    ];

    $element['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Review date'),
      '#default_value' => $review ? $review->getDate() : '',
    ];

    $element['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#maxlength' => 255,
      '#default_value' => $review ? $review->getCity() : '',
    ];

    $element['rating'] = [
      '#type' => 'select',
      '#title' => $this->t('Rating'),
      '#options' => [
        5 => $this->t('5 - excellent'),
        4 => $this->t('4 - good'),
        3 => $this->t('3 - average'),
        2 => $this->t('2 - poor'),
        1 => $this->t('1 - bad'),
      ],
      '#default_value' => $review ? $review->getRating() : 5,
    ];

    $element['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Review text'),
      '#rows' => 3,
      '#default_value' => $review ? $review->getText() : '',
    ];

    $element['remove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete this review'),
      '#default_value' => FALSE,
    ];

    return $element;
  }

  /**
   * Submit handler for the "Add review" button: adds an empty review slot.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addReviewSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('our_clients_new_reviews', (int) $form_state->get('our_clients_new_reviews') + 1);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $fieldset = $form_state->getValue('media_image') ?: [];
    $upload = isset($fieldset['upload'][0]) ? (int) $fieldset['upload'][0] : 0;
    $selected = isset($fieldset['existing']) ? (int) $fieldset['existing'] : 0;

    if (!$upload && !$selected && $this->entity->isNew()) {
      $form['media_image']['#open'] = TRUE;
      $form_state->setErrorByName('media_image', $this->t('Add an image: select one from the media library or upload a new one.'));
    }

    // A review needs at least its text when any of its fields are filled in.
    $reviews_area = $form_state->getValue('reviews_area') ?: [];
    foreach ($reviews_area as $delta => $review) {
      if (!is_array($review) || !empty($review['remove'])) {
        continue;
      }
      $text = trim((string) ($review['text'] ?? ''));
      $filled = $text !== '' || trim((string) ($review['author'] ?? '')) !== ''
        || trim((string) ($review['city'] ?? '')) !== '' || !empty($review['date']);
      if ($filled && $text === '') {
        $form_state->setErrorByName('reviews_area][' . $delta . '][text', $this->t('The review text is required.'));
      }
    }
  }

  /**
   * Resolves a text format for the text_format element.
   *
   * Falls back to basic_html and then to the site fallback format when the
   * stored format no longer exists.
   *
   * @param array $text
   *   Array with a 'format' key.
   *
   * @return string|null
   *   The format machine name or NULL to use the site default.
   */
  protected function resolveFormat(array $text) {
    if (!empty($text['format']) && FilterFormat::load($text['format'])) {
      return $text['format'];
    }
    if (FilterFormat::load('basic_html')) {
      return 'basic_html';
    }
    return filter_fallback_format();
  }

  /**
   * Calculates the weight for a new slide.
   *
   * @return int
   *   The weight one step below the current last slide.
   */
  protected function nextWeight() {
    $query = \Drupal::entityQuery('our_clients_slide');
    $result = $query->sort('weight', 'DESC')->range(0, 1)->execute();
    if ($result) {
      $last = Slide::load(reset($result));
      if ($last) {
        return $last->getWeight() + 1;
      }
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $fieldset = isset($values['media_image']) && is_array($values['media_image']) ? $values['media_image'] : [];

    /** @var \Drupal\our_clients\Entity\Slide $entity */
    $entity->set('label', (string) $values['label']);
    $entity->set('id', (string) $values['id']);
    $entity->set('status', !empty($values['status']));
    $entity->set('description', is_array($values['description']) ? $values['description'] : ['value' => '', 'format' => NULL]);
    $entity->set('weight', (int) $values['weight']);

    // Priority: a freshly uploaded image wins over a library selection; when
    // both are empty an existing slide keeps its current media item.
    $fid = isset($fieldset['upload'][0]) ? (int) $fieldset['upload'][0] : 0;
    $selected = isset($fieldset['existing']) ? (int) $fieldset['existing'] : 0;
    if ($fid) {
      $entity->set('image_media', self::mediaFromUpload($fid, (string) $values['label']));
    }
    elseif ($selected) {
      $entity->set('image_media', $selected);
    }
  }

  /**
   * Returns the source file ID behind the slide's current media item.
   *
   * Used to prefill the upload field when editing a slide whose media item is
   * based on an image file.
   *
   * @param \Drupal\our_clients\Entity\Slide $slide
   *   The slide.
   *
   * @return int
   *   The file ID, 0 if none.
   */
  protected function currentSourceFileId($slide) {
    if (!$slide->getImageMedia() || !\Drupal::moduleHandler()->moduleExists('media')) {
      return 0;
    }
    $media = \Drupal::entityTypeManager()->getStorage('media')->load($slide->getImageMedia());
    if (!$media || $media->getSource()->getPluginId() !== 'image') {
      return 0;
    }
    return (int) $media->getSource()->getSourceFieldValue($media);
  }

  /**
   * Creates a media item from an uploaded file.
   *
   * @param int $fid
   *   The uploaded file ID.
   * @param string $label
   *   The media item name.
   *
   * @return int
   *   The created media ID, 0 on failure.
   */
  protected static function mediaFromUpload($fid, $label) {
    $bundle_id = reset(\Drupal\our_clients\Entity\Slide::imageMediaBundles());
    $media_type = \Drupal::entityTypeManager()->getStorage('media_type')->load($bundle_id);
    if (!$fid || !$media_type) {
      return 0;
    }
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
    if (!$file) {
      return 0;
    }
    if (!$file->isPermanent()) {
      $file->setPermanent();
      $file->save();
    }
    $source_field = $media_type->getSource()->getConfiguration()['source_field'] ?? '';
    $media = \Drupal::entityTypeManager()->getStorage('media')->create([
      'bundle' => $bundle_id,
      'name' => $label,
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
      $source_field => [
        'target_id' => $fid,
        'alt' => $label,
        'title' => $label,
      ],
    ]);
    $media->save();
    return (int) $media->id();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\our_clients\Entity\Slide $slide */
    $slide = $this->entity;

    $slide->set('reviews', $this->processReviews($form_state));

    $status = $slide->save();

    if (!$slide->getImageMedia()) {
      $this->messenger()->addWarning($this->t('The slide @label was saved without an image because the Media module is disabled on this site. Enable the Media module and edit the slide to attach an image.', ['@label' => $slide->label()]));
    }

    $params = ['@label' => $slide->label()];
    $this->messenger()->addStatus($status === SAVED_NEW
      ? $this->t('The slide @label has been added.', $params)
      : $this->t('The slide @label has been saved.', $params)
    );

    $form_state->setRedirect('entity.our_clients_slide.collection');
  }

  /**
   * Creates, updates and deletes the review entities from the form values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The review IDs linked to the slide, in display order.
   */
  protected function processReviews(FormStateInterface $form_state) {
    $reviews_area = $form_state->getValue('reviews_area') ?: [];
    $map = $form_state->get('our_clients_reviews_map') ?: [];
    $storage = \Drupal::entityTypeManager()->getStorage('our_clients_review');

    $kept_ids = [];
    $delta = 0;
    foreach ($reviews_area as $values) {
      if (!is_array($values)) {
        continue;
      }
      $original_id = isset($map[$delta]) ? $map[$delta] : NULL;
      $delta++;

      if (!empty($values['remove'])) {
        continue;
      }

      $text = trim((string) ($values['text'] ?? ''));
      if ($text === '' && trim((string) ($values['author'] ?? '')) === ''
        && trim((string) ($values['city'] ?? '')) === '' && empty($values['date'])) {
        // An untouched empty slot: ignore it.
        continue;
      }

      if ($original_id && ($review = $storage->load($original_id))) {
        // Existing review: update in place.
      }
      else {
        $review = $storage->create(['id' => $this->generateReviewId()]);
      }

      $review->set('author', (string) ($values['author'] ?? ''));
      $review->set('date', (string) ($values['date'] ?? ''));
      $review->set('city', (string) ($values['city'] ?? ''));
      $review->set('rating', max(1, min(5, (int) ($values['rating'] ?? 5))));
      $review->set('text', $text);
      $dependencies = $review->get('dependencies') ?: [];
      $dependencies['enforced']['module'] = array_values(array_unique(array_merge($dependencies['enforced']['module'] ?? [], ['our_clients'])));
      $review->set('dependencies', $dependencies);
      $review->save();

      $kept_ids[] = $review->id();
    }

    // Reviews removed through the "Delete this review" checkbox are deleted.
    $orphaned = array_diff(array_filter($map), $kept_ids);
    foreach ($orphaned as $orphan_id) {
      if ($orphan = $storage->load($orphan_id)) {
        $orphan->delete();
      }
    }

    return $kept_ids;
  }

  /**
   * Generates a unique machine ID for a new review.
   *
   * @return string
   *   The ID.
   */
  protected function generateReviewId() {
    $storage = \Drupal::entityTypeManager()->getStorage('our_clients_review');
    do {
      $id = 'review_' . substr(md5(uniqid('', TRUE)), 0, 10);
    } while ($storage->load($id));
    return $id;
  }

}
