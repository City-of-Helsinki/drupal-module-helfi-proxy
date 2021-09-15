<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi\Functional;

use Drupal\Core\Url;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

/**
 * Tests front page controller.
 *
 * @group helfi_proxy
 */
class FrontControllerTest extends BrowserTestBase {

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
   * Tests the front page.
   */
  public function testFront() : void {
    $this->drupalGet(Url::fromRoute('helfi_proxy.front'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('helfi_proxy.front'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
