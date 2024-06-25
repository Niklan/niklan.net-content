<?php
/**
 * @file
 * Contains \Drupal\helloworld\Form\QueueNode.
 */

namespace Drupal\helloworld\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Объявляем нашу форму для отправки писем.
 */
class QueueNode extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'queue_node_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    # Получаем нашу очередь, чтобы блокировать доступ к кнопку, если она еще
    # активна.
    $queue = \Drupal::queue('helloworld_mass_sending');
    if ($number_of_items = $queue->numberOfItems()) {
      $form['info_text'] = [
        '#type' => 'markup',
        '#markup' => new FormattableMarkup('<div>Данная очередь уже запущена, еще осталось: @number</div>', [
          '@number' => $number_of_items,
        ]),
      ];

      $form['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel current queue'),
        '#disable' => TRUE,
      ];

    }
    else {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create node for each user'),
        '#disable' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    # Получаем объект очереди.
    $queue = \Drupal::queue('helloworld_mass_sending');

    # Если нажата кнопка удаления, мы удаляем нашу очередь.
    if ($form_state->getTriggeringElement()['#id'] == 'edit-delete') {
      $queue->deleteQueue();
    }
    else {
      # Получаем список всех активных пользователей на сайте.
      $query = \Drupal::database()->select('users_field_data', 'u')
        ->fields('u', array('uid', 'name'))
        ->condition('u.status', 1);
      $result = $query->execute();

      # Создаем нашу очередь.
      $queue->createQueue();

      # Добавляем данные в очередь
      foreach ($result as $row) {
        $queue->createItem([
          'uid' => $row->uid,
          'name' => $row->name,
        ]);
      }
    }
  }

}
