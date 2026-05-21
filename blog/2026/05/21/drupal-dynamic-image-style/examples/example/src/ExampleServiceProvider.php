<?php

declare(strict_types=1);

namespace Drupal\example;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\example\Controller\DynamicImageStyleController;
use Drupal\example\DynamicImageStyle\DynamicImageStyle;
use Drupal\example\PathProcessor\DynamicImageStylePathProcessor;
use Drupal\example\Routing\RouteProvider;
use Drupal\example\Twig\DynamicImageStyleExtension;
use Symfony\Component\DependencyInjection\Definition;

final readonly class ExampleServiceProvider implements ServiceProviderInterface {

  #[\Override]
  public function register(ContainerBuilder $container): void {
    $autowire = static fn (string $class): Definition => $container
      ->autowire($class)
      ->setPublic(TRUE)
      ->setAutoconfigured(TRUE);

    $container->setParameter('example.skip_procedural_hook_scan', TRUE);

    $autowire(DynamicImageStyle::class);
    $autowire(DynamicImageStyleController::class);
    $autowire(RouteProvider::class);

    $autowire(DynamicImageStylePathProcessor::class);
    $autowire(DynamicImageStyleExtension::class);
  }

}
