<?php

namespace Drupal\dummy\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class HookThemeExamples
 */
class HookThemeExamples extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function page() {
    return [
      '#markup' => 'Will be removed',
    ];
  }

}
