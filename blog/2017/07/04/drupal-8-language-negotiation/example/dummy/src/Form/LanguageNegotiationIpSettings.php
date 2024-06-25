<?php

namespace Drupal\dummy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class LanguageNegotiationIpSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'collect_phone';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dummy.language_negotiation_ip_settings',
    ];
  }

  /**
   * Создание нашей формы.
   *
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dummy.language_negotiation_ip_settings');
    $form['ips'] = [
      '#type' => 'textarea',
      '#title' => $this->t('IP addresses'),
      '#description' => $this->t('Each IP from new line. For this IP\'s will be used Russian language.'),
      '#default_value' => $config->get('ips'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dummy.language_negotiation_ip_settings')
      ->set('ips', $form_state->getValue('ips'))
      ->save();
  }

}
