<?php

namespace Drupal\dummy\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * @Condition(
 *   id = "dummy_user_authenticated",
 *   label = @Translation("User authenticated"),
 * )
 */
class UserAuthenticated extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return \Drupal::currentUser()->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return 'This plugin has no settings.';
  }

}
