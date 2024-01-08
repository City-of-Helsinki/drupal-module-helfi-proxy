<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

/**
 * Custom metatag test class.
 *
 * @group helfi_proxy
 */
class CustomMetatagTest extends BrowserTestBase {

  /**
   * Drupal node.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $node;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  public function setUp(): void {
    parent::setUp();
    FilterFormat::load('full_html');
    $this->drupalCreateContentType(['type' => 'page']);
    $this->node = $this->drupalCreateNode([
      'title' => 'en title',
      'body' => 'Content here.',
      'langcode' => 'en',
    ]);
  }

  /**
   * Assert element attributes.
   */
  private function assertAttributes(string $selector, array $attributes): void {
    foreach ($attributes as $attribute => $content) {
      $this->assertSession()->elementAttributeContains('css', $selector, $attribute, $content);
    }
  }

  /**
   * Test that custom header metatags are set correctly.
   */
  public function testMetatag() : void {
    $this->drupalGet($this->node->toUrl('canonical'));
    $this->assertAttributes('meta[name="helfi_content_type"]', [
      'content' => 'page',
      'class' => 'elastic',
    ]);
    $this->assertAttributes('meta[name="helfi_content_id"]', [
      'content' => (string) $this->node->id(),
      'class' => 'elastic',
    ]);

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('css', 'meta[name="helfi_content_type"]');
    $this->assertSession()->elementNotExists('css', 'meta[name="helfi_content_id"]');
  }

}
