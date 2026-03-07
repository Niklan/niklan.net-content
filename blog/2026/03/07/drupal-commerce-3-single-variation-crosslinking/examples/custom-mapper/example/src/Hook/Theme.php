<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;

#[Hook('theme')]
final readonly class Theme {

  public function __invoke(): array {
    return [
      'example_product_variant_selector' => [
        'variables' => [
          'groups' => [],
        ],
      ],
    ];
  }

}
