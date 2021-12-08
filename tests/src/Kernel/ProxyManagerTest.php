<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\ProxyTrait;
use Drupal\helfi_proxy\Selector\Selector;
use Drupal\helfi_proxy\Selector\Selectors;
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
   * @param int $port
   *   The port.
   * @param string $uri
   *   The uri.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  private function createRequest(string $host = '127.0.0.1', int $port = 8888, string $uri = '') : Request {
    return Request::create($uri, server: [
      'HTTP_HOST' => $host . ':' . $port,
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
   * Sets the asset path.
   *
   * @param string $path
   *   The asset path.
   */
  private function setAssetPath(string $path) : void {
    $this->config('helfi_proxy.settings')
      ->set('asset_path', $path)
      ->save();
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
    $this->setAssetPath('test-assets');

    $this->assertEquals('test-assets', $this->proxyManager()->getAssetPath());
  }

  /**
   * Tests generic attribute value.
   */
  public function testGenericAttributeValue() : void {
    $request = $this->createRequest();

    $attributeMap = [
      Selectors::get('link'),
      Selectors::get('source'),
      Selectors::get('img'),
    ];
    $this->setAssetPath('test-assets');

    foreach ($attributeMap as $tag) {
      $path = '/sites/default/files/asset.png';
      $this->assertEquals('/test-assets' . $path, $this->proxyManager()->getAttributeValue($request, $tag, $path));
    }
  }

  /**
   * Tests script tag attribute value.
   */
  public function testScriptAttributeValue() : void {
    $this->setAssetPath('test-assets');
    $request = $this->createRequest();
    $this->assertEquals('/test-assets/core/modules/system/test.js', $this->proxyManager()->getAttributeValue($request, Selectors::get('script'), '/core/modules/system/test.js'));
  }

  /**
   * Tests A tags.
   */
  public function testAhrefAttributeValue() : void {
    $request = $this->createRequest();

    $this->assertEquals('https://google.com', $this->proxyManager()->getAttributeValue($request, Selectors::get('a'), 'https://google.com'));

    $this->config('helfi_proxy.settings')
      ->set('prefixes', [
        'sv' => 'prefix-sv',
        'en' => 'prefix-en',
        'fi' => 'prefix-fi',
      ])
      ->save();

    $url = '/fi/prefix-fi/link';
    $this->assertEquals($url, $this->proxyManager()->getAttributeValue($request, Selectors::get('a'), $url));

    $request = $this->createRequest(uri: 'https://localhost/fi/prefix-fi');
    $this->assertEquals('/fi/prefix-fi/link', $this->proxyManager()->getAttributeValue($request, Selectors::get('a'), '/link'));
  }

  /**
   * Tests meta tags.
   */
  public function testMetaTags() : void {
    $request = $this->createRequest();
    $this->setAssetPath('test-assets');

    foreach (['og:image', 'og:image:url'] as $tag) {
      $this->assertEquals('http://' . $this->getHostname() . '/test-assets/path/to/og-image.png', $this->proxyManager()->getAttributeValue($request, Selectors::get($tag), 'https://www.hel.fi/path/to/og-image.png'));
    }
    $this->assertEquals('http://' . $this->getHostname() . '/test-assets/path/to/og-image.png', $this->proxyManager()->getAttributeValue($request, Selectors::get('twitter:image'), 'https://www.hel.fi/path/to/og-image.png'));
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
    $this->assertEquals('https://kymp.blob.core.windows.net/test/og-image.png?itok=123', $this->proxyManager()->getAttributeValue($request, Selectors::get('og:image'), 'https://kymp.blob.core.windows.net/test/og-image.png?itok=123'));
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
    $this->assertEquals('https://sote.blob.core.windows.net/test/og-image.png', $this->proxyManager()->getAttributeValue($request, Selectors::get('og:image'), 'https://sote.blob.core.windows.net/test/og-image.png'));
  }

  /**
   * Tests source srcset.
   */
  public function testSourceSrcSet() : void {
    $request = $this->createRequest();
    $this->setAssetPath('test-assets');

    $values = [
      '/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x',
      '/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x,//helfi-kymp.docker.so/sites/default/files/styles/3_2_xxs_2x/public/image%20%281%29.png?itok=pSa7Ws3i 2x',
    ];

    foreach ($values as $value) {
      $this->assertEquals(
        '/test-assets' . $value,
        $this->proxyManager()->getAttributeValue($request, Selectors::get('source'), $value)
      );
    }
  }

  /**
   * Tests empty string and null values.
   */
  public function testNullValue() : void {
    $request = $this->createRequest();

    foreach ([NULL, ''] as $value) {
      foreach (Selectors::all() as $tag) {
        $this->assertEquals($this->proxyManager()->getAttributeValue($request, $tag, $value), $value);
      }
    }
  }

  /**
   * Tests supported tags with empty values.
   *
   * @dataProvider getEmptyAttributeValueData
   */
  public function testEmptyGetAttributeValue(Selector $tag, string $value) : void {
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
      [Selectors::get('link'), ''],
      [Selectors::get('link'), 'https://localhost/test.svg'],
      [Selectors::get('link'), '//localhost/test.svg'],
      [Selectors::get('a'), ''],
      [Selectors::get('a'), 'https://localhost/test'],
      [Selectors::get('a'), '//localhost/test'],
      [Selectors::get('script'), ''],
      [Selectors::get('script'), 'https://localhost/test.js'],
      [Selectors::get('script'), '//localhost/test.js'],
    ];
  }

}
