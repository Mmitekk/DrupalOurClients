<?php

namespace Drupal\our_clients\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Our clients settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Name of the config object.
   */
  const SETTINGS = 'our_clients.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'our_clients_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    our_clients_maybe_import_russian_translation();

    $config = $this->config(static::SETTINGS);

    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block heading'),
      '#maxlength' => 255,
      '#default_value' => $config->get('heading'),
      '#description' => $this->t('The heading displayed above the slider. Leave empty to hide it. The technical block title can also be changed on the block placement form.'),
    ];

    $form['slider'] = [
      '#type' => 'details',
      '#title' => $this->t('Slider'),
      '#open' => TRUE,
    ];

    $form['slider']['visible_desktop'] = [
      '#type' => 'number',
      '#title' => $this->t('Visible slides on desktop'),
      '#default_value' => $config->get('visible_desktop'),
      '#min' => 1,
      '#max' => 8,
    ];

    $form['slider']['visible_tablet'] = [
      '#type' => 'number',
      '#title' => $this->t('Visible slides on tablet'),
      '#default_value' => $config->get('visible_tablet'),
      '#min' => 1,
      '#max' => 8,
    ];

    $form['slider']['visible_mobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Visible slides on mobile'),
      '#default_value' => $config->get('visible_mobile'),
      '#min' => 1,
      '#max' => 8,
    ];

    $form['slider']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $config->get('autoplay'),
      '#description' => $this->t('Automatically advance the slider. Autoplay is paused while the visitor hovers over the slider and for users who prefer reduced motion.'),
    ];

    $form['slider']['autoplay_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Autoplay interval (ms)'),
      '#default_value' => $config->get('autoplay_speed'),
      '#min' => 1000,
      '#max' => 30000,
      '#step' => 500,
      '#states' => [
        'visible' => [
          ':input[name="autoplay"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['appearance'] = [
      '#type' => 'details',
      '#title' => $this->t('Appearance'),
      '#open' => TRUE,
    ];

    $form['appearance']['slider_max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Slider maximum width (px)'),
      '#default_value' => $config->get('slider_max_width'),
      '#min' => 0,
      '#max' => 3840,
      '#field_suffix' => 'px',
      '#description' => $this->t('Maximum width of the slider area. Set 0 for the full width of the region.'),
    ];

    $form['appearance']['image_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Slide image height (px)'),
      '#default_value' => $config->get('image_height'),
      '#min' => 40,
      '#max' => 600,
      '#field_suffix' => 'px',
      '#description' => $this->t('The height of the image area of a slide. The image fills the area and is cropped when necessary.'),
    ];

    $form['appearance']['image_height_mobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Slide image height on mobile (px)'),
      '#default_value' => $config->get('image_height_mobile'),
      '#min' => 40,
      '#max' => 600,
      '#field_suffix' => 'px',
    ];

    $form['appearance']['name_font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Client name font size (px)'),
      '#default_value' => $config->get('name_font_size'),
      '#min' => 8,
      '#max' => 32,
      '#field_suffix' => 'px',
      '#description' => $this->t('The size of the client name text under the image.'),
    ];

    $form['appearance']['modal_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Modal window width (px)'),
      '#default_value' => $config->get('modal_width'),
      '#min' => 320,
      '#max' => 1600,
      '#field_suffix' => 'px',
    ];

    $form['appearance']['accent_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Accent color'),
      '#default_value' => $config->get('accent_color'),
      '#description' => $this->t('Used for the "More details" button, the arrows on hover and the review quote. Pick the primary color of your site theme to make the block blend in.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ((int) $form_state->getValue('autoplay_speed') < 1000) {
      $form_state->setErrorByName('autoplay_speed', $this->t('The autoplay interval must be at least 1000 ms.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('heading', (string) $form_state->getValue('heading'))
      ->set('visible_desktop', (int) $form_state->getValue('visible_desktop'))
      ->set('visible_tablet', (int) $form_state->getValue('visible_tablet'))
      ->set('visible_mobile', (int) $form_state->getValue('visible_mobile'))
      ->set('autoplay', (bool) $form_state->getValue('autoplay'))
      ->set('autoplay_speed', (int) $form_state->getValue('autoplay_speed'))
      ->set('slider_max_width', (int) $form_state->getValue('slider_max_width'))
      ->set('image_height', (int) $form_state->getValue('image_height'))
      ->set('image_height_mobile', (int) $form_state->getValue('image_height_mobile'))
      ->set('name_font_size', (int) $form_state->getValue('name_font_size'))
      ->set('modal_width', (int) $form_state->getValue('modal_width'))
      ->set('accent_color', (string) $form_state->getValue('accent_color'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
