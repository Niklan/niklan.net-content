<?php

/**
 * @file
 * This file contains functions for preprocess custom defined theme hooks.
 */
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_dummy_example_second(&$variables) {
  // Для удобства записываю значение переменной list_type в локальную переменную
  // в виде ссылки.
  $list_type = &$variables['list_type'];
  // Задаем список допустимых типов.
  $allowed_list_types = ['ol', 'ul'];
  // Если передано значение не из массива выше, мы устанавливаем значение по
  // умолчанию.
  if (!in_array($list_type, $allowed_list_types)) {
    $list_type = 'ul';
  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_dummy_example_quote(&$variables) {
  // Устанавливаем значение по умолчанию для новой переменной.
  $variables['source'] = NULL;
  // Сразу же переношу в локальную переменную-ссылку для дальнейшего удобства.
  $source = &$variables['source'];

  // Если переданы оба значения, то используем их для генерации ссылки.
  if ($variables['source_title'] && $variables['source_url']) {
    // Генерируем объект URL.
    $url = Url::fromUri($variables['source_url'], [
      'attributes' => [
        'target' => '_blank',
        'rel' => 'nofollow',
      ]
    ]);
    // Генерируем ссылку.
    $source = Link::fromTextAndUrl($variables['source_title'], $url)->toString();
  }
  elseif ($variables['source_title']) {
    $source = $variables['source_title'];
  }

  // Добавляем все объявленные переменные в один общий массив.
  $footer = [];
  !$variables['author'] ?: $footer[] = $variables['author'];
  !$variables['year'] ?: $footer[] = $variables['year'];
  !$source ?: $footer[] = $source;
  // И соединяем их в одну строку если там что-то есть.
  $variables['footer'] = !empty($footer) ? implode(', ', $footer) : FALSE;
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function dummy_theme_suggestions_dummy_example_quote(array $variables) {
  $suggestions = [];
  // Подключаем транслитерацию для будущих изменений.
  $transliteration = \Drupal::transliteration();
  // Сначала переносим значения в локальные переменные для удобства.
  $author = $variables['author'];
  $year = $variables['year'];
  $source = $variables['source_title'];
  // Затем надо поработать с переменными.
  // Первым делом подготовим имя автора для использования в названии темплейта.
  // Оно, во-первых должно быть из латинских символов. А что если передали
  // кирилицу? Мы должны это предусмотреть.
  if ($author) {
    // Первым делаем транслетирируем имя.
    $author = $transliteration->transliterate($author);
    // Переводм в нижний регистр.
    $author = strtolower($author);
    // Заменяем пробелы на нижнее подчеркивание, тире использовать НЕЛЬЗЯ.
    // В конечном итоге, например Иванов Иван будет ivanov_ivan, John Doe станет
    // john_doe.
    $author = str_replace(' ', '_', $author);
  }
  // Год нас не интересует, а вот название источника может быть длинным и опять
  // же, на разных языках. Проделываем аналогично имени автора.
  if ($source) {
    $source = $transliteration->transliterate($source);
    $source = strtolower($source);
    $source = str_replace(' ', '_', $source);
  }
  // Добавляем паттерн для года.
  if ($year) {
    $suggestions[] = 'dummy_example_quote__' . $year;
  }
  // Для источника.
  if ($source) {
    $suggestions[] = 'dummy_example_quote__' . $source;
  }
  // Добавляем паттерн для автора.
  if ($author) {
    $suggestions[] = 'dummy_example_quote__' . $author;
  }
  // Ели все переменные заданы регистрируем самый гибкий паттерн.
  if ($author && $year && $source) {
    $suggestions[] = 'dummy_example_quote__' . $author . '__' . $year . '__' . $source;
  }

  return $suggestions;
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_hook_theme_form_example(&$variables) {
  $variables['#attached']['library'][] = 'dummy/hook_theme_form.css';
}