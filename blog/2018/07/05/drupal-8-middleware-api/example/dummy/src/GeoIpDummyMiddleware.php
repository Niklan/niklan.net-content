<?php

namespace Drupal\dummy;

use Geocoder\Provider\GeoPlugin\GeoPlugin;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle6\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * GeoIpDummyMiddleware middleware.
 */
class GeoIpDummyMiddleware implements HttpKernelInterface {

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs the GeoIpDummyMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    // Trying to find actual country and city.
    $this->lookup($request);
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Trying to find country and city by using Geolocation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function lookup(Request $request) {
    $httpClient = new Client();
    $provider = new GeoPlugin($httpClient);
    $geocoder = new StatefulGeocoder($provider, $request->getLocale());
    $geo_query = GeocodeQuery::create($request->getClientIp());
    $results = $geocoder->geocodeQuery($geo_query);
    if ($results->has(0)) {
      $first_locality = $results->get(0);
      $request->headers->set('X-Country', $first_locality->getCountry());
      $request->headers->set('X-City', $first_locality->getLocality());
    }
  }

}
