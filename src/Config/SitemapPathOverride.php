<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\helfi_proxy\ActiveSitePrefix;

/**
 * Override sitemap path dynamically.
 */
class SitemapPathOverride implements ConfigFactoryOverrideInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ActiveSitePrefix $prefix
   *   The active site prefix service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Http\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    private ActiveSitePrefix $prefix,
    private LanguageManagerInterface $languageManager,
    private RequestStack $requestStack,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names): array {
    $overrides = [];
    $url = $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost();

    if (!$url) {
      return $overrides;
    }

    if (in_array('simple_sitemap.settings', $names)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      $prefix = $this->prefix->getPrefix($langcode);
      $baseUrl = sprintf('%s/%s/%s', $url, $langcode, $prefix);
      $overrides['simple_sitemap.settings']['base_url'] = $baseUrl;
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SitemapPathOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
