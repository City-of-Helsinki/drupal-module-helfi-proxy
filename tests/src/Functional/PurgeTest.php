<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\Core\Url;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

/**
 * Tests purge everything page.
 *
 * @group helfi_proxy
 */
class PurgeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'purge',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the front page.
   */
  public function testPurgePage() : void {
    $route = Url::fromRoute('helfi_proxy.purge');
    $this->drupalGet($route);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->createUser(['administer site configuration']));
    $this->drupalGet($route);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Purge');
  }

}
