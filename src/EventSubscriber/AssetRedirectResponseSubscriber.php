<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\helfi_proxy\ProxyManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds the asset path to redirects pointing to local assets.
 *
 * The asset path is removed from the request before it reaches Drupal, so
 * redirects built from the request path, like the ones Stage File Proxy makes
 * after fetching a missing file, would point to a path that isn't routed to
 * this instance.
 */
final readonly class AssetRedirectResponseSubscriber implements EventSubscriberInterface {

  public function __construct(
    private ProxyManagerInterface $proxyManager,
  ) {
  }

  /**
   * Adds the asset path to the redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event) : void {
    $response = $event->getResponse();

    if (!$response instanceof RedirectResponse) {
      return;
    }
    $url = parse_url($response->getTargetUrl());

    if (!$url || empty($url['path'])) {
      return;
    }
    // Only redirects to this site are changed.
    if (isset($url['host']) && $url['host'] !== $event->getRequest()->getHost()) {
      return;
    }
    $path = $this->proxyManager->processPath($url['path']);

    if ($path === NULL || $path === $url['path']) {
      return;
    }
    $target = $path;

    if (isset($url['host'])) {
      $target = sprintf('%s://%s%s%s', $url['scheme'] ?? 'https', $url['host'], isset($url['port']) ? ':' . $url['port'] : '', $path);
    }
    if (isset($url['query'])) {
      $target .= '?' . $url['query'];
    }
    if (isset($url['fragment'])) {
      $target .= '#' . $url['fragment'];
    }
    $response->setTargetUrl($target);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
