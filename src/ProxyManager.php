<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A class to determine sites hostname.
 */
final class ProxyManager {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

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
  protected array $hostPatterns = [
    'helfi-proxy.docker.so',
    'www.hel.fi',
    'www-test.hel.fi',
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
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * Checks if current request is served via proxy.
   *
   * @return bool
   *   TRUE if we're serving through proxy.
   */
  public function isProxyRequest() : bool {
    return in_array($this->request->getHost(), $this->hostPatterns);
  }

  /**
   * Gets the instance name.
   *
   * @return string
   */
  public function getInstanceName() : ? string {
    return $this->config->get('instance_name');
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
