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
 *   id = "dummy_lesson_cities",
 *   label = @Translation("Lesson cities"),
 *   description = @Translation("Parse cities from paragraphs of lesson
 *   places."), stages = {
 *     "add_properties" = 0,
 *   },
 *   hidden = true,
 *   locked = true,
 * )
 */
class LessonCities extends ProcessorPluginBase {

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
        'label' => $this->t('Lesson cities'),
        'description' => $this->t('Parse cities from paragraphs of lesson places.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['dummy_lesson_cities'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    /** @var EntityInterface $entity */
    $entity = $item->getOriginalObject()->getValue();

    if ($entity instanceof EntityInterface && $entity->hasField('field_lesson_places')) {
      $cities = [];

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      foreach ($entity->field_lesson_places->referencedEntities() as $paragraph) {
        $city = $paragraph->field_lesson_city->value;
        // To exclude duplicates.
        $cities[$city] = $city;
      }

      // Add value to index.
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, 'dummy_lesson_cities');
      foreach ($fields as $field) {
        $field->setValues($cities);
      }
    }
  }

}