<?php

namespace Drupal\custom_csv_import;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an CustomCSVImport plugin manager.
 */
class CustomCSVImportManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/CustomCSVImport',
      $namespaces,
      $module_handler,
      'Drupal\custom_csv_import\CustomCSVImportPluginInterface',
      'Drupal\custom_csv_import\Annotation\CustomCSVImport'
    );
    # hook_custom_csv_import_info_alter();
    $this->alterInfo('custom_csv_import_info');
    $this->setCacheBackend($cache_backend, 'custom_csv_import');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
