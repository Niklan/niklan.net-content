<?php

/**
 * @file
 * Contains \Drupal\helloworld\Plugin\Block\PrintMyMessages.
 */

// Пространство имён для нашего блока.
namespace Drupal\helloworld\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Block(
 *   id = "print_my_messages",
 *   admin_label = @Translation("Print my messages"),
 * )
 */
class PrintMyMessages extends BlockBase {

  /**
   * Добавляем наши конфиги по умолчанию.
   *
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'count' => 1,
      'message' => 'Hello World!',
    );
  }

  /**
   * Добавляем в стандартную форму блока свои поля.
   *
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Получаем оригинальную форму для блока.
    $form = parent::blockForm($form, $form_state);
    // Получаем конфиги для данного блока.
    $config = $this->getConfiguration();

    // Добавляем поле для ввода сообщения.
    $form['message'] = array(
      '#type' => 'textfield',
      '#title' => t('Message to printing'),
      '#default_value' => $config['message'],
    );

    // Добавляем поле для количества сообщений.
    $form['count'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#title' => t('How many times display a message'),
      '#default_value' => $config['count'],
    );

    return $form;
  }

  /**
   * Валидируем значения на наши условия.
   * Количество должно быть >= 1,
   * Сообщение должно иметь минимум 5 символов.
   *
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $count = $form_state->getValue('count');
    $message = $form_state->getValue('message');

    // Проверяем введенное число.
    if (!is_numeric($count) || $count < 1) {
      $form_state->setErrorByName('count', t('Needs to be an interger and more or equal 1.'));
    }

    // Проверяем на длину строки.
    if (strlen($message) < 5) {
      $form_state->setErrorByName('message', t('Message must contain more than 5 letters'));
    }
  }

  /**
   * В субмите мы лишь сохраняем наши данные.
   *
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['count'] = $form_state->getValue('count');
    $this->configuration['message'] = $form_state->getValue('message');
  }

  /**
   * Генерируем и выводим содержимое блока.
   *
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $message = '';

    for ($i = 1; $i <= $config['count']; $i++) {
      $message .= $config['message'] . '<br />';
    }

    $block = [
      '#type' => 'markup',
      '#markup' => $message,
    ];
    return $block;
  }

}
