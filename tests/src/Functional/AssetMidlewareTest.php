<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\helfi_proxy\HostnameTrait;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

/**
 * Tests path processor.
 *
 * @group helfi_proxy
 */
class AssetMidlewareTest extends BrowserTestBase {

  use HostnameTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'filter',
    'language',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ]);
    $full_html_format->save();
    $this->drupalCreateContentType(['type' => 'page']);
  }

  /**
   * Tests css and js paths.
   */
  public function testAssetPaths() : void {
    // Copy fixture SVG file to the theme folder.
    $content = file_get_contents(__DIR__ . '/../../fixtures/sprite.svg');
    $svgPath = drupal_get_path('theme', $this->defaultTheme) . '/sprite.svg';

    file_put_contents($svgPath, $content);

    $node = $this->drupalCreateNode([
      'body' => [
        'value' => sprintf('
          <svg class="icon">
            <title>Helsinki</title>
            <use href="/%s#helsinki" />
          </svg>
          <img src="/themes/test.jpg">
        ', $svgPath),
        'format' => 'full_html',
      ],
    ]);

    $this->drupalGet($node->toUrl());
    $html = $this->getSession()->getPage()->getContent();
    $dom = new \DOMDocument();
    @$dom->loadHTML($html);

    $types = ['img' => 'src', 'link' => 'href', 'script' => 'src'];

    foreach ($types as $tag => $attribute) {
      $counter = 0;

      foreach ($dom->getElementsByTagName($tag) as $row) {
        if (!$row->getAttribute($attribute)) {
          continue;
        }
        $this->assertStringContainsString('//' . $this->getHostname(), $row->getAttribute($attribute));
        $counter++;
      }
      // Make sure we have at least one asset with replaced url.
      $this->assertTrue($counter > 0);
    }

    $counter = 0;
    // Make sure SVGs are inlined into dom.
    foreach ($dom->getElementsByTagName('use') as $row) {
      if (!$row->getAttribute('href')) {
        continue;
      }
      $counter++;
      $this->assertEquals('#helsinki', $row->getAttribute('href'));
    }
    $this->assertEquals(1, $counter);
    $this->assertSession()->responseContains('<span style="display: none;"><svg ');
  }

}
