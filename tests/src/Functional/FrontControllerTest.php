<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi\Functional;

use Drupal\Core\Url;
use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;

/**
 * Tests front page controller.
 *
 * @group helfi_proxy
 */
class FrontControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Asserts page title for given languages.
   *
   * @param array $expected
   *   The expected titles.
   */
  private function assertPageTitle(array $expected) : void {
    foreach (['fi', 'sv', 'en'] as $language) {
      $this->drupalGet(Url::fromRoute('helfi_proxy.front'), [
        'query' => ['language' => $language],
      ]);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->elementTextEquals('css', 'h1', $expected[$language]);
    }
  }

  /**
   * Tests the front page.
   */
  public function testFront() : void {
    $this->drupalGet(Url::fromRoute('helfi_proxy.front'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->rootUser);
    $this->assertPageTitle([
      'fi' => 'Front',
      'sv' => 'Front',
      'en' => 'Front',
    ]);

    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $languageManager */
    $languageManager = $this->container->get('language_manager');

    foreach (['fi', 'en', 'sv'] as $langcode) {
      $config = $languageManager->getLanguageConfigOverride($langcode, 'helfi_proxy.settings');
      $config->set('front_page_title', "Front $langcode")
        ->save();
    }

    $this->assertPageTitle([
      'fi' => 'Front fi',
      'sv' => 'Front sv',
      'en' => 'Front en',
    ]);

  }

}
