<?php

namespace Drupal\dummy\Plugin\migrate\source;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Use csv file data as source.
 *
 * @MigrateSource(
 *   id = "dummy_csv"
 * )
 */
class CSV extends SourcePluginBase {

  /**
   * Path to the file relative to module.
   *
   * @var string
   */
  protected $path;

  /**
   * Module which path will be base for full path.
   *
   * @var string
   */
  protected $module;

  /**
   * Full path to the file.
   *
   * @var string
   */
  protected $fullPath;

  /**
   * Columns definition for csv.
   *
   * @var array
   */
  protected $columns;

  /**
   * The main ID in csv file based on columns keys.
   *
   * @var string
   */
  protected $key;

  /**
   * Is first line must be skipped during import.
   *
   * @var bool
   */
  protected $skipFirstLine;

  /**
   * CSV constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\migrate\Plugin\MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    if (empty($this->configuration['path']) || empty($this->configuration['module'])) {
      throw new MigrateException('The path or module is not set.');
    }
    else {
      $this->module = $this->configuration['module'];
      $this->path = $this->configuration['path'];
      $this->fullPath = drupal_get_path('module', $this->module) . '/' . $this->path;

      if (!file_exists($this->fullPath)) {
        throw new MigrateException('CSV file is not found in provided path.');
      }
    }

    if (empty($this->configuration['columns'])) {
      throw new MigrateException('Columns is must be set.');
    }
    else {
      $this->columns = $this->configuration['columns'];
    }

    if (empty($this->configuration['key'])) {
      throw new MigrateException('The key is must be set.');
    }
    else {
      $this->key = $this->configuration['key'];

      $is_key_found = FALSE;
      foreach ($this->columns as $column => $info) {
        if ($this->key == $info['key']) {
          $is_key_found = TRUE;
          break;
        }
      }

      if (!$is_key_found) {
        throw new MigrateException('The key is not match any of columns keys.');
      }
    }

    $this->skipFirstLine = $this->configuration['skip_first_line'] ?? FALSE;
  }

  /**
   * Returns available fields on the source.
   *
   * @return array
   *   Available fields in the source, keys are the field machine names as used
   *   in field mappings, values are descriptions.
   */
  public function fields() {
    $fields = [];
    foreach ($this->columns as $key => $info) {
      $fields[$info['key']] = $info['label'];
    }
    return $fields;
  }

  /**
   * Allows class to decide how it will react when it is treated like a string.
   */
  public function __toString() {
    return $this->fullPath;
  }

  /**
   * Defines the source fields uniquely identifying a source row.
   */
  public function getIds() {
    return [
      $this->key => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * Initializes the iterator with the source data.
   */
  protected function initializeIterator() {
    $csv_data = $this->parseCsv();
    return new \ArrayIterator($csv_data);
  }

  /**
   * Parse CSV to array.
   */
  protected function parseCsv() {
    $items = [];
    $delimiter = !empty($this->configuration['delimiter']) ? $this->configuration['delimiter'] : ',';
    $enclosure = !empty($this->configuration['enclosure']) ? $this->configuration['enclosure'] : '"';
    if (($handle = fopen($this->fullPath, 'r')) !== FALSE) {
      if ($this->skipFirstLine) {
        fgetcsv($handle, 0, $delimiter, $enclosure);
      }
      while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
        $row = [];
        foreach ($this->columns as $key => $info) {
          $row[$info['key']] = $data[$key];
        }
        $items[] = $row;
      }
      fclose($handle);
    }
    return $items;
  }

}
