<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds required CORS headers.
 */
final class CorsResponseSubscriber implements EventSubscriberInterface {

  private const ALLOWED_DOMAINS = [
    'hel.fi',
    'docker.so',
  ];

  /**
   * Adds CORS headers.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event) : void {
    $requestDomain = $event->getRequest()->headers->get('Origin');
    $validHost = FALSE;

    foreach (self::ALLOWED_DOMAINS as $domain) {
      if ($requestDomain === $domain) {
        $validHost = TRUE;
      }

      if (str_ends_with($requestDomain, '.' . $domain)) {
        $validHost = TRUE;
      }
    }
    if (!$validHost) {
      return;
    }

    $event->getResponse()->headers->add([
      'Access-Control-Allow-Origin' => $requestDomain,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', -100];
    return $events;
  }

}
