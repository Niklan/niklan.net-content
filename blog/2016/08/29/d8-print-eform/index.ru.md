---
id: d8-print-eform
language: ru
title: 'Drupal 8: Программный вывод EForm'
created: '2016-08-29T17:09:43'
updated: '2023-10-16T18:21:20'
needs_manual_review: true
description: 'Небольшая заметка о том, как выводить EForm (entityform из Drupal 7) программным способом  где это необходимо.'
promo: 'image/d8eform.png'
tags:
  - Drupal
  - 'Drupal 8'
  - EForm
---

Entityform под Drupal 8 теперь называется EForm и, как ни странно, программный вывод отличается от подхода в 7-ке. Это небольшая заметка о том как выводить EForm программно.

```php {"header":"Программный вывод EForm contact_form"}
$eform_type = EFormType::load('contact_form');
$eform_submit = new EFormSubmissionController();
$form = $eform_submit->submitPage($eform_type);
```

В `$form` будет находится render array с формой. Далее, в зависимости от ситуации, вы либо сразу отдаете его, либо рендерите через `render()`.
