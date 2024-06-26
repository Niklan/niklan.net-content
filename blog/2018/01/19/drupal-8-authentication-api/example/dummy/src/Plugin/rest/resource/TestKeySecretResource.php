<?php

namespace Drupal\dummy\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "test_key_secret_resource",
 *   label = @Translation("Test key secret resource"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/current-user",
 *     "create" = "/api/v1/current-user"
 *   }
 * )
 */
class TestKeySecretResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Safe request methods.
   */
  protected $safeMethods = [
    'HEAD',
    'GET',
    'OPTIONS',
    'TRACE',
  ];

  /**
   * Constructs a new TestKeySecretResource object.
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
   * {@inheritdoc}
   */
  public function get() {
    return new ResourceResponse($this->currentUser->getAccount(), 200);
  }

  /**
   * {@inheritdoc}
   */
  public function post(array $data = []) {
    if ($this->checkAccess($data)) {
      return new ResourceResponse($this->currentUser->getAccount(), 200);
    }
    else {
      return new ResourceResponse("Check for secret doesn't passed.", 400);
    }
  }

  /**
   * Check for key and secret. If secret is used, we check if it valid.
   */
  protected function checkAccess($data) {
    $request = \Drupal::request();
    if (in_array($request->getMethod(), $this->safeMethods)) {
      // Safe method, so if user with valid key is found, we grant access.
      return TRUE;
    }
    else {
      // Unsafe methods is unsafe :) We will check data send to server with
      // simple encryption using secret key.
      // Save hash send by user with request and remove it from data array.
      $user_hash = $data['hash'];
      unset($data['hash']);
      // Added user secret to array with data.
      $data[] = $this->currentUser->getAccount()->dummy_secret->value;
      // Create string from data.
      $string = implode(':', $data);
      // Hash it.
      $our_hash = md5($string);

      if ($user_hash == $our_hash) {
        // The data is valid for user, grant access.
        return TRUE;
      }
      else {
        // Seems like data was compromised.
        return FALSE;
      }
    }
  }

}
