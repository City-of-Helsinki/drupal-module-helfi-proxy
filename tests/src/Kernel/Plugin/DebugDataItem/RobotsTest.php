<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel\Plugin\DebugDataItem;

use Drupal\helfi_proxy\Plugin\DebugDataItem\Robots;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Robots debug data.
 *
 * @group helfi_proxy
 */
class RobotsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'helfi_proxy',
  ];

  /**
   * Tests plugin when robot header setting is not enabled.
   */
  public function testDisabledHeader() : void {
    $sut = Robots::create($this->container, [], 'robots', []);
    $this->assertEquals(['DRUPAL_X_ROBOTS_TAG_HEADER' => FALSE], $sut->collect());
  }

  /**
   * Tests plugin when robot header setting is enabled.
   */
  public function testHeader() : void {
    $this->config('helfi_proxy.settings')
      ->set('robots_header_enabled', TRUE)
      ->save();
    $sut = Robots::create($this->container, [], 'robots', []);
    $this->assertEquals(['DRUPAL_X_ROBOTS_TAG_HEADER' => TRUE], $sut->collect());
  }

}
