<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Url;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\helfi_tunnistamo\Event\RedirectUrlEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tunnistamo redirect url subscriber.
 *
 * @phpcs:ignore
 * @deprecated in helfi_proxy:2.1.2 and is removed from helfi_proxy:3.0.0.
 */
final class TunnistamoRedirectUrlSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ProxyManagerInterface $proxyManager
   *   The proxy manager.
   */
  public function __construct(
    private ProxyManagerInterface $proxyManager
  ) {
  }

  /**
   * Responds to tunnistamo redirect url event.
   *
   * @param \Drupal\helfi_tunnistamo\Event\RedirectUrlEvent $event
   *   Response event.
   */
  public function onRedirectUrlEvent(RedirectUrlEvent $event) : void {
    if (!$url = $this->proxyManager->getConfig(ProxyManagerInterface::TUNNISTAMO_RETURN_URL)) {
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
