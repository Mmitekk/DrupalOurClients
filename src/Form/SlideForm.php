<?php

namespace Drupal\our_clients\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\our_clients\Entity\Slide;

/**
 * Form controller for the Our clients slide add and edit forms.
 */
class SlideForm extends EntityForm {

  /**
   * Key of the form state value holding the original image file ID.
   */
  const ORIGINAL_IMAGE_KEY = 'our_clients_original_image';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\our_clients\Entity\Slide $slide */
    $slide = $this->entity;

    $form_state->set(static::ORIGINAL_IMAGE_KEY, $slide->isNew() ? 0 : $slide->getImage());

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

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#required' => TRUE,
      '#default_value' => $slide->getImage() ? [$slide->getImage()] : NULL,
      '#upload_location' => 'public://our_clients',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg webp'],
      ],
      '#description' => $this->t('PNG, GIF, JPG, JPEG or WebP. A scaled copy is shown in the slider, the larger version opens in the modal window. Recommended size: at least 600x450 pixels.'),
    ];

    $form['image_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image alt text'),
      '#maxlength' => 512,
      '#default_value' => $slide->getImageAlt(),
      '#description' => $this->t('A short text description of the image for accessibility and SEO.'),
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
    $entity->set('image', isset($values['image'][0]) ? (int) $values['image'][0] : 0);
    $entity->set('image_alt', isset($values['image_alt']) ? (string) $values['image_alt'] : '');
    $entity->set('description', is_array($values['description']) ? $values['description'] : ['value' => '', 'format' => NULL]);
    $entity->set('review', is_array($values['review']) ? $values['review'] : ['value' => '', 'format' => NULL]);
    $entity->set('weight', (int) $values['weight']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\our_clients\Entity\Slide $slide */
    $slide = $this->entity;

    $file_usage = \Drupal::service('file.usage');
    $new_fid = $slide->getImage();
    $old_fid = (int) $form_state->get(static::ORIGINAL_IMAGE_KEY);

    // Register the usage of the new image.
    if ($new_fid && $new_fid !== $old_fid && ($file = File::load($new_fid))) {
      if (!$file->isPermanent()) {
        $file->setPermanent();
        $file->save();
      }
      $file_usage->add($file, 'our_clients', 'our_clients_slide', $slide->id());
    }

    // Release the usage of the replaced image.
    if ($old_fid && $old_fid !== $new_fid && ($old_file = File::load($old_fid))) {
      $file_usage->delete($old_file, 'our_clients', 'our_clients_slide', $slide->id());
      Slide::releaseFile($old_file);
    }
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
