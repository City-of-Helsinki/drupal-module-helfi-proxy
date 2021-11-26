<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests path processor.
 *
 * @group helfi_proxy
 */
class SitePrefixPathProcessorTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_translation',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->config('helfi_proxy.settings')
      ->set('prefixes', [
        'sv' => 'prefix-sv',
        'en' => 'prefix-en',
        'fi' => 'prefix-fi',
      ])
      ->save();

    $this->drupalCreateContentType(['type' => 'page']);

    $this->drupalLogin($this->rootUser);
    // Enable translation for basic page.
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][page][translatable]' => TRUE,
      'settings[node][page][settings][language][language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');

    drupal_flush_all_caches();

    $this->node = $this->drupalCreateNode(['title' => 'en title']);
    $this->node->addTranslation('fi', ['title' => 'fi title'])
      ->addTranslation('sv', ['title' => 'sv title'])
      ->save();
  }

  /**
   * Tests that language prefixes are set properly.
   */
  public function testPathProcessor() : void {
    // EN has no language prefix by default.
    foreach (['en' => '', 'fi' => 'fi/', 'sv' => 'sv/'] as $langcode => $langPrefix) {
      $language = \Drupal::languageManager()->getLanguage($langcode);

      $this->drupalGet($this->node->toUrl('canonical', ['language' => $language]));
      $this->assertSession()->addressEquals("/{$langPrefix}prefix-$langcode/node/" . $this->node->id());
      $this->assertCacheContext('site_prefix:prefix-' . $langcode);

      $this->drupalGet('/admin/content', ['language' => $language]);
      $this->assertSession()->addressEquals("/{$langPrefix}prefix-$langcode/admin/content");

      // Admin page should have currrently active and en cache contexts.
      foreach ([$langcode, 'en'] as $context) {
        $this->assertCacheContext('site_prefix:prefix-' . $context);
      }
    }
  }

}
