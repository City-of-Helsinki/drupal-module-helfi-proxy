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
    // Default to simpletest base url when running tests.
    if (drupal_valid_test_ua()) {
      return $this->parseHostName(getenv('SIMPLETEST_BASE_URL'));
    }

    // Default to HOSTNAME on local env.
    if (getenv('APP_ENV') === 'dev') {
      return getenv('HOSTNAME');
    }

    $variables = [
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
   * Gets the clean hostname.
   *
   * @return string
   *   The clean host name.
   */
  protected function getCleanHostname() : string {
    return preg_replace('/[^a-z0-9_]/', '', $this->getHostname());
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

    // Strip protocol. Always fallback to last hostname.
    return str_replace(['https://', 'http://'], '', end($hosts));

  }

}
