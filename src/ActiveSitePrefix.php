<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * A service to figure out currently active site prefix.
 */
final class ActiveSitePrefix implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Closure $configFactoryClosure
   *   The config factory closure.
   */
  public function __construct(
    private readonly LanguageManagerInterface $languageManager,
    #[AutowireServiceClosure(ConfigFactoryInterface::class)] private readonly \Closure $configFactoryClosure,
  ) {
  }

  /**
   * Gets the config factory instance from closure.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  private function getConfigFactory(): ConfigFactoryInterface {
    return ($this->configFactoryClosure)();
  }

  /**
   * Gets the site prefixes.
   *
   * @return null|array{string, string}
   *   The prefixes.
   */
  public function getPrefixes(): ?array {
    $config = $this->getConfigFactory()->get('helfi_proxy.settings');
    $this->addCacheableDependency($config);

    if (!$prefixes = $config->get(ProxyManagerInterface::PREFIXES)) {
      return NULL;
    }
    return $prefixes;
  }

  /**
   * Gets the currently active site prefix.
   *
   * @return string|null
   *   The active prefix.
   */
  public function getPrefix(?string $langcode = NULL) : ?string {
    $prefixes = $this->getPrefixes();
    $langcode = $langcode ?: $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_URL)
      ->getId();

    return $prefixes[$langcode] ?? NULL;
  }

}
