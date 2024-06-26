<?php
/**
 * @file
 * Contains \Drupal\helloworld\Controller\CustomAutocomplete.
 */

namespace Drupal\helloworld\Controller;

// Указываем зависимости.
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

class CustomAutocomplete {

  /**
   * Метод который будет возвращять результаты для автодополнения.
   *
   * {@inheritdoc}
   */
  public function autocomplete(Request $request) {
    // Получаем текущий запрос автокомплита.
    $string = $request->query->get('q');
    // В данном массиве будут результаты для автодополнения, которые будут
    // выданы пользователю при вводе.
    // Каждый результат является массивом состоящим из значения и метки.
    // Значение - то что будет вставлено в поле автозаполнения, метка - то что
    // будет показано в выпадающем списке с возможными автодополнениями для
    // пользователя.
    $matches = [];

    if ($string) {
      // Делаем выборку по всему содержимому типа node, где заголовок похож
      // на введенный в поле автодополнения.
      $query = Database::getConnection()->select('node_field_data', 'n')
        ->fields('n', array('nid', 'title'))
        ->condition('n.title', '%' . $string . '%', 'LIKE')
        ->range(0, 10);
      // Выполняем запрос и получаем результаты.
      $result = $query->execute();

      // Добавляем результаты в массив.
      foreach ($result as $row) {
        $value = Html::escape($row->title . ' (' . $row->nid . ')');
        $label = Html::escape($row->title);
        $matches[] = ['value' => $value, 'label' => $label];
      }
    }

    return new JsonResponse($matches);
  }

}
