<?php

namespace Drupal\dummy\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound and outbound pager query.
 */
class DummyPageProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (preg_match('/.*\\/page\\/([0-9]+)$/', $request->getRequestUri(), $matches)) {
      $path = preg_replace('/(.*)\\/page\\/[0-9]+/', '${1}', $path);
      if ($path == '') {
        $path = '/';
      }
      $request->query->set('page', $matches[1]);
      $request->overrideGlobals();
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (!empty($options['query']['page']) || $options['query']['page'] == 0) {
      if ($options['query']['page'] > 0) {
        $path .= '/page/' . $options['query']['page'];
      }
      unset($options['query']['page']);
    }
    return $path;
  }

}
