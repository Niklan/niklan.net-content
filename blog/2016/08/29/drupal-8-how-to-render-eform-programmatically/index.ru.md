---
id: drupal-8-how-to-render-eform-programmatically
language: ru
title: 'Drupal 8: Программный вывод EForm'
created: '2016-08-29T17:09:43'
updated: '2024-11-30T00:00:00'
description: >-
  Небольшая заметка о том, как выводить EForm (entityform из Drupal 7)
  программным способом, где это необходимо.
promo: 'image/poster.ru.png'
tags:
  - Drupal
  - 'Drupal 8'
  - EForm
---

Entityform под Drupal 8 теперь называется EForm и, как ни странно, программный
вывод отличается от подхода в 7-ке. Это небольшая заметка о том как выводить
EForm программно.

```php {"header":"Программный вывод EForm contact_form"}
$eform_type = EFormType::load('contact_form');
$eform_submit = new EFormSubmissionController();
$form = $eform_submit->submitPage($eform_type);
```

В `$form` будет находиться render array с формой. Далее, в зависимости от
ситуации, вы либо сразу отдаете его, либо рендерите через `render()`.
