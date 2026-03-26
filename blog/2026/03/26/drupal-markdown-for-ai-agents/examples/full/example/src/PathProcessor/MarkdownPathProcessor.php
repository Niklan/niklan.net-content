<?php

declare(strict_types=1);

namespace Drupal\example\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('path_processor_inbound', ['priority' => 200])]
final readonly class MarkdownPathProcessor implements InboundPathProcessorInterface {

  public function processInbound($path, Request $request): string {
    if (!\str_ends_with($path, '.md')) {
      return $path;
    }

    $request->setRequestFormat('llms');

    return \substr($path, 0, -3);
  }

}
