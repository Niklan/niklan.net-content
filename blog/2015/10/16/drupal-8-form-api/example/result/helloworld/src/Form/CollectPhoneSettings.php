<?php

/**
 * @file
 * Contains \Drupal\helloworld\Form\CollectPhoneSettings.
 */

namespace Drupal\helloworld\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class CollectPhoneSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collect_phone_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // Возвращает названия конфиг файла.
    // Значения будут храниться в файле:
    // helloworld.collect_phone.settings.yml
    return [
      'helloworld.collect_phone.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Загружаем наши конфиги.
    $config = $this->config('helloworld.collect_phone.settings');
    // Добавляем поле для возможности задать телефон по умолчанию.
    // Далее мы будем использовать это значение в предыдущей форме.
    $form['default_phone_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default phone number'),
      '#default_value' => $config->get('phone_number'),
    );
    // Субмит наследуем от ConfigFormBase
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Записываем значения в наш конфиг файл и сохраняем.
    $this->config('helloworld.collect_phone.settings')
      ->set('phone_number', $values['default_phone_number'])
      ->save();
  }
}
