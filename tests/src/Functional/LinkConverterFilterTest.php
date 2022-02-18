<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\filter\Entity\FilterFormat;

/**
 * Tests link converter filter with site prefixes.
 *
 * @group helfi_proxy
 */
class LinkConverterFilterTest extends SitePrefixTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    FilterFormat::load('full_html')
      ->setFilterConfig('helfi_link_converter', ['status' => 1])
      ->save();

    $body = function ($langcode) : array {
      return [
        'value' => sprintf('<a href="/relative/link">Link %s</a>', $langcode),
        'format' => 'full_html',
      ];
    };

    $this->node = $this->drupalCreateNode([
      'title' => 'en title',
      'body' => $body('en'),
    ]);

    foreach (['fi', 'sv'] as $langcode) {
      $this->node->addTranslation($langcode, [
        'title' => "$langcode title",
        'body' => $body($langcode),
      ]);
    }
    $this->node->save();
  }

  /**
   * Tests that language prefixes are added to text fields.
   */
  public function testFilter() : void {
    foreach (['en' => '', 'fi' => 'fi/', 'sv' => 'sv/'] as $langcode => $langPrefix) {
      $language = \Drupal::languageManager()->getLanguage($langcode);
      $this->drupalGet($this->node->toUrl('canonical', ['language' => $language]));
      // Make sure links in body field have a site prefix.
      $this->assertSession()
        ->elementAttributeContains('css', '.test-link', 'href',
          "/{$langPrefix}prefix-$langcode/relative/link"
        );
    }
  }

}
