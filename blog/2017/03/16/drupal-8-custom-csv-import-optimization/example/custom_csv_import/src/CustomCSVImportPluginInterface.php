<?php

namespace Drupal\custom_csv_import;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface CustomCSVImportPluginInterface extends PluginInspectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getId();

  /**
   * {@inheritdoc}
   */
  public function getLabel();

  /**
   * {@inheritdoc}
   */
  public function processItem($data, &$context);

}