<?php

namespace Drupal\our_clients\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\our_clients\Entity\Slide;

/**
 * Modal dialog form for picking a media item for a slide.
 *
 * Mirrors the core media library widget: the chosen media ID is written back
 * into the hidden selection element of the slide form and its hidden update
 * button is triggered, so the slide form refreshes its preview without a page
 * reload.
 */
class MediaSelectForm extends FormBase {

  /**
   * The widget ID of the slide form this dialog belongs to.
   *
   * @var string
   */
  protected $widgetId;

  /**
   * The currently selected media ID, preselected in the grid.
   *
   * @var int
   */
  protected $currentMediaId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'our_clients_media_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The slide form passes the widget ID and the current selection through
    // the form state: FormBuilder never forwards getForm() arguments to the
    // form constructor, so constructor parameters cannot be used here.
    $widget_id = (string) $form_state->get('our_clients_media_widget_id');
    $this->widgetId = preg_match('/^[A-Za-z0-9-]+$/', $widget_id) ? $widget_id : '';
    $this->currentMediaId = (int) $form_state->get('our_clients_media_current_id', 0);

    $form['#attached']['library'][] = 'our_clients/our_clients.admin';

    if (!\Drupal::currentUser()->hasPermission('administer our clients')) {
      $form['denied'] = [
        '#markup' => '<p>' . $this->t('You are not allowed to select media.') . '</p>',
      ];
      return $form;
    }

    $grid_wrapper_id = Html::getUniqueId('our-clients-media-grid');

    $form['filter_row'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['our-clients-filter-row']],
    ];
    $form['filter_row']['filter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter by name'),
      '#default_value' => (string) $form_state->getValue('filter', ''),
      '#attributes' => ['placeholder' => $this->t('Start typing a media name…')],
    ];
    $form['filter_row']['apply'] = [
      '#type' => 'button',
      '#value' => $this->t('Apply'),
      '#name' => 'our-clients-filter-apply',
      '#ajax' => [
        'callback' => '::filterGrid',
        'wrapper' => $grid_wrapper_id,
        'progress' => ['type' => 'throbber', 'message' => $this->t('Filtering…')],
      ],
    ];

    $form['library'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $grid_wrapper_id . '" class="our-clients-media-grid">',
      '#suffix' => '</div>',
    ];
    $form['library']['items'] = $this->buildMediaGrid($form_state);

    $form['upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload a new image'),
      '#upload_location' => 'public://our_clients',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg webp'],
      ],
      '#description' => $this->t('The uploaded image is stored in the media library and selected automatically. Recommended size: at least 600x450 pixels.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload and use'),
      '#name' => 'our-clients-media-upload-use',
      '#submit' => ['::uploadSubmit'],
      '#limit_validation_errors' => [['upload']],
      '#ajax' => [
        'callback' => '::uploadSubmitAjax',
        'progress' => ['type' => 'throbber', 'message' => $this->t('Uploading…')],
      ],
    ];
    $form['actions']['insert'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert selected'),
      '#name' => 'our-clients-media-insert',
      '#button_type' => 'primary',
      '#limit_validation_errors' => [['library', 'items']],
      '#ajax' => [
        'callback' => '::insertSubmitAjax',
        'progress' => ['type' => 'throbber', 'message' => $this->t('Inserting…')],
      ],
    ];

    return $form;
  }

  /**
   * Builds the media items grid.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The tableselect render array.
   */
  protected function buildMediaGrid(FormStateInterface $form_state) {
    $element = [
      '#type' => 'tableselect',
      '#header' => [
        'thumb' => $this->t('Preview'),
        'name' => $this->t('Media item'),
      ],
      '#options' => [],
      '#empty' => $this->t('No media items found.'),
      '#multiple' => FALSE,
      '#js_select' => FALSE,
      '#default_value' => $this->currentMediaId ?: NULL,
    ];

    if (!\Drupal::moduleHandler()->moduleExists('media')) {
      return $element;
    }

    $query = \Drupal::entityQuery('media')
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->sort('name', 'ASC')
      ->range(0, 200);
    $bundles = Slide::slideMediaBundles();
    if ($bundles) {
      $query->condition('bundle', $bundles, 'IN');
    }
    $filter = trim((string) $form_state->getValue('filter'));
    if ($filter !== '') {
      $query->condition('name', '%' . \Drupal::database()->escapeLike($filter) . '%', 'LIKE');
    }

    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    foreach ($media_storage->loadMultiple($query->execute()) as $media) {
      $thumbnail_field = $media->hasField('thumbnail') ? $media->get('thumbnail') : NULL;
      $thumbnail = $thumbnail_field && !$thumbnail_field->isEmpty() ? $thumbnail_field->entity : NULL;
      $element['#options'][$media->id()] = [
        'thumb' => [
          'data' => $thumbnail ? [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => $thumbnail->getFileUri(),
            '#attributes' => ['class' => ['our-clients-admin-thumb']],
          ] : [],
        ],
        'name' => ['data' => ['#markup' => Html::escape((string) $media->label())]],
      ];
    }
    return $element;
  }

  /**
   * Ajax callback for the "Apply" button: refreshes the media grid.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The rebuilt grid render array.
   */
  public function filterGrid(array &$form, FormStateInterface $form_state) {
    return $this->buildMediaGrid($form_state);
  }

  /**
   * Submit handler for the "Upload and use" button.
   *
   * Creates the media item and remembers its ID for the ajax callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function uploadSubmit(array &$form, FormStateInterface $form_state) {
    $fids = $form_state->getValue('upload');
    $fid = is_array($fids) && $fids ? (int) reset($fids) : 0;
    $media_id = 0;
    if ($fid) {
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
      $label = $file ? (string) $file->getFilename() : $this->t('Our clients image');
      $media_id = Slide::mediaFromUpload($fid, $label);
    }
    $form_state->set('our_clients_new_media_id', $media_id);
  }

  /**
   * Ajax callback for the "Upload and use" button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response selecting the uploaded media item.
   */
  public function uploadSubmitAjax(array &$form, FormStateInterface $form_state) {
    $media_id = (int) $form_state->get('our_clients_new_media_id');
    if (!$media_id) {
      return (new AjaxResponse())
        ->addCommand(new MessageCommand($this->t('The upload failed. Try again.'), NULL, ['type' => 'warning']));
    }
    return $this->selectMediaResponse($media_id);
  }

  /**
   * Ajax callback for the "Insert selected" button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response selecting the chosen media item.
   */
  public function insertSubmitAjax(array &$form, FormStateInterface $form_state) {
    $raw = $form_state->getValue(['library', 'items']);
    $selected = is_array($raw) ? (int) reset($raw) : (int) $raw;
    if (!$selected) {
      return (new AjaxResponse())
        ->addCommand(new MessageCommand($this->t('Select a media item first.'), NULL, ['type' => 'warning']));
    }
    return $this->selectMediaResponse($selected);
  }

  /**
   * Builds the response that hands a media ID back to the slide form.
   *
   * @param int $media_id
   *   The media ID to select.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response.
   */
  protected function selectMediaResponse($media_id) {
    $response = new AjaxResponse();
    if ($this->widgetId !== '') {
      $response
        ->addCommand(new InvokeCommand('[data-our-clients-media-value="' . $this->widgetId . '"]', 'val', [(string) $media_id]))
        ->addCommand(new InvokeCommand('[data-our-clients-media-update="' . $this->widgetId . '"]', 'trigger', ['mousedown']));
    }
    return $response->addCommand(new CloseDialogCommand());
  }

}
