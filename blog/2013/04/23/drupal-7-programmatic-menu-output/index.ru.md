---
id: drupal-7-programmatic-menu-output
language: ru
title: 'Drupal 7: Программный вывод меню'
created: '2013-04-23T21:05:20'
updated: '2024-11-30T00:00:00'
description: >-
  Освойте программный вывод меню в Drupal 7: упростите темизацию вашего сайта!
promo: 'image/poster.ru.png'
tags:
  - Drupal
  - 'Drupal 7'
---

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
