<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\helfi_proxy\Tag\Tag;
use Symfony\Component\HttpFoundation\Request;

/**
 * A class to determine sites hostname.
 */
final class ProxyManager {

  use ProxyTrait;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

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
   * Converts the given URL to relative.
   *
   * @param string|null $value
   *   The value.
   *
   * @return string|null
   *   the value.
   */
  private function convertAbsoluteToRelative(?string $value) : ? string {
    $parts = parse_url($value);

    // Value is already relative.
    if (empty($parts['host'])) {
      return $value;
    }
    return $parts['path'] ?? NULL;
  }

  /**
   * Gets the value for given attribute.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\helfi_proxy\Tag\Tag $map
   *   The attrbiteu map object.
   * @param string|null $value
   *   The value.
   *
   * @return string|null
   *   The value for given attribute or null.
   */
  public function getAttributeValue(Request $request, Tag $map, ?string $value) : ? string {
    // Certain elements are forced to have absolute URL already (such as
    // og:image:url) so we need to convert the given URL to relative first.
    if ($map->forceRelative) {
      $value = $this->convertAbsoluteToRelative($value);
    }

    if (!$value || str_starts_with($value, 'http') || str_starts_with($value,
        '//')) {
      return $value;
    }

    // Convert value to have a site prefix, like /fi/site-prefix/.
    if ($map->sitePrefix) {
      // Make sure we have active site prefix and the given URL is relative.
      if ((!$prefix = $this->getActivePrefix($request->getPathInfo())) || !str_starts_with($value, '/')) {
        return $value;
      }

      // Scan other languages as well.
      foreach ($this->getInstancePrefixes() as $item) {
        if (str_contains($value, $item)) {
          return $value;
        }
      }
      return sprintf('%s/%s', $prefix, ltrim($value, '/'));
    }

    // Serve certain elements from same domain via relative asset URL.
    if ($map->assetPath) {
      return sprintf('/%s/%s', $this->getAssetPath(), ltrim($value, '/'));
    }
    return sprintf('//%s%s', $this->getHostname(), $value);
  }

  /**
   * Gets the currently active site prefix.
   *
   * @return string|null
   *   The currently active prefix.
   */
  private function getActivePrefix(string $path) : ? string {
    static $prefix;

    if ($prefix === NULL) {
      foreach ($this->getInstancePrefixes() as $langcode => $item) {
        if (str_contains($path, $item)) {
          $prefix = sprintf('/%s/%s', $langcode, $item);
          break;
        }
      }
    }
    return $prefix;
  }

  /**
   * Gets the instance name.
   *
   * @return string|null
   *   The instance name.
   */
  public function getAssetPath() : ? string {
    return $this->config->get('asset_path');
  }

  /**
   * Gets the instance prefixes.
   *
   * @return array
   *   The instance prefixes.
   */
  public function getInstancePrefixes() : array {
    return $this->config->get('prefixes') ?? [];
  }

  /**
   * Gets the tunnistamo return url.
   *
   * @return string|null
   *   The tunnistamo return url.
   */
  public function getTunnistamoReturnUrl() : ? string {
    return $this->config->get('tunnistamo_return_url');
  }

}
