<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests Redirect response subscriber.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\EventSubscriber\RedirectResponseSubscriber
 * @group helfi_proxy
 */
class RedirectResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'link',
    'language',
    'content_translation',
    'path_alias',
    'user',
    'redirect',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('redirect');
    $this->installConfig(['language', 'content_translation']);

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    // We rely on redirect route normalizer.
    $this->config('redirect.settings')
      ->set('route_normalizer_enabled', TRUE)
      ->set('default_status_code', Response::HTTP_FOUND)
      ->save();
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fi' => 'fi', 'sv' => 'sv'])
      ->save();

    \Drupal::service('kernel')->rebuildContainer();
  }

  /**
   * Sets the default proxy domain.
   *
   * @param string $domain
   *   The proxy domain.
   */
  private function setProxyDomain(string $domain) : void {
    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN, $domain)
      ->save();
  }

  /**
   * Run given response through the http kernel.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The handled response.
   */
  private function getHttpKernelResponse(Request $request) : Response {
    $http_kernel = $this->container->get('http_kernel');
    return $http_kernel->handle($request);
  }

  /**
   * Creates a new request object.
   *
   * @param string $uri
   *   The uri.
   * @param array $parameters
   *   The parameters.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  private function createRequest(string $uri, array $parameters = []) : Request {
    return Request::create($uri, parameters: $parameters, server: [
      'HTTP_HOST' => 'localhost:8888',
      // Redirect module cannot perform redirects unless script name is set.
      'SCRIPT_NAME' => '/index.php',
    ]);
  }

  /**
   * Make sure we get an HTTP 200 response when proxy is not enabled.
   *
   * @covers ::onResponse
   * @covers ::getSubscribedEvents
   */
  public function testNoRedirect() : void {
    $request = $this->createRequest('/en/user/login');
    $response = $this->getHttpKernelResponse($request);

    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

  /**
   * Tests redirects.
   *
   * @covers ::getSubscribedEvents
   * @covers ::onResponse
   */
  public function testRedirectResponse() : void {
    $this->setProxyDomain('www.hel.fi');
    $request = $this->createRequest('/user');
    $response = $this->getHttpKernelResponse($request);

    // Make sure other redirects are dealt first, like /user -> /en/user/login
    // before redirecting to proxy domain.
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    $this->assertEquals('http://localhost:8888/en/user/login', $response->headers->get('location'));

    // Make sure we get redirected to proxy domain after other redirects.
    $request = $this->createRequest('/en/user/login');
    $response = $this->getHttpKernelResponse($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    $this->assertEquals('https://www.hel.fi/en/user/login', $response->headers->get('location'));
  }

}
