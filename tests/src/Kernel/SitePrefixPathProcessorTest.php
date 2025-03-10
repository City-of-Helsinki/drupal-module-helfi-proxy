<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\LanguageManagerTrait;
use Drupal\remote_entity_test\Entity\RemoteEntityTest;

/**
 * Tests Active site prefix service.
 *
 * @group helfi_proxy
 */
class SitePrefixPathProcessorTest extends KernelTestBase {

  use LanguageManagerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'system',
    'path_alias',
    'content_translation',
    'menu_link_content',
    'user',
    'helfi_language_negotiator_test',
    'remote_entity_test',
    'helfi_proxy',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
  ];

  /**
   * The proxy prefixes.
   *
   * @var array
   */
  protected array $prefixes = [
    'sv' => 'prefix-sv',
    'en' => 'prefix-en',
    'fi' => 'prefix-fi',
    'zxx' => 'prefix-zxx',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setupLanguages();
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('remote_entity_test');
    $this->config('helfi_proxy.settings')
      ->set('prefixes', $this->prefixes)
      ->save();
    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Tests prefixes with entity URL generation.
   */
  public function testToUrl(): void {
    $entity = RemoteEntityTest::create([
      'id' => '1',
      'title' => 'Test en',
      'langcode' => 'en',
    ]);
    $entity->save();

    $entity->addTranslation('fi', [
      'title' => 'Test fi',
    ])->addTranslation('sv', [
      'title' => 'Test sv',
    ])->save();

    foreach ($this->prefixes as $langcode => $prefix) {
      $language = \Drupal::languageManager()->getLanguage($langcode);
      $url = $entity->toUrl(options: ['language' => $language]);
      $this->assertEquals(sprintf('/%s/rmt/1', $prefix), $url->toString());
    }
  }

}
