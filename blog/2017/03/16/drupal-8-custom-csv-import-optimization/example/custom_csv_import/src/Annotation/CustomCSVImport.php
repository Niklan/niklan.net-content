<?php

namespace Drupal\custom_csv_import\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotations for CustomCSVImport plugins.
 *
 * @Annotation
 */
class CustomCSVImport extends Plugin {

  /**
   * The plugin ID.
   */
  public $id;

  /**
   * Label will be used in interface.
   */
  public $label;

}
