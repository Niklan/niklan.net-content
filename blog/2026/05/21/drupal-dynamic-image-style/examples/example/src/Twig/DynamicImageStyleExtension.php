<?php

declare(strict_types=1);

namespace Drupal\example\Twig;

use Drupal\example\DynamicImageStyle\DynamicImageStyle;
use Drupal\example\DynamicImageStyle\DynamicImageStyleBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

#[AutoconfigureTag('twig.extension')]
final class DynamicImageStyleExtension extends AbstractExtension {

  public function __construct(
    private readonly DynamicImageStyle $dynamicImageStyle,
  ) {}

  public function getFilters(): array {
    return [
      new TwigFilter('dynamic_image_style', $this->dynamicImageStyle(...)),
      new TwigFilter('image_scale_crop', $this->imageScaleCrop(...)),
      new TwigFilter('image_scale', $this->imageScale(...)),
      new TwigFilter('image_convert', $this->imageConvert(...)),
    ];
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function dynamicImageStyle(string|DynamicImageStyleBuilder $input, array $effects = []): DynamicImageStyleBuilder {
    $builder = $this->ensureBuilder($input);
    foreach ($effects as [$id, $data]) {
      $builder = $builder->effect($id, $data);
    }
    return $builder;
  }

  public function imageScaleCrop(string|DynamicImageStyleBuilder $input, int $width, int $height): DynamicImageStyleBuilder {
    return $this->ensureBuilder($input)->effect('image_scale_and_crop', ['width' => $width, 'height' => $height]);
  }

  public function imageScale(string|DynamicImageStyleBuilder $input, ?int $width = NULL, ?int $height = NULL): DynamicImageStyleBuilder {
    $data = \array_filter(['width' => $width, 'height' => $height], static fn ($v): bool => $v !== NULL);
    return $this->ensureBuilder($input)->effect('image_scale', $data);
  }

  public function imageConvert(string|DynamicImageStyleBuilder $input, string $extension): DynamicImageStyleBuilder {
    return $this->ensureBuilder($input)->effect('image_convert', ['extension' => $extension]);
  }

  private function ensureBuilder(string|DynamicImageStyleBuilder $input): DynamicImageStyleBuilder {
    if ($input instanceof DynamicImageStyleBuilder) {
      return $input;
    }
    return new DynamicImageStyleBuilder($this->dynamicImageStyle, uri: $input);
  }

}
