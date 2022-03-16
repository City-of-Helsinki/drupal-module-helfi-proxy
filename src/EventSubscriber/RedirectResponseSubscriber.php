<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
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
   * @param array $validProxyDomains
   *   The valid proxy domains.
   */
  public function __construct(
    private ProxyManagerInterface $proxyManager,
    private array $validProxyDomains
  ) {
  }

  /**
   * Checks whether we need to perform a redirect.
   *
   * @param string $url
   *   The url to check.
   *
   * @return bool
   *   TRUE if page should be redirected.
   */
  private function needsRedirect(string $url) : bool {
    return !in_array(parse_url($url, PHP_URL_HOST), $this->validProxyDomains);
  }

  private function buildRedirectUrl(string $url) : string {
    $uriParts = parse_url($url);

    $responseUrl = vsprintf('https://%s/%s', [
      $this->proxyManager->getConfig(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN),
      ltrim($uriParts['path'], '/'),
    ]);

    if (isset($uriParts['query'])) {
      $responseUrl = sprintf('%s?%s', $responseUrl, $uriParts['query']);
    }
    return $responseUrl;
  }

  /**
   * Redirects to proxy domain by default.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) : void {
    if (
      !$this->validProxyDomains ||
      $this->proxyManager->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN)
    ) {
      // Nothing to do if default proxy domain is not defined.
      return;
    }
    $response = $event->getResponse();

    $url = vsprintf('%s%s', [
      $event->getRequest()->getSchemeAndHttpHost(),
      $event->getRequest()->getRequestUri(),
    ]);

    if ($response instanceof RedirectResponse) {
      $url = $response->getTargetUrl();
    }

    if (!$this->needsRedirect($url)) {
      return;
    }
    $event->setResponse(
      new TrustedRedirectResponse($this->buildRedirectUrl($url))
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    // This must be run after core's RedirectResponseSubscriber, so we
    // can be sure path processor has been run.
    $events[KernelEvents::RESPONSE][] = ['onResponse', -1];

    return $events;
  }

}
