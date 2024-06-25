<?php

namespace Drupal\dummy;

/**
 * {@inheritdoc}
 */
class LazyRenderer {

  /**
   * Renderer for dummy_node_list theme hook.
   */
  public function renderNodeList($max_nodes = 10) {
    $build = [
      '#theme' => 'dummy_node_list',
      '#limit' => $max_nodes,
    ];

    return $build;
  }

}