<?php

namespace Drupal\dummy\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Поле выводит список повторяющихся слов из поля body.
 *
 * @DsField(
 *   id = "word_frequency",
 *   title = @Translation("DS: Word frequency"),
 *   provider = "dummy",
 *   entity_type = "node",
 *   ui_limit = {"article|full"}
 * )
 */
class WordFrequency extends DsFieldBase {

  /**
   * {@inheritdoc}
   * Это наш собственынй метод в котором мы подгатавливаем массив с
   * повторящимеся полями и их количеством повторений. Сделано это лишь для
   * поддержания чистоты кода и читабельности, это не обязательный метод.
   */
  public function prepareWordFrequencyArray() {
    $entity = $this->entity();
    if ($body_value = $entity->body->value) {
      # Получаем весь список повторяющихся слов.
      $words_with_count = array_count_values(str_word_count(strip_tags($body_value), 1));
      # Удаляем значения меньше меньше 2.
      foreach ($words_with_count as $k => $v) {
        if ($v < 2) {
          unset($words_with_count[$k]);
        }
      }
      # Сортируем по убыванию.
      arsort($words_with_count);
      # Теперь нам надо создать массив со значениями, чтобы было проще отдавать
      # на рендер. Нам нужено чтобы ключ был любым а значение: "Слово х N".
      $results = [];
      foreach ($words_with_count as $word => $count) {
        $results[] = $word . ' x ' . $count;
      }
      # Возвращаем результат.
      return $results;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatters() {
    # Возвращаем массив с вомзожными форматами: ключ => метка.
    return ['ol' => 'Нумерованный список', 'ul' => 'Марикрованный список'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    # Записываем выбранный формат в переменную.
    $list_type = $config['field']['formatter'];
    return [
      '#theme' => 'item_list',
      '#title' => 'Повторяющиеся слова:',
      '#items' => $this->prepareWordFrequencyArray(),
      '#list_type' => $list_type,
    ];
  }
}
