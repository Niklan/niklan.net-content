<?php

declare(strict_types=1);

namespace Drupal\example\Resolver;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\example\Contract\ParameterResolver;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ParameterResolverSelector {

  public function __construct(
    #[AutowireIterator(ParameterResolver::class)]
    private iterable $resolvers,
  ) {}

  public function forField(FieldDefinitionInterface $field_definition): ParameterResolver {
    foreach ($this->resolvers as $resolver) {
      \assert($resolver instanceof ParameterResolver);
      if ($resolver->supports($field_definition)) {
        return $resolver;
      }
    }
    throw new \InvalidArgumentException("No resolver found for field type '{$field_definition->getType()}'");
  }

}
