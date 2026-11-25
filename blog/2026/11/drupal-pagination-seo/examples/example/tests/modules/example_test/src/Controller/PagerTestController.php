<?php

declare(strict_types=1);

namespace Drupal\example_test\Controller;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PagerTestController implements TrustedCallbackInterface {

  public function __construct(
    private PagerManagerInterface $pagerManager,
  ) {}

  /**
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager',
    name: 'example_test.pager',
    requirements: ['_access' => 'TRUE'],
    defaults: [
      '_title' => 'Example pager',
      '_title_page_suffix' => TRUE,
    ],
  )]
  public function pager(): array {
    return $this->buildPager();
  }

  /**
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager-custom-title',
    name: 'example_test.pager_custom_title',
    requirements: ['_access' => 'TRUE'],
    defaults: [
      '_title' => 'Route title',
      '_title_page_suffix' => TRUE,
    ],
  )]
  public function customTitle(): array {
    $build = $this->buildPager();
    $build['#title'] = 'Custom title';
    return $build;
  }

  /**
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/no-pager',
    name: 'example_test.no_pager',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'No pager'],
  )]
  public function noPager(): array {
    return [
      '#markup' => '<p>No pager</p>',
      // Makes every query string a separate Dynamic Page Cache variation.
      '#cache' => ['contexts' => ['url.query_args']],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager/child',
    name: 'example_test.pager_child',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'Child page'],
  )]
  public function child(): array {
    return $this->buildPager();
  }

  /**
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager-empty',
    name: 'example_test.pager_empty',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'Empty list'],
  )]
  public function emptyList(): array {
    $this->pagerManager->createPager(0, 10);
    return [
      '#markup' => '<p>Nothing found</p>',
      'pager' => ['#type' => 'pager'],
    ];
  }

  /**
   * A second, smaller pager with the same ID as the main one.
   *
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager-shared-id',
    name: 'example_test.pager_shared_id',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'Shared ID'],
  )]
  public function sharedId(): array {
    $build = $this->buildPager();
    $this->pagerManager->createPager(1, 1);
    return $build;
  }

  /**
   * A second, smaller pager with its own ID.
   *
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager-separate-id',
    name: 'example_test.pager_separate_id',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'Separate ID'],
  )]
  public function separateId(): array {
    $build = $this->buildPager();
    $this->pagerManager->createPager(1, 1, 1);
    return $build;
  }

  /**
   * A Views Mini pager, which uses its own theme hook.
   *
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager-mini',
    name: 'example_test.pager_mini',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'Mini pager'],
  )]
  public function miniPager(): array {
    $this->pagerManager->createPager(10, 1);
    return [
      '#theme' => 'views_mini_pager',
      '#tags' => [],
      '#element' => 0,
      '#parameters' => [],
    ];
  }

  /**
   * The pager is created in a lazy builder, without the marker cache tag.
   *
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager-lazy',
    name: 'example_test.pager_lazy',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'Lazy pager'],
  )]
  public function lazy(): array {
    return ['list' => ['#create_placeholder' => TRUE, '#lazy_builder' => [self::class . ':lazyPager', []]]];
  }

  /**
   * The same, but the parent render array carries the marker cache tag.
   *
   * @return array<string, mixed>
   */
  #[Route(
    path: '/example/pager-lazy-tagged',
    name: 'example_test.pager_lazy_tagged',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => 'Lazy pager with tag'],
  )]
  public function lazyTagged(): array {
    return [
      'list' => ['#create_placeholder' => TRUE, '#lazy_builder' => [self::class . ':lazyPager', []]],
      '#cache' => ['tags' => ['pager:rendered']],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public function lazyPager(): array {
    return $this->buildPager();
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['lazyPager'];
  }

  /**
   * @return array<string, mixed>
   */
  private function buildPager(): array {
    $this->pagerManager->createPager(10, 1);
    return [
      '#markup' => '<p>Pager</p>',
      'pager' => ['#type' => 'pager'],
    ];
  }

}
