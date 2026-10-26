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

    $form['heading_style'] = [
      '#type' => 'details',
      '#title' => $this->t('Heading'),
      '#open' => TRUE,
    ];

    $form['heading_style']['heading_font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Heading font size (px)'),
      '#default_value' => $config->get('heading_font_size'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
      '#description' => $this->t('Font size of the block heading. Leave 0 to inherit the h2 style of the site theme.'),
    ];

    $form['heading_style']['heading_font_size_mobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Heading font size on mobile (px)'),
      '#default_value' => $config->get('heading_font_size_mobile'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
    ];

    $form['heading_style']['heading_margin_top'] = [
      '#type' => 'number',
      '#title' => $this->t('Heading margin top (px)'),
      '#default_value' => $config->get('heading_margin_top'),
      '#min' => 0,
      '#max' => 200,
      '#field_suffix' => 'px',
      '#description' => $this->t('Vertical space above the heading. Leave 0 for the theme default.'),
    ];

    $form['heading_style']['heading_margin_bottom'] = [
      '#type' => 'number',
      '#title' => $this->t('Heading margin bottom (px)'),
      '#default_value' => $config->get('heading_margin_bottom'),
      '#min' => 0,
      '#max' => 200,
      '#field_suffix' => 'px',
      '#description' => $this->t('Vertical space between the heading and the slider. Leave 0 for the theme default.'),
    ];

    $form['button'] = [
      '#type' => 'details',
      '#title' => $this->t('"More details" button'),
      '#open' => TRUE,
    ];

    $form['button']['button_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text color'),
      '#default_value' => $config->get('button_text_color'),
      '#maxlength' => 7,
      '#placeholder' => '#ff5073',
      '#description' => $this->t('Leave empty for the default (#ff5073). Also used for the button border, the slide focus outline and the arrows on hover.'),
    ];

    $form['button']['button_hover_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text color on hover'),
      '#default_value' => $config->get('button_hover_text_color'),
      '#maxlength' => 7,
      '#placeholder' => '#ffffff',
      '#description' => $this->t('Leave empty to keep the normal button text color.'),
    ];

    $form['button']['button_background'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button background'),
      '#default_value' => $config->get('button_background'),
      '#maxlength' => 7,
      '#placeholder' => '#ffffff',
      '#description' => $this->t('Leave empty for white.'),
    ];

    $form['button']['button_hover_background'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button hover background'),
      '#default_value' => $config->get('button_hover_background'),
      '#maxlength' => 7,
      '#placeholder' => '#ff5073',
      '#description' => $this->t('Leave empty for a light tint of the button text color.'),
    ];

    $form['button']['button_border_radius'] = [
      '#type' => 'number',
      '#title' => $this->t('Button border radius (px)'),
      '#default_value' => $config->get('button_border_radius'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
      '#description' => $this->t('Corner rounding of the button. 0 keeps the default pill shape.'),
    ];

    $form['button']['button_font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Button font size (px)'),
      '#default_value' => $config->get('button_font_size'),
      '#min' => 0,
      '#max' => 40,
      '#field_suffix' => 'px',
    ];

    $form['button']['button_font_size_mobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Button font size on mobile (px)'),
      '#default_value' => $config->get('button_font_size_mobile'),
      '#min' => 0,
      '#max' => 40,
      '#field_suffix' => 'px',
    ];

    $form['button']['button_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Button height (px)'),
      '#default_value' => $config->get('button_height'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
    ];

    $form['button']['button_height_mobile'] = [
      '#type' => 'number',
      '#title' => $this->t('Button height on mobile (px)'),
      '#default_value' => $config->get('button_height_mobile'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
    ];

    $form['button']['button_padding_x'] = [
      '#type' => 'number',
      '#title' => $this->t('Button horizontal padding (px)'),
      '#default_value' => $config->get('button_padding_x'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
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

    $form['appearance']['name_margin_top'] = [
      '#type' => 'number',
      '#title' => $this->t('Client name margin top (px)'),
      '#default_value' => $config->get('name_margin_top'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
      '#description' => $this->t('Space between the image and the client name. Leave 0 for the default.'),
    ];

    $form['appearance']['name_margin_bottom'] = [
      '#type' => 'number',
      '#title' => $this->t('Client name margin bottom (px)'),
      '#default_value' => $config->get('name_margin_bottom'),
      '#min' => 0,
      '#max' => 100,
      '#field_suffix' => 'px',
      '#description' => $this->t('Space between the client name and the button. Leave 0 for none.'),
    ];

    $form['appearance']['modal_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Modal window width (px)'),
      '#default_value' => $config->get('modal_width'),
      '#min' => 320,
      '#max' => 1600,
      '#field_suffix' => 'px',
    ];

    $form['appearance']['review_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Review color'),
      '#default_value' => $config->get('review_color'),
      '#maxlength' => 7,
      '#placeholder' => '#ff5073',
      '#description' => $this->t('The color of the client reviews in the modal window: the stars, the left border and the background tint, plus the close button on hover. Leave empty for the default (#ff5073).'),
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
    foreach (['button_text_color', 'button_hover_text_color', 'button_background', 'button_hover_background', 'review_color'] as $key) {
      $value = trim((string) $form_state->getValue($key));
      if ($value !== '' && !preg_match('/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
        $form_state->setErrorByName($key, $this->t('Enter a hexadecimal color like #ff5073, or leave empty.'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Normalizes an optional hex color: lowercase, leading #, empty stays empty.
   *
   * @param mixed $value
   *   The submitted value.
   *
   * @return string
   *   The normalized hex color or an empty string.
   */
  protected function normalizeHexColor($value) {
    $value = trim((string) $value);
    if ($value === '') {
      return '';
    }
    $value = ltrim($value, '#');
    return '#' . strtolower($value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('heading', (string) $form_state->getValue('heading'))
      ->set('heading_font_size', (int) $form_state->getValue('heading_font_size'))
      ->set('heading_font_size_mobile', (int) $form_state->getValue('heading_font_size_mobile'))
      ->set('heading_margin_top', (int) $form_state->getValue('heading_margin_top'))
      ->set('heading_margin_bottom', (int) $form_state->getValue('heading_margin_bottom'))
      ->set('button_text_color', $this->normalizeHexColor($form_state->getValue('button_text_color')))
      ->set('button_hover_text_color', $this->normalizeHexColor($form_state->getValue('button_hover_text_color')))
      ->set('button_background', $this->normalizeHexColor($form_state->getValue('button_background')))
      ->set('button_hover_background', $this->normalizeHexColor($form_state->getValue('button_hover_background')))
      ->set('button_border_radius', (int) $form_state->getValue('button_border_radius'))
      ->set('button_font_size', (int) $form_state->getValue('button_font_size'))
      ->set('button_font_size_mobile', (int) $form_state->getValue('button_font_size_mobile'))
      ->set('button_height', (int) $form_state->getValue('button_height'))
      ->set('button_height_mobile', (int) $form_state->getValue('button_height_mobile'))
      ->set('button_padding_x', (int) $form_state->getValue('button_padding_x'))
      ->set('visible_desktop', (int) $form_state->getValue('visible_desktop'))
      ->set('visible_tablet', (int) $form_state->getValue('visible_tablet'))
      ->set('visible_mobile', (int) $form_state->getValue('visible_mobile'))
      ->set('autoplay', (bool) $form_state->getValue('autoplay'))
      ->set('autoplay_speed', (int) $form_state->getValue('autoplay_speed'))
      ->set('slider_max_width', (int) $form_state->getValue('slider_max_width'))
      ->set('image_height', (int) $form_state->getValue('image_height'))
      ->set('image_height_mobile', (int) $form_state->getValue('image_height_mobile'))
      ->set('name_font_size', (int) $form_state->getValue('name_font_size'))
      ->set('name_margin_top', (int) $form_state->getValue('name_margin_top'))
      ->set('name_margin_bottom', (int) $form_state->getValue('name_margin_bottom'))
      ->set('modal_width', (int) $form_state->getValue('modal_width'))
      ->set('review_color', $this->normalizeHexColor($form_state->getValue('review_color')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
