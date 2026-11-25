<?php

declare(strict_types=1);

namespace Drupal\Tests\example\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('example')]
final class PaginationFunctionalTest extends BrowserTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'example',
    'example_test',
    'metatag',
    'token',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->placeBlock('page_title_block');

    $this->config('metatag.metatag_defaults.global')
      ->set('tags.canonical_url', '[current-page:url:absolute-with-page]')
      ->set('tags.title', '[current-page:title][current-page:paged-text: — page @page] | [site:name]')
      ->save();
  }

  public function testFirstPage(): void {
    $this->drupalGet('example/pager');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementTextEquals('css', 'h1', 'Example pager');
    $this->assertSession()->elementAttributeNotContains('css', 'link[rel="canonical"]', 'href', 'page=');
    $this->assertSession()->elementTextNotContains('css', 'title', '— page');
  }

  public function testSecondPage(): void {
    $this->drupalGet($this->url('example/pager', ['page' => 2]));

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementTextEquals('css', 'h1', 'Example pager — page #2');
    $this->assertSession()->elementAttributeContains('css', 'link[rel="canonical"]', 'href', 'page=2');
    $this->assertSession()->elementTextContains('css', 'title', '— page 2');
    $this->assertSession()->linkByHrefExists('/example/pager');
    $this->assertSession()->linkByHrefExists('/example/pager?page=3');
  }

  public function testLastPageAndOutOfRange(): void {
    $this->drupalGet($this->url('example/pager', ['page' => 10]));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet($this->url('example/pager', ['page' => 11]));
    $this->assertSession()->statusCodeEquals(404);
  }

  public function testCustomTitle(): void {
    $this->drupalGet('example/pager-custom-title');
    $this->assertSession()->elementTextEquals('css', 'h1', 'Custom title');

    $this->drupalGet($this->url('example/pager-custom-title', ['page' => 2]));
    $this->assertSession()->elementTextEquals('css', 'h1', 'Custom title — page #2');
    $this->assertSession()->elementAttributeContains('css', 'link[rel="canonical"]', 'href', 'page=2');
  }

  #[DataProvider('invalidPageProvider')]
  public function testInvalidPage(mixed $page): void {
    $this->drupalGet($this->url('example/pager', ['page' => $page]));
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * @return \Generator<string, array{mixed}>
   */
  public static function invalidPageProvider(): \Generator {
    yield 'letters' => ['abc'];
    yield 'plus' => ['+5'];
    yield 'suffix' => ['2abc'];
    yield 'float' => ['1.5'];
    yield 'exponent' => ['1e3'];
    yield 'leading zero' => ['05'];
    yield 'leading space' => [' 5'];
    yield 'negative' => ['-100'];
    yield 'comma separated' => ['1,2'];
    yield 'empty' => [''];
    yield 'array' => [['1']];
    yield 'too large' => ['999'];
  }

  #[DataProvider('redirectProvider')]
  public function testRedirects(string $path, int $page): void {
    $url = $this->url($path, ['page' => $page]);
    $response = $this->getHttpClient()->get($url, ['allow_redirects' => FALSE]);

    $this->assertSame(301, $response->getStatusCode(), $url);
    $this->assertStringContainsString('/' . $path, $response->getHeaderLine('Location'), $url);
  }

  /**
   * @return \Generator<string, array{string, int}>
   */
  public static function redirectProvider(): \Generator {
    yield 'pager first page zero' => ['example/pager', 0];
    yield 'pager first page one' => ['example/pager', 1];
    yield 'no pager first page zero' => ['example/no-pager', 0];
    yield 'no pager first page one' => ['example/no-pager', 1];
  }

  public function testEmptyListIsNotNotFound(): void {
    $this->drupalGet('example/pager-empty');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Nothing found');

    $this->drupalGet($this->url('example/pager-empty', ['page' => 2]));
    $this->assertSession()->statusCodeEquals(404);
  }

  public function testTokenSuffixRemainsOnNotFoundPage(): void {
    $this->drupalGet($this->url('example/pager', ['page' => 11]));

    $this->assertSession()->statusCodeEquals(404);
    $this->assertSession()->elementTextContains('css', 'title', '— page 11');
  }

  public function testSecondPagerWithSharedIdCausesFalseNotFound(): void {
    $this->drupalGet($this->url('example/pager-shared-id', ['page' => 2]));
    $this->assertSession()->statusCodeEquals(404);
  }

  public function testSecondPagerWithSeparateIdIsValid(): void {
    $this->drupalGet($this->url('example/pager-separate-id', ['page' => 2]));
    $this->assertSession()->statusCodeEquals(200);
  }

  public function testLazyBuilderPagerWithoutTagCausesFalseNotFound(): void {
    $this->drupalGet($this->url('example/pager-lazy', ['page' => 2]));
    $this->assertSession()->statusCodeEquals(404);
  }

  public function testLazyBuilderPagerWithTagOnParentIsValid(): void {
    $this->drupalGet($this->url('example/pager-lazy-tagged', ['page' => 2]));
    $this->assertSession()->statusCodeEquals(200);
  }

  public function testLazyBuilderPagerWithTagOnParentKeepsRangeCheck(): void {
    $this->drupalGet($this->url('example/pager-lazy-tagged', ['page' => 11]));
    $this->assertSession()->statusCodeEquals(404);
  }

  public function testPageWithoutPager(): void {
    $this->drupalGet($this->url('example/no-pager', ['page' => 2]));
    $this->assertSession()->statusCodeEquals(404);
  }

  public function testRedirectKeepsOtherQueryParameters(): void {
    $url = $this->url('example/pager', ['page' => 1, 'foo' => 'bar']);
    $response = $this->getHttpClient()->get($url, ['allow_redirects' => FALSE]);

    $this->assertSame(301, $response->getStatusCode());
    $this->assertStringEndsWith('/example/pager?foo=bar', $response->getHeaderLine('Location'));
  }

  public function testCanonicalKeepsOnlyPageParameter(): void {
    $this->drupalGet($this->url('example/pager', ['page' => 2, 'bar' => 'baz']));

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementAttributeContains('css', 'link[rel="canonical"]', 'href', 'page=2');
    $this->assertSession()->elementAttributeNotContains('css', 'link[rel="canonical"]', 'href', 'bar=');
  }

  public function testBreadcrumbTitleHasNoSuffix(): void {
    $this->placeBlock('system_breadcrumb_block');
    $this->drupalGet($this->url('example/pager/child', ['page' => 2]));

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementTextEquals('css', 'nav[aria-labelledby="system-breadcrumb"] a[href="/example/pager"]', 'Example pager');
  }

  public function testExcludedPathIsNotProcessed(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    $this->drupalGet($this->url('admin/reports/status', ['page' => 1]));

    $this->assertSession()->statusCodeEquals(200);
    $this->assertStringContainsString('page=1', $this->getUrl());
  }

  /**
   * An array "page" on an excluded path must not reach the pager manager.
   */
  public function testExcludedPathWithArrayPageIsNotProcessed(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    $this->drupalGet($this->url('admin/reports/status', ['page' => ['1']]));

    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Builds a raw URL without outbound path processing.
   */
  private function url(string $path, array $query = []): string {
    if ($query === []) {
      return $this->getAbsoluteUrl($path);
    }

    $query_string = \http_build_query(
      $query,
      encoding_type: \PHP_QUERY_RFC3986,
    );
    return $this->getAbsoluteUrl($path . '?' . $query_string);
  }

}
