<?php

declare(strict_types=1);

namespace Drupal\example\Renderer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\example\Converter\HtmlToMarkdownConverter;
use Drupal\example\Event\LlmsRenderEvent;
use Drupal\example\Event\LlmsResponseAlterEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class LlmsRenderer {

  public function __construct(
    private TitleResolverInterface $titleResolver,
    private RendererInterface $renderer,
    private HtmlToMarkdownConverter $htmlToMarkdownConverter,
    private EventDispatcherInterface $eventDispatcher,
  ) {}

  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match): CacheableResponse {
    $render_event = new LlmsRenderEvent($main_content, $request, $route_match);
    $this->eventDispatcher->dispatch($render_event);

    $cacheable_metadata = CacheableMetadata::createFromRenderArray($main_content);
    $cacheable_metadata = $cacheable_metadata->merge($render_event->getCacheableMetadata());

    $markdown = $render_event->hasCustomMarkdown()
      ? $render_event->getMarkdown()
      : $this->renderToMarkdown($main_content, $cacheable_metadata);

    $title = $render_event->getTitle() ?? $this->resolveTitle($request, $route_match, $cacheable_metadata);
    if ($title !== '') {
      $markdown = "# {$title}\n\n{$markdown}";
    }

    $alter_event = new LlmsResponseAlterEvent($markdown, $request, $route_match);
    $this->eventDispatcher->dispatch($alter_event);
    $cacheable_metadata = $cacheable_metadata->merge($alter_event->getCacheableMetadata());

    $response = new CacheableResponse($alter_event->getMarkdown(), 200, [
      'Content-Type' => 'text/markdown; charset=UTF-8',
      'X-Robots-Tag' => 'noindex',
    ]);
    $response->addCacheableDependency($cacheable_metadata);

    return $response;
  }

  private function renderToMarkdown(array &$render_array, CacheableMetadata &$cacheable_metadata): string {
    $render_context = new RenderContext();
    $rendered = $this->renderer->executeInRenderContext($render_context, function () use (&$render_array): string {
      return (string) $this->renderer->render($render_array);
    });

    if (!$render_context->isEmpty()) {
      $bubbleable = $render_context->pop();
      \assert($bubbleable instanceof CacheableMetadata);
      $cacheable_metadata = $cacheable_metadata->merge($bubbleable);
    }

    return $this->htmlToMarkdownConverter->convert($rendered);
  }

  private function resolveTitle(Request $request, RouteMatchInterface $route_match, CacheableMetadata $cacheable_metadata): string {
    $route = $route_match->getRouteObject();
    if ($route === NULL) {
      return '';
    }

    $title = $this->titleResolver->getTitle($request, $route);
    if ($title === NULL) {
      return '';
    }

    if (\is_array($title)) {
      $render_context = new RenderContext();
      $rendered = $this->renderer->executeInRenderContext(
        context: $render_context,
        callable: fn (): string => (string) $this->renderer->render($title),
      );

      if (!$render_context->isEmpty()) {
        $bubbleable = $render_context->pop();
        \assert($bubbleable instanceof CacheableMetadata);
        $cacheable_metadata->addCacheableDependency($bubbleable);
      }

      return \strip_tags($rendered);
    }

    return (string) $title;
  }

}
