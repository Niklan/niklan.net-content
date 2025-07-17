Вывод нужного меню программно:

```php
// Вывод меню "name-of-your-menu".
$menu_array = menu_navigation_links('menu-name-of-your-menu');
print theme('links__name_of_your_menu', array('links' => $menu_array));
```

```php {"header":"Также можно добавить классы для ul"}
$menu_array = menu_navigation_links('menu-name-of-your-menu');
print theme('links__name_of_your_menu', array(
  'links' => $menu_array,
  'attributes' => array(
    'class' => array('links', 'inline', 'clearfix', 'name-of-your-menu'),
  ),
));
```
