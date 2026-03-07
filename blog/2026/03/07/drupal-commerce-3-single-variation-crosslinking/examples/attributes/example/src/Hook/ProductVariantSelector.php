<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\ProductVariationAttributeMapperInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\example\Repository\ProductVariationRepository;

final readonly class ProductVariantSelector {

  private const string FIELD_NAME = 'product_variant_selector';
  private const string PRODUCT_GROUP_FIELD = ProductVariationRepository::PRODUCT_GROUP_FIELD;

  public function __construct(
    private ProductVariationRepository $productVariationRepository,
    private ProductVariationAttributeMapperInterface $attributeMapper,
    private CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  #[Hook('commerce_product_insert')]
  #[Hook('commerce_product_update')]
  #[Hook('commerce_product_delete')]
  public function invalidateSelectorCache(ProductInterface $product): void {
    if (!$this->hasGroupFieldWithValue($product)) {
      return;
    }

    $group_id = $product->get(self::PRODUCT_GROUP_FIELD)->getString();
    $this->cacheTagsInvalidator->invalidateTags([self::getSelectorCacheTag($group_id)]);
  }

  #[Hook('entity_extra_field_info')]
  public function info(): array {
    $product_types = ['default'];

    $definition = [
      'label' => new TranslatableMarkup('Product Variant Selector'),
      'visible' => FALSE,
      'weight' => 0,
    ];

    $definitions = [];
    foreach ($product_types as $product_type) {
      $definitions['commerce_product'][$product_type]['display'][self::FIELD_NAME] = $definition;
    }
    return $definitions;
  }

  #[Hook('commerce_product_view')]
  public function build(array &$build, ProductInterface $product, EntityViewDisplayInterface $display, string $view_mode): void {
    if (!$this->shouldDisplay($product, $display, $view_mode)) {
      return;
    }

    $cacheable_metadata = new CacheableMetadata();
    $groups = $this->prepareGroups($product, $cacheable_metadata);
    $build[self::FIELD_NAME] = [
      '#theme' => 'example_product_variant_selector',
      '#groups' => $groups,
    ];
    $cacheable_metadata->applyTo($build[self::FIELD_NAME]);
  }

  private function shouldDisplay(ProductInterface $product, EntityViewDisplayInterface $display, string $view_mode): bool {
    return $display->getComponent(self::FIELD_NAME)
      && $view_mode === 'full'
      && $this->hasGroupFieldWithValue($product)
      && $product->getDefaultVariation();
  }

  private function hasGroupFieldWithValue($product): bool {
    return $product->hasField(self::PRODUCT_GROUP_FIELD) && !$product->get(self::PRODUCT_GROUP_FIELD)->isEmpty();
  }

  /**
   * @return array<string, array{label: string, options: non-empty-array<array{value: string, url: string, is_active: bool}>}>
   */
  private function prepareGroups(ProductInterface $product, CacheableMetadata $cacheable_metadata): array {
    $group_id = $product->get(self::PRODUCT_GROUP_FIELD)->getString();
    $cacheable_metadata->addCacheTags([self::getSelectorCacheTag($group_id)]);
    $variations = $this->productVariationRepository->loadByGroup($group_id);
    $current_variation = $product->getDefaultVariation();
    $attributes = $this->attributeMapper->prepareAttributes($current_variation, $variations);

    $active_attributes = [];
    foreach ($attributes as $field_name => $attribute) {
      $active_attributes[$field_name] = $current_variation->getAttributeValueId($field_name);
    }

    $groups = [];
    foreach ($attributes as $field_name => $attribute) {
      $groups[$attribute->getId()] = [
        'label' => $attribute->getLabel(),
        'variants' => [],
      ];

      foreach ($attribute->getValues() as $value_id => $value) {
        if ($value_id === '_none') {
          continue;
        }

        $attribute_values = $active_attributes;
        $attribute_values[$field_name] = $value_id;

        $variation = $this->attributeMapper->selectVariation($variations, $attribute_values);
        if (!$variation instanceof ProductVariationInterface) {
          continue;
        }

        $cacheable_metadata->addCacheableDependency($variation);
        if (!$variation->getProduct()?->isPublished()) {
          continue;
        }

        $groups[$attribute->getId()]['variants'][$value_id] = [
          'value' => $value,
          'url' => $variation->getProduct()->toUrl()->toString(),
          'is_active' => (string) $value_id === (string) $current_variation->getAttributeValueId($field_name),
        ];
        $cacheable_metadata->addCacheableDependency($variation->getProduct());
      }
    }

    return \array_filter($groups, static fn ($group) => \count($group['variants']) > 0);
  }

  private static function getSelectorCacheTag(string $group_id): string {
    return "product_variant_selector:$group_id";
  }

}
