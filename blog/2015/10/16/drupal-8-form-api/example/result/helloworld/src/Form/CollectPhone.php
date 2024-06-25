<?php
/**
 * @file
 * Contains \Drupal\helloworld\Form\CollectPhone.
 *
 * В комментарии выше указываем, что содержится в данном файле.
 */

// Объявляем пространство имён формы. Drupal\НАЗВАНИЕ_МОДУЛЯ\Form
namespace Drupal\helloworld\Form;

// Указываем что нам потребуется FormBase, от которого мы будем наследоваться
// а также FormStateInterface который позволит работать с данными.
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Объявляем нашу форму, наследуясь от FormBase.
 * Название класса строго должно соответствовать названию файла.
 */
class CollectPhone extends FormBase {

  /**
   * То что ниже - это аннотация. Аннотации пишутся в комментариях и в них
   * объявляются различные данные. В данном случае указано, что документацию
   * к данному методу надо взять из комментария к самому классу.
   *
   * А в самом методе мы возвращаем название нашей формы в виде строки.
   *
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'collect_phone';
  }

  /**
   * Создание нашей формы.
   *
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Загружаем настройки модули из формы CollectPhoneSettings.
    $config = \Drupal::config('helloworld.collect_phone.settings');
    // Объявляем телефон.
    $form['phone_number'] = array(
      '#type' => 'tel',
      // Не забываем из Drupal 7, что t, в D8 $this->t() можно использовать
      // только с английскими словами. Иначе не используйте t() а пишите
      // просто строку.
      '#title' => $this->t('Your phone number'),
      '#default_value' => $config->get('phone_number')
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your name')
    );

    // Предоставляет обёртку для одного или более Action элементов.
    $form['actions']['#type'] = 'actions';
    // Добавляем нашу кнопку для отправки.
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send name and phone'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * Валидация отправленых данных в форме.
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Если длина имени меньше 5, выводим ошибку.
    if (strlen($form_state->getValue('name')) < 5) {
      $form_state->setErrorByName('name', $this->t('Name is too short.'));
    }
  }

  /**
   * Отправка формы.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Мы ничего не хотим делать с данными, просто выведем их в системном
    // сообщении.
    drupal_set_message($this->t('Thank you @name, your phone number is @number', array(
      '@name' => $form_state->getValue('name'),
      '@number' => $form_state->getValue('phone_number')
    )));
  }

}
