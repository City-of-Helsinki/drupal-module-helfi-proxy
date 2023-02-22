<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\helfi_proxy\ActiveSitePrefix;
use Drupal\helfi_proxy\ProxyManagerInterface;

/**
* Override sitemap path dynamically.
*/
class SitemapPathOverride implements ConfigFactoryOverrideInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ProxyManagerInterface $proxyManager
   *   The proxy manager.
   * @param \Drupal\helfi_proxy\ActiveSitePrefix $prefix
   *   The active site prefix service.
   */
  public function __construct(
    private ProxyManagerInterface $proxyManager,
    private ActiveSitePrefix $prefix,
  ) {
  }

  /**
  * {@inheritdoc}
  */
  public function loadOverrides($names): array {
    $overrides = [];

    if (in_array('simple_sitemap.settings', $names)) {
      $url = \Drupal::request()->getSchemeAndHttpHost();
      $prefix = $this->prefix->getPrefix('fi');
      $langcode = 'fi';
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
