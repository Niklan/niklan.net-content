Формат вывода Views Grid по дефолту выводит сетку таблицей. Когда я решил
сделать себе вывод в два столбца, я прибегнул к этому методу, но я не фанат
таблиц и не видел критической необходимости в использовании оных в данном
случае. Решение оказалось достаточно простое. Берем файлик
view-view-grid.tpl.php из модуля views и немного редактируем его под div'ы. (не
забываем создать копию в своей теме перед редактированием)

```php {"header":"view-view-grid.tpl.php"}
<?php
/**
 * @file
 * Вывод grid div'ами.
 */
?>
<?php if (!empty($title)) : ?>
  <h3><?php print $title; ?></h3>
<?php endif; ?>
<div class="<?php print $class; ?>"<?php print $attributes; ?>>
    <?php foreach ($rows as $row_number => $columns): ?>
      <div <?php if ($row_classes[$row_number]) { print 'class="row ' . $row_classes[$row_number] .'"';  } ?>>
        <?php foreach ($columns as $column_number => $item): ?>
          <div <?php if ($column_classes[$row_number][$column_number]) { print 'class="col ' . $column_classes[$row_number][$column_number] .'"';  } ?>>
          <?php if ($item): ?>  
            <?php print $item; ?>
          <?php endif; ?>           
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
</div>
```

После этого останется только поработать CSS.

## Ссылки

- [Исходный код шаблона с примером](example/views-view-grid.tpl.php)
