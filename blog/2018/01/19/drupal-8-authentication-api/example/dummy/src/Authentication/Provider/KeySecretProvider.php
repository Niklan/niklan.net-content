<?php

namespace Drupal\dummy\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class KeySecretProvider.
 */
class KeySecretProvider implements AuthenticationProviderInterface {

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
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->query->has('key');
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $key = $request->query->get('key');
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties([
        'dummy_key' => $key,
      ]);

    if (!empty($user)) {
      return reset($user);
    }
    return NULL;
  }

}
