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
   * Modules the media library form element depends on.
   */
  const MEDIA_DEPENDENCIES = ['media', 'media_library', 'media_library_form_element'];

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\our_clients\Entity\Slide $slide */
    $slide = $this->entity;

    // The media dependencies may be missing when the module code was updated
    // over an already-enabled module: Composer installs the new dependency
    // packages but never enables them, and Drupal silently skips unknown
    // element types, which would make the media field disappear. Schedule a
    // self-heal (performed after the response, see scheduleDependencyHeal())
    // and fall back to a plain image upload for the current request.
    if (!\Drupal::moduleHandler()->moduleExists('media_library_form_element')) {
      $this->scheduleDependencyHeal();
    }
    $media_element_available = \Drupal::moduleHandler()->moduleExists('media_library_form_element');

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

    if ($media_element_available) {
      $form['image_media'] = [
        '#type' => 'media_library',
        '#title' => $this->t('Media'),
        '#required' => TRUE,
        '#allowed_bundles' => Slide::slideMediaBundles(),
        '#cardinality' => 1,
        '#default_value' => $slide->getImageMedia() ?: NULL,
        '#description' => $this->t('Upload a new image or video, or pick an existing media item from the media library. Images are shown in the slider and enlarged in the modal window; videos show an automatic preview in the slider and are played in the modal window. Recommended image size: at least 600x450 pixels. The alt text is taken from the media item.'),
      ];
    }
    else {
      // Emergency fallback: the classic file upload. The uploaded file is
      // converted into a media entity on save, so nothing is lost.
      $form['image_media_fallback'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Image'),
        '#required' => !$slide->getImageMedia(),
        '#default_value' => $this->currentFallbackFileId($slide) ? [$this->currentFallbackFileId($slide)] : NULL,
        '#upload_location' => 'public://our_clients',
        '#upload_validators' => [
          'file_validate_extensions' => ['png gif jpg jpeg webp'],
        ],
        '#description' => $this->t('The media library modules are not available on this site, so a plain image upload is shown. The uploaded image is stored as a media item automatically.'),
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
   * Schedules the media dependency installation for after the response.
   *
   * Installing modules in the middle of a request invalidates cached entity
   * definitions and breaks the rest of the page, so the installation is
   * deferred to a PHP shutdown function: the current page renders normally
   * (with the fallback image field), the next one gets the media library.
   */
  protected function scheduleDependencyHeal() {
    if (\Drupal::state()->get('our_clients.heal_scheduled')) {
      return;
    }
    \Drupal::state()->set('our_clients.heal_scheduled', TRUE);
    register_shutdown_function(function () {
      $missing = [];
      try {
        $missing = array_filter(self::MEDIA_DEPENDENCIES, function ($module) {
          return !\Drupal::moduleHandler()->moduleExists($module);
        });
        if (!empty($missing)) {
          \Drupal::service('module_installer')->install($missing, TRUE);
          \Drupal::logger('our_clients')->info('Automatically enabled the media dependencies: @modules', [
            '@modules' => implode(', ', $missing),
          ]);
        }
      }
      catch (\Throwable $e) {
        \Drupal::logger('our_clients')->error('Unable to enable the media dependencies automatically: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
      \Drupal::state()->delete('our_clients.heal_scheduled');
    });
  }

  /**
   * Returns the file ID behind the slide's current media item.
   *
   * Used to prefill the emergency managed_file fallback.
   *
   * @param \Drupal\our_clients\Entity\Slide $slide
   *   The slide.
   *
   * @return int
   *   The file ID, 0 if none.
   */
  protected function currentFallbackFileId($slide) {
    if (!$slide->getImageMedia() || !($media = \Drupal::entityTypeManager()->getStorage('media')->load($slide->getImageMedia()))) {
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
    if (array_key_exists('image_media', $values)) {
      $entity->set('image_media', self::extractMediaId($values['image_media']));
    }
    elseif (array_key_exists('image_media_fallback', $values)) {
      // Emergency upload path: convert the file into a media item. When no
      // new file was uploaded the slide keeps its current media item.
      $fid = isset($values['image_media_fallback'][0]) ? (int) $values['image_media_fallback'][0] : 0;
      if ($fid) {
        $entity->set('image_media', self::mediaFromUpload($fid, (string) $values['label']));
      }
    }
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
