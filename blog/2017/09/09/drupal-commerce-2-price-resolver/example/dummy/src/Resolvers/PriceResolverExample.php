<?php

namespace Drupal\dummy\Resolvers;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;

/**
 * Custom price resolver example.
 */
class PriceResolverExample implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $exchange_rate = 60;
    $multiplier = (string) (1 / $exchange_rate);
    return $entity->getPrice()->convert('USD', $multiplier);
    if ($entity->bundle() == 'default') {
      // 5% discount for all products "default" type.
      return $entity->getPrice()->multiply('0.95');
    }
    return NULL;
  }

}
