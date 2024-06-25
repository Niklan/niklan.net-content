<?php if (isset($results)): ?>
  <<?php print $list_format; ?>>
    <?php foreach ($results as $k => $v): ?>
      <li><?php print $k; ?> x <?php print $v; ?></li>
    <?php endforeach; ?>
  </<?php print $list_format; ?>>

  <strong>Количество повторяющихся слов:</strong> <?php print count($results); ?>
<?php endif; ?>
