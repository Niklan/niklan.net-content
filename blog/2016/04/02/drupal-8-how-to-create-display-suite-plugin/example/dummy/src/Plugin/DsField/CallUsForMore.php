<?php

namespace Drupal\dummy\Plugin\DsField;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\ds\Plugin\DsField\DsFieldBase;
# Необходимо для формы.
use Drupal\Core\Form\FormStateInterface;

/**
 * Поле которое выводит количество слов в содержимом.
 *
 * @DsField(
 *   id = "call_us_for_more",
 *   title = @Translation("DS: Call us for more"),
 *   provider = "dummy",
 *   entity_type = "node",
 *   ui_limit = {"article|full"}
 * )
 */
class CallUsForMore extends DsFieldBase {

  /**
   * {@inheritdoc}
   *
   * Задаем настройки по умолчанию.
   */
  public function defaultConfiguration() {
    $config = [
      'telephone' => '+7 (999) 123-45-67',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   * Данный метод должен возвращать форму в соответствии с Form API.
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    # Получаем конфигурацию
    $config = $this->getConfiguration();
    # Название элемента формы должно равнятся ключу в конфигах.
    $form['telephone'] = [
      '#type' => 'tel',
      '#title' => 'Номер телефона',
      '#default_value' => $config['telephone'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * В общем списке полей выводим информацию о номере телефона, чтобы не
   * приходилось загружать форму для проверки. Каждое занчение массива будет
   * выводиться с новой строки.
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();
    return ['Номер телефона: ' . $config['telephone']];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    # Получаем настройки с нашим телефоном.
    $config = $this->getConfiguration();
    return [
      '#type' => 'markup',
      '#markup' => new FormattableMarkup(
        '<strong>Остались вопросы? Позвоните нам:</strong> <a href="tel:@phone">@phone</a>',
        [
          '@phone' => $config['telephone'],
        ]
      )
    ];
  }
}
