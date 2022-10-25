<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\helfi_proxy\ProxyTrait;
use Drupal\helfi_proxy\Selector\AbsoluteUriAttributeSelector;
use Drupal\helfi_proxy\Selector\AttributeSelector;
use Drupal\helfi_proxy\Selector\MultiValueAttributeSelector;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Proxy manager.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\ProxyManager
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
   *
   * @covers ::getConfig
   * @covers ::isConfigured
   * @covers ::__construct
   */
  public function testPrefixes() : void {
    $this->assertFalse($this->proxyManager()->isConfigured(ProxyManagerInterface::PREFIXES));
    $this->assertEquals(NULL, $this->proxyManager()->getConfig(ProxyManagerInterface::PREFIXES));

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
    ], $this->proxyManager()->getConfig(ProxyManagerInterface::PREFIXES));
  }

  /**
   * Tests default proxy domain config.
   *
   * @covers ::getConfig
   * @covers ::isConfigured
   * @covers ::__construct
   */
  public function testDefaultProxyDomain() : void {
    $this->assertFalse($this->proxyManager()->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN));
    $this->assertEquals(NULL, $this->proxyManager()->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN));

    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN, 'www.hel.fi')
      ->save();
    $this->assertEquals('www.hel.fi', $this->proxyManager()->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN));
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
   *
   * @covers ::getConfig
   * @covers ::isConfigured
   * @covers ::__construct
   */
  public function testTunnistamoReturnUrl() : void {
    $this->assertFalse($this->proxyManager()->isConfigured(ProxyManagerInterface::TUNNISTAMO_RETURN_URL));
    $this->assertEquals(NULL, $this->proxyManager()->getConfig(ProxyManagerInterface::TUNNISTAMO_RETURN_URL));

    $prefix = '/fi/site-prefix';
    $this->config('helfi_proxy.settings')
      ->set('tunnistamo_return_url', $prefix)
      ->save();

    $this->assertEquals($prefix, $this->proxyManager()->getConfig(ProxyManagerInterface::TUNNISTAMO_RETURN_URL));
  }

  /**
   * Tests asset path.
   *
   * @covers ::getConfig
   * @covers ::isConfigured
   * @covers ::__construct
   */
  public function testAssetPath() : void {
    $this->assertFalse($this->proxyManager()->isConfigured(ProxyManagerInterface::ASSET_PATH));
    $this->assertEquals(NULL, $this->proxyManager()->getConfig(ProxyManagerInterface::ASSET_PATH));
    $this->setAssetPath('test-assets');

    $this->assertEquals('test-assets', $this->proxyManager()->getConfig(ProxyManagerInterface::ASSET_PATH));
  }

  /**
   * Constructs a HTML tag.
   *
   * @param string $tag
   *   The tag.
   * @param array $attributes
   *   The attributes.
   * @param string|null $value
   *   The value or null.
   * @param bool $hasClosingTag
   *   Whether we should include closing tag or not.
   *
   * @return string
   *   The created html tag.
   */
  private function createHtmlTag(
    string $tag,
    array $attributes,
    string $value = NULL,
    bool $hasClosingTag = TRUE
  ) : string {
    return vsprintf('<%s %s>%s%s', [
      $tag,
      implode(' ', array_map(function ($key, $value) {
        return sprintf('%s="%s"', $key, $value);
      }, array_keys($attributes), $attributes)),
      $value,
      $hasClosingTag ? "</$tag>" : NULL,
    ]);
  }

  /**
   * Tests script tag attribute value.
   *
   * @covers ::processHtml
   * @covers ::getAttributeValue
   * @covers ::getDefaultSelectors
   * @covers ::isAbsoluteUri
   * @covers ::addAssetPath
   * @covers ::__construct
   */
  public function testAttributeSelector() : void {
    $this->setAssetPath('test-assets');
    $request = $this->createRequest();

    $expected = $this->createHtmlTag('script', ['src' => '/test-assets/core/modules/system/test.js']);
    $html = $this->createHtmlTag('script', ['src' => '/core/modules/system/test.js']);

    $processed = $this->proxyManager()
      ->processHtml($html, $request, [new AttributeSelector('//script', 'src')]);
    $this->assertEquals($expected, $processed);
  }

  /**
   * Tests string value processing.
   *
   * @covers ::processValue
   * @covers ::getAttributeValue
   * @covers ::getDefaultSelectors
   * @covers ::isAbsoluteUri
   * @covers ::addAssetPath
   * @covers ::__construct
   */
  public function testStringValueSelector() : void {
    $this->setAssetPath('test-assets');

    $processed = $this->proxyManager()->processValue('/core/modules/system/test.js');
    $this->assertEquals('/test-assets/core/modules/system/test.js', $processed);
  }

  /**
   * Tests AbsoluteUriAttributeSelector() object.
   *
   * @covers ::processHtml
   * @covers ::getAttributeValue
   * @covers ::getDefaultSelectors
   * @covers ::isAbsoluteUri
   * @covers ::addAssetPath
   * @covers ::isLocalAsset
   * @covers ::isCdnAddress
   * @covers ::__construct
   */
  public function testAbsoluteUriAttributeSelector() : void {
    $request = $this->createRequest();
    $this->setAssetPath('test-assets');
    $xpaths = [
      'og:image:url' => 'property',
      'twitter:image' => 'name',
    ];

    foreach ($xpaths as $value => $selector) {
      // Make sure domain is added to relative paths when dealing with
      // AbsoluteUriAttributeSelectors.
      $expected = $this->createHtmlTag('meta', [
        $selector => $value,
        'content' => 'http://' . $this->getHostname() . '/test-assets/themes/contrib/hdbt/test.png',
      ], hasClosingTag: FALSE);
      $html = $this->createHtmlTag('meta', [
        $selector => $value,
        'content' => '/themes/contrib/hdbt/test.png',
      ], hasClosingTag: FALSE);

      $xpath = sprintf('//meta[@%s="%s"]', $selector, $value);
      $attributeSelector = new AbsoluteUriAttributeSelector($xpath, 'content');

      $this->assertEquals(
        $expected,
        $this->proxyManager()->processHtml($html, $request, [$attributeSelector])
      );

      // Make sure the domain is converted to correct one.
      $html = $this->createHtmlTag('meta', [
        $selector => $value,
        'content' => 'http://www.hel.fi/themes/contrib/hdbt/test.png',
      ], hasClosingTag: FALSE);

      $this->assertEquals(
        $expected,
        $this->proxyManager()->processHtml($html, $request, [$attributeSelector])
      );
    }
  }

  /**
   * Tests blob storage url when blob storage name is set.
   *
   * @covers ::processHtml
   * @covers ::getAttributeValue
   * @covers ::getDefaultSelectors
   * @covers ::isCdnAddress
   * @covers ::addAssetPath
   * @covers ::isLocalAsset
   * @covers ::__construct
   */
  public function testBlobStorageUrlWithStorage() : void {
    putenv('STAGE_FILE_PROXY_ORIGIN=');
    putenv('AZURE_BLOB_STORAGE_NAME=kymp');
    $request = $this->createRequest();

    $html = $this->createHtmlTag('meta', [
      'property' => 'og:image:url',
      'content' => 'https://kymp.blob.core.windows.net/test/og-image.png?itok=123',
    ], hasClosingTag: FALSE);
    // Make sure file is served from blob storage when blob storage container
    // is set.
    $this->assertEquals(
      $html,
      $this->proxyManager()->processHtml($html, $request, [
        new AbsoluteUriAttributeSelector('//meta[@property="og:image:url"]', 'content'),
      ])
    );
  }

  /**
   * Tests blob storage url when STAGE_FILE_PROXY_ORIGIN is set.
   *
   * @covers ::processHtml
   * @covers ::getAttributeValue
   * @covers ::getDefaultSelectors
   * @covers ::isCdnAddress
   * @covers ::addAssetPath
   * @covers ::isLocalAsset
   * @covers ::__construct
   */
  public function testBlobStorageUrlWithStageFileProxy() : void {
    putenv('STAGE_FILE_PROXY_ORIGIN=https://kymp.blob.core.windows.net');
    putenv('AZURE_BLOB_STORAGE_NAME=sote');
    $request = $this->createRequest();
    // Make sure file is served from blob storage when blob storage container
    // is set.
    $html = $this->createHtmlTag('meta', [
      'property' => 'og:image:url',
      'content' => 'https://sote.blob.core.windows.net/test/og-image.png',
    ], hasClosingTag: FALSE);

    $this->assertEquals(
      $html,
      $this->proxyManager()->processHtml($html, $request, [
        new AbsoluteUriAttributeSelector('//meta[@property="og:image:url"]', 'content'),
      ])
    );
  }

  /**
   * Tests source srcset.
   *
   * @covers ::processHtml
   * @covers ::getAttributeValue
   * @covers ::getDefaultSelectors
   * @covers ::isCdnAddress
   * @covers ::addAssetPath
   * @covers ::isAbsoluteUri
   * @covers ::handleMultiValue
   * @covers ::__construct
   */
  public function testMultivalueAttributeSelector() : void {
    $request = $this->createRequest();
    $this->setAssetPath('test-assets');

    $values = [
      '/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x',
      '/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x',
      '//helfi-kymp.docker.so/sites/default/files/styles/3_2_xxs_2x/public/image%20%281%29.png?itok=pSa7Ws3i 2x',
    ];
    $html = $this->createHtmlTag('source', ['srcset' => implode(', ', $values)]);

    $expectedValues = [
      '/test-assets/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x',
      '/test-assets/sites/default/files/styles/test/public/image.png?h=948e8679&amp;itok=FwETi0jH 1x',
      // Make sure absolute uris are ignored.
      '//helfi-kymp.docker.so/sites/default/files/styles/3_2_xxs_2x/public/image%20%281%29.png?itok=pSa7Ws3i 2x',
    ];
    $expected = $this->createHtmlTag('source', [
      'srcset' => implode(', ', $expectedValues),
    ]);

    $this->assertEquals(
      $expected,
      $this->proxyManager()->processHtml($html, $request, [
        new MultiValueAttributeSelector('//source', 'srcset', ', '),
      ])
    );
  }

}
