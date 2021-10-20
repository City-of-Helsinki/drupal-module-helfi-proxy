<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Url;
use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_tunnistamo\Event\RedirectUrlEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tunnistamo redirect url subscriber.
 */
final class TunnistamoRedirectUrlSubscriber implements EventSubscriberInterface {

  /**
   * The proxy manager.
   *
   * @var \Drupal\helfi_proxy\ProxyManager
   */
  private ProxyManager $proxyManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ProxyManager $proxyManager
   *   The proxy manager.
   */
  public function __construct(
    ProxyManager $proxyManager
  ) {
    $this->proxyManager = $proxyManager;
  }

  /**
   * Responds to tunnistamo redirect url event.
   *
   * @param \Drupal\helfi_tunnistamo\Event\RedirectUrlEvent $event
   *   Response event.
   */
  public function onRedirectUrlEvent(RedirectUrlEvent $event) {
    if (!$url = $this->proxyManager->getTunnistamoReturnUrl()) {
      return;
    }

    try {
      $event->setRedirectUrl(Url::fromUserInput($url)->setAbsolute());
    }
    catch (\InvalidArgumentException $e) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    if (!class_exists('\Drupal\helfi_tunnistamo\Event\RedirectUrlEvent')) {
      return [];
    }
    return [
      'Drupal\helfi_tunnistamo\Event\RedirectUrlEvent' => ['onRedirectUrlEvent'],
    ];
  }

}
