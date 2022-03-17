<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Session\SessionConfiguration as CoreSessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides the default session configuration.
 *
 * Appends the server hostname to session name. We serve multiple Drupal
 * instances from one domain and every site needs a unique session.
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
    array $options = []) {
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

    if (!$suffix = getenv('DRUPAL_SESSION_SUFFIX')) {
      return $this->getCleanHostname();
    }
    return $suffix;
  }

  /**
   * {@inheritdoc}
   */
  protected function getName(Request $request) {
    $name = parent::getName($request);

    return $name . $this->getSuffix();
  }

}
