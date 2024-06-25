<?php

namespace Drupal\dummy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DummyTaxonomyTermController
 */
class DummyTaxonomyTermController extends ControllerBase {

  /**
   * @var \Drupal\tvi\Service\TaxonomyViewsIntegratorManager
   */
  private $term_display_manager;

  /**
   * TaxonomyViewsIntegratorTermPageController constructor.
   *
   * @param \Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface $term_display_manager
   */
  public function __construct(TaxonomyViewsIntegratorManagerInterface $term_display_manager) {
    $this->term_display_manager = $term_display_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $term_display_manager = $container->get('tvi.tvi_manager');
    return new static($term_display_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function render(TermInterface $taxonomy_term) {
    if ($taxonomy_term->bundle() == 'catalog') {
      /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $childs = $term_storage->loadChildren($taxonomy_term->id());
      if ($childs) {
        return [
          '#theme' => 'dummy_last_products_in_category',
          '#taxonomy_term' => $taxonomy_term,
        ];
      }
    }
    return $this->term_display_manager->getTaxonomyTermView($taxonomy_term);
  }

}
