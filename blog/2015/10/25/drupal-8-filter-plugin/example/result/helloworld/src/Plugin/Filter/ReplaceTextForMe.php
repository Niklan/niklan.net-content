<?php
/**
 * @file
 * Contains Drupal\helloworld\Plugin\Filter\ReplaceTextForMe.
 */

namespace Drupal\helloworld\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\Core\Form\FormStateInterface;

/**
 * Обратите внимание на новую аннтацию 'settings'. В неё мы передаем значения
 * по умолчанию для нашего фильтра.
 *
 * @Filter(
 *   id = "replace_text_for_me",
 *   title = @Translation("Replace string from one to over."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "search" = "Hello World",
 *     "replace" = "Hello"
 *   }
 * )
 */
class ReplaceTextForMe extends FilterBase {

  /**
   * Форма для настройки нашего фильтра.
   * Важно! Название элементов формы должны совпадать с ключами в настройках.
   * Это позволит плагину автоматически подгружать, а также правильно сохранять
   * настройки введенные пользователями.
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#default_value' => $this->settings['search'],
      '#maxlength' => 1024,
      '#size' => 250,
    ];

    $form['replace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replace'),
      '#default_value' => $this->settings['replace'],
      '#maxlength' => 1024,
      '#size' => 250,
    ];

    return $form;
  }

  /**
   * Выполнение нашего фильтра.
   *
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    // Производим замену в соотвтествии с настройками фильтра.
    $text = str_replace($this->settings['search'], $this->settings['replace'], $text);
    // Сохраняем результат и возвращаем.
    $result->setProcessedText($text);
    return $result;
  }

}
