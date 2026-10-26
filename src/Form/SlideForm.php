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
    $form['media_image'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Media image'),
      '#tree' => TRUE,
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $fieldset = $form_state->getValue('media_image') ?: [];
    $upload = isset($fieldset['upload'][0]) ? (int) $fieldset['upload'][0] : 0;
    $selected = isset($fieldset['existing']) ? (int) $fieldset['existing'] : 0;

    if (!$upload && !$selected && $this->entity->isNew()) {
      $form_state->setErrorByName('media_image', $this->t('Add an image: select one from the media library or upload a new one.'));
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
    $entity->set('review', is_array($values['review']) ? $values['review'] : ['value' => '', 'format' => NULL]);
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
    $slide = $this->entity;
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

}
