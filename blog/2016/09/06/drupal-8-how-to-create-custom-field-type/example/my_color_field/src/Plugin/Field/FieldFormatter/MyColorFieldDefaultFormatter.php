<?php

/**
 * @file
 * Contains \Drupal\my_color_field\Plugin\Field\FieldFormatter\MyColorFieldDefaultFormatter.
 */

namespace Drupal\my_color_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/** *
 * @FieldFormatter(
 *   id = "my_color_field_default_formatter",
 *   label = @Translation("HEX color"),
 *   field_types = {
 *     "my_color_field"
 *   }
 * )
 */
class MyColorFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      # Выводим наши элементы.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

}
