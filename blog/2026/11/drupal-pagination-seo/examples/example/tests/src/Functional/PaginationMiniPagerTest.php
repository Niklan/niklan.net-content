<?php

declare(strict_types=1);

namespace Drupal\Tests\example\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks that Views Mini pagers are marked as paginated too.
 *
 * The Views Mini pager uses its own "views_mini_pager" theme hook, not the
 * standard "pager" theme hook, so it needs the same cache tag.
 */
#[Group('example')]
final class PaginationMiniPagerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'example',
    'example_test',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testMiniPagerSecondPageIsValid(): void {
    $this->drupalGet($this->getAbsoluteUrl('example/pager-mini?page=2'));

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Page 2');
  }

  public function testMiniPagerOutOfRangeIsNotFound(): void {
    $this->drupalGet($this->getAbsoluteUrl('example/pager-mini?page=11'));

    $this->assertSession()->statusCodeEquals(404);
  }

}
