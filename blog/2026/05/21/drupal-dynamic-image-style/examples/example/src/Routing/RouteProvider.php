<?php

declare(strict_types=1);

namespace Drupal\example\Routing;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\example\Controller\DynamicImageStyleController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final readonly class RouteProvider {

  public function __construct(
    private StreamWrapperManagerInterface $streamWrapperManager,
  ) {}

  public function __invoke(): RouteCollection {
    $routes = new RouteCollection();

    $wrapper = $this->streamWrapperManager->getViaScheme('public');
    \assert($wrapper instanceof LocalStream);
    $routes->add('example.dynamic_image_style.public', new Route(
      path: '/' . $wrapper->getDirectoryPath() . '/styles/dynamic',
      defaults: [
        '_controller' => DynamicImageStyleController::class,
        '_disable_route_normalizer' => TRUE,
      ],
      requirements: [
        '_access' => 'TRUE',
      ],
      options: [
        'no_cache' => TRUE,
      ],
    ));

    $routes->add('example.dynamic_image_style.private', new Route(
      path: '/system/files/styles/dynamic',
      defaults: [
        '_controller' => DynamicImageStyleController::class,
        '_disable_route_normalizer' => TRUE,
      ],
      requirements: [
        '_access' => 'TRUE',
      ],
      options: [
        'no_cache' => TRUE,
      ],
    ));

    return $routes;
  }

}
