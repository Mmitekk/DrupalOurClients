<?php

namespace Drupal\our_clients\Form;

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

    $form['image_media'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Image'),
      '#required' => TRUE,
      '#allowed_bundles' => Slide::imageMediaBundles(),
      '#cardinality' => 1,
      '#default_value' => $slide->getImageMedia() ?: NULL,
      '#description' => $this->t('Upload a new image or pick an existing one from the media library. It is shown in the slider and enlarged in the modal window. Recommended size: at least 600x450 pixels. The alt text is taken from the media item.'),
    ];

    $description = $slide->getDescription();
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => isset($description['value']) ? $description['value'] : '',
      '#format' => $this->resolveFormat($description),
      '#description' => $this->t('Information about the client. Shown in the modal window.'),
    ];

    $review = $slide->getReview();
    $form['review'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Client review'),
      '#default_value' => isset($review['value']) ? $review['value'] : '',
      '#format' => $this->resolveFormat($review),
      '#description' => $this->t('The review text. Shown in the modal window as a quote.'),
    ];

    $form['weight'] = [
      '#type' => 'hidden',
      '#default_value' => $slide->isNew() ? $this->nextWeight() : $slide->getWeight(),
    ];

    return $form;
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

    /** @var \Drupal\our_clients\Entity\Slide $entity */
    $entity->set('label', (string) $values['label']);
    $entity->set('id', (string) $values['id']);
    $entity->set('status', !empty($values['status']));
    $entity->set('image_media', self::extractMediaId($values['image_media'] ?? NULL));
    $entity->set('description', is_array($values['description']) ? $values['description'] : ['value' => '', 'format' => NULL]);
    $entity->set('review', is_array($values['review']) ? $values['review'] : ['value' => '', 'format' => NULL]);
    $entity->set('weight', (int) $values['weight']);
  }

  /**
   * Extracts a single media ID from the media library element value.
   *
   * The element value may arrive as an ID, a comma-separated string or an
   * array depending on the submission path (full submit, AJAX rebuild or the
   * default value pass-through).
   *
   * @param mixed $raw
   *   The raw element value.
   *
   * @return int
   *   The first numeric media ID, 0 if none.
   */
  protected static function extractMediaId($raw) {
    if (is_array($raw)) {
      $raw = implode(',', $raw);
    }
    foreach (explode(',', (string) $raw) as $part) {
      $part = trim($part);
      if ($part !== '' && ctype_digit($part)) {
        return (int) $part;
      }
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $slide = $this->entity;
    $status = $slide->save();

    $params = ['@label' => $slide->label()];
    $this->messenger()->addStatus($status === SAVED_NEW
      ? $this->t('The slide @label has been added.', $params)
      : $this->t('The slide @label has been saved.', $params)
    );

    $form_state->setRedirect('entity.our_clients_slide.collection');
  }

}
