<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\helfi_api_base\Event\PostDeployEvent;
use Drupal\KernelTests\KernelTestBase;
use Drupal\purge\Plugin\Purge\Invalidation\RegularExpressionInvalidation;

/**
 * Tests deploy hook that purges assets from Varnish cache.
 *
 * @group helfi_proxy
 */
class AssetPurgeDeployHookTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_api_base',
    'path_alias',
    'helfi_proxy',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
    'varnish_purge_tags',
    'varnish_purger',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('varnishpurgersettings');
    $this->installConfig(['helfi_proxy']);
    // Purge plugin configuration is not installed automatically.
    $this->config('purge.plugins')->set('purgers', [
      [
        'instance_id' => 'assets',
        'plugin_id' => 'varnish',
        'order_index' => 1,
      ],
    ])->save();
    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Tests deploy hooks without a configured asset path.
   */
  public function testAssetPurge() : void {
    $eventDispatcher = $this->container->get('event_dispatcher');
    $eventDispatcher->dispatch(new PostDeployEvent());
    /** @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $queuer */
    $queuer = $this->container->get('purge.queue');
    // Make sure queue has no items when the asset path is not configured.
    $this->assertEquals(0, $queuer->numberOfItems());

    $this->config('helfi_proxy.settings')
      ->set('asset_path', 'test-assets')
      ->save();
    // Make sure the asset path gets invalidated when configured.
    $eventDispatcher->dispatch(new PostDeployEvent());
    $this->assertEquals(1, $queuer->numberOfItems());
    /** @var \Drupal\purge\Plugin\Purge\Invalidation\RegularExpressionInvalidation $item */
    $item = $queuer->claim()[0];
    $this->assertInstanceOf(RegularExpressionInvalidation::class, $item);
    $this->assertEquals('test-assets', $item->getExpression());
  }

}
