<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\ProxyManagerInterface;

/**
 * Tests Proxy manager.
 *
 * @group helfi_proxy
 */
class ProxyManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'helfi_proxy',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
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
   */
  public function testAssetPath() : void {
    $this->assertFalse($this->proxyManager()->isConfigured(ProxyManagerInterface::ASSET_PATH));
    $this->assertEquals(NULL, $this->proxyManager()->getConfig(ProxyManagerInterface::ASSET_PATH));
    $this->setAssetPath('test-assets');

    $this->assertEquals('test-assets', $this->proxyManager()->getConfig(ProxyManagerInterface::ASSET_PATH));
  }

  /**
   * Tests process path.
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
