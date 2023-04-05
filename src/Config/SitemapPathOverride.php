<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Language\LanguageInterface;

/**
 * Override sitemap path dynamically.
 */
class SitemapPathOverride implements ConfigFactoryOverrideInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Http\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
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
      // Use languageManager as static service as it will create circular
      // reference via the config.factory.override service.
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId();

      // Load the helfi_proxy.active_prefix on demand as using it as an argument
      // for the constructor will trigger the ServiceCircularReferenceException.
      /** @var \Drupal\helfi_proxy\ActiveSitePrefix $active_prefix */
      $active_prefix = \Drupal::service('helfi_proxy.active_prefix');
      $prefix = $active_prefix->getPrefix($langcode);
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
