<?php

declare(strict_types=1);

namespace Drupal\example\Converter;

use League\HTMLToMarkdown\HtmlConverter;

final readonly class HtmlToMarkdownConverter {

  public function convert(string $html): string {
    $converter = new HtmlConverter([
      'header_style' => 'atx',
      'strip_tags' => TRUE,
      'remove_nodes' => 'script style nav form iframe',
    ]);

    return $converter->convert($html);
  }

}
