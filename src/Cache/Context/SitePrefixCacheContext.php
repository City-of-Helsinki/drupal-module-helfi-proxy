<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_proxy\ActiveSitePrefix;

/**
 * Defines the SitePrefixCacheContext service, for "per site prefix" caching.
 *
 * Cache context ID: 'site_prefix'.
 */
final class SitePrefixCacheContext implements CalculatedCacheContextInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ActiveSitePrefix $sitePrefix
   *   The active site prefix service.
   */
  public function __construct(private ActiveSitePrefix $sitePrefix) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() : string {
    return (string) new TranslatableMarkup('Site prefix');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($prefix = NULL) : string {
    $prefixes = $this->sitePrefix->getPrefixes();

    if ($prefix === NULL) {
      return implode(',', $prefixes);
    }

    return isset($prefixes[$prefix]) ? '1' : '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($prefix = NULL) : CacheableMetadata {
    return (new CacheableMetadata())->setCacheTags(['site_prefix:' . $prefix]);
  }

}
