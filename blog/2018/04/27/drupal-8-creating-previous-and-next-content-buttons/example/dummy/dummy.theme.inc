<?php

/**
 * @file
 * Contains preprocess functions for custom theme hooks.
 */

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_dummy_previous_next(&$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $entity */
  $entity = $variables['entity'];
  $variables['next'] = [];
  $variables['previous'] = [];

  // Trying to find next entity ID.
  $next_entity_id = \Drupal::entityQuery($entity->getEntityTypeId())
    ->condition('type', $entity->bundle())
    ->condition('created', $entity->getCreatedTime(), '>')
    ->range(0, 1)
    ->sort('created', 'ASC')
    ->execute();
  if (!empty($next_entity_id)) {
    $next_entity = \Drupal::entityTypeManager()
      ->getStorage($entity->getEntityTypeId())
      ->load(reset($next_entity_id));
    $variables['next']['label'] = $next_entity->label();
    $variables['next']['url'] = $next_entity->toUrl()->toString(TRUE)->getGeneratedUrl();
  }

  // Trying to find previous entity ID.
  $previous_entity_id = \Drupal::entityQuery($entity->getEntityTypeId())
    ->condition('type', $entity->bundle())
    ->condition('created', $entity->getCreatedTime(), '<')
    ->range(0, 1)
    ->sort('created', 'DESC')
    ->execute();
  if (!empty($previous_entity_id)) {
    $previous_entity = \Drupal::entityTypeManager()
      ->getStorage($entity->getEntityTypeId())
      ->load(reset($previous_entity_id));
    $variables['previous']['label'] = $previous_entity->label();
    $variables['previous']['url'] = $previous_entity->toUrl()->toString(TRUE)->getGeneratedUrl();
  }
}
