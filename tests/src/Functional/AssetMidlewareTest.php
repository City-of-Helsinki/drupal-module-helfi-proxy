<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi\Functional;

use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\helfi_proxy\ProxyTrait;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

/**
 * Tests asset middleware.
 *
 * @group helfi_proxy
 */
class AssetMidlewareTest extends BrowserTestBase {

  use ProxyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'filter',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * The node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->config('helfi_proxy.settings')
      ->set('asset_path', 'test-assets')
      ->set('prefixes', [
        'sv' => 'prefix-sv',
        'en' => 'prefix-en',
        'fi' => 'prefix-fi',
      ])
      ->save();

    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ]);
    $full_html_format->save();
    $this->drupalCreateContentType(['type' => 'page']);

    // Copy fixture SVG file to the theme folder.
    $content = file_get_contents(__DIR__ . '/../../fixtures/sprite.svg');
    $svgPath = drupal_get_path('theme', $this->defaultTheme) . '/sprite.svg';

    file_put_contents($svgPath, $content);

    $this->node = $this->drupalCreateNode([
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
  }

  /**
   * Asserts that asset urls are replaced properly.
   *
   * @param array $types
   *   A key value list of tag -> attribute values.
   */
  private function assertAssetPaths(array $types) : void {
    $html = $this->getSession()->getPage()->getContent();
    $dom = new \DOMDocument();
    @$dom->loadHTML($html);

    foreach ($types as $type) {
      $counter = 0;

      foreach ($dom->getElementsByTagName($type['tag']) as $row) {
        if (!$row->getAttribute($type['attribute'])) {
          continue;
        }
        $this->assertStringContainsString($type['expected'], $row->getAttribute($type['attribute']));
        $counter++;
      }
      // Make sure we have at least one asset with replaced url.
      $this->assertTrue($counter > 0);
    }
  }

  /**
   * Asserts that SVGs are replaced properly.
   */
  private function assertSvgPaths() : void {
    $html = $this->getSession()->getPage()->getContent();
    $dom = new \DOMDocument();
    @$dom->loadHTML($html);

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

  /**
   * Tests css and js paths.
   */
  public function testAssetPaths() : void {
    // Make sure node canonical url works.
    $this->drupalGet($this->node->toUrl());
    $this->assertAssetPaths([
      [
        'tag' => 'img',
        'attribute' => 'src',
        'expected' => '//' . $this->getHostname(),
      ],
      [
        'tag' => 'link',
        'attribute' => 'href',
        'expected' => '//' . $this->getHostname(),
      ],
      [
        'tag' => 'script',
        'attribute' => 'src',
        'expected' => '/test-assets',
      ],
    ]);
    $this->assertSvgPaths();

    // Make sure post requests work when we have form errors.
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->submitForm([
      'name' => 'helfi-admin',
      'pass' => '111',
    ], 'Log in');
    $this->assertAssetPaths([
      [
        'tag' => 'link',
        'attribute' => 'href',
        'expected' => '//' . $this->getHostname(),
      ],
      [
        'tag' => 'script',
        'attribute' => 'src',
        'expected' => '/test-assets',
      ],
    ]);

    // Test node edit form.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertAssetPaths([
      [
        'tag' => 'link',
        'attribute' => 'href',
        'expected' => '//' . $this->getHostname(),
      ],
      [
        'tag' => 'script',
        'attribute' => 'src',
        'expected' => '/test-assets',
      ],
    ]);

    $path = $this->getSession()
      ->getPage()
      ->find('css', '.form-autocomplete')
      ->getAttribute('data-autocomplete-path');

    $this->submitForm([], 'Save');
    $this->assertAssetPaths([
      [
        'tag' => 'img',
        'attribute' => 'src',
        'expected' => '//' . $this->getHostname(),
      ],
      [
        'tag' => 'link',
        'attribute' => 'href',
        'expected' => '//' . $this->getHostname(),
      ],
      [
        'tag' => 'script',
        'attribute' => 'src',
        'expected' => '/test-assets',
      ],
    ]);
    $this->assertSvgPaths();

    // Test json response (autocomplete field).
    $this->drupalGet($path, ['query' => ['q' => 'Anonymous']]);
    $json = json_decode($this->getSession()->getPage()->getContent());
    $this->assertEquals('Anonymous', $json[0]->label);
  }

}
