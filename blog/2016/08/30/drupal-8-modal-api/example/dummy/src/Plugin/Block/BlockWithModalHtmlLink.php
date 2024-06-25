<?php

/**
 * @file
 * Contains \Drupal\dummy\Plugin\Block\BlockWithModalHtmlLink.
 */

namespace Drupal\dummy\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "block_with_modal_html_link",
 *   admin_label = @Translation("Modal API example: HTML link"),
 * )
 */
class BlockWithModalHtmlLink extends BlockBase {

  public function defaultConfiguration() {
    return [
      'nid' => '1',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['nid'] = [
      '#type' => 'textfield',
      '#title' => 'NID to display in modal',
      '#default_value' => $config['nid'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['nid'] = $form_state->getValue('nid');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    return [
      '#type' => 'link',
      '#title' => new FormattableMarkup('Open node @nid in modal!', ['@nid' => $config['nid']]),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $config['nid']]),
      '#options' => [
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ]
      ],
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];
  }

}
