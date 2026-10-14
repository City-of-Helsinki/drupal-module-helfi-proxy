<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
   * @param array<string> $validOriginDomains
   *   An array of domains.
   */
  public function __construct(
    #[Autowire(param: 'helfi_proxy.valid_origin_domains')] private readonly array $validOriginDomains,
  ) {
  }

  /**
   * Checks whether the given origin is allowed.
   *
   * @param string $origin
   *   The origin.
   *
   * @return bool
   *   TRUE if the origin is allowed.
   */
  private function isValidOrigin(string $origin) : bool {
    $host = parse_url($origin, PHP_URL_HOST);

    if (!is_string($host)) {
      return FALSE;
    }
    $host = strtolower($host);

    foreach ($this->validOriginDomains as $domain) {
      // Allow subdomains as well.
      if ($host === $domain || str_ends_with($host, '.' . $domain)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Adds cors headers to a response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event) : void {
    $response = $event->getResponse();
    // The response depends on the Origin header, so make sure reverse proxies
    // don't serve a response generated for one origin to another.
    $response->setVary('Origin', FALSE);

    $origin = $event->getRequest()->headers->get('Origin');

    if (!$origin || !$this->isValidOrigin($origin)) {
      return;
    }

    $response->headers->set('Access-Control-Allow-Origin', $origin);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', -100];
    return $events;
  }

}
