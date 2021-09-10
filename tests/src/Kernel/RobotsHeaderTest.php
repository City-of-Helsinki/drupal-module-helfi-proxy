<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi\Functional;

use Drupal\helfi_proxy\HttpMiddleware\AssetHttpMiddleware;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests X-Robots-tag header.
 *
 * @group helfi_proxy
 */
class RobotsHeaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_proxy',
  ];

  /**
   * The http middleware to test.
   *
   * @var \Drupal\helfi_proxy\HttpMiddleware\AssetHttpMiddleware
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->sut = $this->container->get('helfi_proxy.http_middleware');
  }

  /**
   * Tests that header is set.
   */
  public function testHeaderNotExists() : void {
    $response = $this->sut->handle(Request::createFromGlobals());
    $this->assertNotContains('X-Robots-Tag', $response->headers);
  }

  /**
   * {@inheritdoc}
   */
  public function testHeaderExists() : void {
    putenv(AssetHttpMiddleware::X_ROBOTS_TAG_HEADER_NAME . '=1');
    $response = $this->sut->handle(Request::createFromGlobals());
    $this->assertEquals('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
  }

}
