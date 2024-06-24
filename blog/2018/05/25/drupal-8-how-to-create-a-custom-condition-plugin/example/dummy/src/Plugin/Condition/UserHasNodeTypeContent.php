<?php

namespace Drupal\dummy\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * @Condition(
 *   id = "dummy_user_has_node_type_content",
 *   label = @Translation("User has node type content"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class UserHasNodeTypeContent extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $node_types = [];
    /** @var \Drupal\node\NodeTypeInterface $item */
    foreach (NodeType::loadMultiple() as $item) {
      $node_types[$item->id()] = $item->label();
    }

    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Node type'),
      '#default_value' => $this->configuration['node_type'],
      '#options' => $node_types,
      '#description' => $this->t('Authorship will be check for selected type.'),
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['node_type'] = $form_state->getValue('node_type');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['node_type' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Default result if node_type config is not set.
    if (empty($this->configuration['node_type']) && !$this->isNegated()) {
      return TRUE;
    }

    $user = $this->getContextValue('user');
    $query = \Drupal::database()->select('node_field_data', 'n')
      ->condition('n.uid', $user->id())
      ->condition('n.type', $this->configuration['node_type']);
    $query->addField('n', 'nid');
    $result = $query->countQuery()->execute()->fetchField();
    return $result ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Checks if there is content of the "@node_type" from user.', [
      '@node_type' => $this->configuration['node_type'],
    ]);
  }

}
