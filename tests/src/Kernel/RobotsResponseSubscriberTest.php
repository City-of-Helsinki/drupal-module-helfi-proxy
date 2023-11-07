<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\Core\Render\HtmlResponse;
use Drupal\helfi_proxy\EventSubscriber\RobotsResponseSubscriber;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests robots response headers.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\EventSubscriber\RobotsResponseSubscriber
 * @group helfi_proxy
 */
class RobotsResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'helfi_proxy', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    // system.site is required by path matcher service.
    $this->installConfig(['system']);
  }

  /**
   * Gets the response subscriber service.
   *
   * @return \Drupal\helfi_proxy\EventSubscriber\RobotsResponseSubscriber
   *   The response subscriber.
   */
  private function getSut() : RobotsResponseSubscriber {
    return $this->container->get('helfi_proxy.robots_response_subscriber');
  }

  /**
   * Gets the mock response event.
   *
   * @return \Symfony\Component\HttpKernel\Event\ResponseEvent
   *   The response event.
   */
  private function getResponseEvent(Request $request = NULL) : ResponseEvent {
    if (!$request) {
      $request = Request::createFromGlobals();
    }
    return new ResponseEvent(
      $this->container->get('http_kernel'),
      $request,
      // @todo Rename this once Core requires 7.0 symfony.
      HttpKernelInterface::MASTER_REQUEST,
      new HtmlResponse()
    );
  }

  /**
   * Asserts that response has x-robots-tag header.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event.
   */
  private function assertResponseEventHasHeader(ResponseEvent $event) {
    $this->assertEquals('noindex, nofollow', $event->getResponse()->headers->get('X-Robots-Tag'));
  }

  /**
   * Asserts that response has no x-robots-tag header.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event.
   */
  private function assertResponseEventNoHeader(ResponseEvent $event) : void {
    $this->assertNotContains('X-Robots-Tag', $event->getResponse()->headers);
  }

  /**
   * Tests that robots header is present when configuration is set.
   *
   * @covers ::robotsHeaderEnabled
   * @covers ::onResponse
   * @covers ::__construct
   * @covers ::addRobotHeader
   */
  public function testConfig() : void {
    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::ROBOTS_HEADER_ENABLED, FALSE)
      ->save();
    // Make sure setting config to false does not enable the feature.
    $event = $this->getResponseEvent();
    $this->getSut()->onResponse($event);
    $this->assertResponseEventNoHeader($event);

    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::ROBOTS_HEADER_ENABLED, TRUE)
      ->save();
    $event = $this->getResponseEvent();
    $this->getSut()->onResponse($event);
    $this->assertResponseEventHasHeader($event);
  }

  /**
   * Tests that robots header is not present when no env variable is set.
   *
   * @covers ::robotsHeaderEnabled
   * @covers ::onResponse
   * @covers ::__construct
   */
  public function testNoEnvVariable() : void {
    $event = $this->getResponseEvent();
    $this->getSut()->onResponse($event);
    $this->assertResponseEventNoHeader($event);

    // Make sure setting null values to robots env variable does not
    // enable the feature.
    foreach ([0, NULL, ''] as $value) {
      putenv(RobotsResponseSubscriber::X_ROBOTS_TAG_HEADER_NAME . '=' . $value);
      $event = $this->getResponseEvent();
      $this->getSut()->onResponse($event);
      $this->assertResponseEventNoHeader($event);
    }
  }

  /**
   * Tests that robots header is added when env variable is present.
   *
   * @covers ::robotsHeaderEnabled
   * @covers ::onResponse
   * @covers ::__construct
   * @covers ::addRobotHeader
   */
  public function testEnvVariable() : void {
    putenv(RobotsResponseSubscriber::X_ROBOTS_TAG_HEADER_NAME . '=1');
    $event = $this->getResponseEvent();
    $this->getSut()->onResponse($event);
    $this->assertResponseEventHasHeader($event);
  }

  /**
   * Tests robots path handler.
   *
   * @covers ::robotsHeaderEnabled
   * @covers ::onResponse
   * @covers ::__construct
   * @covers ::addRobotHeader
   */
  public function testRobotsPaths() : void {
    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::ROBOTS_PATHS, [
        '/user*',
        '/test',
      ])
      ->save();

    $paths = [
      '/user/1' => TRUE,
      '/user/login' => TRUE,
      '/user' => TRUE,
      '/test' => TRUE,
      '/test/2' => FALSE,
      '/front' => FALSE,
    ];
    foreach ($paths as $path => $shouldBeEnabled) {
      $request = Request::create($path);
      $event = $this->getResponseEvent($request);
      $this->getSut()->onResponse($event);

      if ($shouldBeEnabled) {
        $this->assertResponseEventHasHeader($event);
      }
      else {
        $this->assertResponseEventNoHeader($event);
      }
    }
  }

}
