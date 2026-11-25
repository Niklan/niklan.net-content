<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

#[AsDecorator(decorates: 'title_resolver')]
final readonly class PageSuffixTitleResolver implements TitleResolverInterface {

  public function __construct(
    #[AutowireDecorated]
    private TitleResolverInterface $inner,
    private PagerManagerInterface $pagerManager,
    private RendererInterface $renderer,
    private TranslationInterface $translation,
    private RequestStack $requestStack,
  ) {}

  /**
   * @return string|array<mixed>|\Stringable|null
   */
  public function getTitle(Request $request, Route $route): string|array|\Stringable|NULL {
    $title = $this->inner->getTitle($request, $route);

    if (!$route->getDefault('_title_page_suffix') || !$title) {
      return $title;
    }

    if ($request !== $this->requestStack->getCurrentRequest()) {
      return $title;
    }

    return $this->addSuffix($title);
  }

  /**
   * @return string|array<mixed>|\Stringable
   */
  public function addSuffix(mixed $title): string|array|\Stringable {
    if (!\is_string($title) && !\is_array($title) && !$title instanceof \Stringable) {
      return '';
    }

    $page_number = $this->pagerManager->findPage();
    if ($page_number < 1) {
      return $title;
    }

    if (Element::isRenderArray($title)) {
      \assert(\is_array($title));
      $title = $this->renderer->renderInIsolation($title);
    }

    if (!\is_string($title) && !$title instanceof \Stringable) {
      return $title;
    }

    return $this->translation->translate(
      string: '@title — page #@number',
      args: ['@title' => $title, '@number' => $page_number + 1],
    );
  }

}
