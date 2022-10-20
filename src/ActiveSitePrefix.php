<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * A service to figure out currently active site prefix.
 */
final class ActiveSitePrefix implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    private LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $configFactory
  ) {
    $this->config = $configFactory->get('helfi_proxy.settings');
    $this->addCacheableDependency($this->config);
  }

  /**
   * Gets the site prefixes.
   *
   * @return array
   *   The prefixes.
   */
  public function getPrefixes() : array {
    if (!$prefixes = $this->config->get(ProxyManagerInterface::PREFIXES)) {
      return [];
    }
    return $prefixes;
  }

  /**
   * Gets the currently active site prefix.
   *
   * @return string|null
   *   The active prefix.
   */
  public function getPrefix(string $langcode = NULL) : ? string {
    $prefixes = $this->getPrefixes();

    if (!$langcode) {
      $langcode = $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_URL)
        ->getId();
    }
    return $prefixes[$langcode] ?? NULL;
  }

}
