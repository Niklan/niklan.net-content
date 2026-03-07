<?php

declare(strict_types=1);

namespace Drupal\example\Resolver;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\example\Contract\ParameterResolver;
use Drupal\example\Data\ParameterValue;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(ParameterResolver::class)]
final readonly class EntityReferenceParameterResolver implements ParameterResolver {

  public function supports(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getType() === 'entity_reference';
  }

  public function resolveValues(FieldDefinitionInterface $field_definition, ProductVariationInterface ...$variations): array {
    $values = [];
    $field_name = $field_definition->getName();

    foreach ($variations as $variation) {
      $field = $variation->get($field_name);
      if ($field->isEmpty()) {
        continue;
      }

      $entity = $field->first()->get('entity')->getValue();
      if (!$entity instanceof EntityInterface) {
        continue;
      }

      $values[$entity->id()] = new ParameterValue((string) $entity->id(), $entity->label());
    }

    return $values;
  }

}
