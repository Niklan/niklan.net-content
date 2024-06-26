<?php

/**
 * @file
 * DisplaySuite custom fields definitions.
 */

/**
 * Implements hook_ds_fields_info().
 */
function dummy_ds_fields_info($entity_type) {
  $fields = array();

  if ($entity_type == 'node') {
    $fields['word_count'] = array(
      'title' => 'DS: Количество слов в тексте',
      'field_type' => DS_FIELD_TYPE_FUNCTION,
      // Нам это поле нужно только в полном формате отображения материала
      // article
      'ui_limit' => array('article|full'),
      // Название функции мы даём на своё усмотрение, но я уже по привычке
      // использую формат: <modulename>_ds_field_<ds_field_name>
      'function' => 'dummy_ds_field_word_count',
      // Файл в котором искать функцию.
      'file' => drupal_get_path('module', 'dummy') . '/includes/ds.field_callbacks.inc',
    );

    $fields['word_frequency_list'] = array(
      'title' => 'DS: Список повторяющихся слов',
      'ui_limit' => array('article|full'),
      'field_type' => DS_FIELD_TYPE_THEME,
      'properties' => array(
        'formatters' => array(
          // Указывается темплейт функция и её название в админке.
          // Вы можете указать несколько, тем самым дав возможность выбора.
          'dummy_word_frequency_list' => 'Список повторяющихся слов',
        ),
        // Настройки не обязательны, но для примера введем их тут. Предоставим
        // выбор в каком списке выводить результаты.
        // Для работы настроек нужно написать два хука:
        // hook_ds_field_settings_form() & hook_ds_field_format_summary().
        'settings' => array(
          'list_wrapper' => array(
            'type' => 'select',
            'options' => array('ul', 'ol'),
          ),
        ),
        'default' => array(
          'list_wrapper' => 'ul',
        ),
      ),
    );

    return array($entity_type => $fields);
  }

  return;
}
