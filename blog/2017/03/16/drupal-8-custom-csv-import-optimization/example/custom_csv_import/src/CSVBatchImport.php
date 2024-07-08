<?php

namespace Drupal\custom_csv_import;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class CSVBatchImport.
 *
 * @package Drupal\custom_csv_import
 */
class CSVBatchImport {

  private $batch;

  private $fid;

  private $file;

  private $skip_first_line;

  private $delimiter;

  private $enclosure;

  private $chunk_size;

  # Название плагина для импорта.
  private $importPluginId;

  # Непосретственно объект плагина.
  private $importPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $fid, $skip_first_line = FALSE, $delimiter = ';', $enclosure = ',', $chunk_size = 20, $batch_name = 'Custom CSV import') {
    $this->importPluginId = $plugin_id;
    $this->importPlugin = \Drupal::service('plugin.manager.custom_csv_import')
      ->createInstance($plugin_id);
    $this->fid = $fid;
    $this->file = File::load($fid);
    $this->skip_first_line = $skip_first_line;
    $this->delimiter = $delimiter;
    $this->enclosure = $enclosure;
    $this->chunk_size = $chunk_size;
    $this->batch = [
      'title' => $batch_name,
      'finished' => [$this, 'finished'],
      'file' => drupal_get_path('module', 'custom_csv_import') . '/src/CSVBatchImport.php',
    ];
    $this->parseCSV();
  }

  /**
   * {@inheritdoc}
   */
  public function parseCSV() {
    $items = [];
    if (($handle = fopen($this->file->getFileUri(), 'r')) !== FALSE) {
      if ($this->skip_first_line) {
        fgetcsv($handle, 0, $this->delimiter);
      }
      while (($data = fgetcsv($handle, 0, $this->delimiter)) !== FALSE) {
        $items[] = $data;
      }
      fclose($handle);
    }

    # После того как распарсили файл в массив, мы разбиваем его на кусочки.
    $chunks = array_chunk($items, $this->chunk_size);
    # Теперь каждый кусок устанавливаем на выполнение.
    foreach ($chunks as $chunk) {
      $this->setOperation($chunk);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($data) {
    $this->batch['operations'][] = [
      [$this->importPlugin, 'processItem'],
      [$data]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setBatch() {
    batch_set($this->batch);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    batch_process();
  }

  /**
   * {@inheritdoc}
   */
  public function finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural(count($results), 'One post processed.', '@count posts processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
