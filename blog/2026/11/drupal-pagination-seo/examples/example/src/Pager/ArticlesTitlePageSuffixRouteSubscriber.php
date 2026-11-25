<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

final class ArticlesTitlePageSuffixRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection): void {
    $collection
      ->get('view.featured_articles.page_1')
      ?->setDefault('_title_page_suffix', TRUE);
  }

}
