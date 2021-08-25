<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

/**
 * A trait to interact with current hostname.
 */
trait HostnameTrait {

  /**
   * Gets the currently active hostname.
   *
   * @return string
   *   The hostname.
   */
  protected function getHostname() : string {
    $variables = [
      'HOSTNAME',
      'DRUPAL_REVERSE_PROXY_ADDRESS',
      'DRUPAL_ROUTES',
    ];

    foreach ($variables as $variable) {
      if ($hostname = getenv($variable)) {
        return $this->parseHostName($hostname);
      }
    }

    throw new \LogicException('Proxy: Invalid hostname.');
  }

  /**
   * Parses hostname from the given environment variable.
   *
   * @param string $hostname
   *   A comma separated list of hostname.
   *
   * @return string
   *   The hostname.
   */
  protected function parseHostName(string $hostname) : string {
    $hosts = explode(',', $hostname);

    // Always fallback to last hostname.
    return end($hosts);
  }

}
