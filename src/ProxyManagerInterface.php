<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\helfi_proxy\Selector\Selector;
use Symfony\Component\HttpFoundation\Request;

/**
 * Proxy manager interface.
 */
interface ProxyManagerInterface {

  /**
   * Gets the value for given attribute.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\helfi_proxy\Selector\Selector $tag
   *   The attrbiteu map object.
   * @param string|null $value
   *   The value.
   *
   * @return string|null
   *   The value for given attribute or null.
   */
  public function getAttributeValue(Request $request, Selector $tag, ?string $value) : ? string;

  /**
   * Gets the instance name.
   *
   * @return string|null
   *   The instance name.
   */
  public function getAssetPath() : ? string;

  /**
   * Gets the instance prefixes.
   *
   * @return array
   *   The instance prefixes.
   */
  public function getInstancePrefixes() : array;

  /**
   * Gets the tunnistamo return url.
   *
   * @return string|null
   *   The tunnistamo return url.
   */
  public function getTunnistamoReturnUrl() : ? string;

}
