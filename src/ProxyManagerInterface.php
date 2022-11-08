<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Symfony\Component\HttpFoundation\Request;

/**
 * Proxy manager interface.
 */
interface ProxyManagerInterface {

  public const PREFIXES = 'prefixes';
  public const ASSET_PATH = 'asset_path';
  public const ROBOTS_PATHS = 'robots_paths';
  public const FRONT_PAGE_TITLE = 'front_page_title';
  public const DEFAULT_PROXY_DOMAIN = 'default_proxy_domain';
  public const SESSION_SUFFIX = 'session_suffix';
  public const ROBOTS_HEADER_ENABLED = 'robots_header_enabled';
  public const TUNNISTAMO_RETURN_URL = 'tunnistamo_return_url';

  /**
   * Manipulates the given attributes to have correct values.
   *
   * @param string $html
   *   The html to manipulate.
   * @param null|\Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param array $selectors
   *   The selectors.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The manipulated response.
   */
  public function processHtml(string $html, Request $request = NULL, array $selectors = []) : string;

  /**
   * Processes a string value.
   *
   * @param string $value
   *   The value.
   * @param null|\Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param array $selectors
   *   The selectors.
   *
   * @return string
   *   The processed value.
   */
  public function processValue(string $value, Request $request = NULL, array $selectors = []) : string;

  /**
   * Whether the proxy is configured or not.
   *
   * @param string $key
   *   The key.
   *
   * @return bool
   *   TRUE if the proxy should be used.
   */
  public function isConfigured(string $key) : bool;

  /**
   * Gets the config.
   *
   * @param string $key
   *   The key to get config for.
   * @param mixed|null $defaultValue
   *   The default value if no value is present.
   *
   * @return mixed
   *   The data.
   */
  public function getConfig(string $key, mixed $defaultValue = NULL) : mixed;

}
