<?php

namespace Drupal\dummy\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Поле которое выводит количество слов в содержимом.
 *
 * @DsField(
 *   id = "word_count",
 *   title = @Translation("DS: Word count"),
 *   provider = "dummy",
 *   entity_type = "node",
 *   ui_limit = {"article|full"}
 * )
 */
class WordCount extends DsFieldBase {
  /**
   * {@inheritdoc}
   *
   * Метод который должен вернуть результат для поля.
   * ВАЖНО! Если в Drupal 7 колбек возвращать должен был строку, то здесь поле
   * обязано возвращать render array. Если хотите просто строкой вывести, юзайте
   * render array типа markup.
   */
  public function build() {
    $entity = $this->entity();
    if ($body_value = $entity->body->value) {
      return [
        '#type' => 'markup',
        '#markup' => new FormattableMarkup(
          '<strong>Количество слов в тексте:</strong> @word_count',
          [
            '@word_count' => str_word_count(strip_tags($body_value))
          ]
        )
      ];
    }
  }
}
