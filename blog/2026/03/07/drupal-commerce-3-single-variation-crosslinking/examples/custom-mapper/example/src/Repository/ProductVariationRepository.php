<?php

declare(strict_types=1);

namespace Drupal\example\Repository;

use Drupal\commerce_product\ProductVariationStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

final readonly class ProductVariationRepository {

  public const string PRODUCT_GROUP_FIELD = 'field_product_group';

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function loadByGroup(string $group_id): array {
    return $this->getStorage()->loadMultiple($this->findIdsByGroup($group_id));
  }

  public function findIdsByGroup(string $group_id): array {
    return $this
      ->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('product_id.entity.' . self::PRODUCT_GROUP_FIELD . '.target_id', $group_id)
      ->execute();
  }

  private function getStorage(): ProductVariationStorageInterface {
    return $this->entityTypeManager->getStorage('commerce_product_variation');
  }

}
