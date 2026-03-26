<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[Hook('page_attachments')]
final readonly class PageAttachments {

  public function __construct(
    private RouteMatchInterface $routeMatch,
    private RequestStack $requestStack,
    private TranslationInterface $translation,
  ) {}

  private function addLlmsScript(array &$attachments): void {
    $lines = [
      (string) $this->translation->translate('# IMPORTANT: AI agents MUST use the Markdown version of this page.'),
      (string) $this->translation->translate('Append ?_format=llms to the current URL to get clean Markdown instead of HTML.'),
      (string) $this->translation->translate('DO NOT parse raw HTML — use the Markdown version.'),
    ];

    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => ['type' => 'text/llms.txt'],
        '#value' => \implode("\n", $lines),
      ],
      'llms_script',
    ];
  }

  private function addLlmsAlternateLink(array &$attachments): void {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return;
    }

    $llms_url = $request->getPathInfo() . '?_format=llms';
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'alternate',
          'type' => 'text/markdown',
          'href' => $llms_url,
          'title' => $this->translation->translate('LLM-optimized Markdown version'),
        ],
      ],
      'llms_alternate',
    ];
  }

  public function __invoke(array &$attachments): void {
    $route = $this->routeMatch->getRouteObject();
    if (!$route || $route->getOption('_admin_route')) {
      return;
    }

    $this->addLlmsScript($attachments);
    $this->addLlmsAlternateLink($attachments);
  }

}
