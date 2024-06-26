<?php

namespace Drupal\dummy\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OrderWorkflowSubscriber event subscriber.
 */
class OrderWorkflowSubscriber implements EventSubscriberInterface {

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * Constructor for OrderWorkflowSubscriber.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->logStorage = $entity_type_manager->getStorage('commerce_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.processing_complete.pre_transition' => ['onProcessingCompleteTransition', -100],
    ];
    return $events;
  }

  /**
   * Creates a log when an order is placed.
   */
  public function onProcessingCompleteTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->logStorage->generate($order, 'order_processed')->save();
  }

}
