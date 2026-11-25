<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Provides [url:absolute-with-page] token.
 */
final readonly class UrlWithPageToken {

  public function __construct(
    private PagerManagerInterface $pagerManager,
    private TranslationInterface $translation,
  ) {}

  /**
   * @return array<string, mixed>
   */
  #[Hook('token_info')]
  public function info(): array {
    return [
      'tokens' => [
        'url' => [
          'absolute-with-page' => [
            'name' => $this->translation->translate('Absolute URL with page query if present'),
          ],
        ],
      ],
    ];
  }

  /**
   * @param array<string, string> $tokens
   * @param array<string, mixed> $data
   * @param array<string, mixed> $options
   *
   * @return array<string, string>
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    if ($type !== 'url' || !isset($tokens['absolute-with-page'])) {
      return [];
    }

    $url = $data['url'];
    \assert($url instanceof Url);
    $url = clone $url;

    $bubbleable_metadata->addCacheContexts(['url.query_args.pagers:0']);
    $current_page = $this->pagerManager->findPage();
    if ($current_page > 0) {
      $url->setOption('query', ['page' => $current_page] + (array) $url->getOption('query'));
    }

    return [
      $tokens['absolute-with-page'] => $url->setAbsolute()->toString(),
    ];
  }

}
