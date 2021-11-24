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
final class ProxyManager implements ProxyManagerInterface {

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
  private function convertAbsoluteToRelative(?string $value) : string {
    $parts = parse_url($value);

    // Value is already relative.
    if (empty($parts['host'])) {
      return $value;
    }

    static $blobStorage;

    if ($blobStorage === NULL) {
      $blobStorage = getenv('AZURE_BLOB_STORAGE_CONTAINER');

      if ($stageFileProxy = getenv('STAGE_FILE_PROXY_ORIGIN')) {
        $blobStorage = $this->parseHostName($stageFileProxy);
      }
    }
    // Skip if file is served from blob storage.
    if ($blobStorage && str_starts_with($parts['host'], $blobStorage)) {
      return $value;
    }

    if (isset($parts['path'])) {
      return $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : NULL);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeValue(Request $request, Tag $map, ?string $value) : ? string {
    // Certain elements are absolute URLs already (such as og:image:url)
    // so we need to convert them to relative URLs first.
    if ($map->forceRelative) {
      $value = $this->convertAbsoluteToRelative($value);

      // Skip non-relative values.
      if (!str_starts_with($value, '/')) {
        return $value;
      }
    }

    // Ignore absolute URLs.
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

    // Serve element from same domain via relative asset URL. Like:
    // /assets/sites/default/files/js/{sha256}.js.
    if ($map->assetPath) {
      return sprintf('/%s/%s', $this->getAssetPath(), ltrim($value, '/'));
    }

    if ($map->multipleValues) {
      $parts = [];
      foreach (explode($map->multivalueSeparator, $value) as $item) {
        $parts[] = sprintf('//%s%s', $this->getHostname(), trim($item));
      }
      return implode($map->multivalueSeparator, $parts);
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
   * {@inheritdoc}
   */
  public function getAssetPath() : ? string {
    return $this->config->get('asset_path');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstancePrefixes() : array {
    return $this->config->get('prefixes') ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTunnistamoReturnUrl() : ? string {
    return $this->config->get('tunnistamo_return_url');
  }

}
