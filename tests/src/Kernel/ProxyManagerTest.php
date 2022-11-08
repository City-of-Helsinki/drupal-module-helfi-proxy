<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Proxy manager.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\ProxyManager
 * @group helfi_proxy
 */
class ProxyManagerTest extends KernelTestBase {

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
   * Tests process path.
   *
   * @covers ::isLocalAsset
   * @covers ::getConfig
   * @covers ::processPath
   */
  public function testProcessPath() : void {
    // Make sure nothing is done if asset path is not configured.
    $this->assertSame('/sites/default/files/test.jpg', $this->proxyManager()->processPath('/sites/default/files/test.jpg'));

    $this->setAssetPath('test-assets');
    $this->assertSame('/test-assets/sites/default/files/test.jpg', $this->proxyManager()->processPath('/sites/default/files/test.jpg'));
    $actual = $this->proxyManager()->processPath('public://test.jpg');
    // KernelTests store files in sites/simpletest/{id}/ folder. Test
    // start and end only.
    $this->assertStringStartsWith('/test-assets/sites/simpletest', $actual);
    $this->assertStringEndsWith('files/test.jpg', $actual);
  }

}
