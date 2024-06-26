<?php

namespace Drupal\custom_csv_import\Plugin\CustomCSVImport;

use Drupal\Core\Annotation\Translation;
use Drupal\custom_csv_import\Annotation\CustomCSVImport;
use Drupal\custom_csv_import\CustomCSVImportPluginBase;
use Drupal\node\Entity\Node;

/**
 * Class Article
 * @package Drupal\custom_csv_import\Plugin\CustomCSVImport
 *
 * @CustomCSVImport(
 *   id = "page",
 *   label = @Translation("Page")
 * )
 */
class Page extends CustomCSVImportPluginBase {

  /**
   * Import article node.
   *
   * @param $data
   * @param $context
   */
  public function processItem($data, &$context) {
    list($id, $title, $body) = $data;
    if (!empty($id)) {
      $node = Node::load($id);
    }
    else {
      $node = Node::create([
        'type' => 'page',
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
    $node->save();
    $context['results'][] = $node->id() . ' : ' . $node->label();
    $context['message'] = $node->label();
  }

}