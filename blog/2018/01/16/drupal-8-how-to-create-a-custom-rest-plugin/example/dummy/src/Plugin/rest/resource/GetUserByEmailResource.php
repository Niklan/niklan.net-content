<?php

namespace Drupal\dummy\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get user by email.
 *
 * @RestResource(
 *   id = "get_user_by_email_resource",
 *   label = @Translation("Get user by email resource"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/get-user-by-email"
 *   }
 * )
 */
class GetUserByEmailResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
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
    $query = \Drupal::request()->query;
    $response = [];
    if ($query->has('email')) {
      $result = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['mail' => $query->get('email')]);
      $user = reset($result);

      if ($user instanceof UserInterface) {
        $response['user'] = [
          'name' => $user->label(),
          'uid' => $user->id(),
          'mail' => $user->getEmail(),
        ];
        return new ResourceResponse($response);
      }
      else {
        $response['user'] = NULL;
        $response['message'] = 'User with email ' . $query->get('email') . ' is not found';
        return new ResourceResponse($response);
      }
    }
    else {
      return new ResourceResponse('Required parameter email is not set.', 400);
    }
  }

}
