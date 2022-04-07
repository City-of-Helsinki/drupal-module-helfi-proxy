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

  /**
   * Builds the redirect url.
   *
   * @param string $url
   *   The URL to parse.
   *
   * @return string
   *   The redirect URL.
   */
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
      !$this->proxyManager->isConfigured(ProxyManagerInterface::DEFAULT_PROXY_DOMAIN) ||
      !$event->getRequest()->isMethod('GET')
    ) {
      // Nothing to do if default proxy domain is not defined.
      // Only redirect on GET requests as well.
      return;
    }
    $response = $event->getResponse();

    if ($response instanceof RedirectResponse) {
      $url = $response->getTargetUrl();
    }
    else {
      $request = $event->getRequest();

      $url = vsprintf('%s%s', [
        $request->getSchemeAndHttpHost(),
        $request->getRequestUri(),
      ]);
    }

    if (!$this->needsRedirect($url)) {
      return;
    }
    $redirect = new TrustedRedirectResponse($this->buildRedirectUrl($url));
    $event->setResponse(
      $redirect
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    // This must be before core's RedirectResponseSubscriber, so we don't break
    // any other redirects made by other modules, like ?destination.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 1];

    return $events;
  }

}
