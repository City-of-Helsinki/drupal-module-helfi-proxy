<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

/**
 * Test base for site prefix tests.
 */
abstract class SitePrefixTestBase extends BrowserTestBase {

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
        'zxx' => 'prefix-zxx',
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

    $this->node = $this->drupalCreateNode([
      'title' => 'en title',
    ]);
    $this->node->addTranslation('fi', ['title' => 'fi title'])
      ->addTranslation('sv', ['title' => 'sv title'])
      ->save();
  }

}
