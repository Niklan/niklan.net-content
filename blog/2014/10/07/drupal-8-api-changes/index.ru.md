---
id: drupal-8-api-changes
language: ru
title: 'Drupal 8: Изменения API'
created: '2014-10-07T17:30:27'
updated: '2024-11-30T00:00:00'
description: 'Подборка изменения в API Drupal 7 > Drupal 8'
promo: 'image/poster.ru.png'
tags:
  - Drupal
  - 'Drupal 8'
---

Что-то давненько в блог не писал ничего, но блог не брошен, просто не было
времени на написание статей, а сейчас еще и Drupal 8 beta вышел, что, как и
отняло время, так и дало простор для написания статей.

Если кто не следит за Drupal, 1 октября была выпущена версия Drupal 8 beta 1.
Что подразумевает собой финишную прямую к релизу. После того как беты сочтут
достаточно качественными и стабильными для продакшена, начнут выпускать RC,
каждую неделю (или две, точно не помню), до тех пор, пока один из RC не
продержится свой срок и не получит в issue ни одного критического бага. Если
прикинуть, то ожидать Drupal 8 (релиз) имеет смысл летом 2015.

А теперь непосредственно к самой статье. Drupal 8 — самый большой долгострой
среди всех предыдущих веток Drupal 8. На данный момент разработка Drupal 8
длится чуть больше, чем три с половиной года. Релиз Drupal 7 после 6 версии
состоялся через 2 года, 10 месяцев и 24 дня. Если прикинуть, то разработка
Drupal 7 длилась всего около 3 лет, а Drupal 8 только вышел к бете, которая
полгода протянет минимум.

Всё это говорит о том, что такое продолжительное время систему явно не улучшали,
а переписывали, причем кардинально. Так оно и есть, Drupal 8 под капотом просто
колоссально отличается от 7 версии. Я не совру, если скажу, что его полностью
переписали, при этом из функционального в ООП. Из этой статьи я постараюсь
сделать сборник самых популярных и известных функций API в 7 и их аналогов/замен
или что с ними случилось в Drupal 8. Поэтому эта статья будет дополняема и
обновляема. И, скорее всего, дальше я уже начну писать гайды под Drupal 8, так
как API очень стабилен и учиться самое время.

P.S. На начальных этапах альфы, да и вообще разработки Drupal 8, ожидалась, что
многие хуки будут действовать по-старому, как и в Drupal 7, для плавного
переучивания программистов. Так вот, в бета 1 хуков осталось просто
катастрофически мало, в общем, кто ожидал такого (а я помню, многие на drupal.ru
в обсуждениях писали про это), то огорчайтесь или радуйтесь, не знаю, но от
Drupal 7 почти ничего не осталось, кроме принципов и идеологии.

**Дата последнего обновления:** 9 октября 2015

### hook_menu()

Функцию окончательно убрали, хотя предполагалось что она будет оставлена до
поздних релизов Drupal 8.x. Не дожила.

