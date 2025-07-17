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
