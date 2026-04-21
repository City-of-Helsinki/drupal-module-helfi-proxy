<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_proxy\ActiveSitePrefix;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Active site prefix service.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_proxy')]
class ActiveSitePrefixTest extends KernelTestBase {

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
   * Gets the service.
   *
   * @return \Drupal\helfi_proxy\ActiveSitePrefix
   *   The service.
   */
  private function getSut() : ActiveSitePrefix {
    $this->container->get('kernel')->rebuildContainer();
    return $this->container->get(ActiveSitePrefix::class);
  }

  /**
   * Tests ::getPrefix() without proxy paths.
   */
  public function testEmptyPrefix() : void {
    $this->assertEquals(NULL, $this->getSut()->getPrefix());
    $this->assertEquals([], $this->getSut()->getPrefixes());
  }

  /**
   * Tests ::getPrefix with proxy paths.
   */
  public function testPrefix() : void {
    $prefixes = [
      'sv' => 'prefix-sv',
      'en' => 'prefix-en',
      'fi' => 'prefix-fi',
    ];
    $this->config('helfi_proxy.settings')
      ->set('prefixes', $prefixes)
      ->save();

    $this->assertEquals('prefix-en', $this->getSut()->getPrefix());
    $this->assertNull($this->getSut()->getPrefix(LanguageInterface::LANGCODE_NOT_APPLICABLE));

    // Make sure we can set langcode not applicable path.
    $prefixes[LanguageInterface::LANGCODE_NOT_APPLICABLE] = 'overridden-prefix';
    $this->config('helfi_proxy.settings')
      ->set('prefixes', $prefixes)
      ->save();

    // Make sure we can override active language by providing langcode
    // as an argument.
    $this->assertEquals('prefix-fi', $this->getSut()->getPrefix('fi'));
    // Make sure langcode stays intact after fetching it again without
    // langcode argument.
    $this->assertEquals('prefix-en', $this->getSut()->getPrefix());

    $this->assertEquals($prefixes, $this->getSut()->getPrefixes());
  }

}