Сейчас реализуется маршрутизацией. Это такой файлик, который ложится в корень
модуля и имеет название MODULENAME.routing.yml. Примерный вид (копипаст из файла
модуля [Mappy для Drupal 8](https://github.com/Niklan/Mappy/tree/8.x-1.x)):

```yml
mappy.settings:
  path: '/admin/config/content/mappy'
  defaults:
    _title: 'Mappy settings'
    _form: '\Drupal\mappy\Form\MappySettingsForm'
  requirements:
    _permission: 'administer site configuration'
```

Мы указываем системное имя для маршрута, путь, по которому будет ожидаться
запрос, заголовок страницы, в данном случае namespace для формы, которая будет
отображена, и права к данной странице.

**Документация на Drupal.org:**
[https://www.drupal.org/developing/api/8/routing](https://www.drupal.org/developing/api/8/routing)

### arg()

Простенькая функция, которая позволяет получить аргументы из URL. Также
выпилена, зачем, я, честно, вообще не понимаю, хотя указано, что из-за роутов и
типа новый метод лучше. Что же, это покажет время, что лучше, а что хуже, благо
написать свой аналог arg() вообще не проблема.

Имеем url **/node/100**

```php {"header":"Drupal 7"}
echo arg(0); // node
echo arg(1); // 100
```

```php {"header":"Drupal 8"}
$path_args = explode('/', current_path());
echo $path_args[0]; // node
echo $path_args[1]; // 1000
```

При этом не забывайте, если функция сама отрабатывала на элементы которые пустые
или вообще не существуют, то тут можно словить error.

**Документация на Drupal.org:** отсутствует

### hook_install() / hook_uninstall()

Хук (install) использовался в файлах модулей **MYMODULE.install**. Данный хук
вызывался в момент включения модуля, и его собрат uninstall, который вызывался в
момент удаления модуля (а не отключения). Позволяли задать базовые значения, а
также подчистить за собой грязь в базе после удаления.

В Drupal 8, все конфигурации хранятся в специальных файлах. Так как раньше
данные хуки в основном использовались для внесения базовых настроек аля *
*variable_set**, **variable_get**, их просто выпилили.

Теперь, чтобы задать настройки по умолчанию, необходимо создать в модуле
следующую структуру

MYMODULE (главная папка модуля)
— config
— install

В нем необходимо создать файлик **MYMODULE.settings.yml**
([пример](https://github.com/Niklan/Mappy/blob/8.x-1.x/config/install/mappy.settings.yml)) -
данные настройки будут импортированы в момент включения модуля, а также
автоматически удалены в момент … удаления. Ведь модули теперь только удаляются,
отключить, сохранив настройки, нельзя. Придется настройки ручками сохранять,
благо это сейчас удобно сделано и в админке.

Внутри него объявляется тип данных, название, описание и значения для данных.

```yml
type: settings
name: My module settings
descrption: Import settings on installation.
variable_a: value
variable_b:
  subvariable: true
```

Затем данные настройки будут доступны по следующему именованию:
MYMODULE.variable и MYMODULE.variable.subvariable.

**Документация на Drupal.org:**
[https://www.drupal.org/node/2087879](https://www.drupal.org/node/2087879)

### variable_get() / variable_set() / variable_del()

Функции позволяющие хранить данные в базе данных, очень просто и понятно.
Удалены в связи с тем что все основные данные и настройки хранятся в
сущностях-конфигурациях.

```php {"header":"Drupal 7"}
// Устанавливаем значение.
variable_set('key', 'value');
// Получаем значение.
variable_get('key'); // value
// Удаляем значение.
variable_del('key');
```

```php {"header":"Drupal 8"}
// Создаем объект конфигурации. machine-name - это название сущности - конфигурации.
// Может быть любым, в контексте модуля имеет смысл использовать паттерн: 
// MYMODULE.settings, например, для настроек.
$config = \Drupal::config('mymodule.settings');
// Запись значения.
$config->set('key','value');
$config->save();
// Получаем значение.
$config->get('key'); // value.
// Удаляем значение.
$config->delete('key');
$config->save();
```

Теперь, если экспортировать конфигурацию сайта, вы обнаружите файлик *
*mymodule.settings.yml** со следующим содержанием key: value.

**Документация на Drupal.org:**
[https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Config!Config.php/class/Config/8](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Config!Config.php/class/Config/8)

### hook_filter_info()

Хук который позволял объявлять свои фильтры для форматирования текста. Удален,
заменен системой плагинов, соответственно фильтр теперь плагин.

**Документация на Drupal.org:**
[https://www.drupal.org/node/2015901](https://www.drupal.org/node/2015901)

**Пример реализации:**
[GitHub](https://github.com/Niklan/Mappy/blob/8.x-1.x/src/Plugin/Filter/MappyFilter.php)

### drupal_add_js() / drupal_add_css() / drupal_add_library()

Функции удалены. Теперь подключаются на этапе подготовки\генерации страницы
средствами attached. Пример как добавить js:

Первым делом в корне модуля\темы создается файл **MYMODULE.libraries.yml**.

Примерное содержание:

```yml
mymodule.somelib:
  version: VERSION
  js:
    js/myscript.js: { }
  dependencies:
    - core/jquery
    - core/drupal
    - core/drupalSettings
```

Затем в хуке подключаем.

```php
/**
 * Implements hook_page_build().
 */
function mymodule_page_build(&$page) {
  $page['#attached']['library'][] = 'mymodule/mymodule.somelib';
}
```

Если необходимо передать в js файл значения, аля Drupal.settings в Drupal 7,
появляется доп строка в хуке:

```php
$page['#attached']['js'][] = array(
  'data' => array(
    'mymodule' => array(
      'location' => drupal_get_path('module', 'mymodule'),
    ),
  'type' => 'setting'
);
```

Теперь в js они получаются немного иначе: **drupalSettings.mymodule.location**

**Документация на Drupal.org:**
[https://www.drupal.org/node/2169605](https://www.drupal.org/node/2169605)

### theme()

Функция удалена. Теперь надо создавать renderable arrays. Если кто не в курсе,
это массив с данными котрые заетм прогоняется через render(). hook_theme()
остался прежним.

```php {"header":"Drupal 7"}
echo theme('theme_name', array('items' => $items));
```

```php {"header":"Drupal 8 "}
$items_array = array(
	'#theme' => 'theme_name',
	'#items' => $items;
);
echo render($items_array);
```

### check_plain()

Функция заменена на ООП аналог.

```php {"header":"Drupal 8"}
// Сначала указываем пространство имен (импортируем) класса, отвечающего за
// операции со строками.
use Drupal\Component\Utility\String;
// Используем новую функцию.
String::checkPlain('string');
```

### node_load() / node_load_multiple()

Функции заменены на ООП аналоги. Они доступна для Drupal 8, но будут удалены в
будущем, еще до релиза Drupal 9\. Так что использовать её сейчас нельзя. Просто
ещё не успели вычистить за ними все следы.

```php {"header":"Drupal 8"}
// Сначала указываем пространство имен (импортируем) класса, отвечающего за операции
// сущностей нод.
Use Drupal\node\Entity\Node;
// Используем новую функцию.
$node = Node::load(1);
$nodes = Node::loadMultiple(array(1,2,3));
```

### node_save()

Функция заменена на ООП аналог.

```php {"header":"Drupal 8 - Вариант 1"}
// Сначала указываем пространство имен (импортируем) класса, отвечающего за операции
// сущностей нод.
Use Drupal\node\Entity\Node;
// Используем новую функцию.
$node = Node::create(
  array(
    'title' => 'Заголовок ноды',
  ),
);

// Собственно сама замена.
$node->save();
```

```php {"header":"Drupal 8 - Вариант 2"}
$node= entity_create(
  'node',
  array(
    'title' => 'Заголовок ноды',
  ),
);

$node->save;
```

### menu_get_object()

Функция удалена. Обратите внимание что новый метод подходит для замены arg().

```php {"header":"Drupal 7"}
$node = menu_get_object();
if ($node->type == 'story') {
  ...
}
```

```php {"header":"Drupal 8"}
$request = \Drupal::request();
$node = $request()->attributes->get('node');
if ($node->type == 'story') {
  ...
}
```

### taxonomy_vocabulary_machine_name_load()

Функция удалена.

```php {"header":"Drupal 7"}
// Загружаем объект таксономии.
$vocabulary = taxonomy_vacabulary_machine_name_load('blog_categories');
// Выводим название словара таксономии.
echo $vocabulary->name;
```

```php {"header":"Drupal 8"}
// Загружаем объект таксономии.
$vocabulary = entity_load('taxonomy_vocabulary', 'blog_categories');
// Выводим название словара таксономии.
echo $vocabulary->label();
```

### taxonomy_get_tree()

Функция удалена, передаваемые параметры остались прежними.

```php {"header":"Drupal 8 - Вариант 1"}
$tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree();
```

```php {"header":"Drupal 8 - Вариант 2"}
$tree = \Drupal\taxonomy\TermStorageController::loadTree();
```

**Документация на Drupal.org:**
[https://api.drupal.org/api/drupal/core!modules!taxonomy!src!TermStorage.php/function/TermStorage%3A%3AloadTree/8](https://api.drupal.org/api/drupal/core!modules!taxonomy!src!TermStorage.php/function/TermStorage%3A%3AloadTree/8)

### field_info_field()

Функция удалена и заменена.

```php {"header":"Drupal 8"}
$fieldInfo = \Drupal\field\Field::fieldInfo();
$fieldInfo->getField($entity_type, $field_name);
```

### field_info_instance()

Функция удалена и заменена.

```php {"header":"Drupal 8"}
$fieldInfo = \Drupal\field\Field::fieldInfo();
$fieldInfo->getInstance('node', 'page', 'field_name');
```

### field_create_field()

Функция удалена и заменена.

```php {"header":"Drupal 8"}
$field = entity_create('field_config', array(
  'name' => 'field_myname',
  'entity_type' => 'node',
  'type' => 'text',
));
$field->save();
```

### field_create_instance()

Функция удалена и заменена.

```php {"header":"Drupal 8"}
$field_instance = entity_create('field_instance_config', array(
  'field_name' => 'field_myname',
  'entity_type' => 'node',
  'bundle' => 'page',
  'label' => 'My field name'
));
$field_instance->save;
```

### drupal_get_http_header() / drupal_http_headers

Функция удалена и заменена.

```php {"header":"Drupal 8"}
use \Symfony\Component\HttpFoundation\Response;
// ...
$response = new Response();
$response->getStatusCode();
```
