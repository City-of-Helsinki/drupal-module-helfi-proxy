<?php


namespace Drupal\Tests\helfi\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests path processor.
 *
 * @group helfi_proxy
 */
class PathTest extends  {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'language',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testPath() : void {
    $this->drupalGet('<front>');
  }

}
