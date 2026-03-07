<?php

declare(strict_types=1);

namespace Drupal\example\Mapper;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\example\Data\ParameterValue;
use Drupal\example\Resolver\ParameterResolverSelector;

final readonly class ProductVariantParameterMapper {

  private const array PARAMETER_FIELDS = [
    'field_color',
    'field_size',
  ];

  public function __construct(
    private EntityFieldManagerInterface $entityFieldManager,
    private ParameterResolverSelector $parameterResolverSelector,
  ) {}

  /**
   * @param list<\Drupal\commerce_product\Entity\ProductVariationInterface> $variations
   * @param array<string, \Drupal\example\Data\ParameterValue> $parameter_values
   */
  public function selectVariation(array $variations, array $parameter_values = []): ?ProductVariationInterface {
    foreach ($variations as $variation) {
      if ($this->variationMatchesParameters($variation, $parameter_values)) {
        return $variation;
      }
    }
    return $this->findBestMatchingVariation($variations, $parameter_values);
  }

  /**
   * @return array<string, array{label: string, values: list<\Drupal\example\Data\ParameterValue>}>
   */
  public function prepareParameters(ProductVariationInterface $selected_variation, ProductVariationInterface ...$variations): array {
    $parameters = [];
    foreach (self::PARAMETER_FIELDS as $field_name) {
      $data = $this->buildParameter($field_name, $selected_variation, ...$variations);
      if (\count($data['values']) === 0) {
        continue;
      }
      $parameters[$field_name] = $data;
    }

    return $parameters;
  }

  public function getParameterValue(string $field_name, ProductVariationInterface $variation): ?ParameterValue {
    $field_definition = $this->getFieldDefinition($field_name, $variation);
    $resolver = $this->parameterResolverSelector->forField($field_definition);
    $values = $resolver->resolveValues($field_definition, $variation);
    $property_value = \array_shift($values);
    return $property_value instanceof ParameterValue ? $property_value : NULL;
  }

  private function variationMatchesParameters(ProductVariationInterface $variation, array $parameter_values): bool {
    foreach ($parameter_values as $field_name => $expected_value) {
      if ($this->getParameterValue($field_name, $variation)?->id !== $expected_value->id) {
        return FALSE;
      }
    }
    return TRUE;
  }

  private function getFieldDefinition(string $field_name, ProductVariationInterface $variation): FieldDefinitionInterface {
    $definitions = $this->entityFieldManager->getFieldDefinitions(
      $variation->getEntityTypeId(),
      $variation->bundle(),
    );
    return $definitions[$field_name] ?? throw new \InvalidArgumentException(\sprintf(
      'Field "%s" not found for %s:%s',
      $field_name,
      $variation->getEntityTypeId(),
      $variation->bundle(),
    ));
  }

  /**
   * Finds best match by relaxing constraints stepwise.
   */
  private function findBestMatchingVariation(array $variations, array &$parameter_values): ?ProductVariationInterface {
    if (\count($variations) === 0) {
      return NULL;
    }

    while (\count($parameter_values) > 0) {
      \array_pop($parameter_values);
      foreach ($variations as $variation) {
        if ($this->variationMatchesParameters($variation, $parameter_values)) {
          return $variation;
        }
      }
    }

    return \array_shift($variations) ?? NULL;
  }

  private function buildParameter(string $field_name, ProductVariationInterface $selected_variation, ProductVariationInterface ...$variations): array {
    $field_definition = $this->getFieldDefinition($field_name, $selected_variation);
    $parameter_index = \array_search($field_name, self::PARAMETER_FIELDS, TRUE);
    $filtered_variations = $this->filterVariationsByEstablishedParameters(
      variations: $variations,
      selected_variation: $selected_variation,
      current_index: $parameter_index,
    );

    return [
      'label' => $field_definition->getLabel(),
      'values' => $this
        ->parameterResolverSelector
        ->forField($field_definition)
        ->resolveValues($field_definition, ...$filtered_variations),
    ];
  }

  private function filterVariationsByEstablishedParameters(array $variations, ProductVariationInterface $selected_variation, int $current_index): array {
    if ($current_index === 0) {
      return $variations;
    }

    $required_values = $this->getEstablishedParameterValues($selected_variation, $current_index);

    return \array_filter($variations, function (ProductVariationInterface $variation) use ($required_values) {
      foreach ($required_values as $field_name => $expected_value) {
        $actual_value = $this->getParameterValue($field_name, $variation);
        if ($actual_value?->id !== $expected_value->id) {
          return FALSE;
        }
      }
      return TRUE;
    });
  }

  /**
   * @return array<string, \Drupal\example\Data\ParameterValue>
   */
  private function getEstablishedParameterValues(ProductVariationInterface $variation, int $current_index): array {
    $values = [];
    for ($i = 0; $i < $current_index; $i++) {
      $field_name = self::PARAMETER_FIELDS[$i];
      $field_value = $this->getParameterValue($field_name, $variation);
      if ($field_value === NULL) {
        continue;
      }
      $values[$field_name] = $this->getParameterValue($field_name, $variation);
    }
    return $values;
  }

}
