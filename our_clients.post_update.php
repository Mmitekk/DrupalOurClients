<?php

/**
 * @file
 * Post update functions for the Our clients module.
 */

/**
 * Converts stored slide image files into media entities.
 */
function our_clients_post_update_image_to_media(array &$sandbox) {  $bundles = \Drupal\our_clients\Entity\Slide::imageMediaBundles();
  $bundle_id = reset($bundles);

  $media_type = \Drupal::entityTypeManager()->getStorage('media_type')->load($bundle_id);
  if (!$media_type) {
    return t('No image media type exists on this site, legacy slide images were not migrated.');
  }
  $source_field = $media_type->getSource()->getConfiguration()['source_field'] ?? '';

  $file_storage = \Drupal::entityTypeManager()->getStorage('file');
  $media_storage = \Drupal::entityTypeManager()->getStorage('media');
  $config_factory = \Drupal::configFactory();

  foreach ($config_factory->listAll('our_clients.slide.') as $name) {
    $config = $config_factory->getEditable($name);
    $fid = (int) $config->get('image');
    $mid = (int) $config->get('image_media');

    if ($mid || !$fid) {
      continue;
    }
    if (!$file_storage->load($fid)) {
      $config->clear('image')->clear('image_alt')->save();
      continue;
    }

    $media = $media_storage->create([
      'bundle' => $bundle_id,
      'name' => (string) $config->get('label'),
      'uid' => 1,
      'status' => 1,
      $source_field => [
        'target_id' => $fid,
        'alt' => (string) $config->get('image_alt'),
        'title' => (string) $config->get('label'),
      ],
    ]);
    $media->save();

    $config->set('image_media', (int) $media->id())
      ->clear('image')
      ->clear('image_alt')
      ->save();
  }
}

/**
 * Detaches the no-longer-needed media_library_form_element module.
 *
 * Since 1.3.0 the slide form ships its own media selection and does not use
 * the contrib module anymore. Composer may have already removed its files;
 * this removes the enabled-module record so the site stays consistent.
 */
function our_clients_post_update_detach_media_library_form_element(array &$sandbox = NULL) {
  if (\Drupal::moduleHandler()->moduleExists('media_library_form_element')) {
    try {
      \Drupal::service('module_installer')->uninstall(['media_library_form_element'], TRUE);
    }
    catch (\Throwable $e) {
      \Drupal::logger('our_clients')->warning('Failed to uninstall media_library_form_element: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }
  // Leftover from the 1.2.1 self-healing attempt.
  \Drupal::state()->delete('our_clients.heal_scheduled');
}

/**
 * Converts the single legacy review text into review entities.
 */
function our_clients_post_update_reviews_to_entity(array &$sandbox = NULL) {
  $storage = \Drupal::entityTypeManager()->getStorage('our_clients_review');
  $config_factory = \Drupal::configFactory();

  foreach ($config_factory->listAll('our_clients.slide.') as $name) {
    $config = $config_factory->getEditable($name);
    $old = $config->get('review');
    if (empty($old['value']) || $config->get('reviews')) {
      continue;
    }

    $review = $storage->create([
      'id' => 'review_' . substr(md5($name . uniqid('', TRUE)), 0, 10),
      'author' => '',
      'date' => date('Y-m-d'),
      'city' => '',
      'rating' => 5,
      'text' => trim(strip_tags((string) $old['value'])),
    ]);
    $dependencies = $review->get('dependencies') ?: [];
    $dependencies['enforced']['module'][] = 'our_clients';
    $review->set('dependencies', $dependencies);
    $review->save();

    $config->set('reviews', [$review->id()])->clear('review')->save();
  }
}

/**
 * Refreshes the default appearance values (Stormoff-style slider design).
 *
 * Settings that still hold the 1.4.0 defaults are updated to the new design
 * defaults; values customized by the site owner are left untouched.
 */
function our_clients_post_update_refresh_default_appearance(array &$sandbox = NULL) {
  $config = \Drupal::configFactory()->getEditable('our_clients.settings');
  $defaults = [
    'image_height' => ['old' => 130, 'new' => 390],
    'image_height_mobile' => ['old' => 100, 'new' => 200],
    'name_font_size' => ['old' => 14, 'new' => 24],
  ];
  foreach ($defaults as $key => $values) {
    if ($config->get($key) === $values['old']) {
      $config->set($key, $values['new']);
    }
  }
  // The heading size settings are gone: the heading now inherits the theme's
  // h2 style entirely.
  $config->clear('heading_font_size')->clear('heading_font_size_mobile');
  $config->save();
}

/**
 * Removes the vague accent color setting.
 *
 * The single "Accent color" was split into understandable per-element colors:
 * the button text color (also used for the button border, the focus outline
 * and the arrows on hover) and the review color in the modal window.
 */
function our_clients_post_update_remove_accent_color(array &$sandbox = NULL) {
  \Drupal::configFactory()->getEditable('our_clients.settings')
    ->clear('accent_color')
    ->save();
}
