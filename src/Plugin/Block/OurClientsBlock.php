<?php

namespace Drupal\our_clients\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\our_clients\Entity\Slide;

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
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');

    $items = [];
    $media_ids = [];
    $file_ids = [];
    foreach ($slides as $slide) {
      $media = $slide->getImageMedia() ? Media::load($slide->getImageMedia()) : NULL;
      if (!$media) {
        continue;
      }

      $source_plugin_id = $media->getSource()->getPluginId();
      $is_video = in_array($source_plugin_id, Slide::VIDEO_SOURCE_PLUGIN_IDS, TRUE);

      if ($is_video) {
        // Videos are represented in the slider by their thumbnail and played
        // in the modal window with the site's default media formatter.
        $thumbnail = $media->get('thumbnail')->entity;
        if (!$thumbnail) {
          continue;
        }
        $items[] = [
          'title' => $slide->label(),
          'is_video' => TRUE,
          'slider_image_url' => $slider_style
            ? $slider_style->buildUrl($thumbnail->getFileUri())
            : $file_url_generator->generateString($thumbnail->getFileUri()),
          'image_alt' => self::thumbnailAlt($media, (string) $slide->label()),
          'modal_image_url' => '',
          'modal_media' => $view_builder->view($media, 'default'),
          'description' => self::processText($slide->getDescription()),
          'reviews' => $this->buildReviews($slide),
        ];
        $media_ids[] = $media->id();
        $file_ids[] = $thumbnail->id();
        continue;
      }

      $file = self::mediaFile($media);
      if (!$file) {
        continue;
      }
      $uri = $file->getFileUri();
      $items[] = [
        'title' => $slide->label(),
        'is_video' => FALSE,
        'slider_image_url' => $slider_style
          ? $slider_style->buildUrl($uri)
          : $file_url_generator->generateString($uri),
        'image_alt' => self::mediaAlt($media, (string) $slide->label()),
        'modal_image_url' => $modal_style
          ? $modal_style->buildUrl($uri)
          : $file_url_generator->generateString($uri),
        'modal_media' => NULL,
        'description' => self::processText($slide->getDescription()),
        'reviews' => $this->buildReviews($slide),
      ];
      $media_ids[] = $media->id();
      $file_ids[] = $file->id();
    }

    if (empty($items)) {
      if (\Drupal::currentUser()->hasPermission('administer our clients')) {
        return [
          '#markup' => '<p>' . $this->t('The "Our clients" slider has no slides yet. Add them on the <a href=":url">Our clients</a> page.', [':url' => '/admin/structure/our-clients']) . '</p>',
          '#cache' => $this->buildCacheMetadata(array_keys($slides), [], []),
        ];
      }
      return [];
    }

    return [
      '#theme' => 'our_clients_slider',
      '#heading' => $settings->get('heading'),
      '#slides' => $items,
      '#autoplay' => (bool) $settings->get('autoplay'),
      '#autoplay_speed' => (int) $settings->get('autoplay_speed'),
      '#visible_desktop' => (int) $settings->get('visible_desktop'),
      '#visible_tablet' => (int) $settings->get('visible_tablet'),
      '#visible_mobile' => (int) $settings->get('visible_mobile'),
      '#slider_max_width' => (int) $settings->get('slider_max_width'),
      '#image_height' => (int) $settings->get('image_height'),
      '#image_height_mobile' => (int) $settings->get('image_height_mobile'),
      '#name_font_size' => (int) $settings->get('name_font_size'),
      '#name_margin_top' => (int) $settings->get('name_margin_top'),
      '#name_margin_bottom' => (int) $settings->get('name_margin_bottom'),
      '#modal_width' => (int) $settings->get('modal_width'),
      '#review_color' => (string) $settings->get('review_color'),
      '#heading_font_size' => (int) $settings->get('heading_font_size'),
      '#heading_font_size_mobile' => (int) $settings->get('heading_font_size_mobile'),
      '#heading_margin_top' => (int) $settings->get('heading_margin_top'),
      '#heading_margin_bottom' => (int) $settings->get('heading_margin_bottom'),
      '#button_text_color' => (string) $settings->get('button_text_color'),
      '#button_hover_text_color' => (string) $settings->get('button_hover_text_color'),
      '#button_border_color' => (string) $settings->get('button_border_color'),
      '#button_background' => (string) $settings->get('button_background'),
      '#button_hover_background' => (string) $settings->get('button_hover_background'),
      '#button_border_radius' => (int) $settings->get('button_border_radius'),
      '#button_font_size' => (int) $settings->get('button_font_size'),
      '#button_font_size_mobile' => (int) $settings->get('button_font_size_mobile'),
      '#button_height' => (int) $settings->get('button_height'),
      '#button_height_mobile' => (int) $settings->get('button_height_mobile'),
      '#button_padding_x' => (int) $settings->get('button_padding_x'),
      '#cache' => $this->buildCacheMetadata(array_keys($slides), $media_ids, $file_ids),
    ];
  }

  /**
   * Builds cache metadata for the block.
   *
   * @param array $slide_ids
   *   The loaded slide IDs.
   * @param array $media_ids
   *   The media IDs referenced by the rendered slides.
   * @param array $file_ids
   *   The file IDs behind those media items.
   *
   * @return array
   *   Cache metadata array with tags and contexts.
   */
  /**
   * Builds the render data for a slide's reviews.
   *
   * @param \Drupal\our_clients\Entity\Slide $slide
   *   The slide.
   *
   * @return array
   *   List of review render arrays (empty when the slide has no reviews).
   */
  protected function buildReviews($slide) {
    $ids = $slide->getReviews();
    if (!$ids) {
      return [];
    }
    $storage = \Drupal::entityTypeManager()->getStorage('our_clients_review');
    /** @var \Drupal\our_clients\Entity\Review[] $reviews */
    $reviews = $storage->loadMultiple($ids);
    $date_formatter = \Drupal::service('date.formatter');

    $items = [];
    foreach ($ids as $id) {
      if (!isset($reviews[$id])) {
        continue;
      }
      $review = $reviews[$id];
      $rating = $review->getRating();
      $date = '';
      if ($review->getDate() && ($timestamp = strtotime($review->getDate()))) {
        $date = $date_formatter->format($timestamp, 'custom', 'd.m.Y');
      }
      $items[] = [
        'author' => $review->getAuthor(),
        'city' => $review->getCity(),
        'date' => $date,
        'rating' => $rating,
        'stars' => str_repeat('★', $rating) . str_repeat('☆', 5 - $rating),
        'text' => [
          '#markup' => nl2br(Html::escape($review->getText())),
        ],
      ];
    }
    return $items;
  }

  protected function buildCacheMetadata(array $slide_ids, array $media_ids, array $file_ids) {
    $tags = [
      'config:our_clients.settings',
      'config:our_clients_slide_list',
      'config:our_clients_review_list',
      'config:image.style.our_clients_slider',
      'config:image.style.our_clients_modal',
      'media_list',
    ];
    foreach ($slide_ids as $id) {
      $tags[] = 'config:our_clients.slide.' . $id;
    }
    foreach ($media_ids as $id) {
      $tags[] = 'media:' . $id;
    }
    foreach ($file_ids as $id) {
      $tags[] = 'file:' . $id;
    }
    return [
      'tags' => Cache::mergeTags($tags, []),
      'contexts' => ['user.permissions'],
    ];
  }

  /**
   * Returns the source file of a media item.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media item.
   *
   * @return \Drupal\file\Entity\File|null
   *   The file or NULL if the media has none.
   */
  protected static function mediaFile(Media $media) {
    $fid = (int) $media->getSource()->getSourceFieldValue($media);
    return $fid ? File::load($fid) : NULL;
  }

  /**
   * Returns the alt text of a media item's thumbnail.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media item.
   * @param string $fallback
   *   Text used when the thumbnail has no alt text.
   *
   * @return string
   *   The alt text.
   */
  protected static function thumbnailAlt(Media $media, string $fallback) {
    if (!$media->get('thumbnail')->isEmpty()) {
      $alt = (string) ($media->get('thumbnail')->first()->alt ?? '');
      if ($alt !== '') {
        return $alt;
      }
    }
    return $fallback;
  }

  /**
   * Returns the alt text of a media item's image.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media item.
   * @param string $fallback
   *   Text used when the media item has no alt text.
   *
   * @return string
   *   The alt text.
   */
  protected static function mediaAlt(Media $media, string $fallback) {
    $source_field = $media->getSource()->getConfiguration()['source_field'] ?? '';
    if ($source_field && $media->hasField($source_field) && !$media->get($source_field)->isEmpty()) {
      $alt = (string) ($media->get($source_field)->first()->alt ?? '');
      if ($alt !== '') {
        return $alt;
      }
    }
    return $fallback;
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
