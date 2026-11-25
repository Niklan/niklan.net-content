<?php

declare(strict_types=1);

namespace Drupal\Tests\example\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks the 404 logic when the response comes from the Dynamic Page Cache.
 *
 * The testing profile installs the Internal Page Cache, whose hits bypass the
 * kernel. Page cache entries are dropped between requests so that the requests
 * reach the kernel and are answered by the Dynamic Page Cache, where no pager
 * is created but the response metadata is still available.
 */
#[Group('example')]
final class PaginationDynamicCacheTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dynamic_page_cache',
    'example',
    'example_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A cache hit for a paginated page must stay a 200.
   */
  public function testPaginatedPageOnCacheHitIsServed(): void {
    $url = $this->getAbsoluteUrl('example/pager?page=2');

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // No pager is created while serving the response below, yet findPage()
    // reads the requested page from the query, and the "pager:rendered" tag is
    // restored together with the cached response.
    $this->container->get('cache.page')->deleteAll();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame('HIT', $this->getSession()->getResponseHeader('X-Drupal-Dynamic-Cache'));
    $this->assertSession()->pageTextContains('Pager');
  }

  /**
   * A non-paginated page with ?page=N must stay a 404.
   */
  public function testNoPagerPageIsNotFound(): void {
    // Warm up the cache with the valid first page of the same route.
    $this->drupalGet($this->getAbsoluteUrl('example/no-pager'));
    $this->assertSession()->statusCodeEquals(200);
    $entries_before = $this->countNoPagerDynamicCacheEntries();
    $this->assertGreaterThan(0, $entries_before);

    foreach (['page=2', 'page=3'] as $query) {
      $this->container->get('cache.page')->deleteAll();
      $this->drupalGet($this->getAbsoluteUrl('example/no-pager?' . $query));
      $this->assertSession()->statusCodeEquals(404);
    }

    // The 200 response must never reach Dynamic Page Cache before the 404 is
    // thrown. (The 404 page itself is a separate "system.404" subrequest and
    // is cached by core; that is unrelated to the "example_test.no_pager"
    // entry counted here.)
    $this->assertSame($entries_before, $this->countNoPagerDynamicCacheEntries());
    $this->assertSession()->pageTextNotContains('No pager');
  }

  /**
   * Different page numbers must not share a cache entry.
   */
  public function testPageNumberIsPartOfTheCacheKey(): void {
    $this->drupalGet($this->getAbsoluteUrl('example/pager?page=2'));
    $this->assertSession()->statusCodeEquals(200);

    $this->container->get('cache.page')->deleteAll();
    $this->drupalGet($this->getAbsoluteUrl('example/pager?page=3'));
    $this->assertSession()->statusCodeEquals(200);
    // A different page number is a different variation, not a cache hit of
    // page 2.
    $this->assertNotSame('HIT', $this->getSession()->getResponseHeader('X-Drupal-Dynamic-Cache'));

    // An out-of-range page is a 404, and it must not poison a valid page.
    $this->container->get('cache.page')->deleteAll();
    $this->drupalGet($this->getAbsoluteUrl('example/pager?page=11'));
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet($this->getAbsoluteUrl('example/pager?page=2'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Counts Dynamic Page Cache entries of the "example_test.no_pager" route.
   */
  private function countNoPagerDynamicCacheEntries(): int {
    return (int) $this->container->get('database')
      ->select('cache_dynamic_page_cache', 'c')
      ->condition('c.cid', '%example_test.no_pager%', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
