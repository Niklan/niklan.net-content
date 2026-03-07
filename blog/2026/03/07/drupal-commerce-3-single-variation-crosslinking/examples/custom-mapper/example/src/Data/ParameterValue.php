<?php

declare(strict_types=1);

namespace Drupal\example\Data;

final readonly class ParameterValue {

  public function __construct(
    public string $id,
    public string $label,
  ) {}

}
