---
id: d8-print-contact-form
language: ru
title: 'Drupal 8: Программный вывод формы Contact'
created: '2015-11-21T18:22:13'
updated: '2023-10-16T18:21:20'
needs_manual_review: true
description: 'Выводим формы модуля contact программно.'
promo: 'image/contactprogrammaticallyd8.png'
tags:
  - Drupal
  - 'Drupal 8'
  - Contact
---

На данный момент webform и eform (entityform) для Durpal 8 ещё не готовы или же сырые, поэтому приходится использовать стандартный модуль Contact для создания форм обратной связи на сайте.

Вызов данной формы, несколько "экзотический", поэтому я решил записать это на память, а может ещё и пригодиться кому.


## Получаем идентификатор формы


В модуле Contact, есть галочка 'Make this the default form' - которая позволяет пометить контактную форму по умолчанию для сайта.

Так как форма может называться как угодно, нам необходимо получать машинное имя данной формы, это делается следующим образом:

```php
$default_form_name = \Drupal::config('contact.settings')->get('default_form');
```

Далее мы получаем форму.

```php
# Получаем форму по её машинному имени напрямую, или можете задействовать
# $default_form_name.
$entity = \Drupal::entityManager()->getStorage('contact_form')->load('feedback');

$message = \Drupal::entityManager()
  ->getStorage('contact_message')
  ->create(array(
    'contact_form' => $entity->id(),
  ));

# Тут будет наша форма.
$form = \Drupal::service('entity.form_builder')->getForm($message)
```

## Выводим форму в Twig


В моём случае надо было вывести форму в темплейте page.html.twig. Для этого в файле `THEMENAME.theme` пишем:

```php {"header":"THEMENAME.theme"}
/**
 * Implements hook_preprocess_page().
 */
function THEMENAME_preprocess_page(&$variables) {
  $default_form = \Drupal::config('contact.settings')->get('default_form');
  $entity = \Drupal::entityManager()
    ->getStorage('contact_form')
    ->load($default_form);

  $message = \Drupal::entityManager()
    ->getStorage('contact_message')
    ->create(array(
      'contact_form' => $entity->id(),
    ));

  $variables['contact_form'] = \Drupal::service('entity.form_builder')->getForm($message);
}
```

```twig {"header":"page.html.twig"}
{{ contact_form }}
```
