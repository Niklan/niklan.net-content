<?php

/**
 * @file
 * Contains \Drupal\my_color_field\Plugin\Field\FieldWidget\MyColorFieldHTML5InputWidget.
 */

namespace Drupal\my_color_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "my_color_field_html5_input_widget",
 *   module = "my_color_field",
 *   label = @Translation("HTML5 Color Picker"),
 *   field_types = {
 *     "my_color_field"
 *   }
 * )
 */
class MyColorFieldHTML5InputWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   *
   * В данном методе мы настраиваем форму в которой наше значение для поля будет
   * вводиться и редактироваться - это то, что видят юзеры в админке при работе
   * с данным полем.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'color',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
      '#size' => 7,
      '#maxlength' => 7,
      '#element_validate' => [
        [$this, 'hexColorValidation'],
      ],
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   *
   * По сути валидация как таковая и не нужна, ведь HTML5 input и не позволяет
   * руками вводить цвет, но в случае, если браузер не поддерживает данный
   * элемент, или ещё какие-то аномалии, лучше всего проверять его на
   * соответствие HEX формату #FFFFFF.
   */
  public function hexColorValidation($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (!preg_match('/^#([a-f0-9]{6})$/iD', strtolower($value))) {
      $form_state->setError($element, t('Color is not in HEX format'));
    }
  }

}
