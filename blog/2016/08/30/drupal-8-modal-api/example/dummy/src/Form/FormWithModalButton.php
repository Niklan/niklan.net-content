<?php
/**
 * @file
 * Contains \Drupal\dummy\Form\FormWithModalButton.
 */

namespace Drupal\dummy\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with modal window.
 */
class FormWithModalButton extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'form_with_modal_button';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => 'Text to show in modal',
      '#default_value' => 'Lorem ipsum',
    ];

    $form['show_im_modal'] = [
      '#type' => 'button',
      '#name' => 'show_im_modal',
      '#value' => 'Show in modal',
      '#ajax' => [
        # Вы также можете указать просто callback функцию а не метод.
        'callback' => '::ajaxModal',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Form submitted.');
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxModal(array &$form, FormStateInterface $form_state) {
    $content['#markup'] = $form_state->getValue('text');
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $title = 'Here is your content in modal';
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $content, ['width' => '400', 'height' => '400']));
    return $response;
  }

}