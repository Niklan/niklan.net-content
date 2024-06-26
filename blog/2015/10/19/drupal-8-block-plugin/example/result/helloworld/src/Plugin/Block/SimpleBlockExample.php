<?php

/**
 * @file
 * Contains \Drupal\helloworld\Plugin\Block\SimpleBlockExample.
 */

// Пространство имён для нашего блока.
// helloworld - это наш модуль.
namespace Drupal\helloworld\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Добавляем простой блок с текстом.
 * Ниже - аннотация, она также обязательна.
 *
 * @Block(
 *   id = "simple_block_example",
 *   admin_label = @Translation("Simple block example"),
 * )
 */
class SimpleBlockExample extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#type' => 'markup',
      '#markup' => '<strong>Hello World!</strong>'
    ];
    return $block;
  }

}
