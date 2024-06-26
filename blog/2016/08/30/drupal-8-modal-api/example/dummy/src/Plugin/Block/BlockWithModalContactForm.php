<?php

/**
 * @file
 * Contains \Drupal\dummy\Plugin\Block\BlockWithModalContactForm.
 */

namespace Drupal\dummy\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "block_with_modal_contact_form",
 *   admin_label = @Translation("Modal API example: Contact form"),
 * )
 */
class BlockWithModalContactForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getContactForms() {
    $bundle_info = \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo('contact_message');
    $forms = [];
    foreach ($bundle_info as $k => $v) {
      $forms[$k] = $v['label'];
    }
    unset($forms['personal']);
    return $forms;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['form'] = [
      '#type' => 'select',
      '#title' => 'Select form to open in modal',
      '#options' => $this->getContactForms(),
      '#empty_option' => '- Select -',
      '#default_value' => $config['form'] ? $config['form'] : FALSE,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['form'] = $form_state->getValue('form');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    return [
      '#type' => 'link',
      '#title' => 'Contact with me!',
      '#url' => Url::fromRoute('entity.contact_form.canonical', ['contact_form' => $config['form']]),
      '#options' => [
        'attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ]
      ],
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];
  }

}
