<?php

namespace Drupal\dummy\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Global search index REST resource.
 *
 * @RestResource (
 *   id = "dummy_global_search",
 *   label = @Translation("Global search"),
 *   uri_paths = {
 *     "canonical" = "/api/search",
 *   }
 * )
 */
class GlobalSearchResource extends ResourceBase {

  /**
   * Responds to GET requests.
   */
  public function get() {
    $text = \Drupal::request()->query->get('text');
    $results = [
      'request' => $text,
      'items' => [],
      'has_more' => FALSE,
    ];

    /** @var \Drupal\search_api\IndexInterface $index_storage */
    $index_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_index');
    $index = $index_storage->load('global');
    /** @var \Drupal\search_api\ParseMode\ParseModePluginManager $parse_mode */
    $parse_mode_manager = \Drupal::service('plugin.manager.search_api.parse_mode');
    /** @var \Drupal\search_api\ParseMode\ParseModeInterface $parse_mode */
    $parse_mode = $parse_mode_manager->createInstance('terms');
    $parse_mode->setConjunction('OR');
    /** @var \Drupal\search_api\Plugin\views\query\SearchApiQuery $query */
    $search_query = $index->query();
    $search_query->setParseMode($parse_mode)
      ->keys($text)
      ->range(0, 5)
      ->sort('search_api_relevance', \Drupal\search_api\Query\QueryInterface::SORT_DESC);
    /** @var \Drupal\search_api\Query\ResultSetInterface $search_result */
    $search_result = $search_query->execute();
    foreach ($search_result->getResultItems() as $result_item) {
      $highlighted = $result_item->getExtraData('highlighted_fields', []);
      if (!empty($highlighted['title_fulltext'])) {
        $title = $highlighted['title_fulltext'][0];
      }
      else {
        $title = $result_item->getField('title')->getValues()[0];
      }

      $results['items'][] = [
        'title' => $title,
        'url' => $result_item->getField('url')->getValues()[0],
      ];
    }

    if ($search_result->getResultCount() > 5) {
      $results['has_mode'] = TRUE;
    }

    $cache = [
      '#cache' => [
        'context' => ['url'],
      ],
    ];
    return (new ResourceResponse($results))->addCacheableDependency($cache);
  }

}
