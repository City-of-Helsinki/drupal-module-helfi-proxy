<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\helfi_proxy\Selector\Selector;
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
   * @param callable $callback
   *   The callback to run value through.
   *
   * @return string|null
   *   The value.
   */
  private function handleMultiValue(string $value, string $separator, callable $callback) : ? string {
    $parts = [];
    foreach (explode($separator, $value) as $item) {
      $parts[] = $callback(trim($item));
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
   * Checks if the given value is an image style.
   *
   * @param string $value
   *   The value.
   *
   * @return bool
   *   TRUE if image is image style.
   */
  private function isImageStyle(string $value) : bool {
    return str_contains($value, '/files/styles/');
  }

  /**
   * Special handling for image styles.
   *
   * @param \Drupal\helfi_proxy\Selector\Selector $tag
   *   The tag.
   * @param string $value
   *   The value with domain added to it.
   *
   * @return string|null
   *   The image style url.
   */
  private function handleImageStyle(Selector $tag, string $value) : ? string {
    if ($tag->multipleValues) {
      return $this->handleMultiValue($value, $tag->multivalueSeparator,
        function (string $value): string {
          return $this->addDomain($value);
        });
    }
    return $this->addDomain($value);
  }

  /**
   * Adds domain to relative URL.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The value with domain added to it.
   */
  private function addDomain(string $value) : string {
    return sprintf('//%s/%s', $this->getHostname(), ltrim($value, '/'));
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeValue(Request $request, Selector $tag, ?string $value) : ? string {
    // Skip if value is being served from CDN already.
    if (!$value || $this->isCdnAddress($value)) {
      return $value;
    }

    // Image styles need special handling because they need to be run through
    // PHP before they are uploaded to CDN.
    if ($this->isImageStyle($value)) {
      return $this->handleImageStyle($tag, $value);
    }
    // Certain elements are absolute URLs already (such as og:image:url)
    // so we need to convert them to relative first and then back to
    // absolute.
    if ($tag->alwaysAbsolute) {
      return $this->handleAlwaysAbsolute($request, $value);
    }

    // Ignore absolute URLs.
    if (str_starts_with($value, 'http') || str_starts_with($value, '//')) {
      return $value;
    }

    // Convert value to have a site prefix, like /fi/site-prefix/.
    if ($tag->sitePrefix) {
      return $this->handleSitePrefix($request, $value);
    }

    if ($tag->multipleValues) {
      return $this->handleMultiValue($value, $tag->multivalueSeparator,
        function (string $value) : string {
          return $this->addAssetPath($value);
        }
      );
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
