<?php

namespace Drupal\dummy\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CookieBackdoorProvider.
 */
class CookieBackdoorProvider implements AuthenticationProviderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks whether suitable authentication credentials are on the request.
   */
  public function applies(Request $request) {
    return $request->cookies->has('backdoor_uid');
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($request->cookies->get('backdoor_uid'));

    if ($user) {
      \Drupal::service('session_manager')->regenerate();
      return $user;
    }
    return NULL;
  }

}
