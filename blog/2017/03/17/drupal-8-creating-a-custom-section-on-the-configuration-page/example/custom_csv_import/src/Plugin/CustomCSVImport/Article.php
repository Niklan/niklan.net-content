<?php

namespace Drupal\custom_csv_import\Plugin\CustomCSVImport;

use Drupal\Core\Annotation\Translation;
use Drupal\custom_csv_import\Annotation\CustomCSVImport;
use Drupal\custom_csv_import\CustomCSVImportPluginBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class Article
 * @package Drupal\custom_csv_import\Plugin\CustomCSVImport
 *
 * @CustomCSVImport(
 *   id = "article",
 *   label = @Translation("Article")
 * )
 */
class Article extends CustomCSVImportPluginBase {

  /**
   * Import article node.
   *
   * @param $data
   * @param $context
   */
  public function processItem($data, &$context) {
    foreach ($data as $item) {
      list($id, $title, $body, $tags) = $item;
      if (!empty($id)) {
        $node = Node::load($id);
      }
      else {
        $node = Node::create([
          'type' => 'article',
          'langcode' => 'ru',
          'uid' => 1,
          'status' => 1,
        ]);
      }

      $node->title = $title;
      $node->body = [
        'value' => $body,
        'format' => 'full_html',
      ];
      $tags_array = explode(',', $tags);
      $tags_ids = [];
      foreach ($tags_array as $k => $v) {
        $query = \Drupal::entityQuery('taxonomy_term');
        $query->condition('vid', 'tags');
        $query->condition('name', $v);
        $query->range(0, 1);
        $result = $query->execute();
        $tid = reset($result);

        if ($tid) {
          $tags_ids[] = $tid;
        }
        else {
          $term = Term::create([
            'name' => $v,
            'vid' => 'tags',
          ]);
          $term->save();
          $tags_ids[] = $term->tid->value;
        }
      }
      $node->field_tags = $tags_ids;
      $node->save();
      $context['results'][] = $node->id() . ' : ' . $node->label();
      $context['message'] = $node->label();
    }
  }

}
