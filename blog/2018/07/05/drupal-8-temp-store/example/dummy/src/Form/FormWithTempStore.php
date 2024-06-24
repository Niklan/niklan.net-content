<?php

namespace Drupal\dummy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\SharedTempStore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Dummy form.
 */
class FormWithTempStore extends FormBase {

  /**
   * Private storage.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Shared storage.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $sharedTempStore;

  /**
   * Constructs a FormWithTempStore object.
   */
  public function __construct(PrivateTempStore $private_temp_store, SharedTempStore $shared_temp_store) {
    $this->privateTempStore = $private_temp_store->get('dummy');
    $this->sharedTempStore = $shared_temp_store->get('dummy');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('tempstore.shared')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_with_temp_store';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['private_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Private message'),
      '#required' => TRUE,
      '#description' => $this->t('This value will be private for each user.'),
      '#default_value' => $this->privateTempStore->get('message'),
    ];

    $form['shared_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Shared message'),
      '#required' => TRUE,
      '#description' => $this->t('This value will be shared for all users.'),
      '#default_value' => $this->sharedTempStore->get('message'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->privateTempStore->set('message', $form_state->getValue('private_message'));
    $this->sharedTempStore->set('message', $form_state->getValue('shared_message'));
  }

}
