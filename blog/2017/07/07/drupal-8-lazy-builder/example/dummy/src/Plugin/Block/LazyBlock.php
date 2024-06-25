<?php

namespace Drupal\dummy\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "dummy_lazy_block",
 *   admin_label = @Translation("Lazy block"),
 * )
 */
class LazyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block['content'] = [
      '#create_placeholder' => TRUE,
      '#lazy_builder' => [
        'dummy.lazy_renderer:renderNodeList', [1000],
      ],
    ];
    return $block;
  }

}
