---
id: d7-print-menu-programmatically
language: ru
title: 'Drupal 7: Программный вывод меню'
created: '2013-04-23T21:05:20'
updated: '2023-10-16T18:21:20'
needs_manual_review: true
description: 'Выводим меню программно.'
promo: 'image/menu.png'
tags:
  - Drupal
  - 'Drupal 7'
---

Вывод нужного меню программно:


~~~php
// Вывод меню "name-of-your-menu".
$menu_array = menu_navigation_links('menu-name-of-your-menu');
print theme('links__name_of_your_menu', array('links' => $menu_array));
~~~

~~~php {"header":"Также можно добавить классы для ul"}
$menu_array = menu_navigation_links('menu-name-of-your-menu');
print theme('links__name_of_your_menu', array(
  'links' => $menu_array,
  'attributes' => array(
    'class' => array('links', 'inline', 'clearfix', 'name-of-your-menu'),
  ),
));
~~~
