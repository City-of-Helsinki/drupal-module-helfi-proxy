<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\Hook;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\helfi_proxy\ProxyManagerInterface;

/**
 * Hook implementations for file URLs.
 */
class FileHooks {

  use AutowireTrait;

  public function __construct(
    private readonly ProxyManagerInterface $proxyManager,
  ) {
  }

  /**
   * Implements hook_file_url_alter().
   *
   * Runs first, before any other alter hooks, more specifically before
   * 'crop_file_url_alter()' which seems to convert certain responsive image
   * styles to an external URL that will break our implementation.
   *
   * @see #UHF-7946
   */
  #[Hook('file_url_alter', order: Order::First)]
  public function fileUrlAlter(&$uri): void {
    $uri = $this->proxyManager->processPath($uri);
  }

}
