<?php

declare(strict_types=1);

namespace Drupal\Tests\example\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks that HTTP 404 answers are not hidden by the page caches.
 */
#[Group('example')]
final class PaginationCacheTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dynamic_page_cache',
    'example',
    'example_test',
    'page_cache',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  #[DataProvider('notFoundProvider')]
  public function testNotFoundIsNotCached(string $path, string $query, bool $authenticated): void {
    if ($authenticated) {
      $this->drupalLogin($this->drupalCreateUser());
    }

    // Warm up the caches with the valid page first, then request twice.
    $this->drupalGet($this->getAbsoluteUrl($path));
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet($this->getAbsoluteUrl($path . '?' . $query));
      $this->assertSession()->statusCodeEquals(404);
    }
  }

  /**
   * @return \Generator<string, array{string, string, bool}>
   */
  public static function notFoundProvider(): \Generator {
    yield 'anonymous, no pager' => ['example/no-pager', 'page=2', FALSE];
    yield 'authenticated, no pager' => ['example/no-pager', 'page=2', TRUE];
    yield 'anonymous, out of range' => ['example/pager', 'page=11', FALSE];
    yield 'authenticated, out of range' => ['example/pager', 'page=11', TRUE];
  }

  public function testNotFoundLeavesNoDynamicPageCacheEntries(): void {
    // Warm up the cache with the valid page.
    $this->drupalGet($this->getAbsoluteUrl('example/no-pager'));
    $before = $this->countNoPagerCacheEntries();
    $this->assertGreaterThan(0, $before);

    foreach (['page=2', 'page=3'] as $query) {
      $this->drupalGet($this->getAbsoluteUrl('example/no-pager?' . $query));
      $this->assertSession()->statusCodeEquals(404);
    }

    // The 200 response must not reach Dynamic Page Cache before the 404.
    $this->assertSame($before, $this->countNoPagerCacheEntries());
  }

  private function countNoPagerCacheEntries(): int {
    return (int) $this->container->get('database')
      ->select('cache_dynamic_page_cache', 'c')
      ->condition('c.cid', '%example_test.no_pager%', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  public function testValidPageIsServedAfterCaching(): void {
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet($this->getAbsoluteUrl('example/pager?page=2'));
      $this->assertSession()->statusCodeEquals(200);
    }
  }

}
