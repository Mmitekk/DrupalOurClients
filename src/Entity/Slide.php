<?php

namespace Drupal\our_clients\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

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
 *     "image",
 *     "image_alt",
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
   * The image file ID.
   *
   * @var int
   */
  protected $image = 0;

  /**
   * The image alt text.
   *
   * @var string
   */
  protected $image_alt = '';

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
   * Returns the image file ID.
   *
   * @return int
   *   The file ID, 0 if none.
   */
  public function getImage() {
    return (int) $this->image;
  }

  /**
   * Returns the image alt text.
   *
   * @return string
   *   The alt text.
   */
  public function getImageAlt() {
    return (string) $this->image_alt;
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
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    $file_usage = \Drupal::service('file.usage');
    foreach ($entities as $slide) {
      if ($slide instanceof Slide && ($fid = $slide->getImage()) && ($file = File::load($fid))) {
        $file_usage->delete($file, 'our_clients', 'our_clients_slide', $slide->id());
        self::releaseFile($file);
      }
    }
  }

  /**
   * Marks the file temporary when no other module uses it.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   */
  public static function releaseFile(FileInterface $file) {
    $usage = \Drupal::service('file.usage')->listUsage($file);
    if (empty($usage)) {
      $file->setTemporary();
      $file->save();
    }
  }

}
