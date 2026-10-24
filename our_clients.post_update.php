<?php

/**
 * @file
 * Post update functions for the Our clients module.
 */

/**
 * Converts stored slide image files into media entities.
 */
function our_clients_post_update_image_to_media(array &$sandbox) {
  $bundles = \Drupal\our_clients\Entity\Slide::imageMediaBundles();
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
