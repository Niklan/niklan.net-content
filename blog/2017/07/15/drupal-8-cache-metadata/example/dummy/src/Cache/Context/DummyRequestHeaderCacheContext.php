<?php

namespace Drupal\dummy\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;

/**
 * Cache context ID: 'dummy_request_header'.
 */
class DummyRequestHeaderCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Dummy request header');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL) {
    $request_headers = $this->requestStack->getCurrentRequest()->headers;
    if ($parameter) {
      if ($request_headers->has($parameter)) {
        return (string) $request_headers->get($parameter);
      }
      elseif ($parameter == 'os') {
        $user_agent = $request_headers->get('user-agent');
        if (preg_match('/linux/i', $user_agent)) {
          return 'linux';
        }
        elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
          return 'mac';
        }
        elseif (preg_match('/windows|win32/i', $user_agent)) {
          return 'windows';
        }
        else {
          return 'other';
        }
      }
      else {
        return '';
      }
    }
    else {
      // If none parameter is passed, we get all available during request and
      // merges them into single string, after that we hash it with md5 and
      // return result.
      $headers_string = implode(';', array_map(function ($entry) {
        return $entry[0];
      }, $request_headers->all()));
      return md5($headers_string);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = NULL) {
    return new CacheableMetadata();
  }
}
