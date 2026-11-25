<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\example\Pager\PageSuffixTitleResolver;
use Symfony\Component\HttpFoundation\RequestStack;

#[Hook('preprocess_page_title')]
final readonly class PreprocessPageTitleSuffix {

  public function __construct(
    private PageSuffixTitleResolver $titleResolver,
    private RequestStack $requestStack,
    private RouteMatchInterface $routeMatch,
  ) {}

  /**
   * @param array{title?: mixed} $variables
   */
  public function __invoke(array &$variables): void {
    $request = $this->requestStack->getCurrentRequest();
    $route = $this->routeMatch->getRouteObject();
    if (!$request || !$route?->getDefault('_title_page_suffix')) {
      return;
    }

    $title = $variables['title'] ?? NULL;
    if (!$title || (!\is_string($title) && !\is_array($title) && !$title instanceof \Stringable)) {
      return;
    }

    $resolved_title = $this->titleResolver->getTitle($request, $route);
    $is_same_title = $title === $resolved_title;
    $is_same_string_title = !\is_array($title)
      && !\is_array($resolved_title)
      && (string) $title === (string) $resolved_title;

    if ($is_same_title || $is_same_string_title) {
      return;
    }

    $variables['title'] = $this->titleResolver->addSuffix($title);
  }

}
