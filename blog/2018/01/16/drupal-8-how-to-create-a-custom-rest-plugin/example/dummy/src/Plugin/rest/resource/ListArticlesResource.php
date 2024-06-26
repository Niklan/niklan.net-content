<?php

namespace Drupal\dummy\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get articles.
 *
 * @RestResource(
 *   id = "list_articles_resource",
 *   label = @Translation("List articles resource"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/list-articles"
 *   }
 * )
 */
class ListArticlesResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Default limit entities per request.
   */
  protected $limit = 10;

  /**
   * Constructs a new ListArticlesResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('dummy'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   */
  public function get() {
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    // Add default cache parameters.
    $cache = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'max-age' => 600,
        'contexts' => ['url.query_args'],
      ],
    ]);

    $response = [
      'items' => [],
      'next_page' => FALSE,
      'prev_page' => FALSE,
    ];
    $request = \Drupal::request();
    $request_query = $request->query;
    $request_query_array = $request_query->all();
    $limit = $request_query->get('limit') ?: $this->limit;
    $page = $request_query->get('page') ?: 0;

    // Find out how many articles do we have.
    $query = \Drupal::entityQuery('node')->condition('type', 'article');
    $articles_count = $query->count()->execute();
    $position = $limit * ($page + 1);
    if ($articles_count > $position) {
      $next_page_query = $request_query_array;
      $next_page_query['page'] = $page + 1;
      $response['next_page'] = Url::createFromRequest($request)
        ->setOption('query', $next_page_query)
        ->toString(TRUE)
        ->getGeneratedUrl();
    }

    if ($page > 0) {
      $prev_page_query = $request_query_array;
      $prev_page_query['page'] = $page - 1;
      $response['prev_page'] = Url::createFromRequest($request)
        ->setOption('query', $prev_page_query)
        ->toString(TRUE)
        ->getGeneratedUrl();
    }

    // Find articles.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->pager($limit);
    $result = $query->execute();
    $articles = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($result);

    /** @var \Drupal\node\Entity\Node $article */
    foreach ($articles as $article) {
      $response['items'][] = [
        'label' => $article->label(),
        'id' => $article->id(),
        'created' => $article->getCreatedTime(),
      ];
      $cache->addCacheableDependency($article);
    }

    return (new ResourceResponse($response, 200))->addCacheableDependency($cache);
  }

}
