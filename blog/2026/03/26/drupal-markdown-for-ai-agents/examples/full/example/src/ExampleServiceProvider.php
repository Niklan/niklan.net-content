<?php

declare(strict_types=1);

namespace Drupal\example;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\example\Converter\HtmlToMarkdownConverter;
use Drupal\example\EventSubscriber\LlmsFooterSubscriber;
use Drupal\example\EventSubscriber\LlmsRouteSubscriber;
use Drupal\example\EventSubscriber\LlmsViewSubscriber;
use Drupal\example\EventSubscriber\PagerLlmsSubscriber;
use Drupal\example\PathProcessor\LlmsFormatPathProcessor;
use Drupal\example\PathProcessor\MarkdownPathProcessor;
use Drupal\example\Renderer\LlmsRenderer;
use Symfony\Component\DependencyInjection\Definition;

final readonly class ExampleServiceProvider implements ServiceProviderInterface {

  public function register(ContainerBuilder $container): void {
    $autowire = static fn (string $class): Definition => $container
      ->autowire($class)
      ->setPublic(TRUE)
      ->setAutoconfigured(TRUE);

    $autowire(HtmlToMarkdownConverter::class);
    $autowire(LlmsRenderer::class);
    $autowire(LlmsViewSubscriber::class);
    $autowire(MarkdownPathProcessor::class);
    $autowire(LlmsFormatPathProcessor::class);
    $autowire(PagerLlmsSubscriber::class);
    $autowire(LlmsFooterSubscriber::class);
    $autowire(LlmsRouteSubscriber::class);
  }

}
