<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

/**
 * A class to determine sites hostname.
 */
final class Hostname {

  use HostnameTrait;

  /**
   * Gets the hostname.
   *
   * @return string
   *   The hostname.
   */
  public static function get() : string {
    $instance = new self();
    return $instance->getHostname();
  }

}
