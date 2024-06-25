<?php

namespace Drupal\dummy\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dummy route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('user.login')) {
      $route->setPath('/auth');
    }

    if ($route = $collection->get('user.logout')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}