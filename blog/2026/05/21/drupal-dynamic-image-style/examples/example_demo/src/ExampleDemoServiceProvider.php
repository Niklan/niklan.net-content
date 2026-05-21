<?php

declare(strict_types=1);

namespace Drupal\example_demo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\example_demo\Controller\DynamicImageStyleDemo;

final readonly class ExampleDemoServiceProvider implements ServiceProviderInterface {

  #[\Override]
  public function register(ContainerBuilder $container): void {
    $container->setParameter('example_demo.skip_procedural_hook_scan', TRUE);

    $container
      ->autowire(DynamicImageStyleDemo::class)
      ->setPublic(TRUE)
      ->setAutoconfigured(TRUE);
  }

}
