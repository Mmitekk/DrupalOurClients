<?php

namespace Drupal\our_clients\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the slides overview form with drag and drop ordering.
 */
class SlideOverviewForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a SlideOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'our_clients_slide_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('our_clients_slide');
    $ids = $storage->getQuery()
      ->sort('weight')
      ->sort('id')
      ->execute();
    /** @var \Drupal\our_clients\Entity\Slide[] $slides */
    $slides = $storage->loadMultiple($ids);

    $form['slides'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Slide'),
        $this->t('Status'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No slides yet. Add the first slide using the "Add slide" button.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'slide-order-weight',
        ],
      ],
    ];

    foreach ($slides as $id => $slide) {
      $form['slides'][$id]['#attributes']['class'][] = 'draggable';

      $info = [];
      if ($slide->getImageMedia() && ($media = $this->entityTypeManager->getStorage('media')->load($slide->getImageMedia()))) {
        $info['thumb'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['our-clients-admin-thumb-wrapper']],
          'thumbnail' => $media->get('thumbnail')->view('thumbnail'),
        ];
      }
      $info['label'] = [
        '#markup' => '<strong>' . \Drupal\Component\Utility\Html::escape((string) $slide->label()) . '</strong>',
      ];
      $form['slides'][$id]['info'] = $info;

      $form['slides'][$id]['status'] = [
        '#markup' => $slide->isEnabled() ? $this->t('Published') : $this->t('Unpublished'),
      ];

      $form['slides'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $slide->getWeight(),
        '#delta' => max(count($slides), 10) + 5,
        '#attributes' => ['class' => ['slide-order-weight']],
      ];

      $form['slides'][$id]['operations'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('entity.our_clients_slide.edit_form', ['our_clients_slide' => $id]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('entity.our_clients_slide.delete_form', ['our_clients_slide' => $id]),
          ],
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save order'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('our_clients_slide');
    foreach ($form_state->getValue('slides') as $id => $value) {
      if ($slide = $storage->load($id)) {
        $slide->set('weight', (int) $value['weight']);
        $slide->save();
      }
    }
    $this->messenger()->addStatus($this->t('The slide order has been saved.'));
  }

}
