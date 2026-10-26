<?php

namespace Drupal\our_clients\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
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

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'our_clients/our_clients.admin';

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

    // The media item is picked in a modal dialog, exactly like the core media
    // library widget: "Select media" opens the dialog, the chosen media ID
    // comes back through the hidden selection element, and the hidden update
    // button refreshes the preview via Ajax.
    $widget_id = Html::getUniqueId('our-clients-media-widget');
    $preview_wrapper_id = $widget_id . '-preview';
    $form_state->set('our_clients_media_widget_id', $widget_id);
    $form_state->set('our_clients_media_preview_wrapper', $preview_wrapper_id);

    $current_media_id = (int) $form_state->getValue(['media_image', 'selection'], $slide->getImageMedia());

    $form['media_image'] = [
      '#type' => 'details',
      '#title' => $this->t('Media image'),
      '#tree' => TRUE,
      '#open' => FALSE,
      '#description' => $this->t('Images are shown in the slider and enlarged in the modal window; videos show an automatic preview in the slider and are played in the modal window.'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('media')) {
      $form['media_image']['selection'] = [
        '#type' => 'hidden',
        '#default_value' => $current_media_id ?: '',
        '#attributes' => [
          'data-our-clients-media-value' => $widget_id,
        ],
      ];

      $form['media_image']['preview'] = [
        '#prefix' => '<div id="' . $preview_wrapper_id . '">',
        '#suffix' => '</div>',
        'item' => $this->buildMediaPreview($current_media_id),
      ];

      $form['media_image']['actions'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['our-clients-media-actions']],
      ];
      $form['media_image']['actions']['select'] = [
        '#type' => 'button',
        '#value' => $this->t('Select media'),
        '#name' => $widget_id . '-select-button',
        '#ajax' => [
          'callback' => '::openMediaDialog',
          'progress' => ['type' => 'throbber', 'message' => $this->t('Opening the media library.')],
        ],
      ];
      $form['media_image']['actions']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => $widget_id . '-remove-button',
        '#submit' => ['::removeMediaSubmit'],
        '#limit_validation_errors' => [['media_image', 'selection']],
        '#ajax' => [
          'callback' => '::removeMediaAjax',
          'progress' => ['type' => 'throbber', 'message' => $this->t('Removing.')],
        ],
      ];
      $form['media_image']['update'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update media'),
        '#name' => $widget_id . '-update-button',
        '#submit' => ['::updateMediaSubmit'],
        '#validate' => [],
        '#limit_validation_errors' => [['media_image', 'selection']],
        '#attributes' => [
          'data-our-clients-media-update' => $widget_id,
          'class' => ['js-hide'],
        ],
        '#ajax' => [
          'callback' => '::updateMediaPreview',
          'wrapper' => $preview_wrapper_id,
          'progress' => ['type' => 'throbber', 'message' => $this->t('Updating media.')],
        ],
      ];

      $form['media_image']['image_alt'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image alt text'),
        '#maxlength' => 255,
        '#default_value' => $slide->getImageAlt(),
        '#description' => $this->t('Describes the image for screen readers. Empty = the client name (or the alt text stored on the media item) is used.'),
      ];
      $form['media_image']['image_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image title text'),
        '#maxlength' => 255,
        '#default_value' => $slide->getImageTitle(),
        '#description' => $this->t('Shown as a tooltip when the mouse hovers over the image. Empty = the client name is used.'),
      ];
    }
    else {
      $form['media_image']['warning'] = [
        '#markup' => '<p>' . $this->t('The Media module is disabled on this site, so no image can be attached to the slide. Enable the Media module to select an image.') . '</p>',
      ];
    }

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

    $media_image = $form_state->getValue('media_image') ?: [];
    $selection = trim((string) ($media_image['selection'] ?? ''));

    if ($selection === '' && $this->entity->isNew() && \Drupal::moduleHandler()->moduleExists('media')) {
      $form['media_image']['#open'] = TRUE;
      $form_state->setErrorByName('media_image', $this->t('Add an image: click "Select media" and choose one from the media library.'));
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
    $media_image = isset($values['media_image']) && is_array($values['media_image']) ? $values['media_image'] : [];

    /** @var \Drupal\our_clients\Entity\Slide $entity */
    $entity->set('label', (string) $values['label']);
    $entity->set('id', (string) $values['id']);
    $entity->set('status', !empty($values['status']));
    $entity->set('description', is_array($values['description']) ? $values['description'] : ['value' => '', 'format' => NULL]);
    $entity->set('weight', (int) $values['weight']);
    $entity->set('image_alt', trim((string) ($media_image['image_alt'] ?? '')));
    $entity->set('image_title', trim((string) ($media_image['image_title'] ?? '')));

    // The hidden selection field is the single source of truth for the media
    // item: an empty value means the media was removed on purpose.
    if (\Drupal::moduleHandler()->moduleExists('media')) {
      $entity->set('image_media', (int) ($media_image['selection'] ?? 0));
    }
  }

  /**
   * Builds the current media selection preview.
   *
   * @param int $media_id
   *   The media ID, 0 for none.
   *
   * @return array
   *   The preview render array.
   */
  protected function buildMediaPreview($media_id) {
    $media_id = (int) $media_id;
    if ($media_id && \Drupal::moduleHandler()->moduleExists('media')) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
      if ($media) {
        $thumbnail = $media->get('thumbnail')->entity;
        return [
          '#type' => 'container',
          '#attributes' => ['class' => ['our-clients-media-preview']],
          'thumb' => $thumbnail ? [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => $thumbnail->getFileUri(),
            '#attributes' => ['class' => ['our-clients-admin-thumb']],
          ] : [],
          'name' => ['#markup' => Html::escape((string) $media->label())],
        ];
      }
    }
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['our-clients-media-preview', 'is-empty']],
      'empty' => ['#markup' => $this->t('No media selected yet.')],
    ];
  }

  /**
   * Ajax callback for the "Select media" button: opens the media dialog.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response opening the dialog.
   */
  public function openMediaDialog(array &$form, FormStateInterface $form_state) {
    $widget_id = (string) $form_state->get('our_clients_media_widget_id');
    $current = (int) ($form_state->getValue(['media_image', 'selection'], 0) ?: 0);
    $content = \Drupal::formBuilder()->getForm(MediaSelectForm::class, $widget_id, $current);
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Select media'), $content, [
      'width' => '75%',
      'height' => '75%',
      'dialogClass' => 'our-clients-media-dialog',
    ]));
    return $response;
  }

  /**
   * Submit handler for the hidden media update button.
   *
   * The value arrives through the hidden selection element; the ajax callback
   * rebuilds the preview from it.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateMediaSubmit(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax callback: rebuilds the media preview from the current selection.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The preview render array.
   */
  public function updateMediaPreview(array &$form, FormStateInterface $form_state) {
    $media_id = (int) ($form_state->getValue(['media_image', 'selection'], 0) ?: 0);
    return $this->buildMediaPreview($media_id);
  }

  /**
   * Submit handler for the "Remove" button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function removeMediaSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setValue(['media_image', 'selection'], '');
  }

  /**
   * Ajax callback for the "Remove" button.
   *
   * Clears the hidden selection in the DOM as well, so the cleared value also
   * survives full form rebuilds such as the "Add review" button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response.
   */
  public function removeMediaAjax(array &$form, FormStateInterface $form_state) {
    $widget_id = (string) $form_state->get('our_clients_media_widget_id');
    $wrapper_id = (string) $form_state->get('our_clients_media_preview_wrapper');
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('[data-our-clients-media-value="' . $widget_id . '"]', 'val', ['']));
    $response->addCommand(new ReplaceCommand('#' . $wrapper_id, $this->buildMediaPreview(0)));
    return $response;
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
      if (\Drupal::moduleHandler()->moduleExists('media')) {
        $this->messenger()->addWarning($this->t('The slide @label was saved without an image. Open the slide again and click "Select media" to attach one.', ['@label' => $slide->label()]));
      }
      else {
        $this->messenger()->addWarning($this->t('The slide @label was saved without an image because the Media module is disabled on this site. Enable the Media module and edit the slide to attach an image.', ['@label' => $slide->label()]));
      }
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
