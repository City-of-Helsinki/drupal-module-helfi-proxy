<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\helfi_proxy\EventSubscriber\TunnistamoRedirectUrlSubscriber;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\helfi_tunnistamo\Event\RedirectUrlEvent;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Tunnistamo redirect url subscriber.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\EventSubscriber\TunnistamoRedirectUrlSubscriber
 */
class TunnistamoRedirectUrlSubscriberTest extends UnitTestCase {

  /**
   * Gets the redirect event stub.
   *
   * @param string $url
   *   The url.
   *
   * @return \Drupal\helfi_tunnistamo\Event\RedirectUrlEvent|mixed|\PHPUnit\Framework\MockObject\MockObject
   *   The stub class.
   */
  private function getRedirectUrlEventStub(string $url) {
    if (!class_exists('\Drupal\helfi_tunnistamo\Event\RedirectUrlEvent')) {
      $stub = $this->createPartialMock('\Drupal\helfi_tunnistamo\Event\RedirectUrlEvent', [
        'setRedirectUrl',
        'getRedirectUrl',
      ]);
      $stub->method('getRedirectUrl')->willReturn(Url::fromUserInput($url));
    }
    else {
      $stub = new RedirectUrlEvent(Url::fromUserInput($url), $this->createMock(Request::class));
    }
    return $stub;
  }

  /**
   * Tests onRedirectUrlEvent().
   *
   * @covers ::onRedirectUrlEvent
   */
  public function testOnRedirectUrlEvent() : void {
    $url = '/fi/test';
    $container = new ContainerBuilder();
    $unrouted_url_assembler = $this->createMock(UnroutedUrlAssemblerInterface::class);
    $unrouted_url_assembler->method('assemble')
      ->willReturn($url);
    $container->set('unrouted_url_assembler', $unrouted_url_assembler);
    $container->set('path.validator', $this->createMock(PathValidatorInterface::class));
    $container->set('router.no_access_checks', $this->createMock(PathValidatorInterface::class));
    \Drupal::setContainer($container);

    $proxyManager = $this->prophesize(ProxyManagerInterface::class);
    $proxyManager->getTunnistamoReturnUrl()->willReturn($url);

    $subscriber = new TunnistamoRedirectUrlSubscriber($proxyManager->reveal());
    $stub = $this->getRedirectUrlEventStub($url);
    $subscriber->onRedirectUrlEvent($stub);
    $this->assertEquals('/fi/test', $stub->getRedirectUrl()->toString());
  }

}
