<?php

namespace Drupal\dummy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HookThemeFormExample
 *
 * @package Drupal\dummy\Form
 */
class HookThemeFormExample extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Обратите на это внимание, данное значение будет использовано в качестве
    // theme hook.
    return 'hook_theme_form_example';
  }

  /**
   * Создание нашей формы.
   *
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['tour_length'] = [
      '#type' => 'number',
      '#default_value' => 1,
      '#min' => 1,
      '#max' => 30,
    ];

    $form['adults'] = [
      '#type' => 'number',
      '#title' => 'Adults',
      '#default_value' => 1,
      '#min' => 1,
      '#max' => 4,
    ];

    $form['kids'] = [
      '#type' => 'number',
      '#title' => 'Kids, 2-12 years',
      '#default_value' => 0,
      '#min' => 0,
      '#max' => 4,
    ];

    $form['infants'] = [
      '#type' => 'number',
      '#title' => 'Infants, 0-2 years',
      '#default_value' => 0,
      '#min' => 0,
      '#max' => 4,
    ];

    $form['arrival_date'] = [
      '#type' => 'date',
      '#title' => 'Arrival date',
    ];

    $form['departure_date'] = [
      '#type' => 'date',
      '#title' => 'Departure date',
    ];

    $form['arrival_city'] = [
      '#type' => 'textfield',
      '#title' => 'Arrival city',
    ];

    $form['departure_city'] = [
      '#type' => 'textfield',
      '#title' => 'Departure city',
    ];

    $form['car'] = [
      '#type' => 'select',
      '#options' => [
        'Tesla',
        'Mercedes',
        'BMW',
      ],
    ];

    $form['hotel'] = [
      '#type' => 'select',
      '#options' => [
        '5*',
        '4*',
        '3*',
      ],
    ];

    $form['my_interests'] = [
      '#type' => 'textfield',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Full name',
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Email*',
      ],
    ];

    $form['country'] = [
      '#type' => 'email',
      '#attributes' => [
        'placeholder' => 'Your Country',
      ],
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#attributes' => [
        'placeholder' => 'Phone number',
      ],
    ];

    $form['comment'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'placeholder' => 'Leave us a comment',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Good Job!');
  }

}
