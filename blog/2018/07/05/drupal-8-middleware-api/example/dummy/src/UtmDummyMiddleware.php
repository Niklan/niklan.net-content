<?php

namespace Drupal\dummy;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * UtmDummyMiddleware middleware.
 */
class UtmDummyMiddleware implements HttpKernelInterface {

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * An array with all available utm_* query params.
   */
  protected $utmQueryList;

  /**
   * Constructs the UtmDummyMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
    $this->utmQueryList = [
      'utm_source',
      'utm_medium',
      'utm_campaign',
      'utm_term',
      'utm_content',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($this->hasUtm($request)) {
      $this->cleanUtm($request);
      // Get uri without any query args.
      // We can't use here Url::createFromRequest(), because core boostrap at
      // very early stage and this will cause error.
      $uri_without_query = strtok($request->getUri(), '?');
      $altered_query_params = empty($request->query->all()) ? '' : '?' . http_build_query($request->query->all());
      return new RedirectResponse($uri_without_query . $altered_query_params);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Checks for utm query parameters in current request.
   */
  public function hasUtm(Request $request) {
    foreach ($this->utmQueryList as $utm_query) {
      if ($request->query->has($utm_query)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Remove all UTM parameters from query.
   *
   * This method only removes UTM query args from parameter bag, not from URL.
   */
  public function cleanUtm(Request $request) {
    foreach ($this->utmQueryList as $utm_query) {
      $request->query->remove($utm_query);
    }
  }

}
