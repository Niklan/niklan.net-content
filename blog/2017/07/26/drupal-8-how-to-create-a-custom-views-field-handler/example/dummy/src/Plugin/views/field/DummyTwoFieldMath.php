<?php

namespace Drupal\dummy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("dummy_two_field_math")
 */
class DummyTwoFieldMath extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $first_field = $this->options['first_field'];
    $second_field = $this->options['second_field'];
    if ($first_field && $second_field) {
      $table = $this->table;
      $first_field_db_table = $table . '__' . $first_field;
      $second_field_db_table = $table . '__' . $second_field;
      $this->query->addField($first_field_db_table, $first_field . '_value', 'dummy_first_field');
      $this->query->addField($second_field_db_table, $second_field . '_value', 'dummy_second_field');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['first_field'] = ['default' => NULL];
    $options['second_field'] = ['default' => NULL];
    $options['math_operation'] = ['default' => '+'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $field_options = $this->getFieldOptions();
    $form['first_field'] = [
      '#type' => 'select',
      '#title' => $this->t('First field'),
      '#required' => TRUE,
      '#options' => $field_options,
      '#default_value' => $this->options['first_field'],
    ];
    $form['second_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Second field'),
      '#required' => TRUE,
      '#options' => $field_options,
      '#default_value' => $this->options['second_field'],
    ];
    $form['math_operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Math operation'),
      '#required' => TRUE,
      '#options' => [
        '+' => '+',
        '-' => '-',
        '*' => '*',
        '/' => '/',
      ],
      '#default_value' => $this->options['math_operation'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (isset($values->dummy_first_field) && isset($values->dummy_second_field)) {
      $result = NULL;
      switch ($this->options['math_operation']) {
        case '+':
          $result = $values->dummy_first_field + $values->dummy_second_field;
          break;

        case '-':
          $result = $values->dummy_first_field + $values->dummy_second_field;
          break;

        case '*':
          $result = $values->dummy_first_field * $values->dummy_second_field;
          break;

        case '/':
          $result = $values->dummy_first_field / $values->dummy_second_field;
          break;
      }

      return [
        '#markup' => $result,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldOptions() {
    $allowed_type_list = ['integer', 'float', 'decimal'];
    $exclude_fields = ['nid', 'vid'];
    $field_map = \Drupal::service('entity_field.manager')->getFieldMap();
    $options = [];
    foreach ($field_map['node'] as $field_name => $field_info) {
      if (in_array($field_info['type'], $allowed_type_list) && !in_array($field_name, $exclude_fields)) {
        $options[$field_name] = $field_name;
      }
    }
    return $options;
  }

}
