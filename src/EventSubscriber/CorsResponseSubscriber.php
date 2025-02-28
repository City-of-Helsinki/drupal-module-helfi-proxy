<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds required CORS headers to a response.
 */
final class CorsResponseSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new instance.
   *
   * @param array $validOriginDomains
   *   An array of domains.
   */
  public function __construct(
    private array $validOriginDomains,
  ) {
  }

  /**
   * Adds cors headers to a response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event) : void {
    $requestDomain = $event->getRequest()->headers->get('Origin');

    if (!$requestDomain) {
      return;
    }
    $validHost = FALSE;

    foreach ($this->validOriginDomains as $domain) {
      if ($requestDomain === $domain) {
        $validHost = TRUE;
      }

      // Allow subdomains as well.
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
