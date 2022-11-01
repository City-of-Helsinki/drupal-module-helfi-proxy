<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Unit;

use Drupal\Core\Render\HtmlResponse;
use Drupal\helfi_proxy\EventSubscriber\RedirectResponseSubscriber;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests asset middleware.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\EventSubscriber\RedirectResponseSubscriber
 * @group helfi_proxy
 */
class RedirectResponseSubscriberTest extends UnitTestCase {

  use ProphecyTrait;

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
    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal());
    $sut->onResponse($event->reveal());
  }

  /**
   * Tests onResponse() when proxy domain matches the current domain.
   *
   * @covers ::onResponse
   * @covers ::__construct
   */
  public function testProxyDomainMatches() : void {
    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn(TRUE);
    $proxyManagerMock->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn('localhost');

    $request = $this->prophesize(Request::class);
    $request->getHttpHost()
      ->shouldBeCalled()
      ->willReturn('localhost');

    $response = $this->prophesize(HtmlResponse::class);
    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $request->reveal(),
      HttpKernelInterface::MASTER_REQUEST,
      $response->reveal()
    );

    // Make sure nothing is done when domain already matches.
    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal());
    $sut->onResponse($event);
    $this->assertInstanceOf(HtmlResponse::class, $event->getResponse());
  }

  /**
   * Tests onResponse() with RedirectResponse.
   *
   * Make sure redirect response stays intact.
   *
   * @covers ::onResponse
   * @covers ::__construct
   */
  public function testRedirectResponse() : void {
    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldBeCalled()
      ->willReturn(TRUE);
    $proxyManagerMock->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
      ->shouldNotBeCalled();

    $url = 'http://localhost:8888/test?x=1';
    $response = $this->prophesize(RedirectResponse::class);
    $response->getTargetUrl()
      ->willReturn($url);

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $this->createMock(Request::class),
      HttpKernelInterface::MASTER_REQUEST,
      $response->reveal()
    );

    $sut = new RedirectResponseSubscriber($proxyManagerMock->reveal());
    $sut->onResponse($event);
    $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    $this->assertEquals($url, $event->getResponse()->getTargetUrl());
  }

}
