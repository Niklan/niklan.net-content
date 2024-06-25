<?php

namespace Drupal\dummy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'CacheContextRequestHeaderTest' block.
 *
 * @Block(
 *  id = "dummy_cache_context_request_header_test",
 *  admin_label = @Translation("Cache context OS test"),
 * ) Как
 */
class CacheContextRequestHeaderTest extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getOs() {
    $request_headers = \Drupal::service('request_stack')->getCurrentRequest()->headers;
    $user_agent = $request_headers->get('user-agent');
    if (preg_match('/linux/i', $user_agent)) {
      return 'Linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
      return 'Mac';
    }
    elseif (preg_match('/windows|win32/i', $user_agent)) {
      return 'Windows';
    }
    else {
      return 'other';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $platform = $this->getOs();
    if ($platform == 'other') {
      return [
        '#markup' => t('Sorry, we have not already created software for you OS.'),
      ];
    }
    else {
      $string = t('Download for @platform', [
        '@platform' => $platform,
      ]);
      $url = Url::fromUri('http://www.example.com/');
      $external_link = \Drupal::l($string, $url);

      return [
        '#markup' => $external_link,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['dummy_request_header:os'];
  }

}
