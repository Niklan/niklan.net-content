<?php

namespace Drupal\dummy\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event firing on page and html preprocesses.
 */
class DummyPreprocessEvent extends Event {

  /**
   * Called during hook_preprocess_html().
   */
  const PREPROCESS_HTML = 'dummy.frontpage.preprocess_html';

  /**
   * Called during hook_preprocess_page().
   */
  const PREPROCESS_PAGE = 'dummy.frontpage.preprocess_page';

  /**
   * Variables from preprocess.
   */
  protected $variables;

  /**
   * DummyFrontpageEvent constructor.
   */
  public function __construct($variables) {
    $this->variables = $variables;
  }

  /**
   * Returns variables array from preprocess.
   */
  public function getVariables() {
    return $this->variables;
  }

}

