<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\helfi_proxy\EventSubscriber\RedirectResponseSubscriber;
use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests Redirect response subscriber.
 *
 * @group helfi_proxy
 */
class RedirectResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'path_alias',
    'helfi_proxy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installConfig(['language', 'content_translation']);

    foreach (['fi', 'sv'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  public function getSut(string $proxyDomain = NULL) : RedirectResponseSubscriber {
    $domains = [];
    if ($proxyDomain) {
      $this->config('helfi_proxy.settings')
        ->set(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN, $proxyDomain)
        ->save();
      $domains = [$proxyDomain];
    }
    return new RedirectResponseSubscriber(
      $this->container->get('helfi_proxy.proxy_manager'),
      $domains
    );
  }

  /**
   * Test expected default values.
   */
  public function testDefaults() : void {
    $this->assertEquals([
      'www.hel.fi',
      'www-test.hel.fi',
      'helfi-proxy.docker.so',
    ], $this->container->getParameter('helfi_proxy.valid_proxy_domains'));
  }

  /**
   * Tests that existing response url is used for redirect responses.
   */
  public function testRedirectResponse() : void {
    $sut = $this->getSut();
  }

}
