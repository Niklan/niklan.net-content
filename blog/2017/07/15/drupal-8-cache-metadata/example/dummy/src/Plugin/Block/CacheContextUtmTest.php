<?php

namespace Drupal\dummy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;

/**
 * Provides a 'CacheContextUtmTest' block.
 *
 * @Block(
 *  id = "dummy_cache_context_utm_test",
 *  admin_label = @Translation("Cache context UTM test"),
 * )
 */
class CacheContextUtmTest extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Static links for testing block caching.
    $build[] = [
      '#markup' => Link::createFromRoute('Yandex, SEO', '<front>', [], [
        'query' => [
          'utm_source' => 'yandex.com',
          'utm_campaign' => 'SEO',
        ],
        'target' => '_blank',
      ])->toString(),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
    $build[] = [
      '#markup' => Link::createFromRoute('Yandex, website', '<front>', [], [
        'query' => [
          'utm_source' => 'yandex.com',
          'utm_campaign' => 'website',
        ],
        'target' => '_blank',
      ])->toString(),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
    $build[] = [
      '#markup' => Link::createFromRoute('Google, SEO', '<front>', [], [
        'query' => [
          'utm_source' => 'google.com',
          'utm_campaign' => 'SEO',
        ],
        'target' => '_blank',
      ])->toString(),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];

    // Get current values.
    $result = 'Мы делаем крутые сайты и занимаемся продвижением, звоните нам!';
    $this->textModifier($result);
    $build[] = [
      '#markup' => $result,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function textModifier(&$result) {
    $utm_source = $this->getUtmSource();
    $utm_campaign = $this->getUtmCampaign();
    if ($utm_source == 'yandex.com') {
      switch ($utm_campaign) {
        case 'SEO':
          $result = 'Поможем продвинуть сайт в ТОП Яндекса!';
          break;

        case 'website':
          $result = 'Создадим крутой сайт, который без проблем займет ТОП Яндекса';
          break;
      }
    }

    if ($utm_source == 'google.com') {
      switch ($utm_campaign) {
        case 'SEO':
          $result = 'Поможем продвинуть сайт в ТОП Google!';
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getUtmValue($parameter) {
    $request_query = \Drupal::service('request_stack')->getCurrentRequest()->query;
    if ($request_query->has($parameter)) {
      return $request_query->get($parameter);
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getUtmSource() {
    return $this->getUtmValue('utm_source');
  }

  /**
   * {@inheritdoc}
   */
  public function getUtmCampaign() {
    return $this->getUtmValue('utm_campaign');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [
      'url.query_args:utm_source',
      'url.query_args:utm_campaign',
    ];
  }

}
