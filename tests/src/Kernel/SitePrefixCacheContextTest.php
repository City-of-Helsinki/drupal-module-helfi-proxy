<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_proxy\Cache\Context\SitePrefixCacheContext;

/**
 * Tests Site prefix cache context.
 *
 * @group helfi_proxy
 */
class SitePrefixCacheContextTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'language',
    'helfi_proxy',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
  ];

  /**
   * Tests without configured proxy prefixes.
   */
  public function testEmptyPrefix() : void {
    $sut = new SitePrefixCacheContext($this->container->get('helfi_proxy.active_prefix'));
    $this->assertEquals('', $sut->getContext(NULL));
    $this->assertEquals('0', $sut->getContext('en'));
    $this->assertEquals(['site_prefix:'], $sut->getCacheableMetadata(NULL)->getCacheTags());
    $this->assertEquals(['site_prefix:en'], $sut->getCacheableMetadata('en')->getCacheTags());
  }

  /**
   * Tests with configured proxy prefixes.
   */
  public function testWithActivePrefix() : void {
    $this->config('helfi_proxy.settings')
      ->set('prefixes', [
        'sv' => 'sv',
        'en' => 'en',
        'fi' => 'fi',
      ])
      ->save();
    $this->container->get('kernel')->rebuildContainer();

    $sut = new SitePrefixCacheContext($this->container->get('helfi_proxy.active_prefix'));
    $this->assertEquals('sv,en,fi', $sut->getContext(NULL));
    $this->assertEquals('1', $sut->getContext('en'));
    $this->assertEquals(['site_prefix:'], $sut->getCacheableMetadata(NULL)->getCacheTags());
    $this->assertEquals(['site_prefix:en'], $sut->getCacheableMetadata('en')->getCacheTags());
  }

}
