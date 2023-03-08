<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests path processor.
 *
 * @group helfi_proxy
 */
class SitePrefixPathProcessorTest extends SitePrefixTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Tests that language prefixes are set properly.
   */
  public function testPathProcessor() : void {
    $map = [
      'en' => '',
      'fi' => 'fi/',
      'sv' => 'sv/',
      LanguageInterface::LANGCODE_NOT_APPLICABLE => '',
    ];
    // EN has no language prefix by default.
    foreach ($map as $langcode => $langPrefix) {
      $language = \Drupal::languageManager()->getLanguage($langcode);

      $this->drupalGet($this->node->toUrl('canonical', ['language' => $language]));
      $this->assertSession()->addressEquals("/{$langPrefix}prefix-$langcode/node/" . $this->node->id());
      $this->assertSession()->statusCodeEquals(200);
      $this->assertCacheContext('site_prefix:prefix-' . $langcode);

      $this->drupalGet('/admin/content', ['language' => $language]);
      $this->assertSession()->addressEquals("/{$langPrefix}prefix-$langcode/admin/content");
      $this->assertSession()->statusCodeEquals(200);

      // Admin page should have currently active and en cache contexts.
      foreach ([$langcode, 'en'] as $context) {
        $this->assertCacheContext('site_prefix:prefix-' . $context);
      }
    }
  }

}
