<?php

declare(strict_types=1);

namespace Drupal\example\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

final class LlmsRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($collection as $route) {
      $format = $route->getRequirement('_format');
      if ($format === NULL) {
        continue;
      }

      $formats = \array_map('trim', \explode('|', $format));
      if (!\in_array('html', $formats, TRUE) || \in_array('llms', $formats, TRUE)) {
        continue;
      }

      $formats[] = 'llms';
      $route->setRequirement('_format', \implode('|', $formats));
    }
  }

}
