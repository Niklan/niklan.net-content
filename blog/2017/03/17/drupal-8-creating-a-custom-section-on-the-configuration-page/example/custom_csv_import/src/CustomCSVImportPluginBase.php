<?php

namespace Drupal\custom_csv_import;

use Drupal\Component\Plugin\PluginBase;

abstract class CustomCSVImportPluginBase extends PluginBase implements CustomCSVImportPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data, &$context) {
    $context['results'][] = 'Result name';
    $context['message'] = 'Status message';
  }

}