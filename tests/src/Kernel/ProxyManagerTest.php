<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi\Functional;

use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\ProxyTrait;
use Drupal\helfi_proxy\Tag\Tag;
use Drupal\helfi_proxy\Tag\Tags;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Proxy manager.
 *
 * @group helfi_proxy
 */
class ProxyManagerTest extends KernelTestBase {

  use ProxyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'helfi_proxy',
  ];

  /**
   * Gets the proxy manager service.
   *
   * @return \Drupal\helfi_proxy\ProxyManager
   *   The proxy manager service.
   */
  private function proxyManager() : ProxyManager {
    $this->container->get('kernel')->rebuildContainer();
    return $this->container->get('helfi_proxy.proxy_manager');
  }

  /**
   * Creates a mock request.
   *
   * @param string $host
   *   The host.
   * @param string $uri
   *   The uri.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  private function createRequest(string $host = '127.0.0.1', string $uri = '') : Request {
    return Request::create($uri, server: [
      'HTTP_HOST' => $host,
    ]);
  }

  /**
   * Tests instance prefixes.
   */
  public function testPrefixes() : void {
    $this->assertEquals([], $this->proxyManager()->getInstancePrefixes());

    $this->config('helfi_proxy.settings')
      ->set('prefixes', [
        'sv' => 'prefix-sv',
        'en' => 'prefix-en',
        'fi' => 'prefix-fi',
      ])
      ->save();

    $this->assertEquals([
      'sv' => 'prefix-sv',
      'en' => 'prefix-en',
      'fi' => 'prefix-fi',
    ], $this->proxyManager()->getInstancePrefixes());
  }

  /**
   * Gets the expected hostname with path.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The url.
   */
  private function expectedUrlWithPath(string $path) : string {
    return sprintf('//%s%s', $this->getHostname(), $path);
  }

  /**
   * Tests tunnistamo return url.
   */
  public function testTunnistamoReturnUrl() : void {
    $this->assertEquals(NULL, $this->proxyManager()->getTunnistamoReturnUrl());

    $prefix = '/fi/site-prefix';
    $this->config('helfi_proxy.settings')
      ->set('tunnistamo_return_url', $prefix)
      ->save();

    $this->assertEquals($prefix, $this->proxyManager()->getTunnistamoReturnUrl());
  }

  /**
   * Tests asset path.
   */
  public function testAssetPath() : void {
    $this->assertEquals(NULL, $this->proxyManager()->getAssetPath());

    $path = 'test-assets';
    $this->config('helfi_proxy.settings')
      ->set('asset_path', $path)
      ->save();

    $this->assertEquals($path, $this->proxyManager()->getAssetPath());
  }

  /**
   * Tests generic attribute value.
   */
  public function testGenericAttributeValue() : void {
    $request = $this->createRequest();

    $attributeMap = [
      Tags::tag('link'),
      Tags::tag('source'),
      Tags::tag('img'),
    ];

    foreach ($attributeMap as $tag) {
      $path = '/sites/default/files/asset.png';
      $this->assertEquals('//' . $this->getHostname() . $path, $this->proxyManager()->getAttributeValue($request, $tag, $path));
    }
  }

  /**
   * Tests script tag attribute value.
   */
  public function testScriptAttributeValue() : void {
    $path = 'test-assets';
    $this->config('helfi_proxy.settings')
      ->set('asset_path', $path)
      ->save();
    $request = $this->createRequest();
    $this->assertEquals('/test-assets/core/modules/system/test.js', $this->proxyManager()->getAttributeValue($request, Tags::tag('script'), '/core/modules/system/test.js'));
  }

  /**
   * Tests A tags.
   */
  public function testAhrefAttributeValue() : void {
    $request = $this->createRequest();

    $this->assertEquals('https://google.com', $this->proxyManager()->getAttributeValue($request, Tags::tag('a'), 'https://google.com'));

    $this->config('helfi_proxy.settings')
      ->set('prefixes', [
        'sv' => 'prefix-sv',
        'en' => 'prefix-en',
        'fi' => 'prefix-fi',
      ])
      ->save();

    $url = '/fi/prefix-fi/link';
    $this->assertEquals($url, $this->proxyManager()->getAttributeValue($request, Tags::tag('a'), $url));

    $request = $this->createRequest(uri: 'https://localhost/fi/prefix-fi');
    $this->assertEquals('/fi/prefix-fi/link', $this->proxyManager()->getAttributeValue($request, Tags::tag('a'), '/link'));
  }

  /**
   * Tests meta tags.
   */
  public function testMetaTags() : void {
    $request = $this->createRequest();

    foreach (['og:image', 'og:image:url'] as $tag) {
      $this->assertEquals('//' . $this->getHostname() . '/path/to/og-image.png', $this->proxyManager()->getAttributeValue($request, Tags::tag($tag), 'https://www.hel.fi/path/to/og-image.png'));
    }
    $this->assertEquals('//' . $this->getHostname() . '/path/to/og-image.png', $this->proxyManager()->getAttributeValue($request, Tags::tag('twitter:image'), 'https://www.hel.fi/path/to/og-image.png'));
  }

  /**
   * Tests blob storage url when blob storage name is set.
   */
  public function testBlobStorageUrlWithStorage() : void {
    putenv('STAGE_FILE_PROXY_ORIGIN=');
    putenv('AZURE_BLOB_STORAGE_NAME=kymp');
    $request = $this->createRequest();
    // Make sure file is served from blob storage when blob storage container
    // is set.
    $this->assertEquals('https://kymp.blob.core.windows.net/test/og-image.png?itok=123', $this->proxyManager()->getAttributeValue($request, Tags::tag('og:image'), 'https://kymp.blob.core.windows.net/test/og-image.png?itok=123'));
  }

  /**
   * Tests blob storage url when STAGE_FILE_PROXY_ORIGIN is set.
   */
  public function testBlobStorageUrlWithStageFileProxy() : void {
    putenv('STAGE_FILE_PROXY_ORIGIN=https://kymp.blob.core.windows.net');
    putenv('AZURE_BLOB_STORAGE_NAME=sote');
    $request = $this->createRequest();
    // Make sure file is served from blob storage when blob storage container
    // is set.
    $this->assertEquals('https://sote.blob.core.windows.net/test/og-image.png', $this->proxyManager()->getAttributeValue($request, Tags::tag('og:image'), 'https://sote.blob.core.windows.net/test/og-image.png'));
  }

  /**
   * Tests source srcset.
   */
  public function testSourceSrcSet() : void {
    $request = $this->createRequest();

    $values = [
      '/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x',
      '/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x,//helfi-kymp.docker.so/sites/default/files/styles/3_2_xxs_2x/public/image%20%281%29.png?itok=pSa7Ws3i 2x',
    ];

    foreach ($values as $value) {
      $this->assertEquals(
        $this->expectedUrlWithPath($value),
        $this->proxyManager()->getAttributeValue($request, Tags::tag('source'), $value)
      );
    }
  }

  /**
   * Tests empty string and null values.
   */
  public function testNullValue() : void {
    $request = $this->createRequest();

    foreach ([NULL, ''] as $value) {
      foreach (Tags::all() as $tag) {
        $this->assertEquals($this->proxyManager()->getAttributeValue($request, $tag, $value), $value);
      }
    }
  }

  /**
   * Tests supported tags with empty values.
   *
   * @dataProvider getEmptyAttributeValueData
   */
  public function testEmptyGetAttributeValue(Tag $tag, string $value) : void {
    $request = $this->createRequest();
    $this->assertEquals($value, $this->proxyManager()->getAttributeValue($request, $tag, $value));
  }

  /**
   * Data provider for testEmptyGetAttributeValue.
   *
   * @return \string[][]
   *   The test data.
   */
  public function getEmptyAttributeValueData() : array {
    return [
      [Tags::tag('link'), ''],
      [Tags::tag('link'), 'https://localhost/test.svg'],
      [Tags::tag('link'), '//localhost/test.svg'],
      [Tags::tag('a'), ''],
      [Tags::tag('a'), 'https://localhost/test'],
      [Tags::tag('a'), '//localhost/test'],
      [Tags::tag('script'), ''],
      [Tags::tag('script'), 'https://localhost/test.js'],
      [Tags::tag('script'), '//localhost/test.js'],
    ];
  }

}
