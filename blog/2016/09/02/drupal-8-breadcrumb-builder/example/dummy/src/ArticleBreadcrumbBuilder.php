<?php

/**
 * @file
 * Contains Drupal\dummy\ArticleBreadcrumbBuilder.
 */

namespace Drupal\dummy;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class ArticleBreadcrumbBuilder.
 */
class ArticleBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  # Необходимо чтобы использовать $this->t().
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $node = $route_match->getParameter('node');
    # Только если мы находимся на странице сущности node типа article.
    return $node instanceof NodeInterface && $node->getType() == 'article';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $node = $route_match->getParameter('node');
    # Добавляем первую крошку на главную страницу.
    $links = [Link::createFromRoute($this->t('Home'), '<front>')];
    # Вторую крошку добавляем если категория задана в поле field_category.
    if (!$node->field_category->isEmpty()) {
      $tid = $node->field_category->target_id;
      $category_term = Term::load($tid);
      # Добавляем хлебную крошку на страницу термина.
      $links[] = Link::createFromRoute($category_term->name->value, 'entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]);
    }
    # Указываем контекст для кэширования данной хлебной крошки. В нашем случае
    # будет кэшироваться только для текущего адреса url.
    $breadcrumb->addCacheContexts(['url.path']);
    return $breadcrumb->setLinks($links);
  }
}
