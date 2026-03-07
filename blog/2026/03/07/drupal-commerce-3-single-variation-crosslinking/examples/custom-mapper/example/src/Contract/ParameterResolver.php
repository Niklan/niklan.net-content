<?php

declare(strict_types=1);

namespace Drupal\example\Contract;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ParameterResolver {

  public function supports(FieldDefinitionInterface $field_definition): bool;

  /**
   * @return array<string, \Drupal\example\Data\ParameterValue>
   */
  public function resolveValues(FieldDefinitionInterface $field_definition, ProductVariationInterface ...$variations): array;

}
