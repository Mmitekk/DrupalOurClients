<?php

namespace Drupal\our_clients\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides the Our clients slider block.
 *
 * @Block(
 *   id = "our_clients_block",
 *   admin_label = @Translation("Our clients slider"),
 *   category = @Translation("Our clients")
 * )
 */
class OurClientsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $settings = \Drupal::config('our_clients.settings');
    $storage = \Drupal::entityTypeManager()->getStorage('our_clients_slide');

    $ids = $storage->getQuery()
      ->condition('status', TRUE)
      ->sort('weight')
      ->sort('id')
      ->execute();

    /** @var \Drupal\our_clients\Entity\Slide[] $slides */
    $slides = $storage->loadMultiple($ids);

    $slider_style = ImageStyle::load('our_clients_slider');
    $modal_style = ImageStyle::load('our_clients_modal');
    $file_url_generator = \Drupal::service('file_url_generator');

    $items = [];
    foreach ($slides as $slide) {
      $file = $slide->getImage() ? File::load($slide->getImage()) : NULL;
      if (!$file) {
        continue;
      }
      $uri = $file->getFileUri();
      $items[] = [
        'title' => $slide->label(),
        'image_alt' => $slide->getImageAlt(),
        'slider_image_url' => $slider_style
          ? $slider_style->buildUrl($uri)
          : $file_url_generator->generateString($uri),
        'modal_image_url' => $modal_style
          ? $modal_style->buildUrl($uri)
          : $file_url_generator->generateString($uri),
        'description' => self::processText($slide->getDescription()),
        'review' => self::processText($slide->getReview()),
      ];
    }

    if (empty($items)) {
      if (\Drupal::currentUser()->hasPermission('administer our clients')) {
        return [
          '#markup' => '<p>' . $this->t('The "Our clients" slider has no slides yet. Add them on the <a href=":url">Our clients</a> page.', [':url' => '/admin/structure/our-clients']) . '</p>',
          '#cache' => $this->buildCacheMetadata(array_keys($slides)),
        ];
      }
      return [];
    }

    return [
      '#theme' => 'our_clients_slider',
      '#heading' => $settings->get('heading'),
      '#slides' => $items,
      '#accent_color' => $settings->get('accent_color'),
      '#autoplay' => (bool) $settings->get('autoplay'),
      '#autoplay_speed' => (int) $settings->get('autoplay_speed'),
      '#visible_desktop' => (int) $settings->get('visible_desktop'),
      '#visible_tablet' => (int) $settings->get('visible_tablet'),
      '#visible_mobile' => (int) $settings->get('visible_mobile'),
      '#slider_max_width' => (int) $settings->get('slider_max_width'),
      '#heading_font_size' => (int) $settings->get('heading_font_size'),
      '#heading_font_size_mobile' => (int) $settings->get('heading_font_size_mobile'),
      '#image_height' => (int) $settings->get('image_height'),
      '#image_height_mobile' => (int) $settings->get('image_height_mobile'),
      '#name_font_size' => (int) $settings->get('name_font_size'),
      '#modal_width' => (int) $settings->get('modal_width'),
      '#cache' => $this->buildCacheMetadata(array_keys($slides)),
    ];
  }

  /**
   * Builds cache metadata for the block.
   *
   * @param array $slide_ids
   *   The loaded slide IDs.
   *
   * @return array
   *   Cache metadata array with tags and contexts.
   */
  protected function buildCacheMetadata(array $slide_ids) {
    $tags = [
      'config:our_clients.settings',
      'config:our_clients_slide_list',
    ];
    foreach ($slide_ids as $id) {
      $tags[] = 'config:our_clients.slide.' . $id;
    }
    return [
      'tags' => Cache::mergeTags($tags, []),
      'contexts' => ['user.permissions'],
    ];
  }

  /**
   * Processes a stored formatted text value for rendering.
   *
   * @param array $text
   *   Array with 'value' and 'format' keys.
   *
   * @return array|null
   *   A render array or NULL when the text is empty.
   */
  protected static function processText(array $text) {
    if (empty($text['value'])) {
      return NULL;
    }
    $format_id = isset($text['format']) ? $text['format'] : NULL;
    if ($format_id && FilterFormat::load($format_id)) {
      return ['#markup' => check_markup($text['value'], $format_id)];
    }
    return ['#markup' => nl2br(Html::escape($text['value']))];
  }

}
