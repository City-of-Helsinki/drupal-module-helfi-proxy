<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * A class to determine sites hostname.
 */
final class ProxyManager implements ProxyManagerInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   */
  public function __construct(
    private ConfigFactoryInterface $configFactory,
    private StreamWrapperManagerInterface $streamWrapperManager,
  ) {
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
   * Checks whether the asset is hosted locally.
   *
   * @param string $value
   *   The path to given asset.
   *
   * @return bool
   *   TRUE if asset is local.
   */
  private function isLocalAsset(string $value) : bool {
    return (bool) preg_match('/^(sites|core|themes|modules)\/\w/', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function processPath(string $value) : ? string {
    $assetPath = $this->getConfig(self::ASSET_PATH);

    if (!$assetPath || str_starts_with($value, $assetPath)) {
      return $value;
    }
    $wrapper = $this->streamWrapperManager->getViaScheme(
      $this->streamWrapperManager::getScheme($value)
    );

    $path = ltrim($value, '/');

    // Convert public:// paths to relative.
    if ($wrapper instanceof LocalStream) {
      $path = $wrapper->getDirectoryPath() . '/' . $this->streamWrapperManager::getTarget($value);
      // KernelTests will convert public://file to vfs://root/simpletest/file.
      // Remove vfs part to make testing possible.
      $path = str_replace('vfs://root/', '', $path);
    }

    if (!$this->isLocalAsset($path)) {
      return $value;
    }
    // Serve element from same domain via relative asset URL. Like:
    // /assets/sites/default/files/js/{sha256}.js.
    return sprintf('/%s/%s', $this->getConfig(self::ASSET_PATH), ltrim($path, '/'));
  }

}
