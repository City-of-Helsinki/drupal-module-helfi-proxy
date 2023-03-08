<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\helfi_proxy\ActiveSitePrefix;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Active site prefix service.
 *
 * @coversDefaultClass \Drupal\helfi_proxy\ActiveSitePrefix
 * @group helfi_proxy
 */
class ActiveSitePrefixTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'language',
    'helfi_proxy',
  ];

  /**
   * Gets the service.
   *
   * @return \Drupal\helfi_proxy\ActiveSitePrefix
   *   The service.
   */
  private function getSut() : ActiveSitePrefix {
    $this->container->get('kernel')->rebuildContainer();
    return $this->container->get('helfi_proxy.active_prefix');
  }

  /**
   * Tests ::getPrefix() without proxy paths.
   *
   * @covers ::getPrefix
   * @covers ::getPrefixes
   */
  public function testEmptyPrefix() : void {
    $this->assertEquals(NULL, $this->getSut()->getPrefix());
    $this->assertEquals([], $this->getSut()->getPrefixes());
  }

  /**
   * Tests ::getPrefix with proxy paths.
   *
   * @covers ::getPrefix
   * @covers ::getPrefixes
   */
  public function testPrefix() : void {
    $prefixes = [
      'sv' => 'prefix-sv',
      'en' => 'prefix-en',
      'fi' => 'prefix-fi',
      LanguageInterface::LANGCODE_NOT_APPLICABLE => 'prefix-en',
    ];
    $this->config('helfi_proxy.settings')
      ->set('prefixes', $prefixes)
      ->save();

    $this->assertEquals('prefix-en', $this->getSut()->getPrefix());
    // Make sure we can override active language by providing langcode
    // as an argument.
    $this->assertEquals('prefix-fi', $this->getSut()->getPrefix('fi'));
    // Make sure langcode stays intact after fetching it again without
    // langcode argument.
    $this->assertEquals('prefix-en', $this->getSut()->getPrefix());

    $this->assertEquals($prefixes, $this->getSut()->getPrefixes());
  }

}
