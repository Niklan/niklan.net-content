<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;

#[Hook('preprocess_pager')]
#[Hook('preprocess_views_mini_pager')]
final readonly class PreprocessPager {

  /**
   * @param array{pager?: array{'#route_name': string}, items?: mixed} $variables
   */
  public function __invoke(array &$variables): void {
    if (!isset($variables['items']) || !\is_array($variables['items'])) {
      return;
    }

    $variables['#cache']['tags'][] = 'pager:rendered';

    // The "pager" theme hook is defined with the "pager" render element,
    // which provides "#route_name". The Views-only "views_mini_pager" theme
    // hook has no such element and builds its links with
    // Url::fromRoute('<current>').
    // Only '<none>' pagers of the "pager" theme hook bypass path processors
    // (the '_no_path' route option). Other routes are already processed by the
    // URL generator, so handling them again would shift the page number twice.
    if (($variables['pager']['#route_name'] ?? NULL) !== '<none>') {
      return;
    }

    \array_walk_recursive($variables['items'], $this->processHref(...));
  }

  private function processHref(mixed &$value, string|int $key): void {
    if ($key !== 'href' || !\is_string($value)) {
      return;
    }

    $value = Url::fromRoute('<current>', [], [
      'query' => UrlHelper::parse($value)['query'],
    ])->toString();
  }

}
