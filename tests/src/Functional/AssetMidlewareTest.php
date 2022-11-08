<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\helfi_proxy\ProxyTrait;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
    $this->node = $this->drupalCreateNode([
      'body' => [
        'value' => '
          <svg class="icon">
            <title>Helsinki</title>
            <use href="/%s#helsinki" />
          </svg>
          <img src="/themes/test.jpg">
        ',
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
    foreach ($types as $type) {
      $counter = 0;

      $elements = $this->getSession()->getPage()->findAll('css', $type['tag']);

      foreach ($elements as $row) {
        if (!$value = $row->getAttribute($type['attribute'])) {
          continue;
        }
        $this->assertStringStartsWith($type['expected'], $value);
        $counter++;
      }
      // Make sure we have at least one asset with replaced url.
      $this->assertTrue($counter > 0);
    }
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
        'expected' => '/test-assets/themes/test.jpg',
      ],
      [
        'tag' => 'link[rel="stylesheet"]',
        'attribute' => 'href',
        'expected' => '/test-assets/',
      ],
      [
        'tag' => 'script',
        'attribute' => 'src',
        'expected' => '/test-assets/',
      ],
    ]);

    // Make sure post requests work when we have form errors.
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->submitForm([
      'name' => 'helfi-admin',
      'pass' => '111',
    ], 'Log in');
    $this->assertAssetPaths([
      [
        'tag' => 'link[rel="stylesheet"]',
        'attribute' => 'href',
        'expected' => '/test-assets/',
      ],
      [
        'tag' => 'script',
        'attribute' => 'src',
        'expected' => '/test-assets/',
      ],
    ]);

    // Test node edit form.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertAssetPaths([
      [
        'tag' => 'link[rel="stylesheet"]',
        'attribute' => 'href',
        'expected' => '/test-assets/',
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
        'expected' => '/test-assets/themes/test.jpg',
      ],
      [
        'tag' => 'link[rel="stylesheet"]',
        'attribute' => 'href',
        'expected' => '/test-assets/',
      ],
      [
        'tag' => 'script',
        'attribute' => 'src',
        'expected' => '/test-assets',
      ],
    ]);

    // Test json response (autocomplete field).
    $this->drupalGet($path, ['query' => ['q' => 'Anonymous']]);
    $json = json_decode($this->getSession()->getPage()->getContent());
    $this->assertEquals('Anonymous', $json[0]->label);
  }

  /**
   * Tests meta tags.
   */
  public function testMetaTags() : void {
    $html = trim('
      <!DOCTYPE html><html><head>
      <meta property="og:site_name" content="Pys&auml;k&ouml;inti">
      <meta property="og:url" content="https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/test-0">
      <meta property="og:title" content="test">
      <meta property="og:image" content="https://www.hel.fi/themes/contrib/hdbt/src/images/og-global.png">
      <meta property="og:image:url" content="https://www.hel.fi/themes/contrib/hdbt/src/images/og-global.png">
      <meta property="og:updated_time" content="2021-11-17T10:11:40+0200">
      <meta property="article:published_time" content="2021-11-17T10:11:33+0200">
      <meta property="article:modified_time" content="2021-11-17T10:11:33+0200">
      <meta name="twitter:image" content="https://www.hel.fi/themes/contrib/hdbt/src/images/og-global.png">
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" content="test | Helsingin kaupunki">
      <meta name="twitter:url" content="https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/test-0">
      </head>
      </html>
    ');

    $request = Request::createFromGlobals();
    $kernelMock = $this->createMock(HttpKernelInterface::class);
    $kernelMock->method('handle')
      ->willReturn(new Response($html));
    $sut = new AssetHttpMiddleware(
      $kernelMock,
      $this->container->get('helfi_proxy.proxy_manager')
    );

    $expected = new FormattableMarkup(
      '<!DOCTYPE html>
<html><head>
      <meta property="og:site_name" content="Pys&auml;k&ouml;inti">
      <meta property="og:url" content="https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/test-0">
      <meta property="og:title" content="test">
      <meta property="og:image" content="http://@hostname/test-assets/themes/contrib/hdbt/src/images/og-global.png">
      <meta property="og:image:url" content="http://@hostname/test-assets/themes/contrib/hdbt/src/images/og-global.png">
      <meta property="og:updated_time" content="2021-11-17T10:11:40+0200">
      <meta property="article:published_time" content="2021-11-17T10:11:33+0200">
      <meta property="article:modified_time" content="2021-11-17T10:11:33+0200">
      <meta name="twitter:image" content="http://@hostname/test-assets/themes/contrib/hdbt/src/images/og-global.png">
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" content="test | Helsingin kaupunki">
      <meta name="twitter:url" content="https://www.hel.fi/fi/kaupunkiymparisto-ja-liikenne/test-0">
      </head>
      </html>
      ', ['@hostname' => $this->getHostname()]);

    $response = $sut->handle($request)->getContent();
    $this->assertEquals(trim((string) $expected), trim($response));
  }

}
