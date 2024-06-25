<?php

namespace Drupal\dummy\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * {@inheritdoc}
 */
class LazyPage extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#title'] = 'Lazy builder test';
    $build['content'] = [
      '#create_placeholder' => TRUE,
      '#lazy_builder' => [
        'dummy.lazy_renderer:renderNodeList', [3000],
      ],
    ];
    return $build;
  }

}
