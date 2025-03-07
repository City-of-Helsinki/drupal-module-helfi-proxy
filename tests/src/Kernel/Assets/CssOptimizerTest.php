<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel\Assets;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;

/**
 * Tests Css optimizer rewrites paths accordingly.
 *
 * @group helfi_proxy
 */
class CssOptimizerTest extends KernelTestBase {

  use ApiTestTrait;

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
   * Asserts the optimized css path.
   *
   * @param string $assetPath
   *   The expected asset path.
   */
  private function assertOptimizedPath(string $assetPath) {
    /** @var \Drupal\Core\Asset\CssOptimizer $optimizer */
    $optimizer = $this->container->get('asset.css.optimizer');
    $css_asset = [
      'group' => -100,
      'type' => 'file',
      'weight' => 0.013,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => $this->getFixturePath('helfi_proxy', 'test.css'),
    ];
    $fixturePath = $assetPath . $this->getExtensionPathResolver()->getPath('module', 'helfi_proxy') . '/tests/fixtures/path/to/something.png';
    $optimized = $optimizer->optimize($css_asset);
    $this->assertStringContainsString('url(' . $fixturePath . ')', $optimized);
  }

  /**
   * Tests asset optimize without a configured asset path.
   */
  public function testRewriteWithoutAssetPath() : void {
    $this->assertOptimizedPath('/');
  }

  /**
   * Make sure CSS paths are rewritten to start with the asset path.
   */
  public function testRewriteWithAssetPath() : void {
    $this->config('helfi_proxy.settings')
      ->set('asset_path', 'test-assets')
      ->save();
    $this->assertOptimizedPath('/test-assets/');
  }

}
