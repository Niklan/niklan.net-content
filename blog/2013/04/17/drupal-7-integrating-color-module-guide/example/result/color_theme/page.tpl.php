<?php /*<div id="page">

  <div id="site-menu">
    <?php 
      $block = module_invoke('system', 'block_view', 'main-menu');
      echo render($block['content']);
    ?>
  </div>

  <div id="main">
    <?php print render($title_prefix); ?>
    <?php if ($title): ?>
      <h1 class="title" id="page-title"><?php print $title; ?></h1>
    <?php endif; ?>
    <?php print render($title_suffix); ?>      
    <?php if ($tabs): ?>
      <div class="tabs"><?php print render($tabs); ?></div>
    <?php endif; ?>
    <?php print $messages; ?>

    <div id="main-content" class="region clearfix">
      <?php print render($page['content']); ?>
    </div>
  </div>

  <div id="footer">
    <?php if ($page['footer']): ?>
      <?php print render($page['footer']); ?>
    <?php endif; ?>
  </div>
</div>
*/?>
<div id="page-wrapper">
  <header id="header">
    <hgroup id="site-name-slogan">
      <h1 id="site-name"><?php print $site_name; ?></h1>
      <h2 id="site-slogan"><?php print $site_slogan; ?></h2>
    </hgroup>

    <nav id="navigation">
      <?php 
        $block = module_invoke('system', 'block_view', 'main-menu');
        echo render($block['content']);
      ?>
    </nav>
  </header>

  <div id="content">
    <?php if ($title): ?>
      <h1 id="page-title"><?php print $title; ?></h1>
    <?php endif; ?>
    <?php print render($page['content']); ?>
  </div>

  <footer id="footer">
    &COPY; 2013
    <?php print render($page['footer']); ?>
  </footer>
</div>