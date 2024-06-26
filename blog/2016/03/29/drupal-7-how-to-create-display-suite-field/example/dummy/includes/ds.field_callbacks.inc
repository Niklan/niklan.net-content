<?php

/**
 * @file
 * Callbacks for DisplaySuite function fields.
 */

/**
 * Наша функция, которая будет вызвана и будет возвращать результат для поля.
 * Если функция ничего не возвращает, или возвращает false или null, то это
 * воспринимается как пустое поле и оно не будет отрендерено.
 *
 * @param $field
 *  В нём содержится вся необходимая нам информация.
 */
function dummy_ds_field_word_count($field) {
  // Обьект сущности, для которой вызвалось это поле, содержится в
  // $field['entity']. Для простоты и чистоты кода я использую EMW.
  // @read http://xandeadx.ru/blog/drupal/549,
  // http://www.mediacurrent.com/blog/entity-metadata-wrapper
  $entity_wrapper = entity_metadata_wrapper('node', $field['entity']);

  if ($body_value = $entity_wrapper->body->value->value()) {
    // Возвращаем результат.
    return format_string(
      '<strong>Количество слов в тексте:</strong> @word_count',
      array(
        '@word_count' => str_word_count(strip_tags($body_value))
      )
    );
  }
}
