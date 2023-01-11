<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\helfi_proxy\ActiveSitePrefix;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\helfi_tunnistamo\Event\RedirectUrlEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tunnistamo return url subscriber.
 */
final class TunnistamoRedirectUrlSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\helfi_proxy\ProxyManagerInterface $proxyManager
   *   The proxy manager.
   * @param \Drupal\helfi_proxy\ActiveSitePrefix $prefix
   *   The active site prefix service.
   */
  public function __construct(
    private LanguageManagerInterface $languageManager,
    private ProxyManagerInterface $proxyManager,
    private ActiveSitePrefix $prefix,
  ) {
  }

  /**
   * Responds to Tunnistamo redirect url event.
   *
   * @param \Drupal\helfi_tunnistamo\Event\RedirectUrlEvent $event
   *   Response event.
   */
  public function onRedirectUrlEvent(RedirectUrlEvent $event) : void {
    $returnUrl = $this->proxyManager->getConfig(ProxyManagerInterface::TUNNISTAMO_RETURN_URL);

    $uriOptions = [];
    // Fallback to automatically constructed return url if return url is not
    // defined.
    if (!$returnUrl && $activePrefix = $this->prefix->getPrefix('fi')) {
      $uriOptions['language'] = $this->languageManager->getLanguage('fi');

      // Tunnistamo return URL is always configured to use /fi prefix.
      $returnUrl = sprintf('/fi/%s/openid-connect/%s', $activePrefix, $event->getClient()->getParentEntityId());
    }

    if (!$returnUrl) {
      return;
    }

    try {
      $event->setRedirectUrl(Url::fromUserInput($returnUrl, $uriOptions)->setAbsolute());
    }
    catch (\InvalidArgumentException $e) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      RedirectUrlEvent::class => ['onRedirectUrlEvent'],
    ];
  }

}
