<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Example test.
 *
 * @group helfi_proxy
 */
class JsExampleTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests something.
   */
  public function testSomething() {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
