<?php

namespace Drupal\dummy\EventSubscriber;

use Drupal\dummy\Event\DummyPreprocessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Dummy event subscriber.
 */
class DummySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      DummyPreprocessEvent::PREPROCESS_HTML => ['preprocessHtml', 100],
      DummyPreprocessEvent::PREPROCESS_PAGE => ['preprocessPage'],
    ];
  }

  /**
   * Example for DummyFrontpageEvent::PREPROCESS_HTML.
   */
  public function preprocessHtml(DummyPreprocessEvent $event) {
    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = \Drupal::service('messenger');
    $messenger->addMessage('Event for preprocess HTML called');
  }

  /**
   * Example for DummyFrontpageEvent::PREPROCESS_HTML.
   */
  public function preprocessPage(DummyPreprocessEvent $event) {
    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = \Drupal::service('messenger');
    $variables = $event->getVariables();
    $sidebars_found = 0;
    foreach ($variables['page'] as $key => $value) {
      if (preg_match("/sidebar_(.+)/", $key)) {
        $sidebars_found++;
      }
    }
    $messenger->addMessage("Found {$sidebars_found} sidebar(s) on the page");
    // Stop further execution.
    $event->stopPropagation();
  }

}