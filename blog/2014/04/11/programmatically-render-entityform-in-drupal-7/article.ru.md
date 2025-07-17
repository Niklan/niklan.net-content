Достаточно часто для создания форм в друпале предлагают использовать Entityform
вместо Webform. Программный вывод подобных форм отличается от привычных webform.

Собственно весь вывод делается в 4 строки.

```php
module_load_include('inc', 'entityform', 'entityform.admin');
$entityform_name= 'ENTITYFORM_NAME';
$entityform = entityform_form_wrapper(entityform_empty_load($entityform_name), 'submit', 'embedded');
print drupal_render($entityform);
```

Мы также можем править элементы и саму форму в $entityform, чтобы добавить
гибкости. Например задать значение по-умолчанию, или спрятать поле из формы на
определенной странице.
