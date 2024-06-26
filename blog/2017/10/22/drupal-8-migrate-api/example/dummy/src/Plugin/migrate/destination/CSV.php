<?php

namespace Drupal\dummy\Plugin\migrate\destination;

use Drupal\migrate\Annotation\MigrateDestination;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Use csv file data as source.
 *
 * @MigrateDestination(
 *   id = "dummy_csv"
 * )
 */
class CSV extends DestinationBase {

  const UPDATE_ROW_STATUS_DELETE = 0;
  const UPDATE_ROW_STATUS_UPDATE = 1;

  /**
   * Path to the file relative to file scheme.
   *
   * @var string
   */
  protected $path;

  /**
   * File scheme where to save results.
   *
   * @var string
   */
  protected $fileScheme = 'public';

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
   * Delta for key in array.
   *
   * @var int
   */
  protected $keyDelta;

  /**
   * Delimiter for CSV.
   *
   * @var string
   */
  protected $delimiter;

  /**
   * Enclosure for CSV.
   *
   * @var string
   */
  protected $enclosure;

  /**
   * CSV constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\migrate\Plugin\MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    if (empty($this->configuration['path'])) {
      throw new MigrateException('The path is not set.');
    }
    else {
      $this->fileScheme = $this->configuration['file_scheme'];
      $this->path = $this->configuration['path'];
      $this->fullPath = $this->fileScheme . '://' . $this->path;
    }

    $this->delimiter = !empty($this->configuration['delimiter']) ? $this->configuration['delimiter'] : ',';
    $this->enclosure = !empty($this->configuration['enclosure']) ? $this->configuration['enclosure'] : '"';

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
      foreach ($this->columns as $delta => $info) {

        if ($this->key == $info['key']) {
          $is_key_found = TRUE;
          $this->keyDelta = $delta;
          break;
        }
      }

      if (!$is_key_found) {
        throw new MigrateException('The key is not match any of columns keys.');
      }
    }

    // Create empty file if not exists.
    if (!file_exists($this->fullPath)) {
      touch($this->fullPath);
      $csv = fopen($this->fullPath, 'w');

      // Add headers.
      if (!empty($this->configuration['add_titles']) && empty($old_destination_id_values)) {
        $headers = [];
        foreach ($this->columns as $key => $info) {
          $headers[$key] = $info['label'];
        }
        fputcsv($csv, $headers, $this->delimiter, $this->enclosure);
      }
      fclose($csv);
    }
  }

  /**
   * Gets the destination IDs.
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * Returns an array of destination fields.
   */
  public function fields(MigrationInterface $migration = NULL) {
    $fields = [];
    foreach ($this->configuration['columns'] as $key => $info) {
      $fields[$info['key']] = $info['label'];
    }
    return $fields;
  }

  /**
   * Import the row.
   *
   * @param \Drupal\migrate\Row $row
   *   Object with information about alld destination values.
   * @param array $old_destination_id_values
   *   An array of old values for keys. Is empty, this means current row is new,
   *   otherwise it's update.
   *
   * @return array|mixed
   *   An array of new ID's according to keys.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $csv = fopen($this->fullPath, 'a+');
    $key_value = $row->getDestinationProperty($this->key);

    $values = [];
    foreach ($this->columns as $key => $info) {
      $values[$key] = $row->getDestinationProperty($info['value']);
    }

    if (empty($old_destination_id_values)) {
      // Import.
      fputcsv($csv, $values, $this->delimiter, $this->enclosure);
    }
    else {
      // Update.
      // Create temp file to rewrite csv for modify CSV.
      $is_found = FALSE;
      while (($data = fgetcsv($csv, 0, $this->delimiter, $this->enclosure)) !== FALSE || $is_found == FALSE) {
        list($old_id) = $old_destination_id_values;
        $current_id = $data[$this->keyDelta];
        if ($current_id == $old_id) {
          $this->updateRow($current_id, $values);
          #fputcsv($csv, $values, $this->delimiter, $this->enclosure);
          $is_found = TRUE;
        }
      }
    }
    fclose($csv);
    return [$key_value];
  }

  /**
   * Updates or remove row from CSV.
   *
   * Because CSV in php doesn't support modifying of row on it's own, we need to
   * create new file with modified data. Yes, this is a lot of unnecessary
   * operations, but this is the only way.
   *
   * @param mixed $id
   *   Identifier of the row which need to be updated or delete.
   * @param array $values
   *   An array of new values to replace if identifier founded.
   * @param int $status
   *   The status for row to be process. Available values:
   *   - delete: self::UPDATE_ROW_STATUS_DELETE.
   *   - update: self::UPDATE_ROW_STATUS_UPDATE.
   */
  public function updateRow($id, array $values, $status = self::UPDATE_ROW_STATUS_UPDATE) {
    $temp_file = \Drupal::service('file_system')->tempnam('/tmp', 'csv_migrate');
    $current_csv = fopen($this->fullPath, 'r');
    $temp_csv = fopen($temp_file, 'a+');
    $is_founded = FALSE;
    while (($data = fgetcsv($current_csv, 0, $this->delimiter, $this->enclosure)) !== FALSE || !$is_founded) {
      if ($data[$this->keyDelta] == $id) {
        $is_founded = TRUE;

        switch ($status) {
          // Add new values instead of old.
          case self::UPDATE_ROW_STATUS_UPDATE:
            fputcsv($temp_csv, $values, $this->delimiter, $this->enclosure);
            break;

          // If we need to delete it, we just don't add it to the new file.
          case self::UPDATE_ROW_STATUS_DELETE:
            continue;
        }
      }
      else {
        fputcsv($temp_csv, $data, $this->delimiter, $this->enclosure);
      }
    }
    fclose($current_csv);
    fclose($temp_csv);
    // Remove the old CSV.
    unlink($this->fullPath);
    // Move new file to the original path.
    copy($temp_file, $this->fullPath);
  }

  /**
   * We add support to rollback CSV.
   *
   * @return bool
   */
  public function supportsRollback() {
    return TRUE;
  }

  /**
   * Rollback process for CSV.
   *
   * As an update, the CSV files doesn't support to directy access needed value
   * by any data. So we need to create file and remove it from it. We can't just
   * remove file, because the rollback can be limited to specific amount or ids
   * and we will handle it.
   *
   * @param array $destination_identifier
   */
  public function rollback(array $destination_identifier) {
    $this->updateRow($destination_identifier[$this->key], [], self::UPDATE_ROW_STATUS_DELETE);
  }

}
