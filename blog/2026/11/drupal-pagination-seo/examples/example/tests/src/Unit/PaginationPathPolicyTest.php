<?php

declare(strict_types=1);

namespace Drupal\Tests\example\Unit;

use Drupal\example\Pager\PaginationPathPolicy;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(PaginationPathPolicy::class)]
#[Group('example')]
final class PaginationPathPolicyTest extends UnitTestCase {

  #[DataProvider('excludedPaths')]
  public function testExcludedPath(string $path): void {
    $this->assertTrue(PaginationPathPolicy::isExcluded($path), $path);
  }

  /**
   * @return \Generator<string, array{string}>
   */
  public static function excludedPaths(): \Generator {
    yield 'admin root' => ['/admin'];
    yield 'admin with slash' => ['/admin/'];
    yield 'uppercase admin' => ['/ADMIN/content'];
    yield 'encoded admin' => ['/%61dmin/content'];
    yield 'language prefix' => ['/en/admin/content'];
    yield 'alias with admin segment' => ['/blog/admin/foo'];
    yield 'jsonapi' => ['/jsonapi/node/article'];
    yield 'language jsonapi' => ['/en/jsonapi/node/article'];
    yield 'sitemap' => ['/sitemap.xml'];
    yield 'language sitemap' => ['/en/sitemap.xml'];
  }

  #[DataProvider('nonExcludedPaths')]
  public function testNonExcludedPath(string $path): void {
    $this->assertFalse(PaginationPathPolicy::isExcluded($path), $path);
  }

  /**
   * @return \Generator<string, array{string}>
   */
  public static function nonExcludedPaths(): \Generator {
    yield 'front page' => ['/'];
    yield 'articles' => ['/articles'];
    yield 'administrator' => ['/administrator'];
    yield 'admin-panel' => ['/admin-panel'];
    yield 'language articles' => ['/en/articles'];
    yield 'jsonapi-like segment' => ['/jsonapi-docs'];
  }

}
