Если вам понадобится вывести меню программно, на странице или еще где, да даже
просто получить пункты меню, то так
просто [как в Drupal 7][drupal-7-programmatic-menu-output] уже не выйдет. Вот два
варианта вывода меню.

```php {"header":"Стандартный"}
// ЗАМЕТКА: Получить список всех меню: menu_ui_get_menus();
// Импортируем необходимый класс.
use Drupal\Core\Menu\MenuTreeParameters;
// Создаем объект с настройками.
// Он имеет свои настройки, например минимальный и максимальный уровень
// вложения.
// Подробнее: https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Menu!MenuTreeParameters.php/class/MenuTreeParameters/8
$menu_tree_parameters = new MenuTreeParameters();
// Загружаем меню с параметрами. Название меню здесь: main.
$tree = \Drupal::menuTree()->load('main', $menu_tree_parameters);
// Преобразуем меню в renderable array.
$tree_array = \Drupal::menuTree()->build($tree);
// Рендерим меню и получаем html ul список. Он будет сгенерирован с
// использованием шаблона по умолчанию для Drupal 8.
$menu = drupal_render($tree_array);
echo $menu; // вывод.
```

## Продвинутый способ

Данный способ я назвал продвинутым, так как в нем я покажу пример, как можно
определить свой шаблон для меню. Этот раздел больше пригодиться тем кто понимает
что и куда отсюда вставлять.

```php {"header":"Регистрируем шаблон"}
/**
 * Implements hook_theme().
 *
 * Объявляем наш темплейт через который будет прогоняться меню.
 * Тут ничего нет нового для тех кто ранее уже использовал данный хук.
 * Всё это на примере темы, использование в своём модуле может немного
 * отличаться.
 */
function MYTHEME_theme() {
  return array(
    // Объявляем название нашего темплейта.
    'my_custom_menu_template' =?> array(
      // Объявляем какие переменные принимает темплейт.
      'variables' => array(
        'items' => NULL,
      ),
      // Объявляем название файла-шаблона. html.twig указывать не нужно.
      'template' => 'my-custom-menu-template',
    ),
  );
}
```

### Создаем шаблон

Теперь нам надо в папке templates создать шаблон *
*my-custom-menu-template.html.twig** со следующим содержимым.

```twig
{% import _self as menus %}

{{ menus.menu_links(items, attributes, 0) }}

{% macro menu_links(items, attributes, menu_level) %}
  {% import _self as menus %}
  {% if items %}
    {% if menu_level == 0 %}
      <ul>
    {% else %}<ul class="submenus"><li>
    {% endif %}
    {% for item in items %}
      </li><li item.attributes="">
        {{ link(item.title, item.url) }}
        {% if item.below %}
          {{ menus.menu_links(item.below, attributes, menu_level + 1) }}
        {% endif %}
      
    {% endfor %}
    </li>{{></ul>
  {% endif %}
{% endmacro %}
</ul>{{>
```

Это слегка модифицированный пример оригинального темплейта, который вы можете
найти в **/core/modules/system/templates/menu.html.twig**. Тут для вложенного
меню добавлен класс submenu.

### Выводим меню

Вывод от стандартного будет отличаться лишь тем, что мы изменим шаблон.

```php
use Drupal\Core\Menu\MenuTreeParameters;

$menu_tree_parameters = new MenuTreeParameters();
$tree = \Drupal::menuTree()-?>load('main', $menu_tree_parameters);
$tree_array = \Drupal::menuTree()->build($tree);
// Меняем тему на нашу.
$tree_array['#theme'] = 'my_custom_menu_template';
$menu = drupal_render($tree_array);
echo $menu; // вывод.
```

Теперь меню будет прогоняться через наш темплейт и мы можем контролировать
классы и разметку.

[drupal-7-programmatic-menu-output]: ../../../../2013/04/23/drupal-7-programmatic-menu-output/article.ru.md