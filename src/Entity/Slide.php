<?php

namespace Drupal\our_clients\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Our clients slide entity.
 *
 * @ConfigEntityType(
 *   id = "our_clients_slide",
 *   label = @Translation("Our clients slide"),
 *   label_collection = @Translation("Our clients slides"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\our_clients\Form\SlideForm",
 *       "edit" = "Drupal\our_clients\Form\SlideForm",
 *       "delete" = "Drupal\our_clients\Form\SlideDeleteForm"
 *     }
 *   },
 *   config_prefix = "slide",
 *   admin_permission = "administer our clients",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "image_media",
 *     "description",
 *     "review",
 *     "weight",
 *     "status",
 *     "uuid",
 *     "langcode"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/our-clients",
 *     "add-form" = "/admin/structure/our-clients/add",
 *     "edit-form" = "/admin/structure/our-clients/{our_clients_slide}",
 *     "delete-form" = "/admin/structure/our-clients/{our_clients_slide}/delete"
 *   }
 * )
 */
class Slide extends ConfigEntityBase {

  /**
   * The slide ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The client name.
   *
   * @var string
   */
  protected $label;

  /**
   * The referenced image media entity ID.
   *
   * @var int
   */
  protected $image_media = 0;

  /**
   * The description (value + format).
   *
   * @var array
   */
  protected $description = [];

  /**
   * The client review (value + format).
   *
   * @var array
   */
  protected $review = [];

  /**
   * The weight used for ordering.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The published status.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Returns the referenced media entity ID.
   *
   * @return int
   *   The media ID, 0 if none.
   */
  public function getImageMedia() {
    return (int) $this->image_media;
  }

  /**
   * Returns the description.
   *
   * @return array
   *   Array with 'value' and 'format' keys.
   */
  public function getDescription() {
    return is_array($this->description) ? $this->description : [];
  }

  /**
   * Returns the client review.
   *
   * @return array
   *   Array with 'value' and 'format' keys.
   */
  public function getReview() {
    return is_array($this->review) ? $this->review : [];
  }

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight() {
    return (int) $this->weight;
  }

  /**
   * Returns whether the slide is enabled.
   *
   * @return bool
   *   TRUE if enabled.
   */
  public function isEnabled() {
    return (bool) $this->status;
  }

  /**
   * Returns the machine names of media bundles using the image source.
   *
   * Used by the post-update migration: legacy slide images are converted into
   * media entities of the first found image bundle.
   *
   * @return string[]
   *   Media bundle IDs.
   */
  public static function imageMediaBundles() {
    $bundles = [];
    foreach (\Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple() as $type) {
      $source = $type->getSource();
      if ($source && $source->getPluginId() === 'image') {
        $bundles[] = $type->id();
      }
    }
    return $bundles ?: ['image'];
  }

  /**
   * The media source plugin IDs treated as video.
   *
   * Core uses "video_file" for uploaded videos and "oembed:video" for remote
   * providers such as YouTube or Vimeo.
   *
   * @var string[]
   */
  const VIDEO_SOURCE_PLUGIN_IDS = ['video_file', 'oembed:video'];

  /**
   * Returns the media bundles allowed for a slide.
   *
   * Every media type based on an image, video or oEmbed video source is
   * allowed, so a slide can hold either an image or a video (uploaded file or
   * a remote one such as YouTube/VK). Falls back to the standard "image"
   * bundle on sites where no such media types exist yet.
   *
   * @return string[]
   *   Media bundle IDs.
   */
  public static function slideMediaBundles() {
    $bundles = [];
    foreach (\Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple() as $type) {
      $source = $type->getSource();
      if (!$source) {
        continue;
      }
      $plugin_id = $source->getPluginId();
      if ($plugin_id === 'image' || in_array($plugin_id, self::VIDEO_SOURCE_PLUGIN_IDS, TRUE)) {
        $bundles[] = $type->id();
      }
    }
    return $bundles ?: ['image'];
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // The media items themselves are kept: they live in the media library and
    // may be reused by other slides or content.
  }

}
