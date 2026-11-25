<?php

declare(strict_types=1);

namespace Drupal\example;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\example\Pager\ArticlesTitlePageSuffixRouteSubscriber;
use Drupal\example\Pager\FirstPageRedirect;
use Drupal\example\Pager\InvalidPageNotFound;
use Drupal\example\Pager\PageNumberPathProcessor;
use Drupal\example\Pager\PageSuffixTitleResolver;
use Drupal\example\Pager\RangeCheckedPagerManager;
use Symfony\Component\DependencyInjection\Definition;

final class ExampleServiceProvider implements ServiceProviderInterface {

  public function register(ContainerBuilder $container): void {
    $autowire = static fn (string $class): Definition => $container
      ->autowire($class)
      ->setPublic(boolean: TRUE)
      ->setAutoconfigured(autoconfigured: TRUE);

    $autowire(PageNumberPathProcessor::class);
    $autowire(FirstPageRedirect::class);
    $autowire(InvalidPageNotFound::class);
    $autowire(RangeCheckedPagerManager::class);
    $autowire(PageSuffixTitleResolver::class);
    $autowire(ArticlesTitlePageSuffixRouteSubscriber::class);
  }

}
