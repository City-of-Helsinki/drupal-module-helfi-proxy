<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Unit;

use Drupal\helfi_proxy\HttpMiddleware\AssetHttpMiddleware;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests asset middleware.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\HttpMiddleware\AssetHttpMiddleware
 * @group helfi_proxy
 */
class AssetHttpMiddlewareTest extends UnitTestCase {

  /**
   * Tests that XML response stays intact.
   *
   * @covers ::handle
   * @covers ::isXmlResponse
   * @dataProvider xmlResponseProvider
   */
  public function testXmlResponse(?string $contentType) : void {
    $headers = $this->prophesize(ParameterBag::class);
    $headers->has('content-type')
      ->shouldBeCalled()
      ->willReturn(TRUE);

    $headers->set('content-type', $contentType);
    $headers->get('content-type')
      ->shouldBeCalled()
      ->willReturn($contentType);

    $responseMock = new Response();
    $responseMock->setContent('123');
    $responseMock->headers = $headers->reveal();

    $mockHttpKernel = $this->createMock(HttpKernelInterface::class);
    $mockHttpKernel->method('handle')
      ->willReturn($responseMock);

    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::ASSET_PATH)
      ->shouldNotBeCalled();

    $requestMock = $this->createMock(Request::class);
    $sut = new AssetHttpMiddleware($mockHttpKernel, $proxyManagerMock->reveal());
    $this->assertEquals(
      $sut->handle($requestMock)->headers->get('content-type'),
      $contentType
    );
  }

  /**
   * The data provider for xml response test.
   *
   * @return \string[][]
   *   The data.
   */
  public function xmlResponseProvider() : array {
    return [
      ['application/xml'],
      ['application/xml; charset=utf-8'],
    ];
  }

  /**
   * Tests that response is intact when no asset path is configured.
   */
  public function testNoAssetPathConfigured() : void {
    $headers = $this->prophesize(ParameterBag::class);
    $headers->has('content-type')
      ->shouldBeCalled()
      ->willReturn(FALSE);
    $responseMock = new Response();
    $responseMock->setContent('123');
    $responseMock->headers = $headers->reveal();

    $mockHttpKernel = $this->createMock(HttpKernelInterface::class);
    $mockHttpKernel->method('handle')
      ->willReturn($responseMock);

    $proxyManagerMock = $this->prophesize(ProxyManagerInterface::class);
    $proxyManagerMock->isConfigured(ProxyManagerInterface::ASSET_PATH)
      ->shouldBeCalled()
      ->willReturn(FALSE);

    $sut = new AssetHttpMiddleware($mockHttpKernel, $proxyManagerMock->reveal());
    $requestMock = $this->createMock(Request::class);
    $sut->handle($requestMock);
  }

}
