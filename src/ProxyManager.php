<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\helfi_proxy\Selector\AbsoluteUriAttributeSelector;
use Drupal\helfi_proxy\Selector\AttributeSelector;
use Drupal\helfi_proxy\Selector\MultiValueAttributeSelector;
use Drupal\helfi_proxy\Selector\SelectorRepositoryTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * A class to determine sites hostname.
 */
final class ProxyManager implements ProxyManagerInterface {

  use ProxyTrait;
  use SelectorRepositoryTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(private ConfigFactoryInterface $configFactory) {
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured(string $key) : bool {
    return $this->getConfig($key) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(string $key, mixed $defaultValue = NULL) : mixed {
    $config = $this->configFactory->get('helfi_proxy.settings')
      ->get($key);

    return $config ?? $defaultValue;
  }

  /**
   * {@inheritdoc}
   */
  public function processHtml(string $html, Request $request, array $selectors = []) : string {
    $dom = new \DOMDocument();
    $previousXmlErrorBehavior = libxml_use_internal_errors(TRUE);
    $encoding = '<?xml encoding="utf-8" ?>';

    @$dom->loadHTML(
      $encoding . $html,
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    $dom->encoding = 'UTF-8';
    $xpath = new \DOMXPath($dom);

    foreach ($selectors ?: $this->getDefaultSelectors() as $selector) {
      foreach ($xpath->query($selector->xpath) as $row) {
        $value = $this
          ->getAttributeValue(
            $selector,
            $request,
            $row->getAttribute($selector->attribute),
          );

        if (!$value) {
          continue;
        }
        $row->setAttribute($selector->attribute, $value);
      }
    }
    $result = trim($dom->saveHTML());
    libxml_use_internal_errors($previousXmlErrorBehavior);

    // Remove the debug xml encoding.
    return str_replace($encoding, '', $result);
  }

  /**
   * Gets the attribute value.
   *
   * @param \Drupal\helfi_proxy\Selector\AttributeSelector $selector
   *   The selector.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string|null $value
   *   The value.
   *
   * @return string|null
   *   The attribute value.
   */
  private function getAttributeValue(AttributeSelector $selector, Request $request, ?string $value) : ? string {
    // Skip if value is being served from CDN already.
    if (!$value || $this->isCdnAddress($value)) {
      return $value;
    }

    // Image styles need special handling because they need to be run through
    // PHP before they are uploaded to CDN.
    if (str_contains($value, '/files/styles/')) {
      if ($selector instanceof MultiValueAttributeSelector) {
        return $this->handleMultiValue($value, $selector->multivalueSeparator,
          function (string $value): string {
            return $this->addDomain($value);
          });
      }
      return $this->addDomain($value);
    }
    // Certain elements might be absolute URLs already (such as og:image:url).
    // Make sure locally hosted files are always served from correct domain.
    if ($selector instanceof AbsoluteUriAttributeSelector) {
      $parts = parse_url($value);

      if (empty($parts['path'])) {
        return $value;
      }

      // Skip non-local assets.
      if (!$this->isLocalAsset($parts['path'])) {
        return $value;
      }

      $value = $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : NULL);
      return sprintf('%s%s', $request->getSchemeAndHttpHost(), $this->addAssetPath($value));
    }

    // Ignore absolute URLs.
    if (str_starts_with($value, 'http') || str_starts_with($value, '//')) {
      return $value;
    }

    if ($selector instanceof MultiValueAttributeSelector) {
      return $this->handleMultiValue($value, $selector->multivalueSeparator,
        function (string $value) : string {
          return $this->addAssetPath($value);
        }
      );
    }

    return $this->addAssetPath($value);
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
   * Checks whether the asset is hosted locally.
   *
   * @param string $value
   *   The path to given asset.
   *
   * @return bool
   *   TRUE if asset is local.
   */
  private function isLocalAsset(string $value) : bool {
    foreach (['/sites', '/core', '/themes'] as $path) {
      if (str_starts_with($value, $path)) {
        return TRUE;
      }
    }
    return FALSE;
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
    return sprintf('/%s/%s', $this->getConfig(self::ASSET_PATH), ltrim($value, '/'));
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
   * Gets the asset path.
   *
   * phpcs:ignore
   * @deprecated in helfi_proxy:2.0.3 and is removed from helfi_proxy:3.0.0. Use ::getConfig() instead.
   *
   * @return string|null
   *   The asset path.
   */
  public function getAssetPath() : ? string {
    return $this->getConfig(self::ASSET_PATH);
  }

  /**
   * Gets the configured prefixes.
   *
   * phpcs:ignore
   * @deprecated in helfi_proxy:2.0.3 and is removed from helfi_proxy:3.0.0. Use ::getConfig() instead.
   *
   * @return array
   *   The instance prefixes.
   */
  public function getInstancePrefixes() : array {
    return $this->getConfig(self::PREFIXES, []);
  }

  /**
   * Gets the tunnistamo return url.
   *
   * phpcs:ignore
   * @deprecated in helfi_proxy:2.0.3 and is removed from helfi_proxy:3.0.0. Use ::getConfig() instead.
   *
   * @return string
   *   The return url.
   */
  public function getTunnistamoReturnUrl() : ? string {
    return $this->getConfig(self::TUNNISTAMO_RETURN_URL);
  }

}
