<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Session\SessionConfiguration as CoreSessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides the default session configuration service.
 *
 * Append a unique suffix to every session cookie, so we can differentiate
 * session cookies on different Drupal instances using same domain.
 */
final class SessionConfiguration extends CoreSessionConfiguration {

  use ProxyTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ProxyManagerInterface $proxyManager
   *   The proxy manager service.
   * @param array $options
   *   The options.
   */
  public function __construct(
    private ProxyManagerInterface $proxyManager,
    array $options = [],
  ) {

    parent::__construct($options);
  }

  /**
   * Gets the session suffix.
   *
   * @return string
   *   The session suffix.
   */
  private function getSuffix() : string {
    if ($this->proxyManager->isConfigured(ProxyManagerInterface::SESSION_SUFFIX)) {
      return $this->proxyManager->getConfig(ProxyManagerInterface::SESSION_SUFFIX);
    }

    // @todo Remove this in 3.0.0 release.
    if (!$suffix = getenv('DRUPAL_SESSION_SUFFIX')) {
      return $this->getCleanHostname();
    }
    return $suffix;
  }

  /**
   * {@inheritdoc}
   */
  protected function getName(Request $request) : string {
    return parent::getName($request) . $this->getSuffix();
  }

}
