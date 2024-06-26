<?php

namespace Drupal\dummy\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * @SearchApiProcessor(
 *   id = "dummy_estimated_read_time",
 *   label = @Translation("Estimated read time"),
 *   description = @Translation("Calculate estimated read time in seconds."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   hidden = true,
 *   locked = true,
 * )
 */
class EstimatedReadTime extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Estimated read time'),
        'description' => $this->t('Calculate estimated read time in seconds.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['dummy_estimated_read_time'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    /** @var EntityInterface $entity */
    $entity = $item->getOriginalObject()->getValue();

    if ($entity instanceof EntityInterface && $entity->hasField('body')) {
      $body_value = $entity->body->value;
      if ($body_value) {
        // Average word per minute reading for all languages.
        // @see https://en.wikipedia.org/wiki/Words_per_minute
        $word_per_minute = 184;
        $word_count = str_word_count(strip_tags($body_value));
        $estimated_read_time = floor(($word_count / $word_per_minute) * 60);

        // Add value to index.
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'dummy_estimated_read_time');
        foreach ($fields as $field) {
          $field->addValue($estimated_read_time);
        }
      }
    }
  }

}