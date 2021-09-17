<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\Session\SessionConfiguration as CoreSessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides the default session configuration.
 *
 * Appends the server hostname to session name. We serve multiple Drupal
 * instances from one domain and every site needs an unique session.
 */
final class SessionConfiguration extends CoreSessionConfiguration {

  use HostnameTrait;

  /**
   * {@inheritdoc}
   */
  protected function getName(Request $request) {
    $name = parent::getName($request);

    if (!$suffix = getenv('DRUPAL_SESSION_SUFFIX')) {
      $suffix = $this->getCleanHostname();
    }
    return $name . $suffix;
  }

}
