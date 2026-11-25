<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

final class PaginationPathPolicy {

  private const array EXCLUDED_SEGMENTS = [
    '/admin/',
    '/jsonapi/',
    '/sitemap.xml/',
  ];

  public static function isExcluded(string $path): bool {
    $path = \strtolower(\rawurldecode($path)) . '/';

    return \array_any(
      array: self::EXCLUDED_SEGMENTS,
      callback: static fn (string $segment): bool => \str_contains($path, $segment),
    );
  }

}
