<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\helfi_tunnistamo\Event\RedirectUrlEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tunnistamo redirect url subscriber.
 */
final class TunnistamoRedirectUrlSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Valid host patterns.
   *
   * @var string[]
   */
  public const HOST_PATTERNS = [
    'helfi-proxy.docker.so',
    'www.hel.fi',
    'www-test.hel.fi',
  ];

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory
  ) {
    $this->configFactory = $configFactory;
  }

  /**
   * Responds to tunnistamo redirect url event.
   *
   * @param \Drupal\helfi_tunnistamo\Event\RedirectUrlEvent $event
   *   Response event.
   */
  public function onRedirectUrlEvent(RedirectUrlEvent $event) {
    // Do nothing if site is not served through one of the predefined proxies.
    if (!in_array($event->getRequest()->getHost(), self::HOST_PATTERNS)) {
      return;
    }

    if ((!$config = $this->configFactory->get('helfi_proxy.settings')) || !$url = $config->get('tunnistamo_return_url')) {
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
      '\Drupal\helfi_tunnistamo\Event\RedirectUrlEvent' => ['onRedirectUrlEvent'],
    ];
  }

}
