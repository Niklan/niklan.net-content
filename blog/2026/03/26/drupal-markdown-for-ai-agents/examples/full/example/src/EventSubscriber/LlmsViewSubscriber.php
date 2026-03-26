<?php

declare(strict_types=1);

namespace Drupal\example\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\example\Renderer\LlmsRenderer;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LlmsViewSubscriber implements EventSubscriberInterface {

  public function __construct(
    #[AutowireServiceClosure(LlmsRenderer::class)]
    private \Closure $llmsRendererFactory,
    private RouteMatchInterface $routeMatch,
  ) {}

  public function onView(ViewEvent $event): void {
    $request = $event->getRequest();
    $result = $event->getControllerResult();
    if (!\is_array($result) || $request->getRequestFormat() !== 'llms') {
      return;
    }

    $llms_renderer = ($this->llmsRendererFactory)();
    \assert($llms_renderer instanceof LlmsRenderer);
    $response = $llms_renderer->renderResponse($result, $request, $this->routeMatch);
    $event->setResponse($response);
  }

  public static function getSubscribedEvents(): array {
    return [KernelEvents::VIEW => 'onView'];
  }

}
