<?php

declare(strict_types=1);

namespace Drupal\example\Resolver;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\example\Contract\ParameterResolver;
use Drupal\example\Data\ParameterValue;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(ParameterResolver::class)]
final readonly class ListStringParameterResolver implements ParameterResolver {

  public function supports(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getType() === 'list_string';
  }

  public function resolveValues(FieldDefinitionInterface $field_definition, ProductVariationInterface ...$variations): array {
    $storage_definition = $field_definition->getFieldStorageDefinition();
    $values = [];

    foreach ($variations as $variation) {
      $field = $variation->get($field_definition->getName());
      if ($field->isEmpty()) {
        continue;
      }

      $options = \options_allowed_values($storage_definition, $variation);
      $selected_option = $field->first()->getString();
      $values[$selected_option] = new ParameterValue($selected_option, $options[$selected_option]);
    }

    return $values;
  }

}
