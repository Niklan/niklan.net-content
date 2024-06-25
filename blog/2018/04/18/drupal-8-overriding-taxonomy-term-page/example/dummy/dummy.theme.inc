<?php

/**
 * @file
 * Dummy custom theme hook preprocesses.
 */

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_dummy_last_products_in_category(&$variables) {
  /** @var \Drupal\taxonomy\TermInterface $taxonomy_term */
  $taxonomy_term = $variables['taxonomy_term'];
  /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $cache = CacheableMetadata::createFromRenderArray([
    '#context' => [
      'url.path',
    ],
  ]);
  // Add current term to cache dependency (tags).
  $cache->addCacheableDependency($taxonomy_term);
  // Create array for our categories data.
  $items = [];
  // Load child terms.
  $childs = $term_storage->loadChildren($taxonomy_term->id());
  // Loop through each child and add it to our result array.
  /** @var \Drupal\taxonomy\TermInterface $child */
  foreach ($childs as $child) {
    $items[] = [
      'id' => $child->id(),
      'weigh' => $child->getWeight(),
      'label' => $child->label(),
      'url' => $child->toUrl()->toString(TRUE)->getGeneratedUrl(),
      'view' => views_embed_view('products', 'embed_2', $child->id()),
    ];
    // Add every child to cache dependency.
    $cache->addCacheableDependency($child);
  }
  // Add items to variables.
  $variables['items'] = $items;
  // Apply cache data to render array.
  $cache->applyTo($variables);
}
