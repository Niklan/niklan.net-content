---
id: creating-a-drush-7-command-to-download-a-module-library
language: ru
title: 'Создаём команду Drush 7 для загрузки библиотеки модуля'
created: '2015-05-25T14:41:38'
updated: '2024-05-25T00:00:00'
description: >-
  Создание собственной команды для Drush 7 — это эффективный способ упростить
  процесс загрузки библиотеки модуля и сэкономить время.
promo: 'image/wallhaven-129615.jpg'
tags:
  - Drupal
  - 'Drupal 7'
  - Drush
---

Алоха народ!

Я часто при темизации, да чего уж, теперь вообще всегда, для слайдера и подобных
штук использую [OwlCarousel](http://owlgraphic.com/owlcarousel/). Этот плагин
гибкий, простой, и не лагает. Все работает как надо и вполне устраивает. Раньше
я его просто закидывал в тему и подключал, но тут оказалось что есть
соответствующий модуль для Drupal, решил попробовать но мне сразу не
понравилось, то что плагин надо также качать, распаковывать, заливать в
Libraries и т. д. В общем просто скинуть js в тему намного быстрее чем так
заморачиваться, и я решил помочь и накатить патч для модуля чтобы можно было
скачать через Drush, поэтому эта статья будет о том как делать драш команды на
загрузку.

Опыта написания под Drush у меня ещё не было и стало просто интересно. Не долго
думая я залез в исходники модуля Colorbox, где это реализовано и на его подобии
накатал патч и разобрался как работает код, о чем и решил поделиться, а то уже
блог мхом зарос.

## Создаём файл

Тут всё просто. Файл для команд драша должен называться MODULE_NAME.drush.inc,
его нужно положить в папку модуля. Как я понял особой разницы где она там будет
лежать — нету, но почти все ложат в подпапку drush, что как по мне, так
правильно.

## Пишем команду

Дальше особо объяснять не нужно, всё предельно просто, особенно если имели дело
с хуками. Достаточно будет и комментариев.

```php
/**
 * Implementation of hook_drush_command().
 */
function MYMODULE_drush_command() {
  $items = array();

  // Define our command to download via drush.
  // Use: drush owlcarousel-plugin
 $items['MYMODULE-download'] = array(
    'callback' => 'drush_MYMODULE_plugin', // Какую функцию вызовет команда.
    // Описание команды для справки. dt() - аналог t() для друпала. Это значит
    // что тут можно использовать только латиницу, а она будет переводимой
    // строкой.
    'description' => dt('Download and install the MYMODULE plugin.'),
    // DRUSH_BOOTSTRAP_DRUSH - означает что вызов данной команды не будет
    // поднимать Drupal, всё будет разрулено на уровне Drush.
    // Другие виды загрузки смотрите тут: http://api.drush.org/api/drush/includes%21bootstrap.inc/6.x
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    // Синонимы команды, например у 'drush user-login' синоним 'drush uli',
    // также и мы можем объявляеть синонимы для нашей команды.
    'aliases' => array('MYMODULE-plugin', 'MYMODULE-download'),
  );

  return $items;
}

/**
 * Implementation of hook_drush_help().
 *
 * Даннаый хук добавляеть информацию в справку.
 */
function MYMODULE_drush_help($section) {
  switch ($section) {
    case 'drush:MYMODULE-download':
      return dt('Download and install the MYMODULE plugin.');
  }
}

/**
 * Implements drush_MODULE_post_pm_enable().
 *
 * Этот хук не обязателен. Он срабатывает каждый раз (!) как включается
 * какой-либо модуль через 'drush en modulename'. Но у него есть полезная
 * задача. Мы можем автоматически скачать и установить плагин, если он был
 * включен через drush. Тем самым избавив пользователя от необходимости затем
 * вводить команду на загрузку плагина.
 */
function drush_MYMODULE_post_pm_enable() {
  $modules = func_get_args();
  // Проверяет на машинное название модуля.
  if (in_array('MYMODULE', $modules)) {
  	// Вызывает нашу функцию, которая используется в качестве callback для
  	// команды.
    drush_MYMODULE_plugin();
  }
}

/**
 * А это уже наша функция для загрузки и установки библотеки.
 * Именно её вызовет Drush после вызова нашей команды.
 */
function drush_MYMODULE_plugin() {
  // Сохраняем путь до папки с библотеками, пригодится для переноса файлов.
  $path = 'sites/all/libraries';

  // Если папки sites/all/libraries ещё нет, мы создаем её.
  if (!is_dir($path)) {
  	// Создание папки.
    drush_op('mkdir', $path);
    // drush_log - пишет сообщение в терминал, в данном случае, это просто
    // указывает юзеру, что путь создан и всё ок.
    drush_log(dt('Directory @path was created', array('@path' => $path)), 'notice');
  }

  // Сохраняем директорию, в которой сейчас находится Drush.
  $olddir = getcwd();
  // Переходим в директорию Libraries.
  chdir($path);

  // Качаем нашу библиотеку. Если по каким-то причинам файл не скачался,
 	// команда drush_download_file() вернет FALSE. Поэтому мы проверяем загрузку.
  if ($filepath = drush_download_file(MYMODULE_DOWNLOAD_URI)) {
    // Сохраняем название архива => 'OwlCarousel-1.3.2.zip'.
    $filename = basename($filepath);
    // Генерируем название как будет называться наша папка после
    // разархивирования => 'OwlCarousel-1.3.2'
    $dirname = 'OwlCarousel-' . basename($filename, '.zip');

    // Так как нам нужно установить плагин в папку all/libraries/owl-carousel
    // то мы проверяем, есть ли такая папка, если есть - удаляем, чтобы плашин
    // обновился.
    if (is_dir('owl-carousel')) {
      drush_delete_dir('owl-carousel', TRUE);
    }

    // Распаковываем скачанный архив 'OwlCarousel-1.3.2.zip'.
    drush_tarball_extract($filename);

    // Так как наша разархиварованная папка называется 'OwlCarousel-1.3.2',
    // то надо выполнить доп. действия. У нас же нужная папка просто лежит
    // внутри распакованной.
    if ($dirname != 'owl-carousel') {
    	// Мы переносим папку all/libraries/OwlCarousel-1.3.2/owl-carousel
    	// в папку all/libraries/owl-carousel
      drush_move_dir($dirname . '/owl-carousel', 'owl-carousel', TRUE);
      // Удаляем папку all/libraries/OwlCarousel-1.3.2, так как мы вытащили
      // нужную нам папку с файлами для модуля и 'мусор', надо за собой
      // подчистить.
      drush_delete_dir($dirname);
      // Записываем где лежит наш плагин.
      $dirname = 'owl-carousel';
    }
  }

  // Проверяем, если папка в результате распаковки стала 'owl-carousel',
  // то выводим что всё хорошо.
  if (is_dir($dirname)) {
    drush_log(dt('MYMODULE plugin has been installed in @path', array('@path' => $path)), 'success');
  }
  else {
  	// Иначе ошибку.
    drush_log(dt('Drush was unable to install the MYMODULE plugin to @path', array('@path' => $path)), 'error');
  }

  // Устанавливаем драшу директорию в которой он начал работу.
  chdir($olddir);
}
```

P.s. Inkscape подвел, виснет, поэтому не могу промо картинку нарисовать, залил
обои. :о
