<?php

namespace Drupal\dummy\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class HookThemeExamples
 */
class HookThemeExamples extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function page() {
    $results = [];
    // Пример №1.
    $results[] = [
      '#theme' => 'dummy_example_first',
    ];
    // Пример №2.
    $results[] = [
      '#theme' => 'dummy_example_second',
      '#list_type' => 'ol',
      '#attributes' => [
        'class' => 'tes',
      ],
      '#items' => [
        [
          'title' => 'Lorem ipsum dolor sit amet',
          'text' => 'Phasellus in ipsum eros. Nunc tellus purus, cursus at tincidunt id, sagittis ut ligula. Ut metus tellus, laoreet ac feugiat vitae, sollicitudin ac quam.',
        ],
        [
          'title' => 'Suspendisse sodales in nulla in porttitor',
          'text' => 'Praesent sapien ante, rhoncus ac lectus quis, tristique consequat purus. Nulla rhoncus tempus venenatis. Donec ultricies libero et ullamcorper blandit. Etiam dictum eros eu turpis lacinia, at ornare sem tincidunt.'
        ]
      ],
    ];
    // Примеры для №3
    $results[] = [
      '#theme' => 'dummy_example_quote',
      '#quote' => 'Praesent sapien ante, rhoncus ac lectus quis, tristique consequat purus.',
    ];
    $results[] = [
      '#theme' => 'dummy_example_quote',
      '#quote' => 'Praesent sapien ante, rhoncus ac lectus quis, tristique consequat purus.',
      '#author' => 'John Doe',
      '#year' => 2017,
    ];
    $results[] = [
      '#theme' => 'dummy_example_quote',
      '#quote' => 'Praesent sapien ante, rhoncus ac lectus quis, tristique consequat purus.',
      '#author' => 'John Doe',
      '#year' => 2017,
      '#source_title' => 'Google',
      '#source_url' => 'https://google.com/',
    ];
    return $results;
  }

}
