<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Asset;

use Drupal\Core\Asset\CssOptimizer as DrupalCssOptimizer;
use Drupal\helfi_proxy\ProxyManagerInterface;

/**
 * Convert files to use asset path.
 */
final class CssOptimizer extends DrupalCssOptimizer {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ProxyManagerInterface $proxyManager
   *   The proxy manager.
   */
  public function __construct(
    private ProxyManagerInterface $proxyManager
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function rewriteFileURI($matches) : string { // phpcs:ignore
    if (!$assetPath = $this->proxyManager->getAssetPath()) {
      return parent::rewriteFileURI($matches);
    }
    // Prefix with base and remove '../' segments where possible.
    $path = $this->rewriteFileURIBasePath . $matches[1];
    $last = '';
    while ($path != $last) {
      $last = $path;
      $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
    }
    $path = file_url_transform_relative(file_create_url($path));

    // Prefix with /{asset-path}.
    return sprintf('url(/%s/%s)', trim($assetPath, '/'), ltrim($path, '/'));
  }

}
