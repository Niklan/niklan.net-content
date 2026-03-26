<?php

declare(strict_types=1);

namespace Drupal\example;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\example\Converter\HtmlToMarkdownConverter;
use Drupal\example\EventSubscriber\LlmsViewSubscriber;
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
  }

}
