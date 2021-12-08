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
  private function convertAbsoluteToRelative(?string $value) : ? string {
    if (!$value) {
      return $value;
    }
    $parts = parse_url($value);

    // Value is already relative.
    if (empty($parts['host'])) {
      return $value;
    }

    if (isset($parts['path'])) {
      return $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : NULL);
    }
    return $value;
  }

  /**
   * Checks if given URL is hosted from a CDN.
   *
   * @param string|null $value
   *   The value.
   *
   * @return bool
   *   TRUE if given url is CDN.
   */
  private function isCdnAddress(?string $value) : bool {
    if (!$value || !$domain = parse_url($value, PHP_URL_HOST)) {
      return FALSE;
    }

    static $patterns;

    if (!is_array($patterns)) {
      $patterns = [];

      if ($stageFileProxy = getenv('STAGE_FILE_PROXY_ORIGIN')) {
        $patterns[] = $this->parseHostName($stageFileProxy);
      }

      if ($blobStorageName = getenv('AZURE_BLOB_STORAGE_NAME')) {
        $patterns[] = sprintf('%s.blob.core.windows.net', $blobStorageName);
      }
    }

    foreach ($patterns as $pattern) {
      if (str_starts_with($domain, $pattern)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Handles tags with 'alwaysAbsolute' option.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $value
   *   The value to convert.
   *
   * @return string|null
   *   The value.
   */
  private function handleAlwaysAbsolute(Request $request, string $value) : ? string {
    $value = $this->convertAbsoluteToRelative($value);

    // Skip non-relative values.
    if (!str_starts_with($value, '/')) {
      return $value;
    }
    return sprintf('%s%s', $request->getSchemeAndHttpHost(), $this->addAssetPath($value));
  }

  /**
   * Prefixes the given value with /{asset-path}.
   *
   * @param string $value
   *   The value.
   *
   * @return string|null
   *   The path.
   */
  private function addAssetPath(string $value) : ? string {
    // Serve element from same domain via relative asset URL. Like:
    // /assets/sites/default/files/js/{sha256}.js.
    return sprintf('/%s/%s', $this->getAssetPath(), ltrim($value, '/'));
  }

  /**
   * Handles tags with 'multipleValues' option.
   *
   * @param string $value
   *   The value.
   * @param string $separator
   *   The separator.
   *
   * @return string|null
   *   The value.
   */
  private function handleMultiValue(string $value, string $separator) : ? string {
    $parts = [];
    foreach (explode($separator, $value) as $item) {
      $parts[] = $this->addAssetPath(trim($item));
    }
    return implode($separator, $parts);
  }

  /**
   * Handles values with 'sitePrefix' option.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $value
   *   The value.
   *
   * @return string|null
   *   The value.
   */
  private function handleSitePrefix(Request $request, string $value) : ? string {
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

  /**
   * {@inheritdoc}
   */
  public function getAttributeValue(Request $request, Tag $map, ?string $value) : ? string {
    // Skip if value is being served from CDN already.
    if (!$value || $this->isCdnAddress($value)) {
      return $value;
    }
    // Certain elements are absolute URLs already (such as og:image:url)
    // so we need to convert them to relative URLs.
    if ($map->alwaysAbsolute) {
      return $this->handleAlwaysAbsolute($request, $value);
    }

    // Ignore absolute URLs.
    if (str_starts_with($value, 'http') || str_starts_with($value, '//')) {
      return $value;
    }

    // Convert value to have a site prefix, like /fi/site-prefix/.
    if ($map->sitePrefix) {
      return $this->handleSitePrefix($request, $value);
    }

    if ($map->multipleValues) {
      return $this->handleMultiValue($value, $map->multivalueSeparator);
    }

    return $this->addAssetPath($value);
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
