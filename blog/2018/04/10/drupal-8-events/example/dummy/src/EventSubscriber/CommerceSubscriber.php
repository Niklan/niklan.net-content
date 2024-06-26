<?php

namespace Drupal\dummy\EventSubscriber;

use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CommerceAjaxSubscriber.
 */
class CommerceSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE => ['onResponse', 50],
      CartEvents::CART_ENTITY_ADD => ['addToCart', 50],
    ];
  }

  /**
   * Respond to AJAX variation update.
   */
  public function onResponse(ProductVariationAjaxChangeEvent $event) {
    $product_variation = $event->getProductVariation();
    $sku = $product_variation->getSku();
    $response = $event->getResponse();
    $response->addCommand(new HtmlCommand('.sku-value', $sku));
  }

  /**
   * React when products added to cart.
   */
  public function addToCart(CartEntityAddEvent $event) {
    $cart = $event->getCart();
    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = \Drupal::service('messenger');
    $messenger->addWarning('Your cart total is ' . $cart->getTotalPrice());
  }

}
