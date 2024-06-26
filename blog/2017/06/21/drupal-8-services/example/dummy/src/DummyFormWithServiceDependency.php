<?php

namespace Drupal\dummy;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DummyFormWithServiceDependency
 *
 * @package Drupal\dummy
 */
class DummyFormWithServiceDependency extends FormBase {

  protected $random_message_generator;
  protected $random_drupal_message;

  /**
   * DummyFormWithServiceDependency constructor.
   *
   * В данный конструктор передаются экземпляры сервисов в том же самом порядке,
   * в каком они указаны в методе create. Соответственно там и указывается что
   * будет передано и загружено.
   *
   * @param \Drupal\dummy\RandomMessageGenerator $random_message_generator
   * @param \Drupal\dummy\RandomDrupalMessage    $random_drupal_message
   */
  public function __construct(\Drupal\dummy\RandomMessageGenerator $random_message_generator, \Drupal\dummy\RandomDrupalMessage $random_drupal_message) {
    $this->random_message_generator = $random_message_generator;
    $this->random_drupal_message = $random_drupal_message;
  }

  /**
   * В данном методе мы указываем все нужные нам сервисыю
   */
  public static function create(ContainerInterface $container) {
    // Передаваться они будут в соответствующем порядке.
    return new static(
      $container->get('dummy.random_message'),
      $container->get('dummy.random_drupal_message')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'dummy_form_with_service_dependecy';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->random_drupal_message->setRandomMessage();

    $form['random_message'] = [
      '#markup' => $this->random_message_generator->getRandomMessage(),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm();
  }
}
