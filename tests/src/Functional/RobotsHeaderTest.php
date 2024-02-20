<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests X-Robots-tag header.
 *
 * @group helfi_proxy
 */
class RobotsHeaderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Make sure robots header is present.
   */
  public function testPathConfiguration() : void {
    // Response header should not exist yet.
    $this->drupalGet('/front');
    $this->assertSession()->responseHeaderDoesNotExist('X-Robots-Tag');

    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::ROBOTS_PATHS, [
        '/*',
      ])
      ->save();
    Cache::invalidateTags(['config:helfi_proxy.settings']);

    $this->drupalGet('/front');
    $this->assertSession()->responseHeaderEquals('X-Robots-Tag', 'noindex, nofollow');
  }

}
