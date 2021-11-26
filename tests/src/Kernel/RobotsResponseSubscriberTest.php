<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\Core\Render\HtmlResponse;
use Drupal\helfi_proxy\EventSubscriber\RobotsResponseSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests robots response headers.
 *
 * @group helfi_proxy
 */
class RobotsResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['helfi_proxy', 'path_alias'];

  /**
   * The system under testing.
   *
   * @var \Drupal\helfi_proxy\EventSubscriber\RobotsResponseSubscriber|null
   */
  protected ?RobotsResponseSubscriber $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();
    $this->sut = $this->container->get('helfi_proxy.robots_response_subscriber');
  }

  /**
   * Gets the mock response event.
   *
   * @return \Symfony\Component\HttpKernel\Event\ResponseEvent
   *   The response event.
   */
  private function getResponseEvent() : ResponseEvent {
    return new ResponseEvent(
      $this->container->get('http_kernel'),
      Request::createFromGlobals(),
      HttpKernelInterface::MASTER_REQUEST,
      new HtmlResponse()
    );
  }

  /**
   * Tests that robots header is not added when no env variable is present.
   */
  public function testNoEnvVariable() : void {
    $response = $this->getResponseEvent();
    $this->sut->onResponse($response);
    $this->assertNotContains('X-Robots-Tag', $response->getResponse()->headers);
  }

  /**
   * Tests that robots header is added when env variable is present.
   */
  public function testEnvVariable() : void {
    putenv(RobotsResponseSubscriber::X_ROBOTS_TAG_HEADER_NAME . '=1');
    $response = $this->getResponseEvent();
    $this->sut->onResponse($response);
    $this->assertEquals('noindex, nofollow', $response->getResponse()->headers->get('X-Robots-Tag'));
  }

}
