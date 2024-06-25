<?php

/**
 * @file
 * Contains \Drupal\my_color_field\Plugin\Field\FieldFormatter\MyColorFieldDivFormatter.
 */

namespace Drupal\my_color_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/** *
 * @FieldFormatter(
 *   id = "my_color_field_div_formatter",
 *   label = @Translation("Div element with background color"),
 *   field_types = {
 *     "my_color_field"
 *   }
 * )
 */
class MyColorFieldDivFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   *
   * Настройки по умолчанию для нашего формата вывода.
   */
  public static function defaultSettings() {
    return [
      'width' => '80',
      'height' => '80',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Форма с настройками для нашего формата вывода.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = array(
      '#type' => 'number',
      '#title' => t('Width'),
      '#field_suffix' => 'px.',
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
    );

    $elements['height'] = array(
      '#type' => 'number',
      '#title' => t('Height'),
      '#field_suffix' => 'px.',
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Данный метод позволяет вывести кратку информацию о текущих настройках поля
   * на странице управления отображением.
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('Width @width px.', array('@width' => $settings['width']));
    $summary[] = t('Height @height px.', array('@height' => $settings['height']));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => new FormattableMarkup(
          '<div style="width: @width; height: @height; background-color: @color;"></div>',
          [
            '@width' => $settings['width'] . 'px',
            '@height' => $settings['height'] . 'px',
            '@color' => $item->value,
          ]
        ),
      ];
    }

    return $element;
  }

}
