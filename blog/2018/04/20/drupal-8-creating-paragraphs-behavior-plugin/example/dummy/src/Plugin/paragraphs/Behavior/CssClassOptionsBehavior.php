<?php

namespace Drupal\dummy\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Annotation\ParagraphsBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * @ParagraphsBehavior(
 *   id = "dummy_css_class_options",
 *   label = @Translation("CSS class options"),
 *   description = @Translation("Options that adds some classes to paragraph and change specific styles."),
 *   weight = 0,
 * )
 */
class CssClassOptionsBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $css_class_options = $paragraph->getBehaviorSetting($this->getPluginId(), 'css_class_options', []);
    foreach ($css_class_options as $class_option) {
      $build['#attributes']['class'][] = 'option--' . str_replace('_', '-', $class_option);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['css_class_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('CSS class options'),
      '#options' => $this->getCssClassOptions($paragraph),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'css_class_options', []),
    ];

    return $form;
  }

  /**
   * Return options for heading elements.
   */
  private function getCssClassOptions(ParagraphInterface $paragraph) {
    $options = [];

    // Options global.
    $options['margin_bottom_32'] = $this->t('Add 32px margin after paragraph');
    $options['style_1'] = $this->t('Style #1: Gray background with light border and inner padding');

    // Options for title.
    if ($paragraph->hasField('field_title')) {
      $options['title_centered'] = $this->t('Center title');
      $options['title_bold'] = $this->t('Bold title');
      $options['title_red'] = $this->t('Red title');
    }

    // Options for image field.
    if ($paragraph->hasField('field_image')) {
      $options['image_black_and_white'] = $this->t('Make image black and white');
    }

    return $options;
  }

}