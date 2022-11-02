<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects to default proxy provider.
 */
final class RedirectResponseSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ProxyManagerInterface $proxyManager
   *   The proxy manager.
   */
  public function __construct(
    private ProxyManagerInterface $proxyManager,
  ) {
  }

  /**
   * Redirects to proxy domain by default.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) : void {
    if (
      !$this->proxyManager->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN) ||
      $event->getResponse() instanceof RedirectResponse
    ) {
      // Nothing to do if default proxy domain is not defined or the response is
      // a redirect response already.
      return;
    }
    $request = $event->getRequest();
    $proxyDomain = $this->proxyManager->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN);

    if ($request->getHttpHost() === $proxyDomain) {
      // The host matches proxy domain already.
      return;
    }
    $uriParts = parse_url($request->getRequestUri());
    $options = [];

    if (isset($uriParts['query'])) {
      $options['query'] = $uriParts['query'];
    }
    $url = Url::fromUri(sprintf('https://%s/%s', $proxyDomain, ltrim($uriParts['path'], '/')), $options);

    $redirect = new TrustedRedirectResponse($url->toString(TRUE)->getGeneratedUrl());
    $redirect->addCacheableDependency($url)
      ->addCacheableDependency($this->proxyManager);
    $event->setResponse(
      $redirect
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];

    return $events;
  }

}
