<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A class to determine sites hostname.
 */
final class ProxyManager {

  use ProxyTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Valid host patterns.
   *
   * @var string[]
   */
  public const HOST_PATTERNS = [
    'helfi-proxy.docker.so',
    'www.hel.fi',
    'www-test.hel.fi',
    '127.0.0.1',
  ];

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $configFactory, RequestStack $requestStack) {
    $this->config = $configFactory->get('helfi_proxy.settings');
    $this->requestStack = $requestStack;
  }

  /**
   * Gets the value for given attribute.
   *
   * @param string $tag
   *   The attribute.
   * @param string|null $value
   *   The value.
   *
   * @return string|null
   *   The value for given attribute or null.
   */
  public function getAttributeValue(string $tag, ?string $value) : ? string {
    if (!$value || str_starts_with($value, 'http') || str_starts_with($value,
        '//')) {
      return $value;
    }

    // Links are always relative to proxy prefix.
    if ($tag === 'a') {
      // Make sure we have active site prefix and the given URL is relative.
      if ((!$prefix = $this->getActivePrefix()) || !str_starts_with($value, '/')) {
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
    // Serve scripts from same domain via relative asset URL.
    if ($tag === 'script') {
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
  private function getActivePrefix() : ? string {
    static $prefix;

    if ($prefix === NULL) {
      $request = $this->requestStack->getCurrentRequest();

      foreach ($this->getInstancePrefixes() as $langcode => $item) {
        if (str_contains($request->getPathInfo(), $item)) {
          $prefix = sprintf('/%s/%s', $langcode, $item);
          break;
        }
      }
    }
    return $prefix;
  }

  /**
   * Checks if current request is served via proxy.
   *
   * @return bool
   *   TRUE if we're serving through proxy.
   */
  public function isProxyRequest() : bool {
    if (!$this->requestStack->getCurrentRequest()) {
      return FALSE;
    }
    return in_array($this->requestStack->getCurrentRequest()->getHost(), self::HOST_PATTERNS);
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
