<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel\Controller;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\helfi_proxy\Controller\FrontController;
use Drupal\helfi_proxy\ProxyManagerInterface;

/**
 * Tests Front controller.
 *
 * @group helfi_proxy
 */
class FrontControllerTest extends KernelTestBase {

  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'helfi_proxy',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('path_alias');
  }

  /**
   * Tests access.
   */
  public function testControllerAccess() : void {
    $request = $this->getMockedRequest('/front');
    $response = $this->processRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Tests build cache tags.
   */
  public function testBuildCacheTags() : void {
    $sut = FrontController::create($this->container);
    $build = $sut->index();
    $this->assertArrayHasKey('content', $build);
    $this->assertEquals(['config:helfi_proxy.settings'], $build['content']['#cache']['tags']);
  }

  /**
   * Tests page title.
   */
  public function testTitle() : void {
    $sut = FrontController::create($this->container);
    $this->assertEquals('Front', $sut->title());

    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::FRONT_PAGE_TITLE, 'Title test')
      ->save();
    $this->assertEquals('Title test', $sut->title());
  }

}
