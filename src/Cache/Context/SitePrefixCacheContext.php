<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Defines the SitePrefixCacheContext service, for "per site prefix" caching.
 *
 * Cache context ID: 'site_prefix'.
 */
final class SitePrefixCacheContext implements CalculatedCacheContextInterface {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('helfi_proxy.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Site prefix');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($prefix = NULL) {
    $prefixes = $this->config->get('prefixes') ?? [];

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
