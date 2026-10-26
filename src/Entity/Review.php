<?php

namespace Drupal\our_clients\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Our clients review entity.
 *
 * Reviews are managed inline on the slide form: a slide references any number
 * of reviews through its multi-value "reviews" property.
 *
 * @ConfigEntityType(
 *   id = "our_clients_review",
 *   label = @Translation("Our clients review"),
 *   label_collection = @Translation("Our clients reviews"),
 *   config_prefix = "review",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "author",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   config_export = {
 *     "id",
 *     "author",
 *     "date",
 *     "city",
 *     "rating",
 *     "text",
 *     "uuid",
 *     "langcode"
 *   }
 * )
 */
class Review extends ConfigEntityBase {

  /**
   * The review ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The review author name.
   *
   * @var string
   */
  protected $author = '';

  /**
   * The review date (Y-m-d).
   *
   * @var string
   */
  protected $date = '';

  /**
   * The review city.
   *
   * @var string
   */
  protected $city = '';

  /**
   * The rating on a 1-5 scale.
   *
   * @var int
   */
  protected $rating = 5;

  /**
   * The review text (plain, rendered with line breaks preserved).
   *
   * @var string
   */
  protected $text = '';

  /**
   * Returns the review author name.
   *
   * @return string
   *   The author name.
   */
  public function getAuthor() {
    return (string) $this->author;
  }

  /**
   * Returns the review date.
   *
   * @return string
   *   The date in Y-m-d format.
   */
  public function getDate() {
    return (string) $this->date;
  }

  /**
   * Returns the review city.
   *
   * @return string
   *   The city.
   */
  public function getCity() {
    return (string) $this->city;
  }

  /**
   * Returns the rating.
   *
   * @return int
   *   The rating, 1-5.
   */
  public function getRating() {
    return max(1, min(5, (int) $this->rating));
  }

  /**
   * Returns the review text.
   *
   * @return string
   *   The text.
   */
  public function getText() {
    return (string) $this->text;
  }

}
