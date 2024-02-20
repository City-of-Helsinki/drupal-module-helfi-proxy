<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\Asset;

use Drupal\Core\Asset\CssOptimizer as DrupalCssOptimizer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Convert files to use asset path.
 */
final class CssOptimizer extends DrupalCssOptimizer {

  /**
   * The asset path.
   *
   * @var string|null
   */
  private ?string $assetPath;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file url generator.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    FileUrlGeneratorInterface $fileUrlGenerator
  ) {
    $this->assetPath = $configFactory->get('helfi_proxy.settings')
      ->get('asset_path');

    parent::__construct($fileUrlGenerator);
  }

  /**
   * {@inheritdoc}
   */
  public function rewriteFileURI($matches) : string { // phpcs:ignore
    if (!$this->assetPath) {
      return parent::rewriteFileURI($matches);
    }
    // Prefix with base and remove '../' segments where possible.
    $path = $this->rewriteFileURIBasePath . $matches[1];
    $last = '';
    while ($path != $last) {
      $last = $path;
      $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
    }
    $path = $this->fileUrlGenerator->transformRelative(
      $this->fileUrlGenerator->generateAbsoluteString($path)
    );

    // Prefix with /{asset-path}.
    return sprintf('url(/%s/%s)', trim($this->assetPath, '/'), ltrim($path, '/'));
  }

}
