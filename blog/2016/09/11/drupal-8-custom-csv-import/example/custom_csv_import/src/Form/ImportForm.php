<?php

namespace Drupal\custom_csv_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\custom_csv_import\CSVBatchImport;

/**
 * Class ImportForm.
 *
 * @package Drupal\custom_csv_import\Form
 */
class ImportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_csv_import.import'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_csv_import.import');

    $form['file'] = [
      '#title' => $this->t('CSV file'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#default_value' => $config->get('fid') ? [$config->get('fid')] : NULL,
      '#upload_validators' => array(
        'file_validate_extensions' => array('csv'),
      ),
      '#required' => TRUE,
    ];

    # Если загружен файл, отображаем дополнительные элементы формы.
    if (!empty($config->get('fid'))) {
      $file = File::load($config->get('fid'));
      $created = \Drupal::service('date.formatter')
        ->format($file->created->value, 'medium');

      $form['file_information'] = [
        '#markup' => $this->t('This file was uploaded at @created.', ['@created' => $created]),
      ];


      # Добавляем кнопку для начала импорта со своим собственным submit handler.
      $form['actions']['start_import'] = [
        '#type' => 'submit',
        '#value' => $this->t('Start import'),
        '#submit' => ['::startImport'],
        '#weight' => 100,
      ];
    }

    $form['additional_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Additional settings'),
    ];

    $form['additional_settings']['skip_first_line'] = [
      '#type' => 'checkbox',
      '#title' => t('Skip first line'),
      '#default_value' => $config->get('skip_first_line'),
      '#description' => t('If file contain titles, this checkbox help to skip first line.'),
    ];

    $form['additional_settings']['delimiter'] = [
      '#type' => 'textfield',
      '#title' => t('Delimiter'),
      '#default_value' => $config->get('delimiter'),
      '#required' => TRUE,
    ];

    $form['additional_settings']['enclosure'] = [
      '#type' => 'textfield',
      '#title' => t('Enclosure'),
      '#default_value' => $config->get('enclosure'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('custom_csv_import.import');
    # Сохраняем FID файлов, чтобы в дальнейшем было проще обращаться.
    $fid_old = $config->get('fid');
    $fid_form = $form_state->getValue('file')[0];

    # Первым делом проверяем, загружались ли ранее файлы, и если загружались
    # отличается ли новый файл от предыдущего.
    if (empty($fid_old) || $fid_old != $fid_form) {
      # Если ранее загружался, то получается что в форму загружен новый файл,
      # следовательно, нам необходимо указать что старый файл мы больше не
      # используем.
      if (!empty($fid_old)) {
        $previous_file = File::load($fid_old);
        \Drupal::service('file.usage')
          ->delete($previous_file, 'custom_csv_import', 'config_form', $previous_file->id());
      }
      # Теперь, не важно, был ли старый файл или нет, нам нужно сохранить
      # новый файл.
      $new_file = File::load($fid_form);
      $new_file->save();
      # Также мы должны указать что наш модуль использует данный файл.
      # В противном случае файл удалиться через определенное время указанное
      # в настройках файловой систмы Drupal. По-умолчанию через 6 часов.
      \Drupal::service('file.usage')
        ->add($new_file, 'custom_csv_import', 'config_form', $new_file->id());
      # Сохраняем всю необходимую для нас информацию в конфиги.
      $config->set('fid', $fid_form)
        ->set('creation', time());
    }

    $config->set('skip_first_line', $form_state->getValue('skip_first_line'))
      ->set('delimiter', $form_state->getValue('delimiter'))
      ->set('enclosure', $form_state->getValue('enclosure'))
      ->save();
  }

  /**
   * {@inheritdoc}
   *
   * Метод для начала импорта из файла.
   */
  public function startImport(array &$form, FormStateInterface $form_state) {
    $config = $this->config('custom_csv_import.import');
    $fid = $config->get('fid');
    $skip_first_line = $config->get('skip_first_line');
    $delimiter = $config->get('delimiter');
    $enclosure = $config->get('enclosure');
    $import = new CSVBatchImport($fid, $skip_first_line, $delimiter, $enclosure);
    $import->setBatch();
  }
}
