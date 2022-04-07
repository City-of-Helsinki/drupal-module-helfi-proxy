<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Unit;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\helfi_proxy\EventSubscriber\RedirectResponseSubscriber;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests asset middleware.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\EventSubscriber\RedirectResponseSubscriber
 * @group helfi_proxy
 */
class RedirectResponseSubscriberTest extends UnitTestCase {

  /**
   * Test response without proxy domains.
   *
   * @covers ::onResponse
   * @covers ::__construct
   */
  public function testNoValidProxyDomains() : void {
    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldNotBeCalled();
    $event = $this->prophesize(ResponseEvent::class);
    $event->getResponse()
      ->shouldNotBeCalled();
    // Make sure response stays intact when no valid proxy domains are set.
    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal(), []);
    $sut->onResponse($event->reveal());
  }

  /**
   * Tests that response is intact when no proxy domain is set.
   *
   * @covers ::onResponse
   * @covers ::__construct
   */
  public function testNoProxyDomain() : void {
    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn(FALSE);
    $event = $this->prophesize(ResponseEvent::class);
    $event->getResponse()
      ->shouldNotBeCalled();
    // Make sure response stays intact when no default proxy domain is set.
    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal(), ['www.hel.fi']);
    $sut->onResponse($event->reveal());
  }

  /**
   * Tests that response stays intact for non-get requests.
   *
   * @covers ::onResponse
   * @covers ::__construct
   */
  public function testPostRequestResponse() : void {
    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn(TRUE);
    $request = $this->prophesize(Request::class);
    $request->isMethod('GET')
      ->willReturn(FALSE);

    $response = $this->prophesize(RedirectResponse::class);
    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request->reveal(),
      HttpKernelInterface::MASTER_REQUEST,
      $response->reveal()
    );
    // Make sure response stays intact when the request method is not GET.
    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal(), ['www.hel.fi']);
    $sut->onResponse($event);
  }

  /**
   * Tests onResponse() with RedirectResponse.
   *
   * Make sure we get redirected to proxy and request data is carried over
   * from RedirectResponse.
   *
   * @covers ::onResponse
   * @covers ::needsRedirect
   * @covers ::buildRedirectUrl
   * @covers ::__construct
   */
  public function testRedirectResponse() : void {
    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn(TRUE);
    $proxyManagerMock->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn('www.hel.fi');

    $response = $this->prophesize(RedirectResponse::class);
    $response->getTargetUrl()
      ->willReturn('http://localhost:8888/test?x=1');

    $request = $this->prophesize(Request::class);
    $request->isMethod('GET')
      ->willReturn(TRUE);

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request->reveal(),
      HttpKernelInterface::MASTER_REQUEST,
      $response->reveal()
    );

    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal(), ['www.hel.fi']);
    $sut->onResponse($event);
    $this->assertInstanceOf(TrustedRedirectResponse::class, $event->getResponse());
    $this->assertEquals('https://www.hel.fi/test?x=1', $event->getResponse()->getTargetUrl());
  }

  /**
   * Tests onResponse() with normal response.
   *
   * @covers ::onResponse
   * @covers ::needsRedirect
   * @covers ::buildRedirectUrl
   * @covers ::__construct
   */
  public function testResponseRedirect() : void {
    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn(TRUE);
    $proxyManagerMock->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn('www.hel.fi');

    $response = new Response();
    $request = $this->prophesize(Request::class);
    $request->getSchemeAndHttpHost()
      ->shouldBeCalled()
      ->willReturn('http://localhost:8888');
    $request->isMethod('GET')
      ->shouldBeCalled()
      ->willReturn(TRUE);
    $request->getRequestUri()
      ->shouldBeCalled()
      ->willReturn('/test?x=1');

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request->reveal(),
      HttpKernelInterface::MASTER_REQUEST,
      $response
    );

    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal(), ['www.hel.fi']);
    $sut->onResponse($event);
    $this->assertInstanceOf(TrustedRedirectResponse::class, $event->getResponse());
    $this->assertEquals('https://www.hel.fi/test?x=1', $event->getResponse()->getTargetUrl());
  }

}
